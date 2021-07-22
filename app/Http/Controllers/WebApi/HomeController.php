<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/7/2
 * Time: 18:08
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\ApiIot\Device;
use App\Models\ApiIot\DeviceEgg;
use App\Models\Order\GoodsSupply;
use App\Models\Order\Order;
use App\Models\Order\Qx;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{

    protected $day30 = null;
    protected $day7 = null;
    protected $today = null;
    protected $thisYear = null;

    public function __construct()
    {
        $this->thisYear = date('Y-01-01 0:00:00');
        $this->day30 = date('Y-m-d 0:00:00', strtotime('-30 day'));
        $this->day7 = date('Y-m-d 0:00:00', strtotime('-7 day'));
        $this->today = date('Y-m-d 0:00:00');
    }

    /**
     * 首页设备数据
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/7/2 18:09
     */
    public function device(Request $request)
    {
        try {

            $status = $request->input('status', 1);

            $where = function ($q) use ($status) {
                $q->where('status2', $status);
            };
            $user_id = Token::$uid;
            $qx = Qx::where('user_id', $user_id)->first();
            $cache_stroe_code = json_decode($qx->cache_stroe_code);
            $cache_power_type = $qx->cache_power_type;

            if ($cache_power_type == 1) {
//设备
                $device = [
                    'day30' => Device::where($where)->where('created_at', '>=', $this->day30)->count(),
                    'day7' => Device::where($where)->where('created_at', '>=', $this->day7)->count(),
                    'today' => Device::where($where)->where('created_at', '>=', $this->today)->count(),
                    'total' => Device::where($where)->count()
                ];

                $whereEgg = function ($q) use ($status) {
                    $q->where('status', $status);
                };

                //蛋仓
                $egg = [
                    'day30' => DeviceEgg::where($whereEgg)->where('created_at', '>=', $this->day30)->count(),
                    'day7' => DeviceEgg::where($whereEgg)->where('created_at', '>=', $this->day7)->count(),
                    'today' => DeviceEgg::where($whereEgg)->where('created_at', '>=', $this->today)->count(),
                    'total' => DeviceEgg::where($whereEgg)->count()
                ];
            } else {
                //设备
                $device = [
                    'day30' => Device::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day30)->count(),
                    'day7' => Device::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day7)->count(),
                    'today' => Device::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->today)->count(),
                    'total' => Device::where($where)->whereIn('store_code', $cache_stroe_code)->count()
                ];

                $whereEgg = function ($q) use ($status) {
                    $q->where('status', $status);
                };


                //蛋仓
                $egg = [
                    'day30' => DeviceEgg::where($whereEgg)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day30)->count(),
                    'day7' => DeviceEgg::where($whereEgg)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day7)->count(),
                    'today' => DeviceEgg::where($whereEgg)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->today)->count(),
                    'total' => DeviceEgg::where($whereEgg)->whereIn('store_code', $cache_stroe_code)->count()
                ];
            }


            return response(ReturnCode::success(['device' => $device, 'egg' => $egg]));
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * 首页订单数据
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/7/2 19:01
     */
    public function order(Request $request)
    {
        try {
            $status = $request->input('status', 1);

            $field = $status == 1 ? 'pay_at' : 'refund_at';

            $where = function ($q) use ($status) {
                $q->where('pay_status', $status);
            };

            $user_id = Token::$uid;
            $qx = Qx::where('user_id', $user_id)->first();
            $cache_stroe_code = json_decode($qx->cache_stroe_code);
            $cache_power_type = $qx->cache_power_type;

            if ($cache_power_type == 1) {
                //订单量
                $count = [
                    'day30' => Order::where($where)->where($field, '>=', $this->day30)->count(),
                    'day7' => Order::where($where)->where($field, '>=', $this->day7)->count(),
                    'today' => Order::where($where)->where($field, '>=', $this->today)->count(),
                    'total' => Order::where($where)->where($field, '>=', $this->thisYear)->count()
                ];

                //金额
                $amount = [
                    'day30' => Order::where($where)->where($field, '>=', $this->day30)->sum('actual_pay'),
                    'day7' => Order::where($where)->where($field, '>=', $this->day7)->sum('actual_pay'),
                    'today' => Order::where($where)->where($field, '>=', $this->today)->sum('actual_pay'),
                    'total' => Order::where($where)->where($field, '>=', $this->thisYear)->sum('actual_pay')
                ];
            } else {
                //订单量
                $count = [
                    'day30' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->day30)->count(),
                    'day7' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->day7)->count(),
                    'today' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->today)->count(),
                    'total' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->thisYear)->count()
                ];

                //金额
                $amount = [
                    'day30' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->day30)->sum('actual_pay'),
                    'day7' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->day7)->sum('actual_pay'),
                    'today' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->today)->sum('actual_pay'),
                    'total' => Order::where($where)->whereIn('store_code', $cache_stroe_code)->where($field, '>=', $this->thisYear)->sum('actual_pay')
                ];
            }


            return response(ReturnCode::success(['count' => $count, 'amount' => $amount]));
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * 运维
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/7/11 18:13
     */
    public function supply(Request $request)
    {
        try {
            $type = $request->input('type', 1);

            $where = function ($q) use ($type) {
                $q->where('supply_type', $type);
            };

            $user_id = Token::$uid;
            $qx = Qx::where('user_id', $user_id)->first();
            $cache_stroe_code = json_decode($qx->cache_stroe_code);
            $cache_power_type = $qx->cache_power_type;

            if ($cache_power_type == 1) {
                //次数
                $count = [
                    'day30' => GoodsSupply::where($where)->where('created_at', '>=', $this->day30)->count(),
                    'day7' => GoodsSupply::where($where)->where('created_at', '>=', $this->day7)->count(),
                    'today' => GoodsSupply::where($where)->where('created_at', '>=', $this->today)->count(),
                    'total' => GoodsSupply::where($where)->where('created_at', '>=', $this->thisYear)->count()
                ];

                //数量
                $amount = [
                    'day30' => GoodsSupply::where($where)->where('created_at', '>=', $this->day30)->sum('num'),
                    'day7' => GoodsSupply::where($where)->where('created_at', '>=', $this->day7)->sum('num'),
                    'today' => GoodsSupply::where($where)->where('created_at', '>=', $this->today)->sum('num'),
                    'total' => GoodsSupply::where($where)->where('created_at', '>=', $this->thisYear)->sum('num')
                ];
            } else {
                //次数
                $count = [
                    'day30' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day30)->count(),
                    'day7' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day7)->count(),
                    'today' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->today)->count(),
                    'total' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->thisYear)->count()
                ];

                //数量
                $amount = [
                    'day30' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day30)->sum('num'),
                    'day7' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->day7)->sum('num'),
                    'today' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->today)->sum('num'),
                    'total' => GoodsSupply::where($where)->whereIn('store_code', $cache_stroe_code)->where('created_at', '>=', $this->thisYear)->sum('num')
                ];
            }


            return response(ReturnCode::success(['count' => $count, 'amount' => $amount]));
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }
}