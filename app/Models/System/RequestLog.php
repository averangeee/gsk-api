<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 18:30
 */

namespace App\Models\System;


use App\Models\BaseModel;

class RequestLog extends BaseModel
{
    protected $table='request_log';

    protected $casts=[
        'params'=>'json'
    ];

    public function res()
    {
        return $this->belongsTo(ResponseLog::class,'id','request_log_id');
    }
}