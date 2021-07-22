<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/24
 * Time: 11:34
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ApiIot\ApiIotUtil;
use App\Libs\ReturnCode;
use App\Models\ApiIot\Device;
use App\Models\ApiIot\DeviceEgg;
use App\Models\ApiIot\DeviceEggBind;
use App\Models\Gashapon\Version;
use App\Models\Order\GoodsSupply;
use App\Models\Order\GoodsSupplyStatus;
use App\Models\Order\Order;
use App\Models\Order\Qx;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Libs\Helper;

class DeviceEggController extends Controller
{
    //绑定商品
    public function sku_bind(Request $request)
    {
        $id = $request->input('id');
        $price = $request->input('price');
        $sku_code = $request->input('sku_code');
        $stock = $request->input('stock');
        $sku_name = $request->input('sku_name');
        $size = $request->input('size');
        $caizhi = $request->input('caizhi');

        $device_egg = DeviceEgg::find($id);
        $device_egg->sku_code = $sku_code;
        $device_egg->price = $price;
        $device_egg->stock = $stock;
        $device_egg->sku_name = $sku_name;
        $device_egg->size = $size;
        $device_egg->caizhi = $caizhi;
        $result = $device_egg->save();

        if ($result > 0) {
            return response(ReturnCode::success([], 'success'));
        } else {
            return response(ReturnCode::error(102, '绑定失败'));
        }
    }

    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $date = $request->input('created_at', null);//创建时间
            $updateDate = $request->input('updated_at', null);//更新时间
            $store_code = $request->input('store_code', null);
            $iot_id = $request->input('iot_id', null);
            $qrcode = $request->input('qrcode', null);
            $sku_code = $request->input('sku_code', null);
            $price_where = $request->input('price_where', '=');//售价条件
            $price = $request->input('price', null);
            $stock_where = $request->input('stock_where', '=');//库存条件
            $stock = $request->input('stock', null);
            $jd = $request->input('jd_where', null);
            $status = $request->input('status', null);
            $bind = $request->input('bind', null);


            $where = function ($query) use ($date, $updateDate, $store_code, $iot_id, $qrcode, $sku_code, $price_where, $price, $stock_where, $stock, $status, $jd, $bind) {
                if (!empty($date)) {
                    $query->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($date[0])), date('Y-m-d 23:59:59', strtotime($date[1]))]);
                }
                if (!empty($updateDate)) {
                    $query->whereBetween('updated_at', [date('Y-m-d 0:00:00', strtotime($updateDate[0])), date('Y-m-d 23:59:59', strtotime($updateDate[1]))]);
                }
                if (!empty($store_code)) {
                    $query->where('store_code', $store_code);
                }
                if (!empty($iot_id)) {
                    $query->where('iot_id', $iot_id);
                }
                if (!empty($qrcode)) {
                    $query->where('new_qrcode', 'like', '%' . $qrcode . '%')->orwhere('egg_code', 'like', '%' . $qrcode . '%');
                }
                if (!empty($sku_code)) {
                    $query->where('sku_code', $sku_code);
                }
                if (strlen($price) > 0 && is_numeric($price)) {
                    $query->where('price', $price_where, $price);
                }

                if (strlen($stock) > 0 && is_numeric($stock)) {
                    $query->where('stock', $stock_where, $stock);
                }
                if (!empty($status)) {
                    $query->where('status', $status);
                }
                if (!empty($jd)) {
                    $query->whereRaw('price' . $jd . 'net_price');
                }
                if (!empty($bind)) {
                    if (intval($bind) == 1) {
                        $query->whereNotNull('sku_code');
                    }
                    if (intval($bind) == 2) {
                        $query->whereNull('sku_code');
                    }
                }
            };

            $case = "avg(case when m_type='sku' then version_id else null end) as sku_v,
                avg(case when m_type='store' then version_id else null end) as store_v";

            $version = Version::where('is_active', 1)->select(DB::raw($case))->first();

