<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/11
 * Time: 12:00
 */

namespace App\Libs;


use App\Models\Shop\PayConfig;
use EasyWeChat\Foundation\Application;

class PayHelper
{
    /**
     * @des 阿里配置
     * @param PayConfig $payConfig
     * @return \AopClient
     */
    public static function resAopClient(PayConfig $payConfig)
    {
        $aop = new \AopClient();

        $aop->appId = $payConfig->app_id;
        //商家私钥 、ISV提供签名私钥
        $aop->rsaPrivateKey = $payConfig->seller_pay_key;
        //支付宝公钥 、ISV中转公钥
        $aop->alipayrsaPublicKey = $payConfig->secret;
        $aop->signType = $payConfig->encrypt_type;
        $aop->format = 'json';

        return $aop;
    }

    /**
     * @des 微信配置
     * @param PayConfig $payConfig
     * @return Application
     */
    public static function resApplication(PayConfig $payConfig)
    {
        $config = config('wechat');

        //wx452dbf54a4d2312a 服务商 1572793081
        //

        if ($payConfig->wechat_fw == 1) {//原来的
            $config['app_id'] = $payConfig->app_id;
            $config['secret'] = $payConfig->secret;
            $config['payment']['merchant_id'] = $payConfig->seller_key;
            $config['payment']['key'] = $payConfig->seller_pay_key;
        } else {//服务商
            $config['app_id'] = "wx452dbf54a4d2312a";
            $config['secret'] = $payConfig->secret;
           // $config['payment']['sub_appid'] = $payConfig->app_id;
            $config['payment']['sub_mch_id'] = $payConfig->seller_key;
            $config['payment']['merchant_id'] = "1572793081";
            $config['payment']['key'] = "1572793081wx452dbf54a4d2312a1811";

        }


        if ($payConfig->wechat_fw == 2) {//服务商
            $config['payment']['cert_path'] = storage_path("app/public/upload/fws/apiclient_cert.pem");
            $config['payment']['key_path'] = storage_path("app/public/upload/fws/apiclient_key.pem");
        } else {
            //证书
            $config['payment']['cert_path'] = storage_path($payConfig->cacert_file);
            $config['payment']['key_path'] = storage_path($payConfig->key_file);
        }


        $app = new Application($config);

        return $app;
    }
}
