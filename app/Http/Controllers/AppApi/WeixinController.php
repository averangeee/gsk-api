<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/12
 * Time: 11:21
 */

namespace App\Http\Controllers\AppApi;


use App\Libs\ReturnCode;
use EasyWeChat\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WeixinController extends Controller
{
    public function index(Request $request)
    {
        try{
            $app=new Application(config('wechat'));

            $js=$app->js;

            $url=$request->input('url');
            $js->setUrl($url);

            $config=$js->config([]);

            return response(ReturnCode::success($config));
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }
}