<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/12
 * Time: 11:14
 */

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\System\EmployeeWeixin;

class BlackUser extends BaseModel
{
    protected $table='black_user';

    protected $guarded=[];

    public function userDetail()
    {
        return $this->belongsTo(EmployeeWeixin::class,'openid','openid');
    }

}
