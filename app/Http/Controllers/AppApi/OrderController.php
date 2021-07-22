<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/15
 * Time: 10:00
 */

namespace App\Http\Controllers\AppApi;


use AlibabaCloud\Dbs\Dbs;
use App\Libs\ApiIot\ApiIotUtil;
use App\Libs\Helper;
use App\Libs\PayHelper;
use App\Libs\ReturnCode;
use App\Models\ApiIot\Device;
use App\Models\ApiIot\DeviceEgg;
use App\Models\ApiIot\DeviceErrorLog;
use App\Models\ApiIot\DeviceErrorLogDes;
use App\Models\Base\DefineNote;
use App\Models\Gashapon\Version;
use App\Models\Order\BlackUser;
use App\Models\Order\Order;
use App\Models\Order\OrderRefund;
use App\Models\Order\OrderRefundReason;
use App\Models\Order\OrderScan;
use App\Models\Order\OrderShadow;
use App\Models\Order\OrderStatus;
use App\Models\Shop\PayConfig;
use App\Models\Shop\ShopStore;
use App\Models\Shop\SkuImg;
use EasyWeChat\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    //创建订单
    public function create(Request $request)
    {
        try {
            $skuCount = $request->input('sku_count', 1);
            $skuName = $request->input('sku_name', null);
            $skuCode = $request->input('sku_code', null);
            $skuPrice = $request->input('sku_price', null);
            $total = $request->input('total', 0);  //支付金额  单位 分
            $payType = $request->input('pay_type', 1);
            $isClient = $request->input('isClient', 0);
            $orderId = $request->input('order_id', 0);
            $eggId = $request->input('egg_id', 0);
            $openid = $request->input('openid', null);
            $url = $request->input('url', null);
            $realIP = $request->header('X-Real-IP', 'X-Real-IP');

            DB::beginTransaction();
            $shadow = null;
            $order = Order::where('order_sn', $orderId)->first();
            if ($order) {
                $shadowData = $order->toArray();
                unset($shadowData['id']);
                unset($shadowData['created_at']);
                unset($shadowData['updated_at']);
                $shadowData['shadown_sn'] = $order->id . '-' . time();

                $shadow = OrderShadow::create($shadowData);

                $orderStatus = OrderStatus::where('order_sn', $orderId)->whereNull('shadown_sn')->first();
                if ($orderStatus) {
                    $orderStatus->shadown_sn = $shadow->shadown_sn;
                    $orderStatus->save();
                }

                if ($order->pay_status == 1) {
                    return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '订单已支付'));
                }
            } else {
                //创建系统订单
                $deviceEgg = DeviceEgg::find($eggId);
                $orderData = [
                    'shop_id' => $deviceEgg->shop_id,
                    'store_code' => $deviceEgg->store_code,
                    'order_sn' => $orderId,
                    'device_egg_id' => $eggId,
                    'iot_id' => $deviceEgg->iot_id,
                    'egg_code' => $deviceEgg->egg_code,
                    'qrcode' => $deviceEgg->qrcode,
                    'sku_code' => $skuCode,
                    'price' => $skuPrice,
                    'pay_count' => $skuCount,
                    'order_amount' => $total / 100,
                    'pay_amount' => $total / 100,
                    'pay_type' => $payType,
                    'public_id' => $openid,
                ];

                $order = Order::create($orderData);
            }

            DB::commit();
            $payOrder = null;
            $payResult = null;
            $returnResult = [];
            //微信支付
            if ($payType == 1) {
                $returnResult = self::wxPay($order, $isClient, $skuName, filter_var($realIP, FILTER_VALIDATE_IP) ? $realIP : $request->ip(), $url);
            }

            //支付宝支付
            if ($payType == 2) {
                $returnResult = self::alipay($order);
            }

            return response(ReturnCode::success(['data' => $returnResult, 'order_id' => $order->id]));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }


    //根据订单支付
    public function checkPay(Request $request, $id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST, '订单不存在'));
            }

            if ($order->pay_at) {
                return response(ReturnCode::success());
            }

            return response(ReturnCode::error(ReturnCode::FAILED));
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    //微信支付
    protected function wxPay(Order $order, $isClient, $skuName, $IP, $url)
    {
        //try{
        $payOrder = null;
        $payConfig = PayConfig::where('shop_id', $order->shop_id)->where('pay_type_id', 1)->where('status', 1)->first();
        if (!$payConfig) {
            throw  new \Exception('暂未开通微信支付');
        }

        $order->pay_config_id = $payConfig->id;
        $order->save();

        $app = PayHelper::resApplication($payConfig);
        $payment = $app->payment;

        $attributes = [
            'trade_type' => 'JSAPI',
            'body' => $skuName,
            'detail' => $skuName,
            'out_trade_no' => $order->order_sn,
            'total_fee' => $order->price * 100, // 单位：分
            'spbill_create_ip' => $IP,
            'time_start' => date('YmdHis', time()),
            'time_expire' => date('YmdHis', time() + 120),
            'notify_url' => config('app.url') . '/api/wx/pay/notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
        ];
        //是否在微信内
        if ($isClient == 1) {
            if ($payConfig->wechat_fw == 1) {
                $attributes['openid'] = $order->public_id;
            } else {
                $attributes['sub_openid'] = $order->public_id;
            }

            $payOrder = new \EasyWeChat\Payment\Order($attributes);
            Log::error($payOrder);
            $payResult = $payment->prepare($payOrder);
            Log::info('微信内');
            Log::info($payResult);
            if ($payResult->return_code == 'SUCCESS') {
                if ($payResult->result_code == 'SUCCESS') {
                    return $payment->configForPayment($payResult->prepay_id, false);
                } else {
                    throw new \Exception($payResult->err_code_des);
                }
            } else {
                throw new \Exception($payResult->return_msg);
            }
        } else {
            $attributes['trade_type'] = 'MWEB';
            $attributes['scene_info'] = '{"h5_info": {"type":"Wap","wap_url": "http://sl2.fmcgbi.com/wap/buy","wap_name": "购买扭蛋"}}';
            $payOrder = new \EasyWeChat\Payment\Order($attributes);
            $payResult = $payment->prepare($payOrder);
            Log::info('微信外');
            Log::info($payResult);
            if ($payResult->return_code == 'SUCCESS') {
                if ($payResult->result_code == 'SUCCESS') {
                    return ['mweb_url' => $payResult->mweb_url];
                } else {
                    throw new \Exception($payResult->err_code_des);
                }
            } else {
                throw new \Exception('请使用微信扫一扫，扫描二维码购买');
            }
        }

//        }catch (\Exception $e){
//            Log::error($e);
//            throw new \Exception($e->getMessage());
//        }
    }

    //支付宝支付
    protected function alipay(Order $order)
    {
        //try{

        $payConfig = PayConfig::where('shop_id', $order->shop_id)->where('pay_type_id', 2)->where('status', 1)->first();
        if (!$payConfig) {
            throw  new \Exception('暂未开通支付宝支付');
        }

        $order->pay_config_id = $payConfig->id;
        $order->save();

        $aop = PayHelper::resAopClient($payConfig);
        $aopRequest = new \AlipayTradeWapPayRequest();

        $bizContent = [
            'subject' => '购买扭蛋',
            'out_trade_no' => $order->order_sn,
            'total_amount' => $order->price,
            'goods_type' => 1,
            'quit_url' => 'http://sl2.fmcgbi.com/wap/buy?egg_code=3997768&device_name=3119046255&qrcode=N7680023',
            'product_code' => 'QUICK_WAP_WAY'
        ];

        $aopRequest->setNotifyUrl('https://api.kjndj.com/api/alipay/pay/notify');
        $aopRequest->setReturnUrl('http://sl2.fmcgbi.com/wap/order?order_id=' . $order->id);
        $aopRequest->setBizContent(json_encode($bizContent));

        $result = $aop->pageExecute($aopRequest, 'GET');
        return ['mweb_url' => $result];
//        }catch (\Exception $e){
//            Log::error($e);
//            throw new \Exception($e->getMessage());
//        }
    }

}
