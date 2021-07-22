<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/15
 * Time: 16:47
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ApiIot\ApiIotUtil;
use App\Libs\ReturnCode;
use App\Models\ApiIot\Device;
use App\Models\ApiIot\DeviceSoftware;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceSoftwareController extends Controller
{
    /**
     * @des 设备软件固件列表
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $created_at = $request->input('created_at', null);
            $keyword = $request->input('keyword', null);
            $status = $request->input('status', null);
            $type = $request->input('type', null);

            $where = function ($query) use ($created_at, $keyword, $status, $type) {
                if (!empty($date)) {
                    $query->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($created_at[0])), date('Y-m-d 23:59:59', strtotime($created_at[1]))]);
                }
                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->orWhere('name', 'like', '%' . $keyword . '%')->orWhere('des', 'like', '%' . $keyword . '%');
                    });
                }
                if (strlen($status) > 0) {
                    $query->where('status', $status);
                }
                if (strlen($type) > 0) {
                    $query->where('type', $type);
                }
            };

            $data = DeviceSoftware::where($where)
                ->with(['creator' => function ($qc) {
                    $qc->select(['employee_code', 'employee_name']);
                }, 'modifier' => function ($qc) {
                    $qc->select(['employee_code', 'employee_name']);
                }, 'attach' => function ($at) {
                    $at->select(['id', 'file_name', 'file_url']);
                }])
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

    /**
     * @des 添加
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function add(Request $request)
    {
        try {
            $type = $request->input('type', null);
            $version = $request->input('version', null);
            $name = $request->input('name', null);
            $des = $request->input('des', null);
            $attach_id = (int)$request->input('attach_id', 0);
            $file_path = $request->input('file_path', null);
            $url = $request->input('url', null);

            $count = DeviceSoftware::where('type', $type)->where('version', $version)->count();
            if ($count > 0) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST));
            }

            if ($attach_id < 1) {
                $attach_id = 0;
                $file_path = '';
            }
            DeviceSoftware::create([
                'name' => "$name",
                'des' => "$des",
                'attach_id' => "$attach_id",
                'file_path' => "$file_path",
                'type' => "$type",
                'version' => "$version",
                'url' => "$url",
                'created_code' => Token::$ucode

            ]);
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(Request $request, $id)
    {
        try {
            $software = DeviceSoftware::find($id);
            if (!$software) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

            $type = $request->input('type', null);
            $version = $request->input('version', null);
            $name = $request->input('name', null);
            $des = $request->input('des', null);
            $attach_id = $request->input('attach_id', null);
            $file_path = $request->input('file_path', null);
            $url = $request->input('url', null);

            $count = DeviceSoftware::where('id', '<>', $id)->where('type', $type)->where('version', $version)->count();
            if ($count > 0) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST));
            }

            $software->name = $name;
            $software->des = $des;
            $software->attach_id = $attach_id;
            $software->file_path = $file_path;
            $software->version = $version;
            $software->type = $type;
            $software->url = $url;
            $software->updated_code = Token::$ucode;
            $software->save();

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
    public function delete(Request $request, $id)
    {
        try {
            $software = DeviceSoftware::find($id);
            if (!$software) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $software->deleted_code = Token::$ucode;
            $software->save();
            $software->delete();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 更新启用，停用；一个类型只允许一个启用
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function status(Request $request, $id)
    {
        try {
            $software = DeviceSoftware::find($id);
            if (!$software) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            DeviceSoftware::where('type', $software->type)->update(['status' => 0]);
            DeviceSoftware::where('id', $id)->update(['status' => 1, 'updated_code' => Token::$ucode]);

            $data['download_url'] = $software->url;//"http://sl2.api.fmcgbi.com/api/show/upload/".$software->attach_id;
            $data['version'] = $software->version;

            $list = Device::whereNull('deleted_at')->where('version_true', '0')->get()->toArray();
            foreach ($list as $k => $v) {
                $result = ApiIotUtil::updateControl($v['device_name'], $software->id, $data);
                Log::info(json_encode($result));
            }


            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }


    //升级中控单个
    public function update_center(Request $request)
    {
        try {
            $iot_id = $request->input('iot_id');

            $device = Device::where('iot_id', $iot_id)->first();
            if (!$device) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $software = DeviceSoftware::where('id', '>', 1)->orderByDesc('id')->first();
            $data['download_url'] = $software->url;
            $data['version'] = $software->version;
            Log::info('单个中控升级' . $iot_id);
            Log::info($data);

            $result = ApiIotUtil::updateControl($device->device_name, $software->id, $data);
            Log::info('单个中控升级');
            Log::info(json_encode($result));


            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //重启中控
    public function restart(Request $request)
    {
        try {
            $iot_id = $request->input('iot_id');

            $device = Device::where('iot_id', $iot_id)->first();
            if (!$device) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            Log::info('单个中控重启' . $iot_id);
            $data['info'] = '无';

            $result = ApiIotUtil::reStart($device->device_name, $device->id, $data);
            Log::info('单个中控升级');
            Log::info(json_encode($result));

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }


}
