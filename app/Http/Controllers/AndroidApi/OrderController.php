<?php

namespace App\Http\Controllers\AndroidApi;

use App\Libs\Helper;
use App\Libs\WxpayService;
use App\Libs\PayHelper;
use App\Libs\ReturnCode;
use App\Models\ApiIot\Device;
use App\Models\ApiIot\DeviceEgg;
use App\Models\Order\Order;
use App\Models\Order\OrderTemp;
use App\Models\Shop\PayConfig;
use EasyWeChat\Support\XML;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use App\Libs\Wxpay\lib\WxPayApi;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;


class OrderController extends BaseController
{
    //取货状态
    function take_status(Request $request)
    {
        Log::info('取货状态');
        Log::info($request);
        $device_id = $request->input('device_id');//设备号
        $error_id = $request->input('error_id');//取货状态
        $order_sn = $request->input('order_sn');//订单号
        $message = $request->input('message');//返回消息
        $aisles = $request->input('aisles');//货道
        $egg_code_x = $request->input('egg_code_x');//返回X
        $egg_code_y = $request->input('egg_code_y');//返回Y
        if (!$device_id) {
            return response(ReturnCode::error('0', '设备号不存在'));
        }
    //    DB::enablequerylog();
    //    $order = Order::where('iot_id', $device_id)->where('order_sn',$order_sn)->first();
        $order = DB::select('select * from sl_order where order_sn="'.$order_sn.'" and iot_id="'.$device_id.'"');
        if ($order) {
            $data_update['updated_at'] = Helper::datetime();
            $data_update['error_id'] = $error_id;
            $data_update['message'] = $message;
            Order::where('iot_id', $device_id)->where('order_sn',$order_sn)->update($data_update);
            DB::update('update sl_device_egg  set stock=stock-1 where  aisles='.$aisles.' and iot_id="'.$device_id.'"');
            return response(ReturnCode::success());

        } else {
            return response(ReturnCode::error('0', '无匹配数据，请稍后再试'));
        }

    }

    //查询订单状态，只允许查询10分钟内的订单http://dev.mh.com/api
    function status(Request $request)
    {
        $order_sn = $request->input('order_sn');//
        if (!$order_sn) {
            return response(ReturnCode::error(102, '参数异常'));
        }

        $created_at = date('Y-m-d H:i:s', (time() - 600));
        //   DB::enablequerylog();

        $detail = Order::where('order_sn', $order_sn)->where('created_at', '>', "'$created_at'")
            ->first();
        //   Log::info(DB::getquerylog());
        if ($detail) {
            //订单存在
            $status_text = ['未支付', '已支付', '支付失败', '已退款', '支付中'];
            //支付状态:0未支付，1已支付,2支付失败.3已退款,4支付中
            $data['status'] = $detail->pay_status;
            $data['desc'] = $status_text[$detail->pay_status];
            return response(ReturnCode::success($data, '订单存在'));
        } else {
            //支付失败
            return response(ReturnCode::error('102', '订单不存在'));
        }
    }

    //下订单
    function create(Request $request)
    {
        $device_id = $request->input('device_id');//设备号
        $pro_list = $request->input('pro_list');//商品列表，包括，x,y,sku_code,number
        $pro_list = json_decode($pro_list);
        if (!$device_id) {
            return response(ReturnCode::error('102', '设备号不存在'));
        }
        if (!$pro_list) {
            return response(ReturnCode::error('102', '商品不能为空'));
        }

        $device = Device::where('iot_id', $device_id)->first();
        //循环计算价格
        $order_price = 0;
        $sku_code = '';
        $price = 0;
        foreach ($pro_list as $k => $v) {
            $device_egg = DeviceEgg::where('id', $v->id)->where('sku_code', $v->sku_code)->first();
            if ($device_egg) {
                $pro_price = $device_egg->price * $v->number;
                $order_price += $pro_price;
                $sku_code = $device_egg->sku_code;
                $price = $device_egg->price * 100;
            } else {
                return response(ReturnCode::error('102', '商品不存在，请稍后再试'));
            }
        }

        $order_sn = time() . rand(100000, 999999);
        $pay_count = count($pro_list);
        $data['store_code'] = $device->store_code;
        $data['order_sn'] = $order_sn;
        $data['iot_id'] = $device_id;
        $data['sku_code'] = $sku_code;
        $data['price'] = $price;
        $data['pay_count'] = $pay_count;
        $data['order_amount'] = $price;
        $data['pay_amount'] = $price;
        $data['pay_status'] = 0;
        $data['created_at'] = Helper::datetime();
        Order::insertGetId($data);

        $res = [];
        $res['pay_url'] = "http://sl2.fmcgbi.com/wap/zfj?order_code=" . $order_sn;
        $res['order_code'] = $order_sn;
        return response(ReturnCode::success($res));
    }

