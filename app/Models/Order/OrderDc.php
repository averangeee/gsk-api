<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/12
 * Time: 11:12
 */

namespace App\Models\Order;

use App\Libs\HashKey;
use App\Libs\PayHelper;
use App\Models\ApiIot\DeviceEgg;
use App\Models\BaseModel;
use App\Models\Gashapon\Sku;
use App\Models\Gashapon\Store;
use App\Models\Shop\PayConfig;
use App\Models\Shop\SkuImg;
use App\Models\Token;
use EasyWeChat\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OrderDc extends Model
{
    protected $table = 'order_dc';

    protected $guarded = [];




}
