<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/12
 * Time: 19:09
 */

namespace App\Models\Gashapon;


use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $connection='gashapon';

    protected $guarded=[];
}