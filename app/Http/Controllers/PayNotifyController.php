<?php
namespace App\Http\Controllers;


use App\Libs\ReturnCode;
use App\Models\Order\Order;
use EasyWeChat\Support\XML;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PayNotifyController extends Controller
{
    //微信异步回调
    public function wxNotify()
    {
        $testxml = file_get_contents("php://input");
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        Log::info('微信回调+++++++++++++++++++++++++++++++++');
        $result = json_decode($jsonxml, true);//转成数组，
        if ($result) {
            //如果成功返回了
            $out_trade_no = $result['out_trade_no'];
            $order = Order::where('order_sn', $out_trade_no)->first();

            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $actualPay = $result['total_fee'];
                $order->pay_type = 1;
                $order->pay_at = date('Y-m-d H:i:s', strtotime($result['time_end']));
                $order->public_id = $result['openid'];
                $order->order_code = $result['transaction_id'];
                $order->actual_pay = ($actualPay / 100);
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
            Log::info("微信支付结果：" . $result);
        }
    }


    //支付宝异步回调接口
    public function alipayNotify(Request $request)
    {
        try {
            $prams = $request->all();
            Log::info($prams);
            Log::info('支付宝回调');
            //订单不存在 告诉支付宝不要再推送了
            $order = Order::where('order_sn', $prams['out_trade_no'])->first();
            if (!$order) {
                return 'success';
            }

            //订单已支付 告诉支付宝不要再推送了
            if ($order->pay_status == 1 && $order->pat_at) {
                return 'success';
            }

            $order->public_id = $prams['buyer_id'];
            $order->pay_at = $prams['gmt_payment'];
            $order->buyer_logon_id = $prams['buyer_logon_id'];
            $order->fund_bill_list = $prams['fund_bill_list'];
            $order->order_code = $prams['trade_no'];
            $order->actual_pay = $prams['buyer_pay_amount'];
            $order->pay_status = 1;

            $order->save();

            //给设备发送消息
            return 'success';
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }
}
