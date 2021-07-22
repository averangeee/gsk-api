<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/15
 * Time: 15:06
 */

namespace App\Models\Shop;


use App\Models\BaseModel;
use App\Models\System\Attachment;

class PayConfig extends BaseModel
{
    protected $table='pay_config';

    protected $guarded=[];

    public static $payType=[
        'wx'=>1,
        'al'=>2
    ];

    public function payType()
    {
        return $this->belongsTo(PayType::class,'pay_type_id','id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function cacert()
    {
        return $this->belongsTo(Attachment::class,'cacert_id');
    }

    public function key()
    {
        return $this->belongsTo(Attachment::class,'key_id');
    }
}