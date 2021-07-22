<?php
/**
 * Created by PhpStorm.
 * User: zhanglihe
 * Date: 16/7/18
 * Time: 下午3:04
 */

namespace App\Libs;


use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Sms
{
    protected static $account = 'C29544189';
    protected static $appKey = '27c03462e14303d263018d5db964345f';
    public static $cachePrefix = '__VC__';

    public static function Post($curlPost, $url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }

    public static function xml_to_array($xml)
    {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $subxml = $matches[2][$i];
                $key = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = self::xml_to_array($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

    public static function send($mobile, $content, $shop_id)
    {
        $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";

        if (empty($mobile)) {
            exit('手机号码不能为空');
        }

        $post_data = "account=" . self::$account . "&password=" . self::$appKey . "&mobile=" . $mobile . "&content=" . rawurlencode($content);
        //密码可以使用明文密码或使用32位MD5加密
        $gets = self::xml_to_array(self::Post($post_data, $target));
        print_r($gets);
        if ($gets['SubmitResult']['code'] == 2) {
            return true;
        }
        Log::error("发送短信失败，号码：{$mobile}, 内容：{$content}，错误详情：" . $gets['SubmitResult']['msg']);
        return false;
    }

    /**
     * 校验短信验证码是否正确
     *
     * @param $mobile
     * @param $verifyCode
     *
     * @return bool
     */
    public static function validation($mobile, $verifyCode)
    {
        $code = Cache::get(self::$cachePrefix . $mobile);
        Log::info('mobile:' . $mobile . ',verifyCode:' . $verifyCode . ',code:' . $code);
        if ($code && intval($verifyCode) == $code) {
            Cache::forget(self::$cachePrefix . $mobile);
            return true;
        }
        return false;
    }

    /**
     * 验证码短信通知
     *
     * @param $mobile
     *
     * @return bool|int
     */
    public static function generate($mobile, $shop_id)
    {
        $code = Cache::get(self::$cachePrefix . $mobile);
        Log::info('mobile:' . $mobile . ',cache code:' . $code);
        if (!$code) {
            $code = rand(1000, 9999);
        }
        $text = "您的验证码是：【" . $code . "】。请不要把验证码泄露给其他人。如非本人操作，可不用理会！";
        if (!self::send($mobile, $text, $shop_id)) {
            return false;
        }
        Log::info('mobile:' . $mobile . ',code:' . $code);
        //写入缓存
        $expiresAt = Carbon::now()->addMinutes(30);
        Cache::put(self::$cachePrefix . $mobile, $code, $expiresAt);
        return $code;
    }
}
