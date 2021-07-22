<?php

namespace App\Http\Middleware;

use App\Libs\ReturnCode;
use App\Models\Token;
use Closure;

class TokenMiddleware
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
        $token=$request->header('x-token')?:$request->input('x-token');

        if(!$token){
            return response(ReturnCode::error(ReturnCode::FORBIDDEN));
        }

        if(!Token::checkToken($token))
        {
            return response(ReturnCode::error(ReturnCode::FORBIDDEN));
        }
        return $next($request);
    }
}
