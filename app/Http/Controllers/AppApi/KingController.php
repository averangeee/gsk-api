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

class KingController extends Controller
{

    protected $login = array('5ba1c5c3514e23', '刘红永', 'kingdee123', 2052);
    protected $cloudUrl = 'http://sl.sfims.com:88/k3cloud/';

    function login()
    {
        $cookie_jar = tempnam('D:\kd', 'CloudSession');//保存登录后的session
        $post_content = $this->create_postdata($this->login);
        $result = $this->invoke_login($this->cloudUrl, $post_content, $cookie_jar);
        $array = json_decode($result, true);
        print_r($result);
        echo '123';
    }

    //登陆
    function invoke_login($cloudUrl, $post_content, $cookie_jar)
    {
        $loginurl = $cloudUrl . 'Kingdee.BOS.WebApi.ServicesStub.AuthService.ValidateUser.common.kdsvc';
        return $this->invoke_post($loginurl, $post_content, $cookie_jar, TRUE);
    }

    //保存
    function invoke_save($cloudUrl, $post_content, $cookie_jar)
    {
        $invokeurl = $cloudUrl . 'Kingdee.BOS.WebApi.ServicesStub.DynamicFormService.Save.common.kdsvc';
        return $this->invoke_post($invokeurl, $post_content, $cookie_jar, FALSE);
    }

    //查询
    function invoke_view($cloudUrl, $post_content, $cookie_jar)
    {
        $invokeurl = $cloudUrl . 'Kingdee.BOS.WebApi.ServicesStub.DynamicFormService.View.common.kdsvc';
        return $this->invoke_post($invokeurl, $post_content, $cookie_jar, FALSE);
    }

    //审核
    function invoke_audit($cloudUrl, $post_content, $cookie_jar)
    {
        $invokeurl = $cloudUrl . 'Kingdee.BOS.WebApi.ServicesStub.DynamicFormService.Audit.common.kdsvc';
        return $this->invoke_post($invokeurl, $post_content, $cookie_jar, FALSE);
    }

    //反审核
    function invoke_unaudit($cloudUrl, $post_content, $cookie_jar)
    {
        $invokeurl = $cloudUrl . 'Kingdee.BOS.WebApi.ServicesStub.DynamicFormService.UnAudit.common.kdsvc';
        return $this->invoke_post($invokeurl, $post_content, $cookie_jar, FALSE);
    }

    //提交
    function invoke_submit($cloudUrl, $post_content, $cookie_jar)
    {
        $invokeurl = $cloudUrl . 'Kingdee.BOS.WebApi.ServicesStub.DynamicFormService.Submit.common.kdsvc';
        return $this->invoke_post($invokeurl, $post_content, $cookie_jar, FALSE);
    }

    function invoke_post($url, $post_content, $cookie_jar, $isLogin)
    {
        $url = 'http://sl.sfims.com:88/k3cloud/';
        $ch = curl_init($url);
        $this_header = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($post_content)
        );

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($isLogin) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
        } else {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    //构造Web API请求格式
    function create_postdata($args)
    {
        $postdata = array(
            'format' => 1,
            'useragent' => 'ApiClient',
            'rid' => $this->create_guid(),
            'parameters' => $args,
            'timestamp' => date('Y-m-d'),
            'v' => '7.2.877.3'
        );
        return json_encode($postdata);
    }

    //生成guid
    function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);// "}"
        return $uuid;
    }

}