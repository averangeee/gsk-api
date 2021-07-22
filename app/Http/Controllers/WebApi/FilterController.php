<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/21
 * Time: 11:25
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\ApiIot\Device;
use App\Models\Base\DefineNote;
use App\Models\Gashapon\Sku;
use App\Models\Gashapon\Store;
use App\Models\Gashapon\Version;
use App\Models\Order\Qx;
use App\Models\Shop\PayType;
use App\Models\Shop\Shop;
use App\Models\Shop\SkuImg;
use App\Models\System\Employee;
use App\Models\System\Role;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FilterController extends Controller
{
    /**
     * sku查询
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/21 11:31
     */
    public function sku(Request $request)
    {
        try {
            $keyword = $request->input('keyword', null);

            $version = Version::where('m_type', 'sku')->where('is_active', 1)->first(['version_id']);
            $where = function ($q) use ($keyword, $version) {
                if (!empty($keyword)) {
                    $q->where('sku_name', 'like', '%' . $keyword . '%')->orWhere('sku_id', 'like', '%' . $keyword . '%');
                }

                if ($version) {
                    $q->where('version_id', $version->version_id);
                }
            };

            $sku = Sku::where($where)
                ->select(['sku_id', 'sku_name', 'price'])
                ->paginate(5)
                ->toArray();

            $list = $sku['data'];
            foreach ($list as $k => $v) {
                $img_detail = SkuImg::where('sku_code', $v['sku_id'])->first();
                if ($img_detail) {
                    $list[$k]['img_url'] = $img_detail->image_url;
                } else {
                    $list[$k]['img_url'] = "";
                }
            }
            $sku['data'] = $list;

            $response['code'] = ReturnCode::SUCCESS;
            $response['data'] = $sku['data'];

            return response($response);
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * store查询
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/21 13:41
     */
    public function store(Request $request)
    {
        try {
            $keyword = $request->input('keyword', null);

            $version = Version::where('m_type', 'store')->where('is_active', 1)->first(['version_id']);
            $where = function ($q) use ($keyword, $version) {
                if (!empty($keyword)) {
                    $q->where(function ($qq) use ($keyword) {
                        $qq->where('store_name', 'like', '%' . $keyword . '%')
                            ->orWhere('store_code', 'like', '%' . $keyword . '%');
                    });
                }

                if ($version) {
                    $q->where('version_id', $version->version_id);
                }
            };

            $user_id = Token::$uid;
            $qx = Qx::where('user_id', $user_id)->first();
            $cache_stroe_code = json_decode($qx->cache_stroe_code);
            if (count($cache_stroe_code) > 0) {
                $store = Store::where($where)
                    ->select(['store_code', 'store_name'])
                    ->whereIn('store_code', $cache_stroe_code)
                    ->paginate(50)
                    ->toArray();
            } else {
                $store = Store::where($where)
                    ->select(['store_code', 'store_name'])
                    ->paginate(50)
                    ->toArray();
            }


            $response['code'] = ReturnCode::SUCCESS;
            $response['data'] = $store['data'];

            return response($response);
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author kevin
     */
    public function device(Request $request)
    {
        try {
            $keyword = $request->input('keyword', null);

            $where = function ($q) use ($keyword) {
                if (!empty($keyword)) {
                    $q->where('device_name', 'like', '%' . $keyword . '%')->orWhere('note', 'like', '%' . $keyword . '%')->orWhere('iot_id', 'like', '%' . $keyword . '%');
                }
            };
            $data = Device::where($where)
                ->select(['iot_id', 'device_name', 'note'])
                ->paginate(50)
                ->toArray();

            $response['code'] = ReturnCode::SUCCESS;
            $response['data'] = $data['data'];
            return response($response);
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author kevin
     */
    public function version(Request $request)
    {
        $type = $request->input('type', 'store');

        $data = Version::where('is_del', 0)
            ->where('m_type', $type)
            ->select('version_id', 'version_name', 'is_active')
            ->orderByDesc('version_name')
            ->limit(24)
            ->get();
        return response(ReturnCode::success($data));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author kevin
     */
    public function shop(Request $request)
    {
        try {
            $type = $request->input('type', 2);

            $shops = [];
            if ($type == 1) {
                $shops = Shop::where('parent_id', 0)->get(['id', 'code', 'name', 'parent_id']);
            } else {
                $shop = new Shop();
                $shops = $shop->getLevel('', ['id', 'code', 'name', 'parent_id']);
            }

            return response(ReturnCode::success($shops));
        } catch (\Exception $exception) {
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }


    public function payType(Request $request)
    {
        try {
            $types = PayType::where('status', 1)->orderBy('sort')->get(['id', 'name']);

            return response(ReturnCode::success($types));
        } catch (\Exception $exception) {
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author kevin
     */
    public function employee(Request $request)
    {
        try {
            $keyword = $request->input('keyword', null);
            $selectValue = $request->input('selectValue', null);
            $limit = $request->input('limit', 50);

            $where = function ($query) use ($keyword, $selectValue) {
                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('employee_code', 'like', '%' . $keyword . '%')->orWhere('employee_name', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%')->orWhere('email', 'like', '%' . $keyword . '%')
                            ->orWhere('tel', 'like', '%' . $keyword . '%');
                    });
                }
                if (!empty($selectValue) && !empty($keyword)) {
//                    $selectValue=explode(',',$selectValue);
                    $query->orWhereIn('id', $selectValue);
                }
            };

            $data = Employee::where('states', 1)->where($where)
                ->select(['id', 'employee_code', 'employee_name'])
                ->paginate($limit)
                ->toArray();

            $response['code'] = ReturnCode::SUCCESS;
            $response['data'] = $data['data'];
            return response($response);

        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author kevin
     */
    public function defineNote(Request $request)
    {
        try {
            $type = $request->input('type', null);

            $define = new DefineNote();
            $defines = $define->getLevel($type, '', ['id', 'type', 'des', 'parent_id']);
            return response(ReturnCode::success($defines));
        } catch (\Exception $exception) {
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * 角色
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/7/2 17:16
     */
    public function getRoles(Request $request)
    {
        try {
            $roles = Role::get(['id', 'name']);
            return response(ReturnCode::success($roles));
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    public function getPowerType(Request $request)
    {
        try {

            $types = [
                ['id' => 1, 'name' => '全部'],
                ['id' => 2, 'name' => '公司简称'],
                ['id' => 3, 'name' => '地区权限'],
                ['id' => 4, 'name' => '门店权限']
            ];
            return response(ReturnCode::success($types));
        } catch (\Exception $e) {
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL, $e->getMessage()));
        }
    }

    public function getPower(Request $request)
    {
        $power_type = $request->input('power_type');//1全部，2简称，3地区
        $power_like = $request->input('power_like');
        $version_id = date('Ym', time());

        switch ($power_type) {
            case 2:
                $list = Store::where('version_id', $version_id)
                    ->where('customer', 'like', '%' . $power_like . '%')
                    ->select('store_code', 'store_name', 'customer', 'region')
                    ->groupBy('customer')
                    ->get()
                    ->toArray();
                break;
            case 3:
                $list = Store::where('version_id', $version_id)
                    ->whereNotNull('region')
                    ->select('store_code', 'store_name', 'customer', 'region')
                    ->groupBy('region')
                    ->get()
                    ->toArray();
                break;
            case 4:
                $list = Store::where('version_id', $version_id)
                    ->where('store_code', 'like', '%' . $power_like . '%')
                    ->orWhere('store_name', 'like', '%' . $power_like . '%')
                    ->select('store_code', 'store_name', 'customer', 'region')
                    ->orderByDesc('id')
                    ->get()
                    ->take(10)
                    ->toArray();
                break;
        }
        return response(ReturnCode::success($list));
    }

}
