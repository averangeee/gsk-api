<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/12
 * Time: 11:12
 */

namespace App\Models\Order;

use App\Libs\HashKey;
use App\Libs\PayHelper;
use App\Models\ApiIot\Device;
use App\Models\ApiIot\DeviceEgg;
use App\Models\BaseModel;
use App\Models\Gashapon\Sku;
use App\Models\Gashapon\Store;
use App\Models\Shop\PayConfig;
use App\Models\Shop\Shop;
use App\Models\Shop\SkuImg;
use App\Models\Token;
use EasyWeChat\Foundation\Application;
use Illuminate\Support\Facades\Log;

class Order extends BaseModel
{
    protected $table = 'order';

    protected $guarded = [];

    public function orderStatus()
    {
        //return $this->hasMany(OrderStatus::Class,'order_sn','order_sn');
        return $this->belongsTo(OrderStatus::Class, 'order_sn', 'order_sn');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'iot_id', 'iot_id');
    }

    public function orefund()
    {
        return $this->belongsTo(OrderRefund::class, 'order_code', 'order_code');
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class, 'sku_code', 'sku_id');
    }

    public function skuImg()
    {
        return $this->belongsTo(SkuImg::class, 'sku_code', 'sku_code');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_code', 'store_code');
    }

    public function egg()
    {
        return $this->belongsTo(DeviceEgg::class, 'egg_code', 'egg_code');
    }

    public function blacklist()
    {
        return $this->belongsTo(OrderRefundBlacklist::class, 'public_id', 'public_id');
    }

    /**
     * @des 退款
     * @param Order $order
     * @param string $refund_reason
     * @return array
     */
    public static function refund(Order $order, $refund_reason = '正常退款')
    {
        try {
            if (!empty($order)) {
                if ($order->pay_status != 1) {
                    return ['status' => false, 'data' => '订单未支付'];
                }
                //目前只有支付宝2，和微信1
                if ($order->pay_type == 1) {
                    return self::refundWx($order, $refund_reason);
                } elseif ($order->pay_type == 2) {
                    return self::refundAl($order, $refund_reason);
                } elseif ($order->pay_type == 3) {
                    return self::refundAl($order, $refund_reason);
                } else {
                    return ['status' => false, 'data' => '平台错误'];
                }
            } else {
                return ['status' => false, 'data' => '参数错误'];
            }
        } catch (\Exception $exception) {
            return ['status' => false, 'data' => $exception->getMessage()];
        }
    }

    //退款ali
    public static function refundAl(Order $order, $refund_reason = '正常退款')
    {
        try {
            $pay_config_id = $order->pay_config_id;
            $payConfig = null;
            if (!empty($pay_config_id)) {
                $payConfig = PayConfig::find($pay_config_id);
            } else {
                //  $payConfig = PayConfig::where('pay_type_id', 2)->where('is_main', 1)->first();
                $payConfig = PayConfig::where('shop_id', $order->shop_id)->first();
            }
            $aop = PayHelper::resAopClient($payConfig);

            $request = new \AlipayTradeRefundRequest();

            $refund_sn = HashKey::refundCode();
            $content = [
                'out_trade_no' => $order->order_sn,
                'trade_no' => $order->order_code,
                'refund_amount' => $order->actual_pay,
                'refund_reason' => $refund_reason,
                'out_request_no' => $refund_sn,
                'operator_id' => Token::$ucode = null ? 'SYS01' : Token::$ucode,
                'store_id' => $order->store_code,
                'terminal_id' => $order->egg_code
            ];


            $request->setBizContent(json_encode($content));
            $result = $aop->execute($request);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;

            if (!empty($resultCode) && $resultCode == 10000) {
                $order->pay_status = 3;
                $order->refund_at = date('Y-m-d H:i:s');
                $order->refund_sn = $refund_sn;
                $order->save();
                OrderRefundBlacklist::createBlacklist($order);
                return ['status' => true, 'data' => $result->$responseNode];
            } else {
                return ['status' => false, 'data' => $result->$responseNode];
            }
        } catch (\Exception $exception) {
            return ['status' => false, 'data' => $exception->getMessage()];
        }
    }

    //退款wx
    public static function refundWx(Order $order, $refund_reason = '正常退款')
    {
        try {
            $pay_config_id = $order->pay_config_id;
            $payConfig = null;
            if (!empty($pay_config_id)) {
                $payConfig = PayConfig::find($pay_config_id);
            } else {
                $payConfig = PayConfig::where('pay_type_id', 1)->where('is_main', 1)->first();
            }
            $app = PayHelper::resApplication($payConfig);
            $payment = $app->payment;

//            $ucode=Token::$ucode=null?'SYS01':Token::$ucode;
            $refund_sn = HashKey::refundCode();
            $result = $payment->refund($order->order_sn, $refund_sn,
                $order->actual_pay * 100, $order->actual_pay * 100);

            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $order->pay_status = 3;
                $order->refund_at = date('Y-m-d H:i:s');
                $order->refund_sn = $refund_sn;
                $order->save();
                OrderRefundBlacklist::createBlacklist($order);
                return ['status' => true, 'data' => $result];
            } else {
                Log::info('微信退款失败');
                Log::info($result);
                return ['status' => false, 'data' => $result];
            }
        } catch (\Exception $exception) {
            return ['status' => false, 'data' => $exception->getMessage()];
        }
    }


}
