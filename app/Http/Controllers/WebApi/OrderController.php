<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/20
 * Time: 17:08
 */

namespace App\Http\Controllers\WebApi;


use App\Http\Controllers\MhController;
use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\ApiIot\DeviceEgg;
use App\Models\ApiIot\DeviceEggLog;
use App\Models\ApiIot\DeviceErrorLogDes;
use App\Models\Gashapon\Store;
use App\Models\Gashapon\Version;
use App\Models\Order\GoodsSupply;
use App\Models\Order\Order;
use App\Models\Order\OrderDc;
use App\Models\Order\OrderReport;
use App\Models\Order\Qx;
use App\Models\Shop\PayConfig;
use App\Models\Token;
use EasyWeChat\Device\Device;
use Illuminate\Support\Facades\Auth;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    /**
     * 新订单表
     * @param Request $request
     */
    function order_list(Request $request)
    {
        $pay_type = $request->input('pay_type', null);
        $pay_status = $request->input('status', null);
        $keyword = $request->input('keyword', null);
        $qrcode = $request->input('qrcode', null);
        $pay_at = $request->input('pay_at', []);
        $store_code = $request->input('store_code', null);
        $sku_code = $request->input('sku_code', null);
        $orderType = $request->input('orderType', null);

        $where = function ($q) use ($pay_status, $pay_type, $keyword, $qrcode, $pay_at, $store_code, $sku_code, $orderType) {
            if (!empty($pay_type)) {
                $q->where('pay_type', $pay_type);
            }

            if (strlen($pay_status) > 0) {
                $q->where('pay_status', $pay_status);
            }

            if (!empty($store_code)) {
                $q->where('store_code', $store_code);
            }

            if (!empty($sku_code)) {
                $q->where('sku_code', $sku_code);
            }

            if (!empty($qrcode)) {
                $q->where('egg_code', 'like', '%' . $qrcode . '%');
            }

            if ($orderType == '2') {
                $q->where('actual_pay', '0.01');
            }
            if ($orderType == '1') {
                $q->where('actual_pay', '>', '0.01');
            }
            if (!empty($keyword)) {
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('order_sn', 'like', '%' . $keyword . '%')
                        ->orWhere('order_code', 'like', '%' . $keyword . '%');
                });
            }

            if (count($pay_at) > 0) {
                $q->whereBetween('pay_at', [date('Y-m-d 0:00:00', strtotime($pay_at[0])), date('Y-m-d 23:59:59', strtotime($pay_at[1]))]);
            }
        };

        $list = Order::where($where)->with(['store'])->get();

        $response['code'] = ReturnCode::SUCCESS;
        $response['data'] = $list;
        $response['total'] = 0;
        $response['sum'] = 10;
        $response['cache_power_type'] = 1;

        return response($response);
    }

    //同步门店数据
    public static function syncStore()
    {
        $month = date('Ym', time());
        $list = DB::select("SELECT * FROM sl_store_sales WHERE version_id='$month'");
        if (count($list) == 0) {
            DB::delete('DELETE FROM sl_store_sales;');
            $storeList = Store::where('version_id', $month)->get()->toArray();
            $count = count($storeList);
            $j = 0;
            $iList = [];
            for ($i = 0; $i < $count; $i++) {
                $iList[$j] = $storeList[$i];
                if (count($iList) == 1500) {
                    DB::table('store_sales')->insert($iList);
                    $iList = [];
                    $j = 0;
                }
                if ($count == ($i + 1)) {
                    DB::table('store_sales')->insert($iList);
                }
                $j++;
            }
        }

    }

    /**
     * 销售订单
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/20 17:09
     */
    public function sales(Request $request)
    {
        $mhc = new MhController();
        $mhc->sync_order();
        $mhc->sync_order_refund();
        $limit = $request->input('limit', 10);
        $pay_type = $request->input('pay_type', null);
        $pay_status = $request->input('status', null);
        $keyword = $request->input('keyword', null);
        $pay_at = $request->input('pay_at', []);
        $store_code = $request->input('store_code', null);
        $sku_code = $request->input('sku_code', null);

        $where = function ($q) use ($pay_status, $pay_type, $keyword, $pay_at, $store_code, $sku_code) {
            if (!empty($pay_type)) {
                $q->where('pay_type', $pay_type);
            }
            if (strlen($pay_status) > 0) {
                $q->where('pay_status', $pay_status);
            }

            if (!empty($store_code)) {
                $q->where('store_code', $store_code);
            }

            if (!empty($sku_code)) {
                $q->where('sku_code', $sku_code);
            }

            if (!empty($keyword)) {
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('order_sn', 'like', '%' . $keyword . '%')
                        ->orWhere('order_code', 'like', '%' . $keyword . '%');
                });
            }
            if (count($pay_at) > 0) {
                $q->whereBetween('pay_at', [date('Y-m-d 0:00:00', strtotime($pay_at[0])), date('Y-m-d 23:59:59', strtotime($pay_at[1]))]);
            }
        };

        $version = Version::where('m_type', 'store')->where('is_active', 1)->first(['version_id']);
        $user_id = Token::$uid;
        $qx = Qx::where('user_id', $user_id)->first();
        $cache_stroe_code = json_decode($qx->cache_stroe_code);
        $cache_power_type = $qx->cache_power_type;
        if ($cache_power_type == 1) {
            $orders = Order::where($where)
                ->with(['store' => function ($q) use ($version) {
                    $col = ['store_code', 'store_name', 'region'];
                    if ($version) {
                        $q->where('version_id', $version->version_id)->select($col);
                    } else {
                        $q->select($col);
                    }
                }, 'sku'])
                ->orderBy('created_at', 'desc')
                ->paginate($limit);
        } else {
            $orders = Order::where($where)
                ->whereIn('store_code', $cache_stroe_code)
                ->with(['store' => function ($q) use ($version) {
                    $col = ['store_code', 'store_name', 'region'];
                    if ($version) {
                        $q->where('version_id', $version->version_id)->select($col);
                    } else {
                        $q->select($col);
                    }
                }, 'sku'])
                ->orderBy('created_at', 'desc')
                ->paginate($limit);
        }

        $orders = $orders->toArray();
        $response['code'] = ReturnCode::SUCCESS;
        $response['data'] = $orders['data'];
        $response['total'] = $orders['total'];
        $response['cache_power_type'] = $cache_power_type;

        return response($response);
    }

    public function salesExport(Request $request)
    {
        $limit = $request->input('limit', 10);
        $keyword = $request->input('keyword', null);
        $pay_status = $request->input('status', null);
        $pay_at = $request->input('pay_at', []);
        $store_code = $request->input('store_code', null);
        $sku_code = $request->input('sku_code', null);
        $pay_type = $request->input('pay_type', null);
        $export_count = $request->input('export_count');//获取导出条数,1

        $where = function ($q) use ($keyword, $pay_status, $pay_at, $store_code, $sku_code, $pay_type) {
            if (!empty($pay_type)) {
                $q->where('pay_type', $pay_type);
            }

            if (strlen($pay_status) > 0) {
                $q->where('pay_status', $pay_status);
            } else {
                $q->whereIn('pay_status', [1, 3]);
            }

            if (!empty($store_code)) {
                $q->where('store_code', $store_code);
            }

            if (!empty($sku_code)) {
                $q->where('sku_code', $sku_code);
            }

            if (!empty($keyword)) {
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('order_sn', 'like', '%' . $keyword . '%')
                        ->orWhere('order_code', 'like', '%' . $keyword . '%');
                });
            }
            if (count($pay_at) > 0) {
                $q->whereBetween('pay_at', [date('Y-m-d 0:00:00', strtotime($pay_at[0])), date('Y-m-d 23:59:59', strtotime($pay_at[1]))]);
            }
        };

        $user_id = Token::$uid;
        $qx = Qx::where('user_id', $user_id)->first();
        $cache_stroe_code = json_decode($qx->cache_stroe_code);
        $cache_power_type = $qx->cache_power_type;

        if ($cache_power_type == 1) {
            if ($export_count && $export_count == 1) {
                $orders = Order::where($where)->count();
            } else {
                $orders = Order::where($where)
                    ->with([
                        'store' => function ($q) {
                            $q->select(['store_code', 'store_name', 'region'])->where('version_id', date('Ym', time()));//->orderByDesc('version_id');
                        },
                        'sku' => function ($q) {
                            $q->select(['sku_id', 'sku_name']);
                        }, 'device'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->paginate($limit)->toArray();
            }
        } else {
            if ($export_count && $export_count == 1) {
                $orders = Order::where($where)->count();
            } else {
                $orders = Order::where($where)
                    ->whereIn('store_code', $cache_stroe_code)
                    ->with([
                        'store' => function ($q) {
                            $q->select(['store_code', 'store_name', 'region'])->where('version_id', date('Ym', time()));//->orderByDesc('version_id');
                        },
                        'sku' => function ($q) {
                            $q->select(['sku_id', 'sku_name']);
                        },
                        'device'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->paginate($limit);
            }
        }

        return response(ReturnCode::success($orders));
    }

    public function salesExports(Request $request)
    {

        $limit = $request->input('limit', 10);
        $keyword = $request->input('keyword', null);
        $pay_status = $request->input('status', null);
        $pay_at = $request->input('pay_at', []);
        $qrcode = $request->input('qrcode', null);
        $store_code = $request->input('store_code', null);
        $sku_code = $request->input('sku_code', null);
        $pay_type = $request->input('pay_type', null);
        $orderType = $request->input('orderType', null);

        $where = function ($q) use ($keyword, $pay_status, $pay_at, $qrcode, $store_code, $sku_code, $pay_type, $orderType) {
            if (!empty($pay_type)) {
                $q->where('pay_type', $pay_type);
            }

            if (strlen($pay_status) > 0) {
                $q->where('pay_status', $pay_status);
            } else {
                $q->whereIn('pay_status', [1, 3]);
            }

            if (!empty($store_code)) {
                $q->where('store_code', $store_code);
            }

            if (!empty($sku_code)) {
                $q->where('sku_code', $sku_code);
            }

            if (!empty($qrcode)) {
                $q->where('egg_code', 'like', '%' . $qrcode . '%');
            }

            if ($orderType == '2') {
                $q->where('actual_pay', '0.01');
            }
            if ($orderType == '1') {
                $q->where('actual_pay', '>', '0.01');
            }
            if (!empty($keyword)) {
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('order_sn', 'like', '%' . $keyword . '%')
                        ->orWhere('order_code', 'like', '%' . $keyword . '%');
                });
            }
            if (count($pay_at) > 0) {
                $q->whereBetween('pay_at', [date('Y-m-d 0:00:00', strtotime($pay_at[0])), date('Y-m-d 23:59:59', strtotime($pay_at[1]))]);
            }
        };

        $user_id = Token::$uid;
        $qx = Qx::where('user_id', $user_id)->first();
        $cache_stroe_code = json_decode($qx->cache_stroe_code);
        $cache_power_type = $qx->cache_power_type;

        DB::enablequerylog();
        if ($cache_power_type == 1) {
            $orders = OrderDc::where($where)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {

            $orders = OrderDc::where($where)
                ->whereIn('store_code', $cache_stroe_code)
                ->orderBy('created_at', 'desc')
                ->get();

        }

        return response(ReturnCode::success($orders));
    }

    /**
     * 上货订单
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/21 11:38
     */
    public function supply(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);

            $supply_type = $request->input('supply_type', null);
            $sku_code = $request->input('sku_code', null);
            $keyword = $request->input('keyword', null);
            $qrcode = $request->input('qrcode', null);
            $created_at = $request->input('created_at', []);
            $store_code = $request->input('store_code', null);

            $typeNames = ['supply', 'check', 'refund', 'damaged'];

            $where = function ($q) use ($sku_code, $supply_type, $keyword, $qrcode, $created_at, $store_code) {
                if (!empty($supply_type)) {
                    $q->where('supply_type', $supply_type);
                }

                if (!empty($sku_code)) {
                    $q->where('sku_code', $sku_code);
                }

                if (!empty($store_code)) {
                    $q->where('store_code', $store_code);
                }

                if (!empty($qrcode)) {
                    $q->where('egg_code', 'like', '%' . $qrcode . '%');
                }

                if (!empty($keyword)) {
                    $q->where('supply_id', 'like', '%' . $keyword . '%');
                }

                if (count($created_at) > 0) {
                    $q->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($created_at[0])), date('Y-m-d 23:59:59', strtotime($created_at[1]))]);
                }
            };

            $sumWhere = function ($q) use ($sku_code, $qrcode, $created_at, $store_code) {
                if (!empty($sku_code)) {
                    $q->where('sku_code', $sku_code);
                }

                if (!empty($store_code)) {
                    $q->where('store_code', $store_code);
                }

                if (!empty($qrcode)) {
                    $q->where('qrcode', 'like', '%' . $qrcode . '%');
                }

                if (count($created_at) > 0) {
                    $q->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($created_at[0])), date('Y-m-d 23:59:59', strtotime($created_at[1]))]);
                }
            };

            $storeV = Version::where('m_type', 'store')->first(['version_id']);
            $skuV = Version::where('m_type', 'sku')->first(['version_id']);

            $orders = GoodsSupply::where($where)
                ->orderBy('created_at', 'desc')
                ->with(['employee' => function ($q) {
                    $q->select(['employee_code', 'employee_name']);
                }, 'supplyStatus' => function ($q) {
                    $q->select(['supply_id', 'open_status', 'open_at', 'close_status', 'close_at', 'lng']);
                }])
                ->paginate($limit);

            foreach ($orders as $order) {
                $order->sku = $order->sku(function ($q) use ($skuV) {
                    if ($skuV) {
                        $q->where('version_id', $skuV->version_id);
                    }
                })->first(['sku_id', 'sku_name']);

                $order->store = $order->store(function ($q) use ($storeV) {
                    if ($storeV) {
                        $q->where('version_id', $storeV->version_id);
                    }
                })->first(['store_code', 'store_name']);

            }

            $orders = $orders->toArray();

            $sum = [];
            $type = 1;
            foreach ($typeNames as $name) {
                $sum[$name]['count'] = GoodsSupply::where($sumWhere)->where('supply_type', $type)->count();
                $sum[$name]['amount'] = GoodsSupply::where($sumWhere)->where('supply_type', $type)->sum('num');
                $type++;
            }


            foreach ($orders['data'] as $k => $v) {
                $orders['data'][$k]['egg'] = DeviceEgg::where('iot_id', $v['iot_id'])->where('egg_code', $v['egg_code'])->first();
            }

            $response['code'] = ReturnCode::SUCCESS;
            $response['data'] = $orders['data'];
            $response['total'] = $orders['total'];
            $response['sum'] = $sum;

            return response($response);
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     *同步失败订单数据
     */
    public function mnnotify(Request $request)
    {
        $order_code = $request->input('order_code');

        try {
            $orderDetail = Order::where('order_code', $order_code)->first();

            if ($orderDetail) {
                //  if ($orderDetail->pay_status != '1') {
                //未支付
                $payConfig = PayConfig::where('shop_id', $orderDetail->shop_id)->where('pay_type_id', 2)->where('status', 1)->first();

                $aop = new \AopClient ();
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = $payConfig->app_id;
                $aop->rsaPrivateKey = $payConfig->seller_pay_key; #env('mini_privateKey');
                $aop->alipayrsaPublicKey = $payConfig->seller_key; # env('mini_alipubliKey');
                $aop->apiVersion = '1.0';
                $aop->signType = $payConfig->encrypt_type;
                $aop->format = 'json';

                $request = new \AlipayTradeQueryRequest ();
                $bizContent = [
                    'trade_no' => $orderDetail->order_code
                ];

                $request->setBizContent(json_encode($bizContent));

                $result = $aop->execute($request);

                Log::info('同步订单');
                Log::info(json_encode($result));
                $resultCode = $result->alipay_trade_query_response->code;
                $trade_status = $result->alipay_trade_query_response->trade_status;
                if (!empty($resultCode) && $resultCode == 10000) {

                    if ($trade_status == "TRADE_SUCCESS") {
                        //回调成功，处理订单状态
                        $orderDetail->error_id = ",3,";
                        $orderDetail->pay_status = 1;
                        $orderDetail->pay_at = $result->alipay_trade_query_response->send_pay_date;
                        $orderDetail->save();
                    }
                    if ($trade_status == "TRADE_CLOSED") {
                        $orderDetail->pay_status = 3;
                        $orderDetail->refund_at = $result->alipay_trade_query_response->send_pay_date;
                        $orderDetail->save();
                    }

                    return response(ReturnCode::success([], 'success'));
                } else {

                    //失败，提示用户
                    return response(ReturnCode::error(ReturnCode::NOT_MODIFY, '未查询到用户的付款记录'));
                }
            } else {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '该数据已同步成功'));
            }
            /* } else {
                 return response(ReturnCode::error(ReturnCode::NOT_FOUND, '查不到该数据'));
             }*/
        } catch (\Exception $exception) {
            return response(ReturnCode::error(ReturnCode::NOT_FOUND, '查不到该数据'));
        }

    }

    /**
     * 修改上报数量
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/11 10:27
     */
    public function supplyEdit(Request $request, $id)
    {
        try {
            $supply = GoodsSupply::find($id);
            if (!$supply) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST, '上报记录不存在'));
            }

            //检查是否为最新上报记录
            $maxSupply = GoodsSupply::where('egg_code', $supply->egg_code)->max('id');
            if ($maxSupply != $id) {
                return response(ReturnCode::error(ReturnCode::FAILED, '只能修改最新的上报'));
            }

            $num = abs($request->input('num', 0));
            if ($supply->supply_type == 3 || $supply->supply_type == 4) {
                $num = -$num;
            }
            $egg = DeviceEgg::where('egg_code', $supply->egg_code)->first();

            $changeNum = $num - $supply->num;

            if (($egg->stock + $changeNum) < 0) {
                return response(ReturnCode::error(ReturnCode::FAILED, '修改记录蛋仓库存将变为负数'));
            }
            DB::beginTransaction();

            $egg->stock += $changeNum;
            $egg->save();

            $supply->num = $num;
            $supply->save();

            DB::commit();

            return response(ReturnCode::success());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * 删除上报
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/11 11:28
     */
    public function supplyDel(Request $request, $id)
    {
        try {
            $supply = GoodsSupply::find($id);
            if (!$supply) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST, '上报记录不存在'));
            }

            //检查是否为最新上报记录
            $maxSupply = GoodsSupply::where('egg_code', $supply->egg_code)->max('id');
            if ($maxSupply != $id) {
                return response(ReturnCode::error(ReturnCode::FAILED, '只能删除最新的上报'));
            }
            $egg = DeviceEgg::where('egg_code', $supply->egg_code)->first();

            if (($egg->stock - $supply->num) < 0) {
                return response(ReturnCode::error(ReturnCode::FAILED, '删除记录蛋仓库存将变为负数'));
            }

            DB::beginTransaction();

            $egg->stock -= $supply->num;
            $egg->save();

            $supply->delete();

            DB::commit();

            return response(ReturnCode::success());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    //销售报表统计
    public function orderReport(Request $request)
    {
        $limit = $request->input('limit', 10);
        $pay_at = $request->input('pay_at');
        $store_code = $request->input('store_code');


        $where = function ($q) use ($pay_at, $store_code) {
            if (!empty($store_code)) {
                $q->where('store_code', $store_code);
            }
            if (count($pay_at) == 2) {
                $q->whereBetween('days', [date('Y-m-d', strtotime($pay_at[0])), date('Y-m-d', strtotime($pay_at[1]))]);
            } else {
                $q->where('days', '>', date('Y-m-d', time() - 86400 * 30));
            }

        };
        // print_r($where);
        if (count($pay_at) == 2) {
            $datetime_start = new \DateTime($pay_at[0]);
            $datetime_end = new \DateTime($pay_at[1]);
            $days = $datetime_start->diff($datetime_end)->days;
            if ($days > 30) {
                return response(ReturnCode::error('102', '请查询时间区间30天内的数据'));
            }
            if ($days < 1) {
                return response(ReturnCode::error('102', '请至少选择两天'));
            }

        }

        $user_id = Token::$uid;
        $qx = Qx::where('user_id', $user_id)->first();
        $cache_stroe_code = json_decode($qx->cache_stroe_code);
        $cache_power_type = $qx->cache_power_type;

        if ($cache_power_type == 1) {
            $list = OrderReport::where($where)
                ->with([
                    'store' => function ($q) {
                        $q->where('version_id', date('Ym', time()));//->orderByDesc('version_id');
                    },])
                ->orderByDesc('days')
                ->paginate($limit)
                ->toArray();
            $lists = OrderReport::where($where)
                ->select('days', DB::raw('sum(order_number) as number'), DB::raw('sum(sum_price) as price'))
                ->groupBy('days')
                ->get()
                ->toArray();

        } else {

            $list = OrderReport::where($where)
                ->whereIn('store_code', $cache_stroe_code)
                ->with([
                    'store' => function ($q) {
                        $q->where('version_id', date('Ym', time()));//->orderByDesc('version_id');
                    },])
                ->orderByDesc('days')
                ->paginate($limit)
                ->toArray();
            $lists = OrderReport::where($where)
                ->whereIn('store_code', $cache_stroe_code)
                ->select('days', DB::raw('sum(order_number) as number'), DB::raw('sum(sum_price) as price'))
                ->groupBy('days')
                ->get()
                ->toArray();
        }

        $days = [];
        $number = [];
        $price = [];
        foreach ($lists as $k => $v) {
            $days[$k] = $v['days'];
            $number[$k] = $v['number'];
            $price[$k] = $v['price'];
        }

        //按小时统计
        $startTime = $pay_at[0];
        $endTime = $pay_at[1];
        $sql = "select DATE_FORMAT(created_at,'%H') hours,count(id) count from sl_order WHERE egg_count=1";
        if (count($pay_at) == 2) {
            $sql .= " and '$startTime'<=pay_at and pay_at<='$endTime'";
        }
        if ($store_code) {
            $sql .= " and store_code='$store_code'";
        }
        $sql .= " group by hours";

        $hourList = DB::select($sql);
        $hours = [];
        $hoursNumber = [];
        foreach ($hourList as $k => $v) {
            $hours[$k] = $v->hours;
            $hoursNumber[$k] = $v->count;
        }

        //按门店统计
        $sqls = "select store_code,count(id) count from sl_order WHERE egg_count=1 ";
        if (count($pay_at) == 2) {
            $sqls .= " and '$startTime'<=pay_at and pay_at<='$endTime'";
        }
        $sqls .= "  group by store_code ORDER BY count desc,store_code LIMIT 20";

        $storeList = DB::select($sqls);

        $store = [];
        $storeNumber = [];
        foreach ($storeList as $k => $v) {
            $store[$k] = $v->store_code;
            $storeNumber[$k] = $v->count;
        }


        $response['days'] = $days;
        $response['number'] = $number;
        $response['price'] = $price;
        $response['data'] = $list['data'];
        $response['total'] = $list['total'];
        $response['hours'] = $hours;
        $response['hoursNumber'] = $hoursNumber;
        $response['store'] = $store;
        $response['storeNumber'] = $storeNumber;
        $response['code'] = ReturnCode::SUCCESS;
        return response($response);
    }

    //图片列表
    function supply_logs(Request $request)
    {
        $limit = $request->input('limit');
        $status = $request->input('zt');
        $type = $request->input('type');
        $where = function ($q) use ($status, $type) {
            if ($status!=null && $status!="") {
                $q->where('status', $status);
            }
            if ($type) {
                $q->where('type', $type);
            }
        };
        $list = DeviceEggLog::where($where)->paginate($limit);

        return response(ReturnCode::success($list, '保存成功！'));
    }
}
