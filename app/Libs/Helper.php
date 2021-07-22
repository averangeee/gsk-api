<?php
/**
 * Created by PhpStorm.
 * User: zhanglihe
 * Date: 16/3/15
 * Time: 下午3:37
 */

namespace App\Libs;

use App\Models\Order\Order;
use App\Models\Shop\PayConfig;
use App\Models\System\Attachment;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Helper
{

    /**
     * 检查用户是否可以购买或着运维
     * status 0 正常，1购买中，2运维中，3已满三个
     */
    public static function codeStatus($deviceName, $qrcode, $status = 1, $openid = '')
    {
        $sl_maintain_status = Cache::get('sl_maintain_status' . $deviceName);

        if ($sl_maintain_status > 1) {
            $response['status'] = 2;
            $response['message'] = "运维中，请稍后购买";
            $response['code'] = ReturnCode::SUCCESS;
            return $response;
        }

        $sl_code_status_list = Cache::get('sl_code_status' . $deviceName);
        if ($sl_code_status_list) {
            foreach ($sl_code_status_list as $k => $v) {
                if ($v['endtime'] < time()) {
                    unset($sl_code_status_list[$k]);
                    Cache::put('sl_code_status' . $deviceName, $sl_code_status_list, 2);//缓存
                } else {
                    if ($v['qrcode'] == $qrcode) {
                        if ($status == 0) {
                            unset($sl_code_status_list[$k]);
                            Cache::put('sl_code_status' . $deviceName, $sl_code_status_list, 2);//缓存
                        } else {
                            if ($v['openid'] == $openid) {
                                $sl_code_status_list[$k]['endtime'] = time() + 120;
                                Cache::put('sl_code_status' . $deviceName, $sl_code_status_list, 2);//缓存
                                return "";
                            } else {
                                $times = $v['endtime'] - time();
                                $times < 1 ? $times = 0 : "";
                                $response['status'] = 1;
                                $response['message'] = "请更换蛋仓或着" . $times . '秒后再购买';
                                $response['code'] = ReturnCode::SUCCESS;
                                return $response;
                            }
                        }
                    }
                }
            }

            if (count($sl_code_status_list) >= 5) {
                //同一设备已经有三个了
                $response['status'] = 3;
                $response['message'] = "当前人数过多，请稍后再试！";
                $response['code'] = ReturnCode::SUCCESS;
                return $response;
            } else {
                if ($status == 1) {
                    $sl_code_status_new['device_name'] = $deviceName;
                    $sl_code_status_new['qrcode'] = $qrcode;
                    $sl_code_status_new['status'] = 1;
                    $sl_code_status_new['endtime'] = time() + 120;
                    $sl_code_status_new['openid'] = $openid;
                    array_push($sl_code_status_list, $sl_code_status_new);
                    Cache::put('sl_code_status' . $deviceName, $sl_code_status_list, 2);//缓存
                }

            }
        } else {
            //正常可购买
            $sl_code_status_list[0]['device_name'] = $deviceName;
            $sl_code_status_list[0]['qrcode'] = $qrcode;
            $sl_code_status_list[0]['status'] = 1;
            $sl_code_status_list[0]['endtime'] = time() + 120;
            $sl_code_status_list[0]['openid'] = $openid;
            Cache::put('sl_code_status' . $deviceName, $sl_code_status_list, 2);//缓存
        }

    }

    //同步支付宝订单
    public static function syncAliMn($order_code)
    {
        $orderDetail = Order::where('order_code', $order_code)->first();
        if ($orderDetail) {
            if ($orderDetail->pay_status != '1') {
                //未支付
                $payConfig = PayConfig::where('shop_id', $orderDetail->shop_id)->where('pay_type_id', 2)->where('status', 1)->first();

                $aop = new \AopClient ();
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = env('mini_appid');
                $aop->rsaPrivateKey = env('mini_privateKey');
                $aop->alipayrsaPublicKey = env('mini_alipubliKey');
                $aop->apiVersion = '1.0';
                $aop->signType = $payConfig->encrypt_type;
                $aop->format = 'json';
                $request = new \AlipayTradeQueryRequest ();
                $bizContent = [
                    'trade_no' => $orderDetail->order_code
                ];
                $request->setBizContent(json_encode($bizContent));
                $result = $aop->execute($request);
                Log::info('同步订单');
                Log::info(json_encode($result));

                if ($result && $result->alipay_trade_query_response && $result->alipay_trade_query_response->code && $result->alipay_trade_query_response->trade_status) {
                    $resultCode = $result->alipay_trade_query_response->code;
                    $trade_status = $result->alipay_trade_query_response->trade_status;
                    if (!empty($resultCode) && $resultCode == 10000 && $trade_status == "TRADE_SUCCESS") {
                        //回调成功，处理订单状态
                        $orderDetail->error_id = ",3,";
                        $orderDetail->pay_status = 1;
                        $orderDetail->pay_at = Helper::datetime();
                        $orderDetail->save();
                    } else {
                        //失败，提示用户
                        $orderDetail->deleted_at = Helper::datetime();
                        $orderDetail->save();
                    }
                } else {
                    $orderDetail->deleted_at = Helper::datetime();
                    $orderDetail->save();
                }

            } else {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '该数据已同步成功'));
            }
        } else {
            return response(ReturnCode::error(ReturnCode::NOT_FOUND, '查不到该数据'));
        }
    }

    //分解bit数
    public static function dbit($num, $ds = 2)
    {
        $res = array();
        while ($num > 0) {
            $re = pow(2, floor(log($num, 2)));
            $num -= $re;
            $number = 1;
            while (true) {
                if ($re / 2 == 1) {
                    break;
                } else {
                    $number++;
                    $re = $re / 2;
                }
            }
            $res[] = $number;
        }
        return $res;
    }

    public static function formatPrice($price)
    {
        return money_format('%.2n', $price); //todo 详细格式化功能待完善
    }

    //返回日期时间
    public static function datetime($time = '')
    {
        if (!$time) {
            $time = time();
        }
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * 验证是否是合法的手机号码
     */
    public static function isValidMobile($mobile)
    {
        return preg_match('/^(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9])\d{8}$/', $mobile);
    }

    /**
     * 验证是否是合法中文
     */
    public static function isValidChinese($word, $length = 16)
    {
        $pattern = "/(^[\x{4e00}-\x{9fa5}]+)/u";

        preg_match($pattern, $word, $match);

        if (!$match) {
            return false;
        }

        if (mb_strlen($match[1]) > $length) {
            return false;
        }

        return $match[1];
    }

    /**
     * 验证是否是合法的身份证号 简单验证
     */
    public static function isValidIdCardNo($idcard)
    {
        $length = strlen($idcard);

        /** 15位老身份证 */
        if ($length == 15) {
            if (checkdate(substr($idcard, 8, 2), substr($idcard, 10, 2), '19' . substr($idcard, 6, 2))) {
                return true;
            }
        }

        /** 18位二代身份证号 */
        if ($length == 18) {
            if (!checkdate(substr($idcard, 10, 2), substr($idcard, 12, 2), substr($idcard, 6, 4))) {
                return false;
            }

            $idcard = str_split($idcard);
            if (strtolower($idcard[17]) == 'x') {
                $idcard[17] = '10';
            }

            /** 加权求和 */
            $sum = 0;
            /** 加权因子 */
            $wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1];
            for ($i = 0; $i < 17; $i++) {
                $sum += $wi[$i] * $idcard[$i];
            }

            /** 得到验证码所位置 */
            $position = $sum % 11;

            /** 身份证验证位值 10代表X */
            $code = [1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2];
            if ($idcard[17] == $code[$position]) {
                return true;
            }
        }

        return false;
    }

    /**
     * 验证是否是合法的银行卡 不包含信用卡
     */
    public static function isValidBankCard($card)
    {
        if (!is_numeric($card)) {
            return false;
        }

        if (strlen($card) < 16 || strlen($card) > 19) {
            return false;
        }

        $cardHeader = [10, 18, 30, 35, 37, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 58, 60, 62, 65, 68, 69, 84, 87, 88, 94, 95, 98, 99];
        if (!in_array(substr($card, 0, 2), $cardHeader)) {
            return false;
        }

        $numShouldCheck = str_split(substr($card, 0, -1));
        krsort($numShouldCheck);

        $odd = $odd['gt9'] = $odd['gt9']['tens'] = $odd['gt9']['unit'] = $odd['lt9'] = $even = [];
        array_walk($numShouldCheck, function ($item, $key) use (&$odd, &$even) {
            if (($key & 1)) {
                $t = $item * 2;
                if ($t > 9) {
                    $odd['gt9']['unit'][] = intval($t % 10);
                    $odd['gt9']['tens'][] = intval($t / 10);
                } else {
                    $odd['lt9'][] = $t;
                }
            } else {
                $even[] = $item;
            }
        });

        $total = array_sum($even);
        array_walk_recursive($odd, function ($item, $key) use (&$total) {
            $total += $item;
        });

        $luhm = 10 - ($total % 10 == 0 ? 10 : $total % 10);

        $lastNumOfCard = substr($card, -1, 1);
        if ($luhm != $lastNumOfCard) {
            return false;
        }

        return true;
    }

    /**
     * 上传base64图片文件出错
     *
     * @DateTime 2016-05-25
     *
     * @param string $base64Img
     *
     * @return   array
     */
    public static function uploadBase64Img($base64Img = '')
    {
        $data = [
            'id' => 0,
            'fileUrl' => '',
        ];

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Img, $result)) {
            $filetype = $result[2];
            $filePath = storage_path('app/temp/') . md5($base64Img) . ".{$filetype}";

            if (!is_dir(storage_path('app/temp/'))) {
                mkdir(storage_path('app/temp/'), 0777, true);
            }

            file_put_contents($filePath, base64_decode(str_replace($result[1], '', $base64Img)));

            $upload = Attachment::uploadToOss($filePath, $filetype);

            if (!is_array($upload) || !$upload['success']) {
                throw new Exception("Error Processing Base64 Image.", ReturnCode::SYSTEM_FAIL);
            }

            $data = $upload['data'];
        } else {
            throw new Exception("图片解析失败.", ReturnCode::SYSTEM_FAIL);
        }

        return $data;
    }

    public static function curlGet($url)
    {
        if (empty($url)) {
            return '';
        }
        $url_ch = curl_init();
        curl_setopt($url_ch, CURLOPT_URL, $url);
        //curl_setopt($url_ch, CURLOPT_USERAGENT, kr_randUseragent());
        curl_setopt($url_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($url_ch, CURLOPT_FOLLOWLOCATION, 1); //是否抓取跳转后的页面

        curl_setopt($url_ch, CURLOPT_REFERER, $url);

        curl_setopt($url_ch, CURLOPT_TIMEOUT, 10);
        $url_output = trim(curl_exec($url_ch));
        curl_close($url_ch);
        if ($url_output) {
            return $url_output;
        }
        return '';
    }

    public static function randNumber($len)
    {
        $number = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $result = '';
        for ($i = 0; $i < $len; $i++) {
            $result .= array_rand($number, 1);
        }
        return $result;
    }

    public static function randLetter($len)
    {
        $letter = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        $result = '';
        for ($i = 0; $i < $len; $i++) {
            $result .= $letter[array_rand($letter, 1)];
        }
        return $result;
    }

    /**
     * 过滤script脚本
     * todo 可优化为支持无限层多维数组
     *
     * @param $input
     *
     * @return mixed
     */
    public static function cleanInput($input)
    {
        $preg = "/<script[\s\S]*?<\/script>/i";
        if (is_array($input)) {
            foreach ($input as &$item) {
                if (is_array($item)) {
                    foreach ($item as &$item2) {
                        if (!is_array($item2)) {
                            $item2 = preg_replace($preg, "", $item2);
                        }
                    }
                    continue;
                }
                $item = preg_replace($preg, "", $item);
            }
            return $input;
        }
        return preg_replace($preg, "", $input);
    }

    public static function toXml($data, $firstElement)
    {
        $xml = '<' . $firstElement . '>';
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $xml = $xml . self::toXml($val, $key);
            } else {
                $xml = $xml . '<' . $key . '>' . $val . '</' . $key . '>';
            }
        }
        $xml = $xml . '</' . $firstElement . '>';
        return $xml;

    }

    //判断时间戳是否超时
    public static function checkTimestamp($timestamp)
    {
        $timeDiff = self::timeDiff($timestamp, time());
        if (abs($timeDiff) <= 5) {
            return true;
        }
        return false;
    }

    /**
     * 计算两个时间戳之差
     * @param $begin_time
     * @param $end_time
     * @return array
     */
    public static function timeDiff($begin_time, $end_time)
    {
        if ($begin_time < $end_time) {
            $startTime = $begin_time;
            $endTime = $end_time;
        } else {
            $startTime = $end_time;
            $endTime = $begin_time;
        }
        $timeDiff = $endTime - $startTime;
        $days = intval($timeDiff / 86400);
        $remain = $timeDiff % 86400;
        $hours = intval($remain / 3600);
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        $secs = $remain % 60;
        $res = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
//        Log::info($res);
        return $mins;
    }
}
