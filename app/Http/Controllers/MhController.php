<?php

namespace App\Http\Controllers;


use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Libs\Sms;
use App\Models\ApiIot\Device;
use App\Models\Mh\DDeviceSim;
use App\Models\Mh\DeviceMessage;
use App\Models\Mh\DOrder;
use App\Models\Mh\DOrderProduct;
use App\Models\Mh\DOrderReturn;
use App\Models\Mh\DProduct;
use App\Models\Mh\DwDevice;
use App\Models\Order\Order;
use App\Models\Order\OrderDetail;
use App\Models\System\EmployeeWeixin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MhController extends Controller
{
    //定时同步订单数据过来,计划5分钟同步一次
    function sync_order()
    {
        $n_detail = Order::where('type',1)->orderByDesc('id')->first();
        $create_time = $n_detail->created_at;

        $list = DOrder::where('order_status', '1')
            ->whereRaw("create_time > '$create_time' ")
            ->orderBy('create_time')
            /*->take(500)*/
            ->get();

        foreach ($list as $k => $v) {
            $order_id = $v->id;
            $order_product = DOrderProduct::where("order_id", $v->id)->first();//订单详情
            if ($order_product) {
                $product_detail = DProduct::where('id', $order_product->product_id)->first();//产品详情

                $order_detail = Order::where('order_sn', $v->order_code)->first();
                if (!$order_detail) {//插入订单表
                    $device_detail = DwDevice::where('id', $v->device_id)->first();
                    $sim_detail = DDeviceSim::where('device_id', $v->device_id)->first();
                    $refund_detail = DOrderReturn::whereRaw("order_id = '$order_id'")->first();
                    $pay_status = 1;
                    if ($refund_detail) {
                        $pay_status = 3;
                    }
                    $store_code = '';
                    if ($sim_detail) {
                        $store_code = $sim_detail->sim_card;
                    }
                    $data_order['iot_id'] = $device_detail->device_serial;
                    $data_order['store_code'] = $store_code;
                    $data_order['order_sn'] = $v->order_code;
                    $data_order['sku_code'] = $product_detail->commodity_code;
                    $data_order['price'] = $v->order_cost;
                    $data_order['pay_count'] = 1;
                    $data_order['pay_amount'] = $v->order_cost;
                    $data_order['order_amount'] = $v->order_cost;
                    $data_order['pre1'] = $v->id;
                    $data_order['created_at'] = $v->create_time;
                    $data_order['updated_at'] = $v->update_time;
                    $data_order['pay_at'] = $v->update_time;
                    $data_order['pay_status'] = $pay_status;
                    $data_order['pay_type'] = $v->pay_type;
                    $order_id = Order::insertGetId($data_order);

                    $data_detail['order_id'] = $order_id;
                    $data_detail['sku_code'] = $product_detail->barcode;
                    $data_detail['price'] = $v->order_cost;
                    $data_detail['created_at'] = $v->create_time;
                    $data_detail['updated_at'] = $v->update_time;
                    $detail_id = OrderDetail::insertGetId($data_detail);
                }
                Log::info($order_id . '----------------自贩机自动同步老平台数据----------------' );
            }

        }

        //echo 'sync success';
    }

    //定时同步设备,计划5分钟同步一次
    function sync_device()
    {
        $device_list = DB::connection("mh")->select("SELECT * FROM tb_dw_device WHERE device_status=1 and device_serial!=''");

        foreach ($device_list as $k => $v) {
            $sim_detail = DDeviceSim::where('device_id', $v->id)->first();
            if ($sim_detail) {
                $detail = Device::where('iot_id', $v->device_serial)->first();
                $device_data['store_code'] = $sim_detail->sim_card;
                $device_data['iot_id'] = $v->device_serial;
                $device_data['device_code'] = $v->device_serial;
                $device_data['device_name'] = $v->device_name;
                $device_data['created_at'] = $v->create_time;
                $device_data['updated_at'] = $v->update_time;
                $device_data['line_at'] = $v->update_time;
                if ($detail) {
                    $type=$detail->type;
                    $device_data['type'] =$detail->type;
                    if($type==1){
                        $detail->update($device_data);
                    }else{
                    }
                } else {
                    Device::insertGetId($device_data);
                }
            }
        }
    }

    //定时同步退款订单
    //定时同步退款订单
    function sync_order_refund()
    {
        $create_time = Helper::datetime(time() - (86400 * 7));
        $list = DOrderReturn::where('create_time', '>', $create_time)->where('return_status', 1)->get();
        foreach ($list as $k => $v) {
            $detail = DOrder::where('id', $v->order_id)->first();

            if ($detail) {
                $order_detail = Order::where('order_sn', $detail->order_code)->first();
                if ($order_detail) {
                    $order_detail->pay_status = 3;
                    $order_detail->refund_at = $detail->create_time;
                    $order_detail->save();
                }
            }

        }

        //$refund_detail = DOrderReturn::whereRaw("order_id = '$order_id'")->first();
    }
}
