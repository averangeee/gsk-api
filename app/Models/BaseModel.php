<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 17:40
 */

namespace App\Models;


use App\Models\System\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model
{
    use SoftDeletes;

    protected $guarded=[];

    protected $hidden=['created_code','updated_code','deleted_code','deleted_at'];

    //创建人
    public function creator()
    {
        return $this->belongsTo(Employee::Class,'created_code','employee_code');
    }
    //修改人
    public function modifier()
    {
        return $this->belongsTo(Employee::Class,'updated_code','employee_code');
    }
    //删除人
    public function deleteMan()
    {
        return $this->belongsTo(Employee::Class,'deleted_code','employee_code');
    }
}