<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/5
 * Time: 14:33
 */

namespace App\Models\Message;


use App\Models\BaseModel;
use App\Models\System\Employee;
use App\Models\Token;

class MessagesStatus extends BaseModel
{
    protected $table='messages_status';

    public function message()
    {
        return $this->belongsTo(Messages::class,'messages_id','id');
    }

    public function toId()
    {
        return $this->belongsTo(Employee::class,'to_id','id');
    }

    public static function getMsg($type)
    {
        $where=function ($query) use($type){
            switch ($type){
                case 1: //个人
                    $query->where('msg_type',1);
                    break;
                case 2: //2,4 系统，公告
                    $query->whereIn('msg_type',[2,4]);
                    break;
                case 3: //预警
                    $query->where('msg_type',3);
                    break;
            }
        };

        $data=MessagesStatus::where('to_id',Token::$uid)->where($where)->where('status',0)
            ->select(['id','messages_id','msg_level','created_code','created_at'])
            ->with(['message'=>function($q){
                $q->select(['id','iot_id','form_id','msg_title'])->with(['device'=>function($qq){
                    $qq->select(['iot_id','device_name','note']);
                }]);
            },'creator'=>function($q){
                $q->select(['employee_code','employee_name']);
            }])
            ->orderByDesc('msg_level')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $data;
    }
}