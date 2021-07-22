<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/12
 * Time: 13:52
 */

namespace App\Http\Controllers\WebApi;

use App\Libs\Helper;
use App\Models\Order\BlackUser;
use App\Models\Order\Order;
use App\Models\Order\OrderRefundBlacklist;
use App\Models\Token;
use App\Libs\ReturnCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlacklistController extends Controller
{
    /**
     * @des 查询黑名单
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $created_at = $request->input('created_at', null);
            $type = $request->input('type');

            $where = function ($query) use ($created_at, $type) {
                if (!empty($created_at)) {
                    $query->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($created_at[0])), date('Y-m-d 23:59:59', strtotime($created_at[1]))]);
                }
                if (strlen($type) > 0) {
                    $query->where('type', $type);
                }
            };

            $data = BlackUser::where($where)
                ->with(['userDetail'])
                ->orderByDesc('number')
                ->paginate($limit)
                ->toArray();


            foreach ($data['data'] as $k => $v) {
                $data['data'][$k]['user_detail']['nickname'] = base64_decode($v['user_detail']['nickname']);
                $successNumber = Order::where('egg_count', '>', 0)->where('public_id', $v['openid'])->count();
                $data['data'][$k]['successNumber'] = $successNumber;

                $number = Order::where('egg_count', 0)->whereIn('pay_status', [1, 3])->where('public_id', $v['openid'])->count();
                $data['data'][$k]['number'] = $number;

                $month = date('Y-m', time());
                $month_number = Order::where('egg_count', 0)->where('created_at','>',$month)->whereIn('pay_status', [1, 3])->where('public_id', $v['openid'])->count();
                $data['data'][$k]['month_number'] = $month_number;
            }

            $response['data'] = $data['data'];
            $response['total'] = $data['total'];
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function save(Request $request)
    {
        $openid = $request->input('openid');
        $type = $request->input('type');

        $detail = BlackUser::where('openid', $openid)->first();
        if ($detail) {
            $detail->type = $type;
            if ($type == 2) {
                $detail->sf_at = Helper::datetime();
            }
            $detail->save();
            return response(ReturnCode::success());
        } else {
            return response(ReturnCode::error(ReturnCode::FAILED, "参数异常"));
        }
    }

    /**
     * @des 切换 1 启用 ，0 停用
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function status(Request $request, $id)
    {
        try {
            $blacklist = OrderRefundBlacklist::find($id);
            if (!$blacklist) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

            $status = $request->input('status', 1);
            $blacklist->status = $status;
            $blacklist->updated_code = Token::$ucode;
            $blacklist->save();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 1.锁定 0.放开
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function lock(Request $request, $id)
    {
        try {
            $blacklist = OrderRefundBlacklist::find($id);
            if (!$blacklist) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

            $is_lock = $request->input('is_lock', 1);
            $blacklist->is_lock = $is_lock;
            $blacklist->updated_code = Token::$ucode;
            $blacklist->save();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
}
