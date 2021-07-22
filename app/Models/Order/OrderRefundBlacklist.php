<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/24
 * Time: 17:13
 */

namespace App\Models\Order;


use App\Models\BaseModel;
use App\Models\System\EmployeeWeixin;

class OrderRefundBlacklist extends BaseModel
{
    protected $table = 'order_refund_blacklist';


    /**
     * @des 建立退款名单
     * @param Order $order
     * @return int
     */
    public static function createBlacklist(Order $order)
    {
        try {
            if ($order) {
                $blacklist = self::where('public_id', $order->public_id)->first();
                if ($blacklist) {
                    $refundNum = $blacklist->refund_num + 1;
                    $blacklist->refund_num = $refundNum;
                    $blacklist->save();
                } else {
                    self::create([
                        'pay_type' => $order->pay_type,
                        'buyer_logon_id' => $order->buyer_logon_id,
                        'public_id' => $order->public_id,
                        'buyer_user_name' => $order->buyer_user_name,
                        'refund_num' => 1
                    ]);
                }
                return 1;
            } else {
                return 2;
            }
        } catch (\Exception $exception) {
            return 0;
        }
    }

    public function userDetail()
    {
        return $this->belongsTo(EmployeeWeixin::class,'public_id','openid');
    }
}
