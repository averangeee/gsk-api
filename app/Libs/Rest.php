<?php
/**
 * Created by PhpStorm.
 * User: zhanglihe
 * Date: 16/3/9
 * Time: 下午4:43
 */
namespace App\Libs;

class Rest
{
    protected static $http = false;

    protected static $url;

    protected static $appKey;

    protected static $appSecret;

    protected static $timeout = 10;

    public static function get($api, $params = [], $options = [])
    {
        $params = [
            'query' => $params
        ];
        return self::request('get', $api, $params, $options);
    }

    public static function post($api, $params = [], $options = [])
    {
        $params = [
            'form_params' => $params
        ];
        return self::request('post', $api, $params, $options);
    }

    private static function request($type, $api, $parameters, $options)
    {
        if (!self::$http) {
            self::$http      = app()->make('Http');
        }
        /** 生成sign */
        $signTime = time();
        $sign     = md5(self::$appKey . self::$appSecret . $signTime);

        $parameters['headers']              = [];
        $parameters['headers']['App-Key']   = self::$appKey;
        $parameters['headers']['Sign-Time'] = $signTime;
        $parameters['headers']['Sign']      = $sign;
        $parameters['connect_timeout']      = self::$timeout;
        foreach ($options as $k => $opt) {
            $parameters[$k] = $opt;
        }

        $result = self::$http->request($type, self::$url . $api, $parameters);

        if ($result->getStatusCode() !== 200) {
            return false;
        }

        $response = (string)$result->getBody();

        if (!$response) {
            return false;
        }
        return json_decode($response, true);
    }

    public static function config($options = [])
    {
        isset($options['url']) && self::$url = $options['url'];
        return new static;
    }

    public static function curlGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_NOBODY, true);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }
}