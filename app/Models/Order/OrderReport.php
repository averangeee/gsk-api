<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\Gashapon\Store;
use Illuminate\Database\Eloquent\Model;


class OrderReport extends Model
{
    protected $table = 'order_report';


    public function store()
    {
        return $this->belongsTo(Store::class, 'store_code', 'store_code');
    }
}