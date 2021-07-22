<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2018/8/13
 * Time: 14:35
 */

namespace App\Libs;


class HashKey
{
    //https://www.cnblogs.com/dcb3688/p/4608007.html



    /**
     * @des 随机生成cd_key ,20个字符
     * @param $len - 分隔字符串个数
     * @param int $step 单个字符个数
     * @return string
     */
    public static function cdKey($len,$step=5)
    {
        $res=[];
        for($l=1;$l<=$len;$l++){
            $str='';
            for($i=1;$i<=$step;$i++){
                $str=$str.chr(rand(65, 90));
            }
            $res[]=$str;
        }
        $return=implode('-',$res);
        return $return;
    }


    /**
     * @des 字符串
     * @date 2018-8-16 17:03:44
     * @param $len
     * @param int $step
     * @param string $key
     * @return string
     */
    public static function strKey($len,$step=5,$key='')
    {
        $res=[];
        for($l=1;$l<=$len;$l++){
            $str='';
            for($i=1;$i<=$step;$i++){
                $str=$str.chr(rand(65, 90));
            }
            $res[]=$str;
        }
        $return=implode($key,$res);
        return $return;
    }
    public static function guidDate($prefix,$max=99,$len=2)
    {
//        chr(rand(65, 90)).
        $guid='PK'.$prefix.date('Yms').substr('000000000'. rand(0, $max),-$len);
        return $guid;
    }
    public static function guid($prefix,$max=99,$len=2)
    {
//        chr(rand(65, 90)).
        $guid='PK'.$prefix.time().substr('000000000'. rand(0, $max),-$len);
        return $guid;
    }

    public static function pkcode($prefix,$val,$len=2)
    {
        $guid=$prefix.substr('000000000'. $val,-$len);
        return $guid;
    }

    //生成蛋仓码
    public static function qrcode($val)
    {
        return 'N'.substr('0000000'. $val,-7);
    }

    public static function qrcode2($code,$val)
    {
        $code=substr('000'. $code,-3);
        return 'N'.$code.substr('0000'. $val,-4);
    }

    public static function kcode($len=1,$len2=1,$step=4){
        return self::cdKey($len,$step).($len==0?'':'-').time().($len2==0?'':'-').self::cdKey($len2,$step);
    }

    /**
     * @des 退款码
     * @return string
     */
    public static function refundCode()
    {
        return 'H'.time().substr('0000'. rand(0, 999),-3);
    }
}