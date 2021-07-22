<?php

namespace App\Http\Middleware;

use App\Models\System\RequestLog;
use App\Models\System\ResponseLog;
use App\Models\Token;
use Closure;
use Illuminate\Support\Facades\Log;

class ApiLogMiddleware
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
        if(!isset(\App\Libs\RequestLog::$log[$routeName])){
            return $next($request);
        }
        //$real1=$request->header('X-Forwarded-For','X-Forwarded-For');
        $realIP=$request->header('X-Real-IP','X-Real-IP');

        $log=[
            'method' => $method,
            'params' => $request->all(),
            'path'=>$path,
            'fun'=>\App\Libs\RequestLog::$log[$routeName],
            'url' => $request->url(),
            'type' => Token::$type==null?2:Token::$type,
            'ip' => filter_var($realIP,FILTER_VALIDATE_IP)?$realIP:$request->ip(),
            'user_agent' => isset($request->server()['HTTP_USER_AGENT'])? $request->server()['HTTP_USER_AGENT']:null,
            'uid'=>Token::$uid,
            'created_code'=>Token::$ucode
        ];

        $logs=RequestLog::create($log);

        $request['log_id']=$logs?$logs->id:null;
        return $next($request);
    }

    //终端中间件
    public function terminate($request, $response)
    {
        try{
            $routeName= $request->route()->getName();
            if(!isset(\App\Libs\RequestLog::$log[$routeName])){
                return;
            }
            $log_id=$request->input('log_id',null);
            if(!empty($log_id)){
                $params=$request->all();
                unset($params['log_id']);
                $res=$response->original;
                ResponseLog::create([
                    'request_log_id'=>$log_id,
                    'params'=>$params,
                    'res_code'=>isset($res['code'])?$res['code']:NULL,
                    'res_msg'=>isset($res['msg'])?$res['msg']:NULL,
                    'res_total'=>isset($res['total'])?$res['total']:NULL,
                    'res_detail'=>$res
                ]);
            }
        }
        catch (\Exception $exception){
        }
    }
}
