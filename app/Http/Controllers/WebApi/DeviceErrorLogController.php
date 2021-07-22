<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/15
 * Time: 14:38
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\ApiIot\DeviceEgg;
use App\Models\ApiIot\DeviceErrorLog;
use App\Models\ApiIot\DeviceErrorLogDes;
use App\Models\Gashapon\Store;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeviceErrorLogController extends Controller
{
    /**
     * @des 查询错误日志
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $created_at = $request->input('created_at', null);
            $keyword = $request->input('keyword', null);
            $iot_id = $request->input('iot_id', null);
            $error_id = $request->input('error_id', null);
            $qrcode = $request->input('qrcode');

            $where = function ($query) use ($created_at, $iot_id, $error_id, $keyword, $qrcode) {
                if (!empty($created_at)) {
                    $query->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($created_at[0])), date('Y-m-d 23:59:59', strtotime($created_at[1]))]);
                }
                if (!empty($iot_id)) {
                    $query->where('iot_id', $iot_id);
                }
                if (strlen($error_id) > 0) {
                    $query->where('error_id', $error_id);
                }
                if (!empty($qrcode)) {
                    $query->where('egg_code', $qrcode);
                }

                if (!empty($keyword)) {
                    $des = DeviceErrorLogDes::where('error_des', 'like', '%' . $keyword . '%')->pluck('error_id');
                    if (!empty($des->toArray())) {
                        $query->whereIn('error_id', $des);
                    }
                }
            };

            $times = time() - 600;

            $errorList = DeviceErrorLog::where('error_id', '>', 0)
                ->whereNull('deleted_at')
                ->where('error_id', '>', '0')
                ->where('error_id', '<', 260)
                ->where('created_at', '>', Helper::datetime($times))
                ->where($where)
                ->get()
                ->toArray();
            $idStr = '';
            foreach ($errorList as $k => $v) {
                $errDeviceEgg = DeviceEgg::where('egg_code', $v['egg_code'])->where('iot_id', $v['iot_id'])->whereNull('deleted_at')->first();
                if ($errDeviceEgg) {
                    $idStr ? $idStr .= ',' . $v['id'] : $idStr = $v['id'];
                }
            }

            $idStr = explode(',', $idStr);
            $data = DeviceErrorLog::whereIn('id',$idStr)
                ->with(['iot' => function ($q) {
                    $q->select(['iot_id', 'note', 'device_code']);
                }, 'des' => function ($qq) {
                    $qq->where('language', Token::$language)->select(['error_id_bit', 'error_des']);
                }, 'store'])
                ->orderByDesc('id')
                ->paginate($limit)
                ->toArray();

            foreach ($data['data'] as $k => $v) {
                $errorlist = Helper::dbit($v['error_id']);
                $error_des = "";
                foreach ($errorlist as $elk => $elv) {
                    $errorDesc = DeviceErrorLogDes::where('error_id', $elv)->first();
                    if ($error_des) {
                        $error_des .= "+" . $errorDesc->error_des;
                    } else {
                        $error_des = $errorDesc->error_des;
                    }
                }
                $data['data'][$k]['des']['error_des'] = $error_des;

                $eggDetail = DeviceEgg::where('egg_code', $v['egg_code'])->where('iot_id', $v['iot_id'])->first();
                $data['data'][$k]['egg'] = $eggDetail;
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

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request, $id)
    {
        try {
            $deviceErr = DeviceErrorLog::find($id);
            if (!$deviceErr) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $deviceErr->deleted_code = Token::$ucode;
            $deviceErr->save();
            $deviceErr->delete();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //-DeviceErrorLogDes----------------------------------------------------------------------------

    /**
     * @des 错误码列表
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function indexDes(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $created_at = $request->input('created_at', null);
            $keyword = $request->input('keyword', null);

            $where = function ($query) use ($created_at, $keyword) {
                if (!empty($created_at)) {
                    $query->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($created_at[0])), date('Y-m-d 23:59:59', strtotime($created_at[1]))]);
                }
                if (!empty($keyword)) {
                    $query->where('error_des', 'like', '%' . $keyword . '%');
                }
            };

            $data = DeviceErrorLogDes::where($where)->where('language', Token::$language)
                ->orderByDesc('id')
                ->paginate($limit)
                ->toArray();

            $response['data'] = $data['data'];
            $response['total'] = $data['total'];
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function add(Request $request)
    {
        try {
            $error_id = $request->input('error_id', null);
            //判断是否存在
            $count = DeviceErrorLogDes::where('error_id', $error_id)
                ->where('language', Token::$language)->count();
            if ($count > 0) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST));
            }

            $insert = [
                'language' => Token::$language,
                'error_id' => $error_id,
                'error_id1' => $error_id,
            ];


        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $deviceErrDes = DeviceErrorLogDes::find($id);
            if (!$deviceErrDes) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $error_id = $request->input('error_id', null);
            //判断是否存在
            $count = DeviceErrorLogDes::where('id', '<>', $id)->where('error_id', $error_id)
                ->where('language', Token::$language)->count();
            if ($count > 0) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST));
            }

            $deviceErrDes->error_id = $error_id;
            $deviceErrDes->error_des = $request->input('error_des', null);
            $deviceErrDes->is_buy = $request->input('is_buy', 0);
            $deviceErrDes->updated_code = Token::$ucode;
            $deviceErrDes->save();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 删除
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function remove(Request $request, $id)
    {
        try {
            $deviceErrDes = DeviceErrorLogDes::find($id);
            if (!$deviceErrDes) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $deviceErrDes->deleted_code = Token::$ucode;
            $deviceErrDes->save();
            $deviceErrDes->delete();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
}