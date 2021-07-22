<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/9
 * Time: 16:39
 */

namespace App\Libs\ApiIot;


class ApiIotReturnCode
{
    const SUCCESS            = 0; //成功
    const FAILED             =4; //失败
    const FORBIDDEN          = 4001; //权限不足
    const SYSTEM_FAIL        = 4002; //系统错误，如数据写入失败之类的
    const PARAMS_ERROR       = 4003; //参数错误
    const NOT_FOUND          = 4004; //资源未找到
    const ACCESS_TOKEN_ERROR = 4005; //access_token错误
    const AUTHORIZE_FAIL     = 4006; //权限验证失败
    const NOT_MODIFY         = 4007; //没有变动
    const RECORD_EXIST       = 4008; //记录已存在
    const SIGN_FAIL          = 4009; //签名错误
    const RECORD_NOT_EXIST   = 4010; //记录不存在
    const IOT_ID_NOT_EXIST   = 4011; //Iot_id 不存在
    const ALI_SYS_ERROR     =4012;//阿里返回错误
    const SIGN_TIMESTAMP=4013;//时间戳错误
    const SIGN_TIMESTAMP_TIMEOUT=4014;//时间戳错误
    const REGISTER_AGAIN=4015;//接收到此编码需要重新注册

    //中文错误详情
    public static $codeTexts = [
        0    => '操作成功',
        4    =>'失败',
        4001 => '权限不足',
        4002 => '系统错误，请联系管理员',
        4003 => '参数错误',
        4004 => '资源未找到',
        4005 => 'TOKEN无效',
        4006 => '权限不足',
        4007 => '没有修改',
        4008 => '记录已存在',
        4009 => '签名错误',
        4010 => '记录不存在',
        4011 => 'Iot_id 不存在',
        4012 => '阿里返回错误',
        4013 => '时间戳错误',
        4014 => '时间戳超时',
        4015 => '注册信息有误，请重新注册',
    ];

    const RES_REGISTER=0; //注册
    const RES_SUPPLY=1; //上货
    const RES_BUY=2; //购买
    const RES_REPAIR=3;//报修
    const RES_IOT=4;//设备
    const RES_SIGN=5;//签字
    const RES_PRICE=6;//更新价格


    public static function create($type,$code, $data = [], $msg = '')
    {
        if (empty($msg) && isset(self::$codeTexts[$code])) {
            $msg = self::$codeTexts[$code];
        }

        return ['type'=>$type,'code' => $code, 'msg' => $msg, 'data' => $data];
    }

    public static function success($type,$data = [], $msg = '')
    {
        if (empty($msg) && isset(self::$codeTexts[self::SUCCESS])) {
            $msg = self::$codeTexts[self::SUCCESS];
        }
        return ['type'=>$type,'code' => self::SUCCESS, 'msg' => $msg, 'data' => $data];
    }

    public static function error($type,$code, $msg = '')
    {
        if (empty($msg) && isset(self::$codeTexts[$code])) {
            $msg = self::$codeTexts[$code];
        }
        return ['type'=>$type,'code' => $code, 'msg' => $msg];
    }

}