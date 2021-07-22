<?php

namespace App\Console\Commands;

use App\Libs\ApiIot\ApiIotUtil;
use App\Libs\Helper;
use App\Models\ApiIot\Device;
use App\Models\Order\Egg;
use App\Models\Order\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SynDeviceCommand extends Command
{
    protected $name = 'kingdee';//命令名
    protected $description = '按日统计蛋仓各种情况';//命令描述

    public function handle()
    {
       Log::info('测试执行定时任务');

    }
}
