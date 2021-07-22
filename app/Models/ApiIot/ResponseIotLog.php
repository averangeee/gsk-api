<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 18:31
 */

namespace App\Models\ApiIot;


use App\Models\BaseModel;

class ResponseIotLog extends BaseModel
{
    protected $table='response_iot_log';

    protected $hidden=[];

    protected $guarded=[];

    protected $casts=[
        'params'=>'json',
        'res_detail'=>'json'
    ];
}