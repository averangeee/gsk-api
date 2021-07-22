<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/10
 * Time: 10:24
 */

namespace App\Libs\ApiIot;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use App\Libs\HashKey;
use App\Models\ApiIot\DeviceMessage;
use Illuminate\Support\Facades\Log;

class ApiIotUtil
{
    const TOPIC_SEND_NAME='/user/get';

    public static function loadIot()
    {
        AlibabaCloud::accessKeyClient(Config('app.accessKeyId'), Config('app.accessKeySecret'))
            ->regionId(Config('app.regionId'))->asDefaultClient();
    }

    public static function runIotUtil($action,$query=[])
    {
        try {
            self::loadIot();
            $options=[
                'RegionId' => Config('app.regionId'),
                'ProductKey' => Config('app.productKey')
            ];
            if($query){
                $options=array_merge($options,$query);
            }

            $result = AlibabaCloud::rpc()
                ->product('Iot')
                // ->scheme('https') // https | http
                ->version('2018-01-20')
                ->action($action)
                ->method('POST')
                ->options([
                    'query' => $options
                ])
                ->request()
                ->toArray();

            if($result['Success']){
                return $result['Data'];
            }else{
                return $result;
            }
        } catch (ClientException $e) {
            throw new \Exception($e);
        } catch (ServerException $e) {
            throw new \Exception($e);
        }catch (\Exception $exception){
            throw new \Exception($exception);
        }
    }

    public static function runIotUtilInfo($action,$query=[])
    {
        try {
            self::loadIot();
            $options=[
                'RegionId' => Config('app.regionId'),
                'ProductKey' => Config('app.productKey')
            ];
            if($query){
                $options=array_merge($options,$query);
            }

            $result = AlibabaCloud::rpc()
                ->product('Iot')
                // ->scheme('https') // https | http
                ->version('2018-01-20')
                ->action($action)
                ->method('POST')
                ->options([
                    'query' => $options
                ])
                ->request();

            $result=json_decode($result,true);

            $check=['MessageId'];
            foreach ($result as $key=>$val){
                //科学计数法转成字符
                if(in_array($key,$check)){
                    $result[$key]=number_format($val,0,'','');
                }
            }

//            $result=   $result->toArray();
            return $result;
        } catch (ClientException $e) {
            throw new \Exception($e);
        } catch (ServerException $e) {
            throw new \Exception($e);
        }catch (\Exception $exception){
            throw new \Exception($exception);
        }
    }

    public static function checkTopic($productKey,$topic)
    {
        try{
            $topicFullName='/'.$productKey.'/${deviceName}'.$topic;
            $result=self::runIotUtilInfo('QueryProductTopic',[]);
            $res=false;
            if($result['Success']){
                $res= $result['Data']['ProductTopicInfo'];

                foreach ($res as $item){
                    if($item['TopicShortName']==$topicFullName){
                        $res= true;
                    }
                }
                return $res;
            }else{
                return $res;
            }
        }
        catch (\Exception $exception){
            throw new \Exception($exception);
        }
    }

    public static function sendIotPub($device,$topic,$msg,$qos=1)
    {
        try{
            $productKey=Config('app.productKey');
            $msg_id=HashKey::kcode(3,0,4);
            $msg['msg_id']=$msg_id;

            $msgJson=json_encode($msg);
            $msgbase64=base64_encode($msgJson);
            $topicBase='/'.$productKey.'/'.$device.$topic;

            $options=[
                'TopicFullName' => $topicBase,
                'MessageContent' => $msgbase64,
                'Qos' => $qos
            ];

            //checktopic是否存在，不存在创建
            $topicShort=str_replace('/user/','',$topic);
            $check=self::checkTopic($productKey,$topic);

            if(!$check){
                $topicOptions=[
                    'TopicShortName'=>$topicShort,
                    'Operation'=>'SUB',
                    'Desc'=>'云端创建'
                ];
                $rr=self::runIotUtilInfo('CreateProductTopic',$topicOptions);
            }
            $result=self::runIotUtilInfo('Pub',$options);
            try {
                $bmsg = json_decode($msgJson);
                //记录消息，日志
                $message = [
                    'msg_id' => $msg_id,
                    'product_key' => $productKey,
                    'device_name' => $device,
                    'topic' => $topicBase,
                    'topic_short' => $topic,
                    'type' => isset($bmsg->type) ? $bmsg->type : 9,
                    'msg' => isset($bmsg->msg) ? $bmsg->msg : null,
                    'con' => isset($bmsg->con) ? $bmsg->con : null,
                    'content' => $bmsg,
                    'content_base64' => $msgbase64,
                    'qos' => $qos,
                    'result' => $result['Success'],
                    'message_id' => isset($result['MessageId']) ? $result['MessageId'] : null,
                    'result_content' => $result
                ];

                DeviceMessage::create($message);
            }catch (\Exception $e){}
//            Log::info($result);
            return $result;
        }
        catch (\Exception $exception){
            throw new \Exception($exception);
        }
    }

