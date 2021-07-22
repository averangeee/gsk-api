<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/25
 * Time: 17:59
 */

namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $table='gsk_token';

    protected $guarded=[];

    /** 验证为token时的uid */
    public static $uid;
    public static $ucode;
    public static $uname;
    public static $type;
    public static $language;

    /**
     * 生成token
     * @param $type
     * @param $uid
     * @param array $data
     * @return Model|mixed|null|string|static
     * @throws \Exception
     * @author yss
     * @date  2018/9/20 10:21
     */
    public static function generate($type,$uid,$uname, $ucode,$language='zhCN', $data = [])
    {
        //清除原token todo 以后再启用
        //self::where(['type' => $type, 'created_code' => $ucode,'language'=>$language])->delete();

        //todo 暂时支持多账号登录
        if ($token = self::where(['type' => $type, 'created_code' => $ucode,'language'=>$language])->first()) {
            $token->expired_at=Carbon::now()->addDay()->toDateTimeString();
            $token->save();
            return $token->token;
        }

        //生成新token
        $token = md5($type . '-' . $ucode . '-' . $ucode.'-'. time() . rand(0, 9999));
        self::insert([
            'token'      => $token,
            'type'       => $type,
            'uid'        => $uid,
            'uname'        => $uname,
            'created_code' => $ucode,
            'language' => $language,
            'data'       => $data ? json_encode($data) : '[]',
            'expired_at' => Carbon::now()->addDay()->toDateTimeString()
        ]);
        return $token;
    }

    /**
     * token验证
     * @param $token
     * @return bool
     * @author yss
     * @date  2018/9/20 11:56
     */
    public static function checkToken($token)
    {
        $token = self::where('token', $token)->first();
        if ($token) {
            self::$uid  = $token->uid;
            self::$ucode = $token->created_code;
            self::$type = $token->type;
            self::$language = $token->language;
            self::$uname = $token->uname;
        }
        return $token ? true : false;
    }
}
