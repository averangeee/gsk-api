<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/29
 * Time: 17:58
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Shop\PayConfig;
use App\Models\System\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class payConfigController extends Controller
{
    /**
     * 支付配置列表
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/29 18:06
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $shop_id = $request->input('shop_id', null);
            $pay_type = $request->input('pay_type_id', null);
            $status = $request->input('status', null);

            $where = function ($q) use ($shop_id, $pay_type, $status) {
                if (!empty($shop_id)) {
                    $q->where('shop_id', $shop_id);
                }

                if (!empty($pay_type)) {
                    $q->where('pay_type_id', $pay_type);
                }

                if (!empty($status)) {
                    $q->where('status', $status);
                }
            };

            $configs = PayConfig::where($where)
                ->with(['shop' => function ($q) {
                    $q->select(['id', 'name', 'query_string']);
                }, 'payType' => function ($q) {
                    $q->select(['id', 'name']);
                }, 'cacert' => function ($q) {
                    $q->select(['id', 'file_name', 'file_url']);
                }, 'key' => function ($q) {
                    $q->select(['id', 'file_name', 'file_url']);
                }])
                ->paginate($limit)
                ->toArray();


            $response['code'] = ReturnCode::SUCCESS;
            $response['data'] = $configs['data'];
            $response['total'] = $configs['total'];

            return response($response);
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * 添加支付配置
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/30 14:37
     */
    public function create(Request $request)
    {
        try {
            $shop_id = $request->input('shop_id', null);
            $pay_type_id = $request->input('pay_type_id', null);
            $encrypt_type = $request->input('encrypt_type', null);
            $token = $request->input('token', null);
            $app_id = $request->input('app_id', null);
            $message_id = $request->input('message_id', null);
            $secret = $request->input('secret', null);
            $seller_key = $request->input('seller_key', null);
            $seller_pay_key = $request->input('seller_pay_key', null);
            $cacert_id = $request->input('cacert_id', null);
            $cacert_file = $request->input('cacert_file', null);
            $key_id = $request->input('key_id', null);
            $key_file = $request->input('key_file', null);
            $remarks = $request->input('remarks', null);


            $cacert_value = '';
            $key_value = '';

            if (!empty($cacert_file)) {
                $cacert_value = file_get_contents(storage_path($cacert_file));
            }
            if (!empty($key_file)) {
                $key_value = file_get_contents(storage_path($key_file));
            }

            PayConfig::create([
                'shop_id' => $shop_id,
                'pay_type_id' => $pay_type_id,
                'encrypt_type' => $encrypt_type,
                'token' => $token,
                'app_id' => $app_id,
                'message_id' => $message_id,
                'secret' => $secret,
                'seller_key' => $seller_key,
                'seller_pay_key' => $seller_pay_key,
                'cacert_id' => $cacert_id,
                'cacert_file' => $cacert_file,
                'cacert_value' => $cacert_value,
                'key_id' => $key_id,
                'key_file' => $key_file,
                'key_value' => $key_value,
                'remarks' => $remarks,
            ]);

            return response(ReturnCode::success());

        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * 修改配置
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/31 16:47
     */
    public function update(Request $request, $id)
    {
        try {

            $config = PayConfig::find($id);
            if (!$config) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST, '配置不存在'));
            }


            $shop_id = $request->input('shop_id', null);
            $pay_type_id = $request->input('pay_type_id', null);
            $encrypt_type = $request->input('encrypt_type', null);
            $token = $request->input('token', null);
            $app_id = $request->input('app_id', null);
            $message_id = $request->input('message_id', null);
            $secret = $request->input('secret', null);
            $seller_key = $request->input('seller_key', null);
            $seller_pay_key = $request->input('seller_pay_key', null);
            $cacert_file = $request->input('cacert_file', null);
            $cacert_id = $request->input('cacert_id', null);
            $key_file = $request->input('key_file', null);
            $key_id = $request->input('key_id', null);
            $remarks = $request->input('remarks', null);

            $config->shop_id = $shop_id;
            $config->pay_type_id = $pay_type_id;
            $config->encrypt_type = $encrypt_type;
            $config->token = $token;
            $config->app_id = $app_id;
            $config->message_id = $message_id;
            $config->secret = $secret;
            $config->seller_key = $seller_key;
            $config->seller_pay_key = $seller_pay_key;
            $config->remarks = $remarks;


            if (!empty($cacert_file) && $cacert_file != 'null') {
                $config->cacert_file = $cacert_file;
                $config->cacert_value = file_get_contents(storage_path($cacert_file));
            }


            if (!empty($key_file) && $key_file != 'null') {
                $config->key_file = $key_file;
                $config->key_value = file_get_contents(storage_path($key_file));
            }


            $config->save();

            return response(ReturnCode::success());
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * 修改配置状态
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/31 16:57
     */
    public function changeStatus(Request $request, $id)
    {
        try {
            $config = PayConfig::find($id);
            if (!$config) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST, '配置不存在'));
            }

            $status = $request->input('status', 2);

            //如果是启用配置，则检查当前是否有启用的配置，需将现在启用的配置禁用之后才能启用新的配置
            if ($status == 1) {
                $usedConfig = PayConfig::where('shop_id', $config->shop_id)
                    ->where('pay_type_id', $config->pay_type_id)
                    ->where('status', 1)
                    ->first(['id']);

                if ($usedConfig) {
                    return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '请将该商家当前启用的配置停用'));
                }
            }

            $config->status = $status;
            $config->save();

            return response(ReturnCode::success());
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }
}