<?php

namespace App\Http\Controllers\AndroidApi;

use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\ApiIot\DeviceEgg;
use App\Models\ApiIot\DeviceEggLog;
use App\Models\ApiIot\DeviceEggn;
use App\Models\Mh\Banner;
use App\Models\Mh\SkuType;
use App\Models\Shop\SkuImg;
use App\Models\System\Employee;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\MySqlConnection;
use App\Models\GSK\Device;
use App\Models\GSK\City;
use App\Models\GSK\Ck;
use App\Models\GSK\ManagerReset;
use App\Models\GSK\Img;
use App\Models\GSK\WeixiuLog;
use App\Models\GSK\AllocationLog;
use App\Models\GSK\WeixiuApply;
use OSS\OssClient;
use phpDocumentor\Reflection\Types\Null_;


class ProductController extends BaseController
{
    //区域首页
    function index(Request $request)
    {

        $user_id = $request->input('user_id');
            $result=DB::select("SELECT

                                SUM(CASE WHEN status in (1,2,3,4,5,6)  THEN 1 ELSE 0 END) as total_device,
                                SUM(CASE WHEN status = 1  THEN 1 ELSE 0 END) as ck,
                                SUM(CASE WHEN status = 2  THEN 1 ELSE 0 END) as db,
                                SUM(CASE WHEN status = 3  THEN 1 ELSE 0 END) as zx,
                                SUM(CASE WHEN status in (4,5)  THEN 1 ELSE 0 END) as bx,
                                SUM(CASE WHEN status = 6  THEN 1 ELSE 0 END) as bg,
                                SUM(CASE WHEN status = 1  THEN 1 ELSE 0 END)/count(1)  as ck_percent,
                                SUM(CASE WHEN status = 2  THEN 1 ELSE 0 END)/count(1)  as db_percent,
                                SUM(CASE WHEN status = 3  THEN 1 ELSE 0 END)/count(1)  as zx_percent,
                                SUM(CASE WHEN status in (4,5)  THEN 1 ELSE 0 END)/count(1)  as bx_percent,
                                SUM(CASE WHEN status = 6  THEN 1 ELSE 0 END)/count(1)  as bg_percent
                                FROM gsk_device where yq_manager_id='".$user_id."'");
                        return response(ReturnCode::success($result, 'success'));
    }

    //执行公司首页
    function zhixing_index(Request $request)
    {
        $user_id = $request->input('user_id');
        $result=DB::select("SELECT
                                SUM(CASE WHEN status = 2  THEN 1 ELSE 0 END) as accept,
                                SUM(CASE WHEN status =3 THEN 1 ELSE 0 END) as used,
                                SUM(CASE WHEN status =4  THEN 1 ELSE 0 END) as weixiu
                                FROM gsk_allocation_log where operater='".$user_id."'");
        return response(ReturnCode::success($result, 'success'));
    }

        //维修中心首页
        function weixiu_index(Request $request)
        {
            $result=DB::select('SELECT
                                    SUM(CASE WHEN device_status in (5,6,7,8)  THEN 1 ELSE 0 END) as In_weixiu,
                                    SUM(CASE WHEN device_status= 9  THEN 1 ELSE 0 END) as weixiu_end
                                    FROM gsk_weixiu_apply

                                    ');
            $response['data']=$result;
            $response['code']=0;
            return response(ReturnCode::success($result, 'success'));
        }


    //区域设备列表
    function product(Request $request)
    {
        $temp=[];
        $limit = $request->input('limit');//分页条数
        $type=$request->input('type');
        $user_id = $request->input('user_id');
        $gsk_code = $request->input('gsk_code');
        $city_id = $request->input('city_id');
        $yq_manager_id= $request->input('yq_manager_id');
        $serache_status = $request->input('serache_status');
        $where1 = function ($q) use ($gsk_code,$city_id,$serache_status) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
            if ($city_id) {
                $q->where('city_id', $city_id);
            }
            if ($serache_status) {
                if($serache_status==1){

                }elseif ($serache_status==2){
                    $q->where('status', 1);
                }elseif ($serache_status==3){
                    $q->where('status', 2);
                }elseif ($serache_status==4){
                    $q->where('status', 3);
                }elseif ($serache_status==5){
                    $q->whereIn('status', [4,5]);
                }elseif ($serache_status==6){
                    $q->where('status', 6);
                }elseif ($serache_status==7){
                    $q->where('status', 7);
                }else{
                    $q->whereIn('status', [1,2,3,4,5,6]);
                }

            }
        };
        $where3 = function ($q) use ($gsk_code,$city_id) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
            if ($city_id) {
                $q->where('city_id', $city_id);
            }

        };

        $where2 = function ($q) use ($gsk_code,$city_id,$yq_manager_id) {
            if ($gsk_code) {
                $q->where('gsk_code','like','%'. $gsk_code.'%');
            }
            if ($city_id) {
                $q->where('city_id', $city_id);
            }
            if ($yq_manager_id) {
                $q->where('yq_manager_id',$yq_manager_id);
            }
        };
        $where4 = function ($q) use ($gsk_code,$city_id,$yq_manager_id,$serache_status) {
            if ($gsk_code) {
                $q->where('gsk_code','like','%'. $gsk_code.'%');
            }
            if ($city_id) {
                $q->where('city_id', $city_id);
            }
            if ($yq_manager_id) {
                $q->where('yq_manager_id',$yq_manager_id);
            }
            if ($serache_status) {
                if($serache_status==1){

                }elseif ($serache_status==2){
                    $q->where('status', 1);
                }elseif ($serache_status==3){
                    $q->where('status', 2);
                }elseif ($serache_status==4){
                    $q->where('status', 3);
                }elseif ($serache_status==5){
                    $q->whereIn('status', [4,5]);
                }elseif ($serache_status==6){
                    $q->where('status', 6);
                }elseif ($serache_status==7){
                    $q->where('status', 7);
                }else{

                }

            }
        };
        if($type==1){
            $list = Device::where($where1)->whereIn('status',[1,2,3,4,5,6,7])->where('yq_manager_id',$user_id)->orwhere('yq_manager_id_two',$user_id)->orWhere('yq_manager_id_gh',$user_id)
         //   $list = Device::where($where1)->where('ck_name',2)->where('yq_manager_id',$user_id)->orwhere('yq_manager_id_two',$user_id)->orWhere('yq_manager_id_gh',$user_id)
                ->with(['city','address','employee','jyemployee'])
                ->paginate($limit)->toArray();


            if($list['data']){

                for($i=0;$i<count($list['data']);$i++){
                 //   dd($list['data'][0]['employee']['employee_name']);
                    $temp[$i]['id']=$list['data'][$i]['id'];
                    $temp[$i]['name']=$list['data'][$i]['name'];
                    $temp[$i]['gsk_code']=$list['data'][$i]['gsk_code'];
                    $temp[$i]['type']=$list['data'][$i]['type'];
                    $temp[$i]['db_style']=$list['data'][$i]['db_style'];
                    $temp[$i]['channel']=$list['data'][$i]['channel'];
                    $temp[$i]['status']=$list['data'][$i]['status'];
                    $temp[$i]['area']=$list['data'][$i]['area'];
                    $temp[$i]['region']=$list['data'][$i]['region'];
                    $temp[$i]['real_city']=$list['data'][$i]['real_city'];
                    $temp[$i]['tj_status']=$list['data'][$i]['tj_status'];
                    $temp[$i]['yq_manager_id']=$list['data'][$i]['yq_manager_id'];
                    $temp[$i]['yq_manager_id_two']=$list['data'][$i]['yq_manager_id_two'];
                    $temp[$i]['city_name']=$list['data'][$i]['city']['city_name'];
                    $temp[$i]['ck_name']=$list['data'][0]['address']['name'];
                    $temp[$i]['gh_flag']=$list['data'][$i]['gh_flag'];
                    $temp[$i]['yqmanager_name']=$list['data'][$i]['employee']['employee_name'];
                    $temp[$i]['yqmanager_jy']=$list['data'][$i]['jyemployee']['employee_name'];
                    $js = ManagerReset::where('yq_manager_id',$user_id)->where('status',0)->where('gsk_code',$list['data'][$i]['gsk_code'])
                        //    ->orWhere('user_id',$user_id)->where('status',0)
                        ->orderByDesc('id')
                        ->get()
                        ->toArray();
                    $fs = ManagerReset::where('user_id',$user_id)->where('status',0)->where('gsk_code',$list['data'][$i]['gsk_code'])
                        //    ->orWhere('user_id',$user_id)->where('status',0)
                        ->orderByDesc('id')
                        ->get()
                        ->toArray();
                    if(count($js)>0){
                        $flag=1; //接收人
                    }else if(count($fs)>0){
                        $flag=2;  //发送人
                    }else{
                        $flag='';
                    }
                    $temp[$i]['flag']=$flag;
                }
                $response['data']=$temp;
                $response['data']=$temp;
                $response['current_page']=$list['current_page'];
                $response['last_page']=$list['last_page'];
                $response['from']=$list['from'];
                $response['to']=$list['to'];
                $response['per_page']=$list['per_page'];
                $response['total']=$list['total'];


            }else{
                $temp=[];
                $response['data']=$temp;
                $response['current_page']=$list['current_page'];
                $response['last_page']=$list['last_page'];
                $response['from']=$list['from'];
                $response['to']=$list['to'];
                $response['per_page']=$list['per_page'];
                $response['total']=$list['total'];

            }

            $list1 = Device::where($where3)->where('yq_manager_id',$user_id)->where('status','!=',9)
                ->with(['city','address','employee'])
                ->get()->toArray();
            $ck_list = Device::where($where3)->where('yq_manager_id',$user_id)->where('status',1)
                ->get()->toArray();
            $db_list= Device::where($where3)->where('yq_manager_id',$user_id)->where('status',2)
                ->get()->toArray();
            $zx_list= Device::where($where3)->where('yq_manager_id',$user_id)->where('status',3)
                ->get()->toArray();
            $bx_list= Device::where($where3)->where('yq_manager_id',$user_id)->whereIn('status',[4,5])
                ->get()->toArray();
            $bg_list= Device::where($where3)->where('yq_manager_id',$user_id)->where('status',6)
                ->get()->toArray();
            $jy_list= Device::where($where3)->where('yq_manager_id',$user_id)->where('status',7)
                ->get()->toArray();
        }else{
            $list = Device::where($where4)->where('status','!=',9)
         //   $list = Device::where($where4)->where('ck_name',1)
                ->with(['city','address','employee','jyemployee'])
                ->paginate($limit)->toArray();

            if($list){
                for($i=0;$i<count($list['data']);$i++){
                    //   dd($list['data'][0]['employee']['employee_name']);
                    $temp[$i]['id']=$list['data'][$i]['id'];
                    $temp[$i]['name']=$list['data'][$i]['name'];
                    $temp[$i]['gsk_code']=$list['data'][$i]['gsk_code'];
                    $temp[$i]['type']=$list['data'][$i]['type'];
                    $temp[$i]['db_style']=$list['data'][$i]['db_style'];
                    $temp[$i]['channel']=$list['data'][$i]['channel'];
                    $temp[$i]['status']=$list['data'][$i]['status'];
                    $temp[$i]['area']=$list['data'][$i]['area'];
                    $temp[$i]['region']=$list['data'][$i]['region'];
                     $temp[$i]['real_city']=$list['data'][$i]['real_city'];
                    $temp[$i]['tj_status']=$list['data'][$i]['tj_status'];
                    $temp[$i]['yq_manager_id']=$list['data'][$i]['yq_manager_id'];
                    $temp[$i]['yq_manager_id_two']=$list['data'][$i]['yq_manager_id_two'];
                    $temp[$i]['city_name']=$list['data'][$i]['city']['city_name'];
                    $temp[$i]['ck_name']=$list['data'][0]['address']['name'];
                    $temp[$i]['gh_flag']=$list['data'][$i]['gh_flag'];
                    $temp[$i]['yqmanager_name']=$list['data'][$i]['employee']['employee_name'];
                    $temp[$i]['yqmanager_jy']=$list['data'][$i]['jyemployee']['employee_name'];
                    $js = ManagerReset::where('yq_manager_id',$list['data'][$i]['yq_manager_id'])->where('status',0)->where('gsk_code',$list['data'][$i]['gsk_code'])
                        //    ->orWhere('user_id',$user_id)->where('status',0)
                        ->orderByDesc('id')
                        ->get()
                        ->toArray();
                    $fs = ManagerReset::where('user_id',$list['data'][$i]['yq_manager_id'])->where('status',0)->where('gsk_code',$list['data'][$i]['gsk_code'])
                        //    ->orWhere('user_id',$user_id)->where('status',0)
                        ->orderByDesc('id')
                        ->get()
                        ->toArray();
                    if(count($js)>0){
                        $flag=1; //接收人
                    }else if(count($fs)>0){
                        $flag=2;  //发送人
                    }else{
                        $flag='';
                    }
                    $temp[$i]['flag']=$flag;
                }
                $response['data']=$temp;
                $response['current_page']=$list['current_page'];
                $response['last_page']=$list['last_page'];
                $response['from']=$list['from'];
                $response['to']=$list['to'];
                $response['per_page']=$list['per_page'];
                $response['total']=$list['total'];


            }else{
                $temp=[];
                $response['data']=$temp;
                $response['current_page']=$list['current_page'];
                $response['last_page']=$list['last_page'];
                $response['from']=$list['from'];
                $response['to']=$list['to'];
                $response['per_page']=$list['per_page'];
                $response['total']=$list['total'];

            }

            $list1 = Device::where($where2)->where('status','!=',9)
                ->with(['city','address','employee'])
                ->get()->toArray();
            $ck_list = Device::where($where2)->where('status',1)
                ->get()->toArray();
            $db_list= Device::where($where2)->where('status',2)
                ->get()->toArray();
            $zx_list= Device::where($where2)->where('status',3)
                ->get()->toArray();
            $bx_list= Device::where($where2)->whereIn('status',[4,5])
                ->get()->toArray();
            $bg_list= Device::where($where2)->where('status',6)
                ->get()->toArray();
            $jy_list= Device::where($where2)->where('status',7)
                ->get()->toArray();
        }

       /* $result=DB::select('SELECT
                                    count(1) as total_device,
                                    SUM(CASE WHEN status = "1"  THEN 1 ELSE 0 END) as ck,
                                    SUM(CASE WHEN status = "2"  THEN 1 ELSE 0 END) as db,
                                    SUM(CASE WHEN status = "3"  THEN 1 ELSE 0 END) as zx,
                                    SUM(CASE WHEN status in (4,5)  THEN 1 ELSE 0 END) as bx,
                                    SUM(CASE WHEN status = "6"  THEN 1 ELSE 0 END) as bg
                                    FROM gsk_device
                                    '); */


        $response['total_device']=count($list1);
        $response['ck']=count($ck_list);
        $response['db']=count($db_list);
        $response['zx']=count($zx_list);
        $response['bx']=count($bx_list);
        $response['bg']=count($bg_list);
        $response['jy']=count($jy_list);
        return response(ReturnCode::success($response, 'success'));
    }

    //获取仪器编码
    public function gsk_code_list(Request $request)
    {
        $list = Device::where('deleted_at','=',null)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //获取省份
    public function province_list(Request $request)
    {
        $list = City::whereIn('pid',[452,453,454,455])
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //城市列表
    public function city_list(Request $request)
    {
        $pid=$request->input('pid');
        $where = function ($q) use ($pid) {
            if ($pid) {
                $q->where('pid', $pid);
            }
        };
        $list = City::where('deleted_at','=',null)
            ->where($where)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //仓库列表
    public function ck_list(Request $request)
    {
        $list = Ck::where('deleted_at','=',null)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }


    //区域申请调拨
    function apply_allocation(Request $request)
    {
        try {
        $gsk_code = $request->input('gsk_code');
        $allocation_style = $request->input('style');
        $operater = $request->input('operater');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $city_id = $request->input('city_id');
        $allocation_date = $request->input('allocation_date');
        $allocation_way = $request->input('way');
        $msg = $request->input('msg');
        $user_id = $request->input('user_id');
        $gsk_temp=explode(',',$gsk_code);

            $real_id=DB::select("select city_name from gsk_city where id='".$city_id."'");
            if($real_id){
                $real_city=$real_id[0]->city_name;
            }

            $count_num=count($gsk_temp);
        for($i=0;$i<$count_num;$i++) {
            AllocationLog::insert([
                'gsk_code' => $gsk_temp[$i],
                'allocation_date' => $allocation_date,
                'status' => 2,
                'device_status' => 2,
                'operater' => $operater,
                'style' => $allocation_style,
                'number' => 1,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'way' => $allocation_way,
                'city_id' => $city_id,
                'db_city_id' => $real_city,
                'msg' => $msg,
                'user_id' => $user_id,
                'created_at' => Helper::datetime()
            ]);
            $real_id=DB::select("select city_name from gsk_city where id='".$city_id."'");
            if($real_id){
                $real_city=$real_id[0]->city_name;
            }

            Device::where('gsk_code', $gsk_code)->update([
                'status' => 2,
                //     'db_style' => 1,
                'real_city'=>$real_city,
                'updated_at' => Helper::datetime()
            ]);
          /*  if($allocation_style==2){
                Device::where('gsk_code', $gsk_code)->update([
                    'status' => 2,
                    'db_style' => 1, //内部调拨（区域同事之间的借用）
                    'real_city'=>$real_city,
                    'yq_manager_id_two'=>$operater,
                    'updated_at' => Helper::datetime()
                ]);

            }else{
                Device::where('gsk_code', $gsk_code)->update([
                    'status' => 2,
                    //     'db_style' => 1,
                    'real_city'=>$real_city,
                    'updated_at' => Helper::datetime()
                ]);
            } */

        }
        return response(ReturnCode::success([], '成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //执行获取调拨信息渲染
    public function allocation_info(Request $request)
    {
        $gsk_code = $request->input('gsk_code');
        $list = AllocationLog::where('gsk_code',$gsk_code)
            ->with(['employee','city'])
            ->orderByDesc('id')
            ->limit(1)
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //执行公司验收
    function operater_company_check(Request $request)
    {
        try {
            $now_component = $request->input('now_component');
            $is_normal = $request->input('is_normal');
            $gsk_code = $request->input('gsk_code');
            $ys_city_id = $request->input('city_name');
            $city_id = $request->input('city_id');
            $user_id = $request->input('user_id');

            $real_id=DB::select("select id from gsk_city where city_name='".$ys_city_id."'");
            if($real_id){
                $real_city=$real_id[0]->id;
            }


            $list_s= AllocationLog::where('gsk_code',$gsk_code)->whereIn('status',[2,6])
                ->where('operater',$user_id)
                ->orderByDesc('id')
                ->limit(1)
                ->get()
                ->toArray();
            if(count($list_s)<0){
                return response(ReturnCode::error('100', '设备已验收请勿重复操作'));

            }

            $res=DB::select("select id from gsk_allocation_log where gsk_code='".$gsk_code."'  and status=2");
                AllocationLog::where('gsk_code', $gsk_code)->update([
                    'status' => 3,
                    'device_status' => 3,
                    'gsk_code' =>$gsk_code,
                    'is_normal' =>$is_normal,
                    'ys_city_id' =>$ys_city_id,
                    'city_id'=>$real_city,
                    'now_component' =>$now_component,
                    'updated_at' => Helper::datetime()
                ]);
                Device::where('gsk_code', $gsk_code)->update([
                    'status' => 3,
                    'real_city' => $ys_city_id,
                    'updated_at' => Helper::datetime()
                ]);

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //区域调拨记录
    function allocation_list(Request $request)
    {
        $status = $request->input('status');
        $user_id=$request->input('user_id');
        $where = function ($q) use ($status) {
            if ($status) {
                $q->where('status', $status);
            }
        };
        $list = AllocationLog::where($where)->where('user_id',$user_id)
            ->get()->toArray();
        return response(ReturnCode::success($list, 'success'));

    }



    //执行公司调拨
    function operater_company_allocation(Request $request)
    {
        try {
            $gsk_code = $request->input('gsk_code');
            $city_id = $request->input('city_id');
            $db_city_id = $request->input('db_city_id');
            $gsk_code_temp=explode(',',$gsk_code);

            $realCDB=DB::select("select city_name from gsk_city where id='".$city_id."'");
            if($realCDB){
                $real_city=$realCDB[0]->city_name;
            }

            $count=count($gsk_code_temp);
            for($i=0;$i<$count;$i++){
                AllocationLog::where('gsk_code', $gsk_code_temp[$i])->whereIn('status',[3,6])->update([
                    'city_id' => $city_id,
                    'db_city_id' => $real_city,
                    'status' => 6,
                    'device_status' => 6,
                    'updated_at' => Helper::datetime()
                ]);
                $realCDB=DB::select("select city_name from gsk_city where id='".$city_id."'");
                if($realCDB){
                    $real_city=$realCDB[0]->city_name;
                }

                Device::where('gsk_code', $gsk_code_temp[$i])->update([
                    'real_city'=>$real_city,
                    'updated_at' => Helper::datetime()
                ]);
            }
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //执行公司归还gsk
    function operater_company_replay(Request $request)
    {
        try {
            $gsk_code = $request->input('gsk_code');
            $now_component = $request->input('now_component');
            $replay_way = $request->input('replay_way');
            $replay_msg = $request->input('replay_msg');
            $gsk_code_temp=explode(',',$gsk_code);
            $count=count($gsk_code_temp);
            for($i=0;$i<$count;$i++){
                AllocationLog::where('gsk_code', $gsk_code_temp[$i])->whereIn('status',[3,4,6])->update([
                    'now_component' => $now_component,
                    'replay_way' => $replay_way,
                    'replay_msg' => $replay_msg,
                    'status' =>5,
                    'gh_status'=>1,
                    'device_status' =>5,
                    'updated_at' => Helper::datetime()
                ]);

            }
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //执行公司报修申请
    function operater_company_report(Request $request)
    {
        try {
            $operator= $request->input('user_id');
            $gsk_code = $request->input('gsk_code');
            $city_id = $request->input('city_id');
            $weixiu_city_id = $request->input('weixiu_city_id');
            $fault_date = $request->input('fault_date');
            $fault_body = $request->input('fault_body');
            $fault_case = $request->input('fault_case');
            $fault_img= $request->input('fault_img');
            $other_case= $request->input('other_case');
            $weixiu_replay_style= $request->input('weixiu_replay_style');
            if($gsk_code==null){
                return response(ReturnCode::error('100', '请选择设备'));
            }
            if($weixiu_replay_style==2){
                $device_status=1;
                $submit_status=2;
            }else{
                $device_status=Null;
                $submit_status=1;
            }
            $fault_msg=$request->input('fault_msg');
            $ck_address= $request->input('ck_address');
         //   $device_status= $request->input('device_status');
            $replay_msg= $request->input('replay_msg');
            $gsk_code_temp=explode(',',$gsk_code);
            $answer_content=$request->input('answer_content');
            $count=count($gsk_code_temp);
            for($i=0;$i<$count;$i++){
                WeixiuApply::insert([

                    'gsk_code' => $gsk_code_temp[$i],
                    'operator' => $operator,
                    'city_id' => $city_id,
                    'weixiu_city_id' => $weixiu_city_id,
                    'device_status' =>$device_status,
                    'quyu_id' => $operator,
                    'fault_date' => $fault_date,
                    'fault_body' => $fault_body,
                    'fault_case' => $fault_case,
                    'img' => $fault_img,
                    'weixiu_replay_style' => $weixiu_replay_style,
                    'fault_msg' => $fault_msg,
                    'ck_address' => $ck_address,
                    'replay_msg' => $replay_msg,
                    'answer_content' => $answer_content,
                    'flag'=> 1,
                  //  'status'=>4,
                    'submit_status'=>$submit_status,
                    'other_case'=> $other_case,
                    'created_at' => Helper::datetime(),
                    'apply_date' => Helper::datetime()
                ]);
                AllocationLog::where('gsk_code', $gsk_code_temp[$i])->update([
                    'status' =>4,
                    'device_status' =>4,
                    'updated_at' => Helper::datetime()
                ]);
                if($weixiu_replay_style==2){
                    Device::where('gsk_code', $gsk_code_temp[$i])->update([
                        'tj_status' =>2,
                        'status'=>4,
                        'updated_at' => Helper::datetime()
                    ]);
                }else{
                    Device::where('gsk_code', $gsk_code_temp[$i])->update([
                        'tj_status' =>1,
                        'status'=>4,
                        'updated_at' => Helper::datetime()
                    ]);
                }



            }
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
     //区域提交报修申请时区域确认
    function quyu_apply_report(Request $request)
    {
        try {
            $type=$request->input('type');
            $operator= $request->input('user_id');
            $gsk_code = $request->input('gsk_code');
            $city_id = $request->input('city_id');
            $weixiu_city_id = $request->input('weixiu_city_id');
            $fault_date = $request->input('fault_date');
            $fault_body = $request->input('fault_body');
            $fault_case = $request->input('fault_case');
            $fault_img= $request->input('fault_img');
            $weixiu_replay_style= $request->input('weixiu_replay_style');
            $fault_msg=$request->input('fault_msg');
            $ck_address= $request->input('ck_address');
            $device_status= $request->input('device_status');
            $replay_msg= $request->input('replay_msg');
            $gsk_code_temp=explode(',',$gsk_code);
            $answer_content=$request->input('answer_content');
            $count=count($gsk_code_temp);
            for($i=0;$i<$count;$i++){
                if($type==1){
                    WeixiuApply::where('gsk_code', $gsk_code)->where('submit_status','=',1)->update([
                        'device_status2' =>4,
                        'flag'=> 1,
                        'submit_status'=> 2,
                        'device_status' => $device_status,
                        'updated_at' => Helper::datetime()
                    ]);
                    Device::where('gsk_code', $gsk_code_temp[$i])->update([
                        'status' =>4,
                        'tj_status'=>2,
                        'updated_at' => Helper::datetime()
                    ]);

                }else{
                    WeixiuApply::where('gsk_code', $gsk_code)->where('submit_status','=',1)->update([
                        'city_id' => $city_id,
                        'weixiu_city_id' => $weixiu_city_id,
                        'device_status' =>$device_status,
                        'device_status2' =>4,
                        'quyu_id' => $operator,
                        'fault_date' => $fault_date,
                        'fault_body' => $fault_body,
                        'fault_case' => $fault_case,
                        'img' => $fault_img,
                        'weixiu_replay_style' => $weixiu_replay_style,
                        'fault_msg' => $fault_msg,
                        'ck_address' => $ck_address,
                        'replay_msg' => $replay_msg,
                        'answer_content' => $answer_content,
                        'device_status' => $device_status,
                        'flag'=> 1,
                        'submit_status'=> 2,
                        'updated_at' => Helper::datetime()
                    ]);
                    Device::where('gsk_code', $gsk_code_temp[$i])->update([
                        'status' =>4,
                        'tj_status'=>2,
                        'updated_at' => Helper::datetime()
                    ]);


                }
            }
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //区域报修申请
    function quyu_report(Request $request)
    {
        try {
            $operator= $request->input('user_id');
            $gsk_code = $request->input('gsk_code');
            $city_id = $request->input('city_id');
            $weixiu_city_id = $request->input('weixiu_city_id');
            $weixiu_place = $request->input('weixiu_place');
            $fault_date = $request->input('fault_date');
            $fault_body = $request->input('fault_body');
            $fault_case = $request->input('fault_case');
            $fault_img= $request->input('fault_img');
            $fault_msg= $request->input('fault_msg');
            $weixiu_replay_style= $request->input('weixiu_replay_style');
            $fault_msg=$request->input('fault_msg');
            $ck_address= $request->input('ck_address');
            $device_status= $request->input('device_status');
            $replay_msg= $request->input('replay_msg');
            $gsk_code_temp=explode(',',$gsk_code);
            $answer_content=$request->input('answer_content');
            $count=count($gsk_code_temp);
            for($i=0;$i<$count;$i++){
                WeixiuApply::insert([
                    'gsk_code' => $gsk_code_temp[$i],
                    'operator' => $operator,
                    'city_id' => $city_id,
                    'weixiu_city_id' => $weixiu_city_id,
                    'weixiu_place' => $weixiu_place,
                    'device_status' =>$device_status,
                    'device_status2' =>5,
                    'flag' =>1,
                    'submit_status'=>2,
                    'quyu_id' => $operator,
                    'fault_date' => $fault_date,
                    'fault_body' => $fault_body,
                    'fault_case' => $fault_case,
                    'fault_msg' => $fault_msg,
                    'img' => $fault_img,
                    'weixiu_replay_style' => $weixiu_replay_style,
                    'fault_msg' => $fault_msg,
                    'ck_address' => $ck_address,
                    'replay_msg' => $replay_msg,
                    'answer_content' => $answer_content,
                    'created_at' => Helper::datetime()
                ]);
                Device::where('gsk_code', $gsk_code_temp[$i])->update([
                    'status' =>5,
                    'tj_status' =>1,
                    'updated_at' => Helper::datetime()
                ]);
                AllocationLog::where('gsk_code', $gsk_code_temp[$i])->where('device_status',3)->update([
                    'device_status' =>4,
                    'status'=>4,
                    'updated_at' => Helper::datetime()
                ]);

            }
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //维修公司仪器列表
    public function report_device_list(Request $request)
    {
        $list = WeixiuApply::where('deleted_at','=',null)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        return response($response);
    }

    //维修公司维修
    function weixiu_company_report(Request $request)
    {
        try {
            $user_id= $request->input('user_id');
            $gsk_code = $request->input('gsk_code');
            $now_component = $request->input('now_component');
            $fault_body = $request->input('fault_body');
            $fault_part = $request->input('fault_part');
            $fault_case= $request->input('fault_case');
            $device_status= $request->input('device_status');


                WeixiuApply::insert([
                    'gsk_code' =>$gsk_code,
                    'user_id' => $user_id,
                    'fault_body' => $fault_body,
                    'now_component' => $now_component,
                    'fault_part' => $fault_part,
                    'fault_case' => $fault_case,
                    'device_status' => $device_status,
                    'updated_at' => Helper::datetime()
                ]);
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //区域仪器倒仓
    function device_back_ck(Request $request)
    {
        try {
            $gsk_code = $request->input('gsk_code');
                Device::where('gsk_code', $gsk_code)->update([
                    'status' => 1,
                    'updated_at' => Helper::datetime()
                ]);
               WeixiuApply::where('gsk_code', $gsk_code)->where('gh_status',1)->update([
                'device_status' => 9,
                'updated_at' => Helper::datetime()
            ]);
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //区域仪器倒仓
    function device_back_ck_bak(Request $request)
    {
        try {
            $flag = $request->input('flag');
            $gsk_code = $request->input('gsk_code');
            $user_id=$request->input('user_id');
          /*  Device::where('gsk_code', $gsk_code)->update([
                'status' => 1,
                'updated_at' => Helper::datetime()
            ]);
            WeixiuApply::where('gsk_code', $gsk_code)->where('gh_status',1)->update([
                'device_status' => 9,
                'updated_at' => Helper::datetime()
            ]);
            return response(ReturnCode::success());  */

            if($flag==1){ //执行方归还仪器到仓
                $Rcity=DB::select("select city_id from gsk_device where gsk_code ='".$gsk_code."'");
                if($Rcity){
                    $Rid=$Rcity[0]->city_id;
                }

                $Rname=DB::select("select city_name from gsk_city where id='".$Rid."'");
                if($Rname){
                    $Rname=$Rname[0]->city_name;
                }



                Device::where('gsk_code', $gsk_code)->update([
                    'status' => 1,
                    'real_city'=>$Rname,
                    'updated_at' => Helper::datetime()
                ]);
                AllocationLog::where('gsk_code', $gsk_code)->whereIn('status',[3,4,6])->update([
                    'status' => 5,
                    'gh_status'=>2,
                    'device_status' => 5,
                    'updated_at' => Helper::datetime()
                ]);
                AllocationLog::where('gsk_code', $gsk_code)->where('status',5)->where('gh_status',1)->update([
                    'status' => 5,
                    'gh_status'=>2,
                    'device_status' => 5,
                    'updated_at' => Helper::datetime()
                ]);
                return response(ReturnCode::success());

            }else if($flag==2){ //报修仪器到仓
                $res=DB::select("select id from gsk_weixiu_apply where flag=1 and submit_status=1 and weixiu_replay_style=1 and  gsk_code='".$gsk_code."'");
                if($res){
                    return response(ReturnCode::error('100', '待区域先提交报修'));
                }
                $list12 = WeixiuApply::where('gsk_code',$gsk_code)->where('is_show',Null)
                    ->get()
                    ->toArray();
                if($list12){
                    return response(ReturnCode::error('100', '待维修仪器不能仪器到仓'));
                }

                $list11 = WeixiuApply::where('gsk_code',$gsk_code)->where('is_show',1)->whereNotIn('device_status',[7,8])
                    ->get()
                    ->toArray();
                if($list11){
                    return response(ReturnCode::error('100', '还在维修中不能仪器入仓'));
                }

                /*   $list12 = WeixiuApply::where('gsk_code',$gsk_code)->where('is_show',Null)
                       ->get()
                       ->toArray();


                   if($list12){
                       $Rcity=DB::select("select city_id from gsk_device where gsk_code ='".$gsk_code."'");
                       if($Rcity){
                           $Rid=$Rcity[0]->city_id;
                       }
                       $Rname=DB::select("select city_name from gsk_city where id='".$Rid."'");
                       if($Rname){
                           $Rname=$Rname[0]->city_name;
                       }

                       Device::where('gsk_code', $gsk_code)->update([
                           'status' => 1,
                           'real_city'=>$Rname,
                           'updated_at' => Helper::datetime()
                       ]);

                       WeixiuApply::where('gsk_code', $gsk_code)->where('is_show',Null)->update([
                           'device_status' => 9,
                           'gh_status' => 2,
                           'is_show' => 1,
                           'updated_at' => Helper::datetime()
                       ]);
                       return response(ReturnCode::success());

                   } */

                $Rcity=DB::select("select city_id from gsk_device where gsk_code= '".$gsk_code."'");
                if($Rcity){
                    $Rid=$Rcity[0]->city_id;
                }

                $Rname=DB::select("select city_name from gsk_city where id='".$Rid."'");
                if($Rname){
                    $Rname=$Rname[0]->city_name;
                }

                Device::where('gsk_code', $gsk_code)->update([
                    'status' => 1,
                    'real_city'=>$Rname,
                    'updated_at' => Helper::datetime()
                ]);

                AllocationLog::where('gsk_code', $gsk_code)->where('status',4)->update([  //调拨也要同步更新状态
                    'device_status' => 5,
                    'status' => 5,
                    'updated_at' => Helper::datetime()
                ]);


                WeixiuApply::where('gsk_code', $gsk_code)->where('gh_status',1)->update([
                    'device_status' => 9,
                    'gh_status' => 2,
                    'is_show' => 1,
                    'submit_status' => 2,
                    'updated_at' => Helper::datetime()
                ]);
                return response(ReturnCode::success());
            }else if($flag==3){ // 仪器变更时仪器到仓
                $list = ManagerReset::where('gsk_code',$gsk_code)
                    ->with(['city'])
                    ->orderByDesc('id')
                    ->limit(1)
                    ->get()
                    ->toArray();
                if($list){
                    $yq_manager_id=$user_id;
                    $area=$list[0]['area'];
                    $city_id=$list[0]['city_id'];
                    $channel=$list[0]['channel'];
                    $address_id=$list[0]['ck_id'];
                    $real_id=DB::select("select city_name from gsk_city where id='".$list[0]['city_id']."'");
                    if($real_id){
                        $real_city=$real_id[0]->city_name;
                    }
                    Device::where('gsk_code', $gsk_code)->update([
                        'status' => 1,
                        'address_id'=>$address_id,
                        'city_id'=>$city_id,
                        'channel'=>$channel,
                        'area'=>$area,
                        'gh_flag'=>4,
                        'real_city'=>$real_city,
                        'yq_manager_id'=>$yq_manager_id,
                        'updated_at' => Helper::datetime()
                    ]);
                    return response(ReturnCode::success());

                }


            }else if($flag==4){ // 内部调拨接收时仪器到仓
                $Rcity=DB::select("select city_id from gsk_device where gsk_code= '".$gsk_code."'");
                $Rid=$Rcity[0]->city_id;
                $Rname=DB::select("select city_name from gsk_city where id='".$Rid."'");
                Device::where('gsk_code', $gsk_code)->update([
                //    'status' => 7,
                    'status'=>1,
                    'db_style'=>2,
                    'real_city'=>$Rname,
                    'updated_at' => Helper::datetime()
                ]);
                return response(ReturnCode::success());
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }





    //区域新增报修
    function quyu_add_report(Request $request)
    {
        try {
            $user_id= $request->input('user_id');
            $gsk_code = $request->input('gsk_code');
            $city_id = $request->input('city_id');
            $weixiu_city_id = $request->input('weixiu_city_id');
            $fault_date = $request->input('fault_date');
            $fault_body = $request->input('fault_body');
            $fault_case = $request->input('fault_case');
            $fault_img= $request->input('fault_img');
            $weixiu_replay_style= $request->input('weixiu_replay_style');
            $fault_msg=$request->input('fault_msg');
            //  $ck_address= $request->input('ck_address');
            $replay_msg= $request->input('replay_msg');
            $gsk_code_temp=explode(',',$gsk_code);
            $count=count($gsk_code_temp);
            for($i=0;$i<$count;$i++){
                WeixiuApply::insert([
                    'gsk_code' => $gsk_code_temp[$i],
                    'city_id' => $city_id,
                    'status' => 4,
                    'quyu_id' => $user_id,
                    'fault_date' => $fault_date,
                    'fault_body' => $fault_body,
                    'fault_case' => $fault_case,
                    'fault_img' => $fault_img,
                    'weixiu_replay_style' => $weixiu_replay_style,
                    'fault_msg' => $fault_msg,
                    //   'ck_address' => $ck_address,
                    'replay_msg' => $replay_msg,
                    'created_at' => Helper::datetime()
                ]);
                Device::where('gsk_code', $gsk_code_temp[$i])->update([
                    'status' => 4,
                    'updated_at' => Helper::datetime()
                ]);

            }
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

            //区域维修记录-历史报修记录
            public function weixiu_history_list(Request $request)
            {
                $gsk_code=$request->input('gsk_code');
               /* $where = function ($q) use ($gsk_code) {
                    if ($gsk_code) {
                        $q->where('gsk', $gsk_code);
                    }
                }; */
                $list = WeixiuApply::where('gsk_code',$gsk_code)->where('flag','2')
                    ->orderByDesc('id')
                    ->get()
                    ->toArray();
                $response['data']=$list;
                $response['code']=0;
                return response($response);
            }


    //区域最新维修记录-详情
    public function nowdate_history_info(Request $request)
    {
        $user_id=$request->input('user_id');
        $limit = $request->input('limit');//分页条数
        $status=$request->input('status');
        $where = function ($q) use ($status) {
            if ($status) {
                $q->where('status', $status);
            }
        };
        $gsks=DB::select("select gsk_code from gsk_device where yq_manager_id='".$user_id."'");
        $gids='';
        for($i=0;$i<count($gsks);$i++){
            $gids.=','.$gsks[$i]->gsk_code;
        }
        $ssl=ltrim($gids,',');
        $ssl=explode(',',$ssl);
        $cf=DB::select('select max("id") as id  from "gsk_weixiu_apply" group by "gsk_code" having (count("gsk_code") > 0)');
        $cf_ids='';
        for($i=0;$i<count($cf);$i++){
            $cf_ids.=','.$cf[$i]->id;
        }
        $ss=ltrim($cf_ids,',');
        $ids=explode(',',$ss);
        $list = WeixiuApply::where($where)->whereIn('gsk_code',$ssl)
            ->whereIn('id',$ids)
            ->with(['city','employee','device'])
            ->paginate()
            //  ->get()
            ->toArray();
        $response['data']=$list;
        return response($response);
    }

    //临时文件
    private function genTempFilePath($file_name,$temp_name){
        $temp_dir=env('upload_temp_dir');
        if(!is_dir($temp_dir)) {
            mkdir($temp_dir, 0700, true);
             }
            $ext='';
            if(preg_match('/.*(\.\w+)$/',$file_name,$m)){
                $ext=$m[1];
            }
            $ret=$temp_dir.DIRECTORY_SEPARATOR.basename($temp_name).$ext;
            dd($ret);
            return $ret;
    }


    //保存图片
    function file_exists_S3(Request $request)
    {
        if (!$request->hasFile("file")) {
            return ['success' => false, 'msg' => '上传文件为空'];
        }
        $file = $request->file('file');
        //判断文件上传过程中是否出错
        if (!$file->isValid()) {
            return ['success' => false, 'msg' => '文件上传出错'];
        }
        $fileSize = ceil($file->getClientSize() / 1024);
        $fileExt = $file->getClientOriginalExtension();
        $fileName = $file->getClientOriginalName();
        $sn = 'gsk';
        if ($fileExt) {
            $fileExt = strtolower($fileExt);
        }
    //    $path = 'food/' . date('Ymd') . '/';
        $path = 'upload/' . date('Ymd-His') .'-'. rand(1000,9999).'/';
        $tempName = $sn . '.' . $fileExt; //重命名
        // 临时存储文件夹
        $storagePath = public_path($path);
        // 创建目录
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }
        //本地储存
        $file->move($storagePath, $tempName);
      //  $order->url = $path . $tempName;
      //  $order->url_type = 2;
      //  $result = $order->save();
        $url = $path . $tempName;
     //   $urls="https://gsk.api.fmcgbi.com/".$url;
       // $res=DB::insert('insert into gsk_img (url)  values ("'.$urls.'") RETURNING id');
        //dd($res);
      /*  $res=Img::insert([
            'url' =>$urls,
            'created_at' => Helper::datetime()
        ]);l
        $file_id=db::select('select id from gsk_img where url="'.$urls.'"');
        $fid=$file_id[0]->id;  */
        $urls=$url;
        $response['data']=$urls;
        $response['code']=0;
        return response($response);

    }

    //通过gsk_code
    public function search_gsk_code(Request $request)
    {
        try {
            $code=$request->input('code');
            $list = Device::where('code',$code)
                ->select('gsk_code')
                ->orderByDesc('id')
                ->get();
            $response['data']=$list;
            $response['code']=0;
            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }


    //维修公司维修
    function weixiu_company_check(Request $request)
    {
        try {
            $weixiu_city_id = $request->input('weixiu_city_id');
            $device_status = $request->input('device_status');
            $now_component = $request->input('now_component');
            $w_fault_body = $request->input('fault_body');
            $gsk_code = $request->input('gsk_code');
            $user_id = $request->input('user_id');
            $fault_part = $request->input('fault_part');
            $w_fault_case = $request->input('fault_case');
            $other_case = $request->input('other_case');

         /*   $res=DB::select("select id from gsk_weixiu_apply where device_status=9 and gsk_code='".$gsk_code."'");
            if($res){
                return response(ReturnCode::error('100', '此仪器已维修结束不要重复维修'));
            } */


            $list11 = WeixiuApply::where('gsk_code',$gsk_code)->where('submit_status',1)
                ->orderByDesc('id')
                ->limit(1)
                ->get()
                ->toArray();
            if(count($list11)>0){
                return response(ReturnCode::error('100', '待区域负责人审核提交后才能维修'));
            }
        /*    $list_s = WeixiuApply::where('gsk_code',$gsk_code)->whereIn('device_status',[7,8])->where('flag',2)
                ->orderByDesc('id')
                ->get()->toArray();

            if(count($list_s)>0){
                return response(ReturnCode::error('100', '请勿重复提交已维修'));
            } */

            $list1 = WeixiuApply::where('gsk_code',$gsk_code)->where('submit_status',2)
                ->orderByDesc('id')
                ->limit(1)
                ->get()
                ->toArray();
            if($list1){
                $apply_id=$list1[0]['id'];
                $apply_date=$list1[0]['apply_date'];
            }

            if($device_status==7 || $device_status==8){
                $flag=2;
            }else{
                $flag=1;
            }
            if($device_status==7){
                $result_status=1;//维修成功
            }elseif ($device_status==8){
                $result_status=2;//维修失败
            }else{
                $result_status=NULL;//
            }
            WeixiuApply::where('gsk_code', $gsk_code)->where('id',$apply_id)->update([
                'gsk_code' =>$gsk_code,
                'now_component' =>$now_component,
                'w_fault_body' =>$w_fault_body,
                'weixiu_uid' =>$user_id,
                'fault_part' =>$fault_part,
                'w_fault_case' =>$w_fault_case,
                'device_status' =>$device_status,
                'apply_id' =>$apply_id,
                'apply_date' =>$apply_date,
                'other_case' =>$other_case,
                'weixiu_city_id' =>$weixiu_city_id,
                'flag'=>$flag,
                'is_show'=>1,
                'result_status'=>$result_status,
                'updated_at' => Helper::datetime()
            ]);
            Device::where('gsk_code', $gsk_code)->update([
                'real_city'=>$weixiu_city_id,
                'updated_at' => Helper::datetime()
            ]);




            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
}
