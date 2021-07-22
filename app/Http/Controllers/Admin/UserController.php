<?php /*封装金蝶K3cloud webapi;author：王志锋email:wangzhifeng@tongdog.com.cn */

namespace kingdeeapi;
class kingdeeapi
{
    private $username;
    private $password;
    private $apiurl;
    private $acctID;    //构造函数，初始化

    public function __construct($username, $password, $apiurl, $acctID)
    {
        $this->username = $username;
        $this->password = $password;
        $this->acctID = $acctID;
        $this->apiurl = $apiurl;
        $this->cookie = $this->getcookie();
    }

    //登陆接口获取cookie
    private function getcookie()
    {
        $apiurl = "https://" . $this->apiurl . "/k3cloud/Kingdee.BOS.WebApi.ServicesStub.AuthService.ValidateUser.common.kdsvc";
        $logindata = array("acctid" => $this->acctID, "username" => $this->username, "password" => $this->password, "lcid" => 2052,);
        $postdata = json_encode($logindata);
        $result = $this->httpRequest($apiurl, $postdata, false);
    }

    //http请求
    public function httpRequest($url, $post_content, $isLogin = true)
    {        //cookie文件
        ///$cookie_jar = tempnam('/Applications/XAMPP/xamppfiles/temp/', 'cookie');
        $ch = curl_init($url);
        $this_header = array('Content-Type: application/json', 'Content-Length: ' . strlen($post_content),);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this_header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_HEADER,true);
        if ($isLogin) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, "/Applications/XAMPP/xamppfiles/htdocs/law/simplewind/extend/kingdeeapi/cookie.txt");
        } else {
            curl_setopt($ch, CURLOPT_COOKIEJAR, "/Applications/XAMPP/xamppfiles/htdocs/law/simplewind/extend/kingdeeapi/cookie.txt");
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