    function notity_respose()
    {

        Log::info('微信支付回调');
        $testxml = file_get_contents("php://input");
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
        Log::info(json_encode($result));
        if ($result) {
            //如果成功返回了
            $out_trade_no = $result['out_trade_no'];
            $order = Order::where('order_sn', $out_trade_no)->first();
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $actualPay = $result['total_fee'];
                $order->pay_type = 1;
                $order->pay_at = date('Y-m-d H:i:s', strtotime($result['time_end']));
                $order->order_code = $result['transaction_id'];
                $order->actual_pay = $actualPay / 100;
                $order->pay_status = 1;

            } else {
                $order->pay_status = 2;
            }
            $result = $order->save();
            if ($result) {
                $response = [
                    'return_code' => 'SUCCESS',
                    'return_msg' => 'OK',
                ];
            } else {
                $response = [
                    'return_code' => 'FAIL',
                    'return_msg' => 'OK',
                ];
            }
            return new Response(XML::build($response));
        }

    }

    function notity_ali()
    {
        Log::info('支付宝支付回调');
        if(!empty($_POST)) {//如果$_POST数据不为空的话
            Log::info($_POST);
            $result=$_POST;
            $out_trade_no = $result['out_trade_no'];
            $order = Order::where('order_sn', $out_trade_no)->first();
           if($order){
               if ($result['trade_status'] == 'TRADE_SUCCESS') {
                   $actualPay = $result['receipt_amount'];
                   $order->pay_type = 2;
                   $order->pay_at = date('Y-m-d H:i:s', strtotime($result['gmt_payment']));
                   $order->order_code = $result['trade_no'];
                   //    $order->actual_pay = $actualPay / 100;
                   $order->actual_pay = $actualPay;
                   $order->pay_status = 1;

               } else {
                   $order->pay_status = 2;
               }
               $result = $order->save();
               if ($result) {
                   $response = [
                       'return_code' => 'SUCCESS',
                       'return_msg' => 'OK',
                   ];
               } else {
                   $response = [
                       'return_code' => 'FAIL',
                       'return_msg' => 'OK',
                   ];
               }
               return new Response(XML::build($response));
           }

        }
    }

    function pay(Request $request)
    {
        $order_sn = $request->input('order_code');//订单号
        $client = $request->input('client');//订单号
        $openid = $request->input('openid');//订单号
        $pay_amount ='';
        if($order_sn){
            $amount = Order::where('order_sn', $order_sn)->first();
            if ($amount) {
                $pay_amount = $amount->pay_amount;
            }
        }

        //判断是微信支付
        if ($client == "wechat") {
            $arr = $this::payWx($order_sn, $openid,$pay_amount);
            return response(ReturnCode::success($arr));
        } else if ($client == "ali") { //判断是不是支付宝
            $return = $this::payZfb($order_sn,$pay_amount);
            return $return;
        }

    }

    //微信支付
    protected function payWx($order_sn, $openid,$pay_amount)
    {
        $appid = env('appid');
        $mch_id = env('mch_id');
        $sub_mch_id = env('sub_mch_id');
        $sub_openid = $openid;
        $apiKey = env('apiKey');

        $wxPay = new WxpayService($mch_id, $appid, $apiKey, $sub_openid, $sub_mch_id);

        $outTradeNo = $order_sn;     //你自己的商品订单号
        $payAmount = $pay_amount/100;          //付款金额，单位:元
     //   $payAmount = 0.01;
        $orderName = '商品购买';    //订单标题
        $notifyUrl = 'http://zfj.api.fmcgbi.com/api/app/order/notify';     //付款成功后的回调地址(不要有问号)
        $payTime = strval(time());      //付款时间
        $arrs = $wxPay->createJsBizPackage($payAmount, $outTradeNo, $orderName, $notifyUrl, $payTime);

        return $arrs;
    }
    //支付宝支付
    protected function payZfb($order_sn,$pay_amount)
    {
        //try{
        $payConfig = [];

        $aop = new \AopClient();

        $aop->appId = env('zfb_appId');
        //商家私钥 、ISV提供签名私钥
        $aop->rsaPrivateKey = env('zfb_PrivateKey');
        //支付宝公钥 、ISV中转公钥
        $aop->alipayrsaPublicKey = env('zfb_PublicKey');
        $aop->signType = "RSA2";
        $aop->format = 'json';
        $order_detail = Order::where('order_sn', $order_sn)->first();

        $aopRequest = new \AlipayTradeWapPayRequest();

        $bizContent = [
            'subject' => '商品购买',
            'out_trade_no' => $order_sn,
            'total_amount' => $pay_amount/100,
          //  'total_amount' => 0.01,
            'goods_type' => 1,
            'product_code' => 'QUICK_WAP_WAY'
        ];
    //    $notify = config('app.url') . '/api/wx/pay/notify';
        $notify ="http://zfj.api.fmcgbi.com/api/app/order/notity_ali";
        $aopRequest->setNotifyUrl($notify);
     //   $aopRequest->setReturnUrl($notify);
        $aopRequest->setBizContent(json_encode($bizContent));

        $result = $aop->pageExecute($aopRequest, 'GET');

        return ['mweb_url' => $result,'code'=>0];
    }


}



