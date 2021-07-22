<?php

namespace App\Http\Middleware;

use Closure;
use App\Libs\ApiIot\ApiIotRequestLog;
use App\Models\ApiIot\RequestIotLog;
use App\Models\ApiIot\ResponseIotLog;

class IotLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $method = $request->method();
        $path=$request->path();
        $routeName= $request->route()->getName();

        if(!isset(ApiIotRequestLog::$routerFun[$routeName])){
            return $next($request);
        }

        $log=[
            'method' => $method,
            'path'=>$path,
            'fun'=>ApiIotRequestLog::$routerFun[$routeName],
            'url' => $request->url(),
            'params' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => isset($request->server()['HTTP_USER_AGENT'])? $request->server()['HTTP_USER_AGENT']:null,
            'iot_id' => $request->input('iot_id',null)? $request->input('iot_id',null):null,
            'device_code' => $request->input('device_name',null)? $request->input('device_name',null):null
        ];

        $logs=RequestIotLog::create($log);

        $request['log_id']=$logs?$logs->id:0;

        return $next($request);
    }

    //终端中间件
    public function terminate($request, $response)
    {
//        Log::info($request->input('log_id',0));
//        Log::info($request->all());
//        Log::info($response->original);
        try{
            $routeName= $request->route()->getName();
            if(!isset(ApiIotRequestLog::$routerFun[$routeName])){
                return;
            }
            $log_id=$request->input('log_id',0);
            $params=$request->all();
            unset($params['log_id']);
            $res=$response->original;
            ResponseIotLog::create([
                'request_log_id'=>$log_id,
                'params'=>$params,
                'res_code'=>isset($res['code'])?$res['code']:NULL,
                'res_msg'=>isset($res['msg'])?$res['msg']:NULL,
                'res_detail'=>$res
            ]);
        }
        catch (\Exception $exception){
        }
    }
}
