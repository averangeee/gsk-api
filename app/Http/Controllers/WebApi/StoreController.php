<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/28
 * Time: 10:32
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ImportExcel;
use App\Libs\ReturnCode;
use App\Models\Gashapon\Store;
use App\Models\Shop\Shop;
use App\Models\Shop\ShopStore;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $keyword = $request->input('keyword', null);
            $version = $request->input('version', null);
            $status_dz = $request->input('status_dz', null);
            $status_db = $request->input('status_db', null);

            $where = function ($query) use ($keyword, $status_dz, $status_db) {
                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->orWhere('store_code', 'like', '%' . $keyword . '%')
                            ->orWhere('store_name', 'like', '%' . $keyword . '%');
                    });
                }
                if (!empty($status_dz)) {
                    if ($status_dz == '空') {
                        $query->whereNull('status_dz');
                    } else {
                        $query->where('status_dz', $status_dz);
                    }
                }

                if (!empty($status_db)) {
                    if ($status_db == '空') {
                        $query->whereNull('status_db');
                    } else {
                        $query->where('status_db', $status_db);
                    }
                }
            };

            if (empty($version)) {
                $version = Store::max('version_id');
            }

            $data = Store::where('is_del', 0)
                ->where('version_id', $version)->whereNotNull('region')
                ->where($where)
                ->select(['store_code', 'store_name', 'region', 'city', 'channel', 'sales_group', 'sales_name', 'sales_phone', 'status_db', 'status_dz', 'store_address'])
                ->orderBy('store_code')
                ->paginate($limit)
                ->toArray();

            foreach ($data['data'] as $k => $v) {
                $shopStore = ShopStore::where('store_code', $v['store_code'])->where('status',1)->first();
                if ($shopStore) {
                    $shopDetail = Shop::find($shopStore->shop_id);
                    $data['data'][$k]['shop_name'] = $shopDetail->name;
                } else {
                    $data['data'][$k]['shop_name'] = "未绑定";
                }

            }
            $response['data'] = $data['data'];
            $response['total'] = $data['total'];
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function index_store(Request $request)
    {
        try {
            $keyword = $request->input('keyword', null);
            $version = $request->input('version', null);
            $status_dz = $request->input('status_dz', null);
            $status_db = $request->input('status_db', null);

            $where = function ($query) use ($keyword, $status_dz, $status_db) {
                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->orWhere('store_code', 'like', '%' . $keyword . '%')
                            ->orWhere('store_name', 'like', '%' . $keyword . '%');
                    });
                }
                if (!empty($status_dz)) {
                    if ($status_dz == '空') {
                        $query->whereNull('status_dz');
                    } else {
                        $query->where('status_dz', $status_dz);
                    }
                }

                if (!empty($status_db)) {
                    if ($status_db == '空') {
                        $query->whereNull('status_db');
                    } else {
                        $query->where('status_db', $status_db);
                    }
                }
            };

            if (empty($version)) {
                $version = Store::max('version_id');
            }

            $data = Store::where('is_del', 0)
                ->where('version_id', $version)->whereNotNull('region')
                ->where($where)
                ->select(['store_code', 'store_name', 'region', 'city', 'channel', 'sales_group', 'sales_name', 'sales_phone', 'status_db', 'status_dz', 'store_address'])
                ->orderBy('store_code')
                ->get()
                ->toArray();

            foreach ($data as $k => $v) {
                $shopStore = ShopStore::where('store_code', $v['store_code'])->where('status',1)->first();
                if ($shopStore) {
                    $shopDetail = Shop::find($shopStore->shop_id);
                    $data[$k]['shop_name'] = $shopDetail->name;
                } else {
                    $data[$k]['shop_name'] = "未绑定";
                }

            }
            $response['data'] = $data;
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }


    //绑定商家
    public function bind(Request $request)
    {
        try {
            $shop_id = $request->input('shop_id', null);
            $store_code = $request->input('store_code', null);

            $detail = ShopStore::where('store_code', $store_code)
                ->where('shop_id', $shop_id)->where('status', 1)->first();

            DB::beginTransaction();
            if ($detail) {
                $detail->store_code = $store_code;
                $detail->shop_id = $shop_id;
                $detail->status = 1;
                $detail->created_code = Token::$ucode;
                $detail->save();
            } else {
                ShopStore::where('store_code', $store_code)->where('status', 1)->update(['status' => 2]);
                $data = [
                    'shop_id' => $shop_id,
                    'store_code' => $store_code,
                    'status' => 1,
                    'created_code' => Token::$ucode
                ];

                ShopStore::create($data);
            }


            DB::commit();
            return response(ReturnCode::success([], '绑定成功'));
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //查询绑定历史
    public function detail(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $store_code = $request->input('store_code', null);
            $date = $request->input('created_at', null);//创建时间
            $status = $request->input('status', null);

            if (empty($store_code)) {
                return response(ReturnCode::error(ReturnCode::PARAMS_ERROR));
            }
            $where = function ($query) use ($date, $status) {
                if (!empty($date)) {
                    $query->whereBetween('created_at', [date('Y-m-d 0:00:00', strtotime($date[0])), date('Y-m-d 23:59:59', strtotime($date[1]))]);
                }
                if (strlen($status)) {
                    $query->where('status', $status);
                }
            };
            $data = ShopStore::where('store_code', $store_code)->where($where)
                ->select(['id', 'shop_id', 'store_code', 'status', 'created_code', 'created_at'])
                ->with(['shop' => function ($q) {
                    $q->select(['id', 'name', 'parent_id']);
                }, 'store' => function ($q) {
                    $q->select(['store_code', 'store_name'])->orderBy('version_id');
                }])
                ->orderBy('status')
                ->orderByDesc('created_at')
                ->paginate($limit)
                ->toArray();

            $response['data'] = $data['data'];
            $response['total'] = $data['total'];
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //删除数据
    public function delete(Request $request, $id)
    {
        try {
            $shopStore = ShopStore::find($id);
            if (!$shopStore) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $shopStore->deleted_code = Token::$ucode;
            $shopStore->save();

            $shopStore->delete();

            return response(ReturnCode::success([], '删除成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @des 导入绑定数据
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function import(Request $request)
    {
        try {
            $res = ImportExcel::importExcel($request, ['isCheck' => true, 'size' => 10]);

            if ($res['code'] === 0) {
                $importData = $res['data'];

                DB::beginTransaction();

                $success = 0;
                $errorList = [];
                $check = [];
                foreach ($importData as $key => $item) {
                    $shop_code = $item[0];
                    $store_code = $item[2];
                    $check[] = $item[2];
                    $row = $key + 1;

                    $errorMsg = [
                        'row_index' => $row,
                        'shop_code' => $shop_code,
                        'store_code' => $store_code,
                        'error_msg' => ''
                    ];
                    if (empty($shop_code) || empty($store_code)) {
                        $errorMsg['error_msg'] = '商家编码或门店编码为空';
                        $errorList[] = $errorMsg;
                        continue;
                    } else {
                        $shop = Shop::where('code', $shop_code)->first(['id']);
                        if ($shop) {
                            $shop_id = $shop->id;
                            $count = ShopStore::where('shop_id', $shop_id)
                                ->where('store_code', $store_code)->where('status', 1)->count();
                            if ($count > 0) {
                                $errorMsg['error_msg'] = '记录已存在';
                                $errorList[] = $errorMsg;
                                continue;
                            } else {
                                $shopStore = ShopStore::where('store_code', $store_code)->where('status', 1)->first();
                                if ($shopStore) {
                                    $shopStore->status = 2;
                                    $shopStore->save();
                                }
                                ShopStore::create([
                                    'shop_id' => $shop_id,
                                    'store_code' => $store_code,
                                    'status' => 1,
                                    'created_code' => Token::$ucode
                                ]);
                                $success++;
                                continue;
                            }
                        } else {
                            $errorMsg['error_msg'] = '无效的商家编码';
                            $errorList[] = $errorMsg;
                            continue;
                        }
                    }
                }
                if (count($check) != count(array_unique($check))) {
                    DB::rollBack();
                    return response(['code' => 7, 'msg' => '门店编码有重复记录']);
                }

                DB::commit();
                return response(['code' => 0, 'data' => $errorList, 'msg' => '共' . count($check) . '条记录，成功' . $success . '条']);
            } else {
                return response($res);
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
}