    /*
     * @param $device_name
     * @param $sn  传值 上货：supply_id  购买：order_id  广告：adverts_id,其他：other
     * @param int $type
     * @param string $con
     * @param string $msg
     * @throws \Exception
     */
    public static function sendMsg($device_name,$sn,$type=1,$con='open',$msg='上货开锁',$data=null)
    {
        try{
            $send=[
                'type'=>$type,
                'order_id'=>$sn,
                'data'=>$data,
                'msg'=>$msg,
                'con'=>$con,
                'timestamp'=>time()
            ];
//            $send=json_encode($send);
            return self::sendIotPub($device_name,self::TOPIC_SEND_NAME,$send);
        }
        catch (\Exception $exception){
            throw new \Exception($exception);
        }
    }

    //supply-open
    public static function supplyOpenLock($device_name,$supply_id,$data=null)
    {
        return self::sendMsg($device_name,$supply_id,ApiIotReturnCode::RES_SUPPLY,'open','上货开锁',$data);
    }
    //supply-done
    public static function supplyDone($device_name,$supply_id,$data=null)
    {
        return self::sendMsg($device_name,$supply_id,ApiIotReturnCode::RES_SUPPLY,'done','上货完成',$data);
    }
    //supply-enter
    public static function supplyEnter($device_name,$supply_id=null,$data=null)
    {
        return self::sendMsg($device_name,$supply_id,ApiIotReturnCode::RES_SUPPLY,'enter','进入运维',$data);
    }
    //supply-cancel
    public static function supplyCancel($device_name,$supply_id=null,$data=null)
    {
        return self::sendMsg($device_name,$supply_id,ApiIotReturnCode::RES_SUPPLY,'cancel','退出运维',$data);
    }

    //buy-cancel(pay_cancel)
    public static function payCancel($device_name,$order_id,$data=null)
    {
        return self::sendMsg($device_name,$order_id,ApiIotReturnCode::RES_BUY,'pay_cancel','订单取消',$data);
    }
    //buy-wait(pay_wait)
    public static function payWait($device_name,$order_id,$data=null)
    {
        return self::sendMsg($device_name,$order_id,ApiIotReturnCode::RES_BUY,'pay_wait','订单支付中...',$data);
    }
    //buy-open（pay_success）
    public static function paySuccess($device_name,$order_id,$data=null)
    {
        return self::sendMsg($device_name,$order_id,ApiIotReturnCode::RES_BUY,'pay_success','订单支付成功',$data);
    }
    //buy-error(pay_error)
    public static function payError($device_name,$order_id,$data=null)
    {
        return self::sendMsg($device_name,$order_id,ApiIotReturnCode::RES_BUY,'pay_error','订单支付失败',$data);
    }

    //update_adverts
    public static function updateAdverts($device_name,$adverts_id){
        return self::sendMsg($device_name,$adverts_id,ApiIotReturnCode::RES_IOT,'update_adverts','更新广告');
    }

    //更新价格 update_sales_price
    public static function updatePrice($device_name,$id,$data=null){
        return self::sendMsg($device_name,$id,ApiIotReturnCode::RES_PRICE,'update_sales_price','更新产品售价',$data);
    }

    //远程更新中控
    public static function updateControl($device_name,$id,$data=null){
        return self::sendMsg($device_name,$id,ApiIotReturnCode::RES_IOT,'download','更新主控程序',$data);
    }

    //重启中控
    public static function reStart($device_name,$id,$data=null){
        return self::sendMsg($device_name,$id,ApiIotReturnCode::RES_IOT,'re_start','重启中控',$data);
    }
    //进入运维
    public static function showNumber($device_name,$id,$data=null){
        return self::sendMsg($device_name,$id,ApiIotReturnCode::RES_IOT,'show_number','显示蛋仓数字',$data);
    }

    //退出运维
    public static function endShowNumber($device_name,$id,$data=null){
        return self::sendMsg($device_name,$id,ApiIotReturnCode::RES_IOT,'end_show_number','结束显示数字',$data);
    }

}
