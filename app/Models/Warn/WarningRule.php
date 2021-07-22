<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/5
 * Time: 17:35
 */

namespace App\Models\Warn;


use App\Models\BaseModel;

class WarningRule extends BaseModel
{
    protected $table='warning_rule';

    protected $casts=[
        'status'=>'boolean',
        'params'=>'json',
        'is_sys'=>'boolean'
    ];
}