//            DB::enableQueryLog();

            $user_id = Token::$uid;
            $qx = Qx::where('user_id', $user_id)->first();
            $cache_stroe_code = json_decode($qx->cache_stroe_code);
            $cache_power_type = $qx->cache_power_type;

            if ($cache_power_type == 1) {
                $data = DeviceEgg::where($where)
                    ->select(['id', 'iot_id', 'shop_id', 'store_code', 'egg_code', 'qrcode', 'new_qrcode', 'sku_code', 'net_price', 'price',
                        'stock', 'status', 'patch_date', 'sales_date', 'line_at', 'created_at', 'updated_at', 'updated_code', 'sort', 'error_status'])
                    ->with(['sku' => function ($query) use ($version) {
                        $col = ['sku_id', 'sku_name'];
                        if ($version) {
                            $query->where('version_id', $version->sku_v)->select($col);
                        } else {
                            $query->select($col);
                        }
                    }, 'store' => function ($q) use ($version) {
                        $col = ['store_code', 'store_name', 'customer', 'region', 'sales_group', 'store_address'];
                        if ($version) {
                            $q->where('version_id', $version->store_v)->select($col);
                        } else {
                            $q->select($col);
                        }
                    }, 'modifier' => function ($qc) {
                        $qc->select(['employee_code', 'employee_name']);
                    }, 'device' => function ($dd) {
                        $dd->select(['iot_id', 'device_name', 'note']);
                    }, 'errorDes' => function ($ed) {
                        $ed->select(['error_id_bit', 'error_des']);
                    }])
                    ->orderBy('iot_id')
                    ->orderBy('status')
                    ->orderBy('sort')
                    ->orderByDesc('created_at')
                    ->paginate($limit)
                    ->toArray();
            } else {
                $data = DeviceEgg::where($where)
                    ->whereIn('store_code', $cache_stroe_code)
                    ->select(['id', 'iot_id', 'shop_id', 'store_code', 'egg_code', 'qrcode', 'new_qrcode', 'sku_code', 'net_price', 'price',
                        'stock', 'status', 'patch_date', 'sales_date', 'line_at', 'created_at', 'updated_at', 'updated_code', 'sort', 'error_status'])
                    ->with(['sku' => function ($query) use ($version) {
                        $col = ['sku_id', 'sku_name'];
                        if ($version) {
                            $query->where('version_id', $version->sku_v)->select($col);
                        } else {
                            $query->select($col);
                        }
                    }, 'store' => function ($q) use ($version) {
                        $col = ['store_code', 'store_name'];
                        if ($version) {
                            $q->where('version_id', $version->store_v)->select($col);
                        } else {
                            $q->select($col);
                        }
                    }, 'modifier' => function ($qc) {
                        $qc->select(['employee_code', 'employee_name']);
                    }, 'device' => function ($dd) {
                        $dd->select(['iot_id', 'device_name', 'note']);
                    }, 'errorDes' => function ($ed) {
                        $ed->select(['error_id_bit', 'error_des']);
                    }])
                    ->orderBy('iot_id')
                    ->orderBy('status')
                    ->orderBy('sort')
                    ->orderByDesc('created_at')
                    ->paginate($limit)
                    ->toArray();
            }

            foreach ($data['data'] as $k => $v) {
                if (count(Helper::dbit($v['error_status'])) > 1) {
                    $data['data'][$k]['error_des']['error_des'] = '复合性错误';
                }

                $goodsSupplyStatus = GoodsSupplyStatus::where('supply_id', 'like', "M" . $v['egg_code'] . "%")->orderByDesc('id')->first();

                if ($goodsSupplyStatus && $goodsSupplyStatus->close_status == '1') {
                    $keyStatus = "1";//正常
                } else {
                    $keyStatus = "2";
                }
                $data['data'][$k]['keyStatus'] = $keyStatus;
                $data['data'][$k]['shopping_number'] = Order::where('egg_code', $v['egg_code'])->where('pay_status', 1)->count();
            }
            //print_r($data);

