<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/29
 * Time: 17:15
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Order\Order;
use App\Models\Order\OrderRefund;
use App\Models\Order\OrderRefundReason;
use App\Models\Order\Qx;
use App\Models\System\Attachment;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\Helper;

class RefundController extends Controller
{
    /**
     * @des 退款列表
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $sn = $request->input('sn', null);
            $date = $request->input('created_at', null);
            $updateDate = $request->input('updated_at', null);
            $store_code = $request->input('store_code', null);
            $refund_type_id = $request->input('refund_type_id', null);//退款类型
            $keyword = $request->input('keyword', null);
            $updated_code = $request->input('updated_code', null);
            $status = $request->input('status', null);
            $is_handle = $request->input('is_handle', null);
            $export = $request->input('export');
            $ret = '1';

            $where = function ($query) use ($sn, $date, $updateDate, $store_code, $refund_type_id, $keyword, $updated_code, $status, $is_handle, $ret) {
                if (!empty($sn)) {
                    $query->where(function ($q) use ($sn) {
                        $q->orWhere('order_sn', 'like', '%' . $sn . '%')->orWhere('order_code', 'like', '%' . $sn . '%');
                    });
                }
                if (!empty($date)) {
                    $query->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($date[0])), date('Y-m-d 23:59:59', strtotime($date[1]))]);
                }
                if (!empty($updateDate)) {
                    $query->whereBetween('updated_at', [date('Y-m-d 0:00:00', strtotime($updateDate[0])), date('Y-m-d 23:59:59', strtotime($updateDate[1]))]);
                }
                if (!empty($store_code)) {
                    $query->where('store_code', $store_code);
                }
                if (!empty($refund_type_id)) {
                    $query->where('refund_type_id', $refund_type_id);
                }
                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->orWhere('refund_note', 'like', $keyword)->orWhere('refund_tel', 'like', $keyword);
                    });
                }
                if (!empty($updated_code)) {
                    $query->where('updated_code', $updated_code);
                }
                if (strlen($status) > 0) {
                    $query->where('status', $status);
                }
                if (strlen($is_handle) > 0) {
                    $query->where('is_handle', $is_handle);
                }
//   ->where('refund_note', 'not like', '自动%退款')

                if (!empty($ret)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->orWhere('refund_note', 'not like', '自动%退款')->orWhereNull('refund_note');
                    });
                }

            };
            DB::enablequerylog();

            //处理掉已经自动退款的，不让显示
            $user_id = Token::$uid;
            $qx = Qx::where('user_id', $user_id)->first();
            $cache_stroe_code = json_decode($qx->cache_stroe_code);
            $cache_power_type = $qx->cache_power_type;
            if ($cache_power_type == 1) {
                $refList = OrderRefund::where('status', 0)->get()->toArray();
            } else {
                $refList = OrderRefund::where('status', 0)->whereIn('store_code', $cache_stroe_code)->get()->toArray();
            }

            $order_sn_arr = [];
            foreach ($refList as $k => $v) {
                $order_sn_arr[$k] = $v['order_sn'];
            }
            if (count($order_sn_arr) > 0) {
                $orderList = Order::where('pay_status', 3)->whereIn('order_sn', $order_sn_arr)->get()->toArray();
                if (count($orderList)) {
                    foreach ($orderList as $k => $v) {//回填已退款的数据
                        $rfDetailY = OrderRefund::where('order_sn', $v['order_sn'])->where('status', 1)->first();
                        $rfDetailN = OrderRefund::where('order_sn', $v['order_sn'])->where('status', 0)->first();
                        if ($rfDetailY && $rfDetailN) {
                            $rfDetailN->deleted_at = \App\Libs\Helper::datetime();
                            $rfDetailN->save();
                        }
                    }
                }
            }
            if ($export == 1) {
                if ($cache_power_type == 1) {
                    $data = OrderRefund::where($where)
                        ->with(['order' => function ($q) {
                            $q->select(['order_sn', 'qrcode', 'sku_code', 'price', 'pay_count', 'pay_amount', 'actual_pay', 'pay_status', 'pay_at', 'public_id'])
                                ->with(['blacklist' => function ($qq) {
                                    $qq->select(['public_id', 'refund_num', 'status', 'updated_at']);
                                }]);
                        }, 'modifier' => function ($q) {
                            $q->select(['employee_code', 'employee_name']);
                        }, 'note' => function ($q) {
                            $q->select(['id', 'parent_id', 'des']);
                        }, 'reason' => function ($qq) {
                            $qq->select(['id', 'refund_id', 'is_refund', 'reason', 'created_code', 'created_at'])->with(['creator' => function ($q) {
                                $q->select(['employee_code', 'employee_name']);
                            }]);
                        }])
                        ->orderByDesc('id')
                        ->get()
                        ->toArray();
                } else {
                    $data = OrderRefund::where($where)
                        ->whereIn('store_code', $cache_stroe_code)
                        ->with(['order' => function ($q) {
                            $q->select(['order_sn', 'qrcode', 'sku_code', 'price', 'pay_count', 'pay_amount', 'actual_pay', 'pay_status', 'pay_at', 'public_id'])
                                ->with(['blacklist' => function ($qq) {
                                    $qq->select(['public_id', 'refund_num', 'status', 'updated_at']);
                                }]);
                        }, 'modifier' => function ($q) {
                            $q->select(['employee_code', 'employee_name']);
                        }, 'note' => function ($q) {
                            $q->select(['id', 'parent_id', 'des']);
                        }, 'reason' => function ($qq) {
                            $qq->select(['id', 'refund_id', 'is_refund', 'reason', 'created_code', 'created_at'])->with(['creator' => function ($q) {
                                $q->select(['employee_code', 'employee_name']);
                            }]);
                        }])
                        ->orderByDesc('id')
                        ->get()
                        ->toArray();
                }

                $data['data'] = $data;
                $data['total'] = count($data);
            } else {

                if ($cache_power_type == 1) {
                    $data = OrderRefund::where($where)
                        ->with(['order' => function ($q) {
                            $q->select(['order_sn', 'qrcode', 'sku_code', 'price', 'pay_count', 'pay_amount', 'actual_pay', 'pay_status', 'pay_at', 'public_id'])
                                ->with(['blacklist' => function ($qq) {
                                    $qq->select(['public_id', 'refund_num', 'status', 'updated_at']);
                                }]);
                        }, 'modifier' => function ($q) {
                            $q->select(['employee_code', 'employee_name']);
                        }, 'note' => function ($q) {
                            $q->select(['id', 'parent_id', 'des']);
                        }, 'reason' => function ($qq) {
                            $qq->select(['id', 'refund_id', 'is_refund', 'reason', 'created_code', 'created_at'])->with(['creator' => function ($q) {
                                $q->select(['employee_code', 'employee_name']);
                            }]);
                        }])
                        ->orderByDesc('id')
                        ->paginate($limit)
                        ->toArray();
                } else {
                    $data = OrderRefund::where($where)
                        ->whereIn('store_code', $cache_stroe_code)
                        ->with(['order' => function ($q) {
                            $q->select(['order_sn', 'qrcode', 'sku_code', 'price', 'pay_count', 'pay_amount', 'actual_pay', 'pay_status', 'pay_at', 'public_id'])
                                ->with(['blacklist' => function ($qq) {
                                    $qq->select(['public_id', 'refund_num', 'status', 'updated_at']);
                                }]);
                        }, 'modifier' => function ($q) {
                            $q->select(['employee_code', 'employee_name']);
                        }, 'note' => function ($q) {
                            $q->select(['id', 'parent_id', 'des']);
                        }, 'reason' => function ($qq) {
                            $qq->select(['id', 'refund_id', 'is_refund', 'reason', 'created_code', 'created_at'])->with(['creator' => function ($q) {
                                $q->select(['employee_code', 'employee_name']);
                            }]);
                        }])
                        ->orderByDesc('id')
                        ->paginate($limit)
                        ->toArray();
                }

            }
            //  print_r(DB::getquerylog());

            $response['data'] = $data['data'];
            $response['total'] = $data['total'];
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 加载图片
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getImage(Request $request)
    {
        try {
            $imgIds = $request->input('refund_img_id', null);
            $data = Attachment::whereIn('id', explode(',', $imgIds))->select(DB::raw('file_url,1 as is_click'))->get();
            return response(ReturnCode::success($data));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 审核
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function refund(Request $request, $id)
    {
        try {
            $refund = OrderRefund::find($id);
            if (!$refund) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $reason = $request->input('reason', null);
            $is_refund = $request->input('is_refund', null);

            $re = $is_refund == 'true' ? 1 : 0;
            DB::beginTransaction();

            //超过72小时不允许退款
            $order = Order::where('order_sn', $refund->order_sn)->first();
            if ($order->pay_status != 1) {
                return response(ReturnCode::error(ReturnCode::FAILED, '请到订单列表，检查订单状态是否已支付'));
            }
            if (strtotime($order->pay_at) + (86400 * 3) < time()) {
                return response(ReturnCode::error(ReturnCode::FAILED, '超过72小时不允许退款，请联系线下退款'));
            }

            $refund->is_handle = 1;
            $refund->handle_count = $refund->handle_count + 1;
            $refund->updated_code = Token::$ucode;

            OrderRefundReason::create([
                'refund_id' => $id,
                'is_refund' => $re,
                'reason' => $reason,
                'created_code' => Token::$ucode
            ]);
            if ($re == 1) {
                Log::info('order');
                Log::info($order);
                $res = Order::refund($order);
                Log::info($res);
                if ($res['status']) {
                    $refund->status = 1;
                }
            }
            $refund->save();

            DB::commit();
            return response(ReturnCode::success($res, '审核成功'));
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 删除退款记录
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $refund = OrderRefund::find($id);
            if (!$refund) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            DB::beginTransaction();
            $refund->deleted_code = Token::$ucode;
            $refund->save();

            $refund->delete();

            OrderRefundReason::where('refund_id', $id)->delete();

            DB::commit();
            return response(ReturnCode::success([], '删除成功'));
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }


    public function orderRefund(Request $request, $id)
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

            $res = Order::refund($order, '测试退款');
            if ($res['status']) {
                $order->pay_status = 3;
                $order->save();
            }
            return response(ReturnCode::success($res));
        } catch (\Exception $exception) {
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
}