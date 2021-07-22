<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/10
 * Time: 10:19
 */

namespace App\Models\ApiIot;

use App\Libs\ApiIot\ApiIotUtil;
use App\Models\BaseModel;
use App\Models\Gashapon\Store;
use App\Models\Shop\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Device extends BaseModel
{
    protected $table='device';

    protected $guarded=[];

//    protected $appends=['status_name'];

    public static $IotExist='iot.device.AlreadyExistedDeviceName';

    public static $status=[
        'UNACTIVE'=>1,
        'ONLINE'=>2,
        'OFFLINE'=>3,
        'DISABLE'=>4
    ];

    public static $statusName=[
        1=>'未激活',
        2=>'在线',
        3=>'离线',
        4=>'禁用'
    ];

//    public function getStatusNameAttribute()
//    {
//        return array_get(self::$statusName,$this->status,'未知');
//    }

    //同步信息
    public static function synDeviceInfo($device,$iot_id)
    {
        try{
            if($device){
                $resultDetail=ApiIotUtil::runIotUtilInfo('QueryDeviceDetail',['IotId' =>$iot_id]);

                $device->line_time=empty($resultDetail['Data']['GmtOnline'])?NULL:$resultDetail['Data']['GmtOnline'];
                $device->ip_address=isset($resultDetail['Data']['IpAddress'])?$resultDetail['Data']['IpAddress']:Null;
                $device->status=isset(self::$status[$resultDetail['Data']['Status']])?self::$status[$resultDetail['Data']['Status']]:1;
                $device->syn_time=date('Y-m-d H:i:s');
                $device->save();
            }
        }
        catch (\Exception $exception){
            Log::error($exception);
        }
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class,'shop_id','id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_code','store_code');
    }

    public function eggList()
    {
        return $this->hasMany(DeviceEgg::Class,'iot_id','iot_id');
    }

    public function bindStore()
    {
        return $this->hasMany(DeviceStore::Class,'iot_id','iot_id');
    }

    public function bindFirmware()
    {
        return $this->hasMany(DeviceFirmware::Class,'iot_id','iot_id');
    }

    public function message()
    {
        return $this->hasMany(DeviceMessage::class,'device_name','device_name');
    }
}