//            Log::info(DB::getQueryLog());

            $response['data'] = $data['data'];
            $response['total'] = $data['total'];
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //批量更新
    public function batchPrice(Request $request, $type)
    {
        try {
            $sku_code = $request->input('sku_code', null);
            $price = $request->input('sales_price', null);
            $ids = $request->input('ids', null);//需要设置的id列表

            switch ($type) {
                case 1: //更新所有设备

                    break;
                case 2:

                    break;
            }

        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //设置售价，单个记录设置；只允许更新售价，不可换绑
    public function singlePrice(Request $request, $id)
    {
        try {
            $newPrice = $request->input('sales_price', null);
            $deviceEgg = DeviceEgg::find($id);
            if (!$deviceEgg) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            DB::beginTransaction();
            $deviceEgg->price = $newPrice;
            $deviceEgg->updated_code = Token::$ucode;
            $deviceEgg->save();

            DeviceEggBind::where('device_egg_id', $id)->update(['status' => 2]);

            DeviceEggBind::create([
                'shop_id' => $deviceEgg->shop_id,
                'store_code' => $deviceEgg->store_code,
                'iot_id' => $deviceEgg->iot_id,
                'device_egg_id' => $id,
                'egg_code' => $deviceEgg->egg_code,
                'qrcode' => $deviceEgg->qrcode,
                'sku_code' => $deviceEgg->sku_code,
                'net_price' => $deviceEgg->net_price,
                'price' => $newPrice,
                'status' => 1,
                'created_code' => Token::$ucode
            ]);

            DB::commit();

            $device = Device::where('iot_id', $deviceEgg->iot_id)->first(['id', 'device_name', 'iot_id']);
            if ($device) {
                $data = [
                    'egg_code' => $deviceEgg->egg_code,
                    'price' => $newPrice,
                    'stock' => $deviceEgg->stock,
                    'sort' => $deviceEgg->sort
                ];
                $res = ApiIotUtil::updatePrice($device->device_name, $id, $data);

                return response(ReturnCode::success($res, '修改成功'));
            } else {
                DB::rollBack();
                return response(ReturnCode::error(ReturnCode::NOT_FOUND, '设备信息丢失'));
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 后台复位
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resetEgg(Request $request, $id)
    {
        try {
            $deviceEgg = DeviceEgg::find($id);
            if (!$deviceEgg) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $deviceEgg->status = 3;
            $deviceEgg->updated_code = Token::$ucode;
            $deviceEgg->save();

            return response(ReturnCode::success([], '成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //删除
    public function delete(Request $request, $id)
    {
        try {
            $deviceEgg = DeviceEgg::find($id);
            if (!$deviceEgg) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            DB::beginTransaction();
            $deviceEgg->deleted_code = Token::$ucode;
            $deviceEgg->save();

            $deviceEgg->delete();
            DB::commit();
            return response(ReturnCode::success([], '删除成功'));
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 更新价格
     * @param $deviceEgg 二代机
     * @param $price 设置价格
     * @param $send 是否推送 1 推送，2 不推送
     * @return int 返回
     */
    private function updatePrice($deviceEgg, $price, $send)
    {
        try {
            $id = $deviceEgg->id;
            $oldPrice = $deviceEgg->price;
            if (floatval($oldPrice) === floatval($price)) {
                return 0;
            }

            DB::beginTransaction();

            $deviceEgg->price = $price;
            $deviceEgg->updated_code = Token::$ucode;
            $deviceEgg->save();

            DeviceEggBind::where('device_egg_id', $id)->update(['status' => 2]);

            DeviceEggBind::create([
                'shop_id' => $deviceEgg->shop_id,
                'store_code' => $deviceEgg->store_code,
                'iot_id' => $deviceEgg->iot_id,
                'device_egg_id' => $id,
                'egg_code' => $deviceEgg->egg_code,
                'qrcode' => $deviceEgg->qrcode,
                'sku_code' => $deviceEgg->sku_code,
                'net_price' => $deviceEgg->net_price,
                'price' => $price,
                'status' => 1,
                'created_code' => Token::$ucode
            ]);

            DB::commit();

            if ($send == 2) {
                return 1;
            }

            $device = Device::where('iot_id', $deviceEgg->iot_id)->first(['id', 'device_name', 'iot_id']);
            if ($device) {
                $data = [
                    'egg_code' => $deviceEgg->egg_code,
                    'price' => $price,
                    'stock' => $deviceEgg->stock,
                    'sort' => $deviceEgg->sort
                ];
                ApiIotUtil::updatePrice($device->device_name, $id, $data);
                return 1;
            } else {
                DB::rollBack();
                return 0;
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return 0;
        }
    }
}