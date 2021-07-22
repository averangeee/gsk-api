<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/12
 * Time: 19:09
 */

namespace App\Models\Mh;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BModel extends Model
{

    protected $guarded = [];
    use SoftDeletes;
}
