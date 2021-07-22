<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 18:31
 */

namespace App\Models\System;


use App\Models\BaseModel;

class ResponseLog extends BaseModel
{
    protected $table='response_log';

    //protected $hidden=[];

    protected $casts=[
        'res_detail'=>'json',
        'params'=>'json'
    ];
}