<?php

namespace App\Http\Middleware;

use Closure;
use App\Libs\ApiIot\ApiIotReturnCode;
use App\Libs\Helper;
use Illuminate\Support\Facades\Log;

class IotRegisterMiddleware
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
        $timestamp = $request->header('Timestamp') ?: $request->input('timestamp', null);

        if (!$sign) {

            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::SIGN_FAIL));
        }

        if (!$timestamp) {
            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::SIGN_TIMESTAMP));
        }

        if (!$this->checkSign($request)) {
            Log::info("签名调试");
            Log::info($request);

            return response(ApiIotReturnCode::error(ApiIotReturnCode::RES_SIGN, ApiIotReturnCode::SIGN_FAIL));
        }

        return $next($request);
    }

    public function checkSign($request)
    {
        $salt = Config('app.salt');
        $sign = $request->header('Sign') ?: $request->input('sign');
        $params = $request->all();
        unset($params['sign']);
        unset($params['log_id']);
        ksort($params);
        $query = null;
        foreach (array_keys($params) as $key) {
            $pp = $params[$key];
            if (!is_array($pp)) {
                if ($query) {
                    $query = $query . $key . $pp;
                } else {
                    $query = $key . $pp;
                }
            }
        }
        $validSign = strtoupper(md5($query . $salt));

        if ($sign != $validSign) {
            return false;
        }
        return true;
    }
}
