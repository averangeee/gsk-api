<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/6/11
 * Time: 14:18
 */

namespace App\Http\Controllers;


use App\Models\System\EmployeeWeixin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WxOuthController extends Controller
{
    public function outh(Request $request)
    {
        $user = $request->session()->get('wechat.oauth_user',null);
        $url  = $request->input('url');
        Log::info($url);
        if($user){
            $employee=EmployeeWeixin::where('openid',$user->id)->first();
            if($employee){
                EmployeeWeixin::where('openid',$user->id)
                    ->update([
                        'nickname' =>base64_encode($user->nickname),
                        'avatar'   => $user->avatar,
                        'province' => $user['original']['province'],
                        'city'     => $user['original']['city'],
                        'sex'      => $user['original']['sex']
                    ]);
            }else{
                EmployeeWeixin::create([
                    'openid'   => $user->id,
                    'nickname' => base64_encode($user->nickname),
                    'avatar'   => $user->avatar,
                    'province' => $user['original']['province'],
                    'city'     => $user['original']['city'],
                    'sex'      => $user['original']['sex']
                ]);
            }
            return redirect($url.'&openid='.$user->id);
        }else{
            Log::info('无用户');
            Log::inof(json_encode($user));
        }

    }
}