<?php

namespace App\Models\GSK;

use Illuminate\Database\Eloquent\Model;

class Ck extends Model
{
    //
    protected $table = 'gsk_ck';

    protected $guarded=[];

    public function city()
    {
        return $this->hasOne(City::Class,'id','city');
    }

}
