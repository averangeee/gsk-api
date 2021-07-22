<?php

namespace App\Http\Middleware;

use Closure;
use App\Libs\ApiIot\ApiIotReturnCode;
use App\Libs\Helper;
use App\Models\ApiIot\Device;
use Illuminate\Support\Facades\Log;

class SignMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $sign = $request->header('Sign') ?: $request->input('sign');
        $iot_id = $request->header('iot_id') ?: $request->input('iot_id');
        $timestamp = $request->header('Timestamp') ?: $request->input('timestamp', null);

        if (!$iot_id) {
            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::PARAMS_ERROR, '签名参数错误'));
        }

        if (!$sign) {
            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::SIGN_FAIL));
        }

        if (!$timestamp) {
            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::SIGN_TIMESTAMP));
        }
        if (!Helper::checkTimestamp($timestamp)) {
//            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN,ApiIotReturnCode::SIGN_TIMESTAMP_TIMEOUT));
        }

        //判断 device_secret
        $device = Device::where('iot_id', $iot_id)->first();
        if (!isset($device->device_secret)) {
            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::REGISTER_AGAIN));
        }
        $salt = $device->device_secret;
      //  Log::info("签名调试c");
        if (!$this->checkSign($request, $salt)) {
            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::SIGN_FAIL));
        }
       // Log::info("签名调试d");
        //更新设备信息
        Device::synDeviceInfo($device, $iot_id);

        return $next($request);
    }

    private function checkSign($request, $salt)
    {
        $sign = $request->header('Sign') ?: $request->input('sign');

        $params = $request->all();
      //  Log::info("_____________________");
       // Log::info($params);
       // Log::info("++++++++++++++++++++++");
        unset($params['sign']);
        unset($params['log_id']);
        ksort($params);
        $query = null;
        foreach (array_keys($params) as $key) {
            $pp = $params[$key];
            if ($key != 's') {
                if (!is_array($pp)) {
//                $pp=implode('', $pp);
                    if ($query) {
//                    $query=$query.'&'.$key.'='.$pp;
                        $query = $query . $key . $pp;
                    } else {
//                    $query=$key.'='.$pp;
                        $query = $key . $pp;
                    }
                }
            }
        }

//        if(!empty($query)){
//            $query=$query."\n";
//        }
        //$validSign = md5(http_build_query($params) . $salt);
        $validSign = strtoupper(md5($query . $salt));
        if ($sign != $validSign) {
            return false;
        }
        return true;
    }


}
