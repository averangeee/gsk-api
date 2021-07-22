<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/21
 * Time: 13:26
 */

namespace App\Http\Controllers\WebApi;
use Maatwebsite\Excel\Facades\Excel;
use App\Libs\Helper;
use App\Exceptions\Handler;
use App\Http\Controllers\MhController;
use App\Libs\ApiIot\ApiIotUtil;
use App\Libs\ReturnCode;
use App\Models\GSK\Device;
use App\Models\GSK\Ck;
use App\Models\GSK\AllocationLog;
use App\Models\GSK\WeixiuApply;
use App\Models\GSK\City;
use App\Models\GSK\Brand;
use App\Models\GSK\Employee;
use App\Models\Shop\SkuImg;
use App\Models\System\Attachment;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Null_;

class DeviceController extends Controller
{
    //仓库列表
    public function ck_list(Request $request)
    {
        $limit = $request->input('limit', 10);
        $keyword = $request->input('keyword');

        $where = function ($query) use ($keyword) {
            if (!empty($keyword)) {
                $query->where('name', 'like','%'.$keyword.'%')->orWhere('address','like','%'.$keyword.'%');;
            }
        };
        $list = Ck::where($where)
            ->with(['city'])
            ->orderByDesc('id')
            ->paginate($limit)
            ->toArray();
     //   return response($list);
        $response['data']=$list['data'];
        $response['total']=$list['total'];
        $response['code']=ReturnCode::SUCCESS;

        return response($response);

    }



    /**
     * @des 删除
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function ck_delete(Request $request)
    {
        try {
            $id = $request->input('id');
            $res = Ck::find($id);
            if (!$res) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST));
            }
            $res->deleted_at = Helper::datetime();
            $res->save();
            $res->delete();
            return response(ReturnCode::success([], '成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //新增仓库
    public function ck_add(Request $request)
    {
        $name = $request->input('name');
        $city = $request->input('city');
        $contact_name = $request->input('contact_name');
        $phone = $request->input('phone');
        $address = $request->input('address');
     //   $created_at=date('Y-m-d H:i:s');
        try {
            $ck=Ck::where('name',$name)->first();
            if($ck){
                return response(ReturnCode::error(1001,'该仓库名称已存在'));
            }else{
                Ck::insert([
                    'name'=>$name,
                    'address'=>$address,
                    'city'=>$city,
                    'contact_name'=>$contact_name,
                    'phone'=>$phone,
                //    'created_at'=>$created_at
                    'created_at'=>Helper::datetime()
                ]);

                return response(ReturnCode::success([], '成功'));
            }


        } catch (\Exception $exception) {
         //   Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //修改仓库
    public function ck_edit(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $city = $request->input('city');
        $contact_name = $request->input('contact_name');
        $phone = $request->input('phone');
        $address = $request->input('address');
        try {
            $device = Ck::find($id);
            $device->name = $name;
            $device->address = $address;
            $device->city = $city;
            $device->contact_name = $contact_name;
            $device->phone = $phone;
            $device->save();
            return response(ReturnCode::success([], '成功'));

        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //设备列表
    public function device_list(Request $request)
    {
        $limit = $request->input('limit', 10);
        $keyword = $request->input('keyword');
        $city_id = $request->input('city_id');
        $address_id = $request->input('address_id');
        $ck_name=$request->input('ck_name');
        $yq_manager_id=$request->input('yq_manager_id');
        $status=$request->input('status');
        $area=$request->input('area');
        $channel=$request->input('channel');

        $where = function ($query) use ($keyword,$city_id,$address_id,$ck_name,$yq_manager_id,$status,$area,$channel) {
            if (!empty($keyword)) {
                $query->where('brand','like','%'. $keyword.'%')
                    ->orwhere('name','like','%'.$keyword.'%')
                    ->orwhere('code','like','%'.$keyword.'%')
                    ->orwhere('gsk_code','like','%'.$keyword.'%');

            }
            if(!empty($city_id)){
                $query->where('city_id',$city_id);
            }
            if(!empty($address_id)){
                $query->where('address_id',$address_id);
            }
            if(!empty($yq_manager_id)){
                $query->where('yq_manager_id',$yq_manager_id);
            }
            if(!empty($ck_name)){
                $query->where('ck_name',$ck_name);
            }


            if(!empty($status)){
                    $query->where('status',$status);

            }




            if(!empty($area)){
                $query->where('area',$area);
            }
            if(!empty($channel)){
                $query->where('channel',$channel);
            }
        };

        DB::enablequerylog();
        $list = Device::where($where)
            ->with(['city','address','employee','provice'])
            ->orderByDesc('id')
            ->paginate($limit)
            ->toArray();
        Log::info(DB::getquerylog($list));
        //   return response($list);
    /*    $result_count=DB::select('SELECT
                                    SUM(CASE WHEN status = "1"  THEN 1 ELSE 0 END) as used,
                                    SUM(CASE WHEN status = "2"  THEN 1 ELSE 0 END) as accept,
                                    SUM(CASE WHEN status = "3"  THEN 1 ELSE 0 END) as weixiu,
                                    SUM(CASE WHEN status = "4"  THEN 1 ELSE 0 END) as weixiu,
                                    SUM(CASE WHEN status = "5"   THEN 1 ELSE 0 END) as weixiu,
                                    SUM(CASE WHEN status = "6"  THEN 1 ELSE 0 END) as weixiu,
                                    FROM gsk_device
                                ');  */
        $response['data']=$list['data'];
        $response['total']=$list['total'];
        $response['code']=ReturnCode::SUCCESS;

        return response($response);

    }

    //新增设备
    public function device_add(Request $request)
    {
        $name = $request->input('name');
        $provice_id = $request->input('provice_id');
        $brand = $request->input('brand');
        $models = $request->input('models');
        $type = $request->input('type');
        $area = $request->input('area');
        $channel = $request->input('channel');
        $city_id = $request->input('city_id');
        $code = $request->input('code');
     //   $yq_manager = $request->input('yq_manager');
        $yq_manager_id = $request->input('yq_manager_id');
        $gsk_code = $request->input('gsk_code');
      //  $region = $request->input('region',Null);
        $tt_num = $request->input('tt_num');
        $product_date = $request->input('product_date');
        $ck_name = $request->input('ck_name');
        $address_id = $request->input('address_id');
        $cg_date = $request->input('cg_date');
      //  $manger = $request->input('manger',Null);
      //  $department = $request->input('department',Null);
        $phone = $request->input('phone');
        if($ck_name==1){
            $status=9;
        }else{
            $status=1;
        }
        if(!$city_id && !$provice_id){
            return response(ReturnCode::error(100,'省份和城市必填一个'));
        }
        if($city_id){
            $city=DB::select("select pid ,city_name from gsk_city where id='".$city_id."'");
            if($city){
                $provice_id =$city[0]->pid; //
                $real_city =$city[0]->city_name; //
            }
        }else{

        }



        try {
            $device=Device::where('code',$code)->first();
            if($device){
                return response(ReturnCode::error(1001,'该仪器编码已存在'));
            }else{
                Device::insert([
                    'name'=>$name,
                    'brand'=>$brand,
                    'models'=>$models,
                    'type'=>$type,
                    'area'=>$area,
                    'channel'=>$channel,
                    'city_id'=>$city_id,
                    'provice_id'=>$provice_id,
                    'code'=>$code,
                    //'region'=>$region,
                    'yq_manager_id'=>$yq_manager_id,
                    'gsk_code'=>$gsk_code,
                    'tt_num'=>$tt_num,
                    'product_date'=>$product_date,
                    'ck_name'=>$ck_name,
                    'address_id'=>$address_id,
                    'cg_date'=>$cg_date,
                 //   'manger'=>$manger,
                //    'department'=>$department,
                    'real_city'=>$real_city,
                    'phone'=>$phone,
                    'status'=>$status,
                    'created_at'=>Helper::datetime()
                ]);
                return response(ReturnCode::success([], '成功'));
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //修改设备
    public function device_edit(Request $request)
    {
        $id = $request->input('id');
        $provice_id = $request->input('provice_id');
        $name = $request->input('name');
        $brand = $request->input('brand');
        $models = $request->input('models');
        $type = $request->input('type');
        $area = $request->input('area');
        $channel = $request->input('channel');
        $city_id = $request->input('city_id');
        $code = $request->input('code');
        $yq_manager_id = $request->input('yq_manager_id');
        $gsk_code = $request->input('gsk_code');
      //  $region = $request->input('region');
        $tt_num = $request->input('tt_num');
        $product_date = $request->input('product_date');
        $ck_name = $request->input('ck_name');
        $address_id = $request->input('address_id');
        $cg_date = $request->input('cg_date');
    //    $manger = $request->input('manger');
    //    $department = $request->input('department');
        $phone = $request->input('phone');
        if($ck_name==1){
            $status=0;
        }else{
            $status=1;
        }
        try {
            $device = Device::find($id);
            $device->provice_id = $provice_id;
            $device->name = $name;
            $device->brand = $brand;
            $device->models = $models;
            $device->type = $type;
            $device->area = $area;
            $device->channel = $channel;
            $device->city_id = $city_id;
            $device->code = $code;
         //   $device->region = $region;
            $device->yq_manager_id = $yq_manager_id;
            $device->gsk_code = $gsk_code;
            $device->tt_num = $tt_num;
            $device->product_date = $product_date;
            $device->ck_name = $ck_name;
            $device->address_id = $address_id;
            $device->cg_date = $cg_date;
        //    $device->manger = $manger;
         //   $device->department = $department;
            $device->phone = $phone;
            $device->status = $status;
            $device->save();
            return response(ReturnCode::success([], '成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //删除设备

    public function device_delete(Request $request)
    {
        try {
            $id = $request->input('id');
            $res = Device::find($id);
            if (!$res) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST));
            }
            DB::delete('delete from gsk_device where id='.$id);
          //  $res->delete();
            return response(ReturnCode::success([], '成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //调拨记录
    public function allocation_list(Request $request)
    {
        $limit = $request->input('limit', 10);
        $user_id=$request->input('user_id');
        $allocation_date = $request->input('allocation_date');
        $status = $request->input('status');
        $operater = $request->input('operater');

          $where = function ($query) use ($allocation_date,$status,$operater) {
               if (!empty($allocation_date)) {
                   $query->where('allocation_date', $allocation_date);
               }
               if (!empty($status)) {
                   $query->where('status', $status);
               }
               if (!empty($operater)) {
                   $query->where('operater', $operater);
               }
           };
        /*    $list = AllocationLog::where($where)->where('user_id',$user_id)
               ->get()->toArray();
           return response(ReturnCode::success($list, 'success')); */

        $cf=DB::select('select max("id") as id  from "gsk_allocation_log" group by "gsk_code" having (count("gsk_code") > 0)');
        //   $cf=DB::select("select  id  from `gsk_weixiu_apply` group by `gsk_code` )");
        $cf_ids='';
        for($i=0;$i<count($cf);$i++){
            $cf_ids.=','.$cf[$i]->id;
        }
        $ss=ltrim($cf_ids,',');
        $ids=explode(',',$ss);
            $list = AllocationLog::where($where)
                ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_allocation_log.gsk_code')
                ->whereIn('gsk_allocation_log.id',$ids)
                ->with(['employee'])
                ->paginate($limit)
                ->toArray();
        $response['data']=$list;
        $response['code']=0;
        $response['total']=count($list);
        return response($response);

    }

    //调拨记录详情
    public function allocation_Infolist(Request $request)
    {
        $limit = $request->input('limit', 10);
        $user_id=$request->input('user_id');
        $gsk_code=$request->input('gsk_code');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
       /* $list = AllocationLog::where('gsk_code',$gsk_code)
            ->with(['city','employee'])
            ->orderByDesc('id')
            ->get()
            ->toArray();

        $cf=DB::select("select max(`id`) as id  from `gsk_weixiu_apply` group by `gsk_code` having (count(`gsk_code`) > 0)");
        //   $cf=DB::select("select  id  from `gsk_weixiu_apply` group by `gsk_code` )");
        $cf_ids='';
        for($i=0;$i<count($cf);$i++){
            $cf_ids.=','.$cf[$i]->id;
        }
        $ss=ltrim($cf_ids,',');
        $ids=explode(',',$ss); */
        $list = AllocationLog::where($where)
         //   ->whereIn('allocation_log.id',$ids)
            ->with(['employee'])
            ->paginate($limit)
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //维修记录详情
    public function weixiu_Infolist(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        /*  $where = function ($q) use ($gsk_code) {
              if ($gsk_code) {
                  $q->where('gsk_code', $gsk_code);
              }
          }; */
        $list = WeixiuApply::where('gsk_code',$gsk_code)
            ->with(['city','employee'])
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //维修记录
    public function weixiu_log_list(Request $request)
    {
        // $limit = $request->input('limit', 10);
        /*    $allocation_date = $request->input('allocation_date');
          $status = $request->input('status');
          $operater = $request->input('operater');

          $list = WeixiuApply::where('deleted_at','=',null)
              ->orderByDesc('id')
              ->paginate($limit)
              ->toArray();
          //   return response($list);
          $response['data']=$list['data'];
          $response['total']=$list['total'];
          $response['code']=ReturnCode::SUCCESS;

          return response($response); */

        $limit = $request->input('limit', 10);
        $status=$request->input('status');
        $where = function ($q) use ($status) {
            if ($status) {
                $q->where('status', $status);
            }
        };
        $cf=DB::select('select max("id") as id  from "gsk_weixiu_apply" group by "gsk_code" having (count("gsk_code") > 0)');
        $cf_ids='';
        for($i=0;$i<count($cf);$i++){
            $cf_ids.=','.$cf[$i]->id;
        }
        $ss=ltrim($cf_ids,',');
        $ids=explode(',',$ss);
        $list = WeixiuApply::where($where)
          ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_weixiu_apply.gsk_code')
            ->whereIn('gsk_weixiu_apply.id',$ids)
            ->with(['city','employee'])
            ->paginate($limit)
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        $response['total']=count($list);
        return response($response);


    }

    //城市列表
    public function city_list(Request $request)
    {
        $list = City::where('deleted_at','=',null)
            ->whereNotIn('pid',[452,453,454,455])
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //根据省份获取城市
    public function city_by_province(Request $request)
    {
        $pid = $request->input('pid');
        $list = City::where('deleted_at','=',null)
            ->where('pid',$pid)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //区域市场人员列表
    public function market_list(Request $request)
    {
        $list = Employee::where('deleted_at','=',null)
            ->where('role_id','=',1)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        return response($response);
    }

    //品牌列表
    public function brand_list(Request $request)
    {
        $limit = $request->input('limit', 10);
        $keyword = $request->input('keyword');

        $where = function ($query) use ($keyword) {
            if (!empty($keyword)) {
                $query->where('name', $keyword);
            }
        };
        $list = Brand::where($where)
            ->orderByDesc('id')
            ->paginate($limit)
            ->toArray();
        //   return response($list);
        $response['data']=$list['data'];
        $response['total']=$list['total'];
        $response['code']=ReturnCode::SUCCESS;

        return response($response);

    }



    /**
     * @des 删除
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */

    //删除品牌
    public function brand_delete(Request $request)
    {
        try {
            $id = $request->input('id');
            $res = Brand::find($id);
            if (!$res) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST));
            }
            $res->deleted_at = Helper::datetime();
            $res->save();
            $res->delete();
            return response(ReturnCode::success([], '成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //新增品牌
    public function brand_add(Request $request)
    {
        $name = $request->input('name');
       // $address = $request->input('address');
        try {
            $ck=Brand::where('name',$name)->first();
            if($ck){
                return response(ReturnCode::error(1001,'该名称已存在'));
            }else{
                Brand::insert([
                    'name'=>$name,
                 //   'address'=>$address,
                    'created_at'=>Helper::datetime()
                ]);

                return response(ReturnCode::success([], '成功'));
            }


        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //编辑品牌
    public function brand_edit(Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
    //    $address = $request->input('address');
        try {
            $device = Brand::find($id);
            $device->name = $name;
         //   $device->address = $address;
            $device->save();
            return response(ReturnCode::success([], '成功'));

        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //导入设备列表
    function export_device(Request $request)
    {
        try {
            $list1 = Device::orderByDesc('id')
                ->get()
                ->toArray();
            $total1=count($list1);
            ini_set('memory_limit', '3072M');
            set_time_limit(0);
            $file = $_FILES;

            $excel_file_path = $file['file']['tmp_name'];

            $res = [];

            Excel::load($excel_file_path, function ($reader) use (&$res) {
                $reader = $reader->getSheet(0);
                $res = $reader->toArray();

            });

            $len = count($res);
            $j = 0;
            $k = 0;
            $data = [];
            if (!stripos($res[1][13], '-')) {
                return response(ReturnCode::error('102', '请检查excel中的生产日期的日期格式'));
            }
            if (!stripos($res[1][16], '-')) {
                return response(ReturnCode::error('102', '请检查excel中采购时间的日期格式'));
            }

            for ($i = 1; $i < $len; $i++) {
                $data[$j]['name'] = $res[$i][0];//仪器名称
                $data[$j]['brand'] = $res[$i][1];//仪器品牌
                $data[$j]['models'] = $res[$i][2];//仪器型号
                $data[$j]['type'] = $res[$i][3];//类别
                $data[$j]['area'] = $res[$i][4];//所属大区
                $data[$j]['channel'] = $res[$i][5];//城市
                $data[$j]['provice_temp'] = $res[$i][6];//城市
            //    $data[$j]['provice_id'] = $res[$i][6];//城市
                $data[$j]['city_temp'] = $res[$i][7];//城市
                $data[$j]['real_city'] = $res[$i][7];//城市
                $data[$j]['code'] = $res[$i][8];//骨密仪器编码
                $data[$j]['yq_manager'] = $res[$i][9];//仪器负责人
                $data[$j]['phone'] = substr($res[$i][10],-11); //联系电话
                $data[$j]['gsk_code'] = $res[$i][11]; //gsk编码
                $data[$j]['tt_num'] = $res[$i][12]; //探头编号
                $data[$j]['product_date'] = $res[$i][13]; //生产日期
                $data[$j]['ck_temp'] = $res[$i][14]; //所属仓库
                $data[$j]['content'] = $res[$i][15]; //（收货地址）仓库地址
                $data[$j]['cg_date'] = $res[$i][16]; //采购时间






                if(strpos($data[$j]['content'],"总部")!==false){

                    $data[$j]['status'] = 9; //采购时间
                    $data[$j]['ck_name'] = 1; //采购时间
                }
                else if(strpos($res[$i][15],"区域")!==false){
                    $data[$j]['status'] = 1; //采购时间
                    $data[$j]['ck_name'] = 2; //采购时间
                }else{
                    return response(ReturnCode::error('100', '第'.$i.'行请填入正确的仪器所属地'));
                }


               /* if ($j % 50 == 0 && $j > 0) {
                    DB::table('gsk_device')->insert($data);
                    $j = 0;
                    $data = [];
                }
                $j++;
                $k++; */
                $gsk_code=DB::select("select id from gsk_device where gsk_code='". $res[$i][11]."'");
                if(count($gsk_code)>0){
                    //有这条数据不插入
                    unset($res[$i]);
                }else{
                    //没有插入数据
                 //   DB::table('gsk_device')->insert($data);
                    $yq_manager_id=DB::select("select id from gsk_employee where employee_name='".$res[$i][9]."'");
                    if($yq_manager_id){
                        $yqmanager_id=$yq_manager_id[0]->id;
                        $data[$j]['yq_manager_id'] = $yqmanager_id; //
                    }else{
                        return response(ReturnCode::error('100', '第'.$i.'行请填入正确的仪器负责人'));
                    }
                    $ck_id=DB::select("select id from gsk_ck where name='".$res[$i][14]."'");
                    if($ck_id){
                        $address_id=$ck_id[0]->id;
                        $data[$j]['address_id'] = $address_id; //
                    }else{
                        return response(ReturnCode::error('100', '第'.$i.'行请填入正确的所属仓库'));
                    }
                    //有城市选城市
                    if(!$res[$i][6] && !$res[$i][7]){
                        return response(ReturnCode::error('100', '第'.$i.'行请填上正确的省份或者城市'));
                    }
                    if($res[$i][7]){
                        $ck_city=DB::select("select id,pid from gsk_city where city_name='".$res[$i][7]."'");
                        if($ck_city){
                            $city_id=$ck_city[0]->id;
                            $data[$j]['city_id'] = $city_id; //
                            $data[$j]['provice_id'] =$ck_city[0]->pid; //
                        }
                    }else{
                        $provice=DB::select("select id from gsk_city where pid in (452,453,454,455) and  provice_name='".$res[$i][6]."'");
                        if($provice){
                            $data[$j]['city_id'] = NULL; //
                            $data[$j]['provice_id'] =$provice[0]->id; //
                        }

                    }
                  /*  $ck_city=DB::select("select id from gsk_city where city_name='".$res[$i][7]."'");
                   if($ck_city){
                        $city_id=$ck_city[0]->id;
                        $data[$j]['city_id'] = $city_id; //
                    }else{
                        return response(ReturnCode::error('100', '第'.$i.'行请填入正确的城市'));
                    } */
                    DB::table('gsk_device')->insert($data);
                    unset($data);
                }
            }


            $list2 = Device::orderByDesc('id')
                ->get()
                ->toArray();
            $total2=count($list2);
            $response['total'] =$len-1;
            $response['sum'] =$total2-$total1;
            return response(ReturnCode::success($response));
        } catch (\Exception $exception) {
            return response(ReturnCode::error('101', '请检查excel格式'));
        }

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

    //获取地图数据
    public function get_map_list(Request $request)
    {

        //涉及的省份
        $relatp=0;
        //设计城市
        $rcity=DB::select('select real_city   from  gsk_device  GROUP BY (real_city  )');
        $recity=count($rcity);
       //总台数
        $rtotal=DB::select('select count(*) as Atotal  from  gsk_device  where status!=9');
        $Atotal=$rtotal[0]->Atotal;

        $provice = DB::select('select  c.provice_name , sum(c.total) as p_total from (select a.provice_name ,b.real_city,b.total  from dbo.gsk_city  as  a  ,(select real_city ,count(id) as total from dbo.gsk_device  GROUP BY real_city  ) as b  where a.city_name=b.real_city) as c GROUP BY c.provice_name');
         $pids="";

        if($provice){
            $relatp=count($provice);

               for($i=0;$i<count($provice);$i++){
                $pid_temp=DB::select("select id from dbo.gsk_city where provice_name='".$provice[$i]->provice_name."'  and pid in (452,453,454,455)");
                if($pid_temp){
                  $pids.=','.$pid_temp[0]->id;
                }
            }
            $pids=ltrim($pids,',');
            $pids=explode(',',$pids);
           // dd($pids);
          //  $all_provice=DB::select('select provice_name from gsk_city where pid=0 and pid not in ("'.$pids.'")');
            $list_s= City::whereIn('pid',[452,453,454,455])->whereNotIn('id',$pids)
            ->select('provice_name','p_total')
                ->get()
                ->toArray();

            $list=[];
            for($i=0;$i<count($provice);$i++){
                $list[$i]['provice_name']=$provice[$i]->provice_name;
                $list[$i]['p_total']=$provice[$i]->p_total;

            }
            $list_all=array_merge($list,$list_s);
            $last_names = array_column($list_all,'p_total');
            array_multisort($last_names,SORT_DESC,$list_all);

            foreach ($list_all as $k => $v) {
                $list_all[$k]['children'] = DB::select("select c.real_city , c.total from  (select a.provice_name ,b.real_city,b.total  from (select real_city ,count(id) as total from dbo.gsk_device  GROUP BY real_city  ) as b  ,gsk_city  as  a   where b.real_city=a.city_name) as c where c.provice_name='".$v['provice_name']."'");

            }
       }
        else{
            $list_all=[];
        }
        $response['data']=$list_all;
        $response['code']=0;
        $response['provice']=$relatp;
        $response['city']=$recity;
        $response['total']=$Atotal;
        return response($response);
    }

    //区域分布
    public function tjbyarea(Request $request)
    {

        $res=DB::select('select gsk_device.area   AS area ,count(gsk_device.area) AS number  from gsk_device  group by gsk_device.area');
        $response['data']=$res;
        $response['code']=0;
        return response($response);
    }
    //渠道分布统计
    public function tjbychannel(Request $request)
    {
        $res=DB::select('select gsk_device.channel   AS channel ,count(gsk_device.channel) AS number  from gsk_device  group by gsk_device.channel');
        $response['data']=$res;
        $response['code']=0;
        return response($response);
    }

    //状态统计分布
    public function tjbystatus(Request $request)
    {
        $res=DB::select('select gsk_device.status   AS status ,count(gsk_device.status) AS number  from gsk_device  group by gsk_device.status');
        $temp=[];
        if($res){
            for($i=0;$i<count($res);$i++){
                $status=$res[$i]->status;
                if($status==9){
                    $temp[$i]['status']='总部';
                }elseif ($status==1){
                    $temp[$i]['status']='区域仓库';
                }elseif ($status==2){
                    $temp[$i]['status']='调拨中';
                }elseif ($status==3){
                    $temp[$i]['status']='执行方';
                }elseif ($status==4){
                    $temp[$i]['status']='报修-执行公司';
                }elseif ($status==5){
                    $temp[$i]['status']='报修-自提';
                }elseif ($status==6){
                    $temp[$i]['status']='仪器变更';
                }elseif ($status==8){
                    $temp[$i]['status']='报废';
                }else{
                    $temp[$i]['status']='暂无状态';
                }
                $temp[$i]['count']=$res[$i]->number;
            }
        }else{
            $temp=[];
        }
        $response['data']=$temp;
        $response['code']=0;
        return response($response);
    }

    //故障原因占比
    public function tjbycase(Request $request)
    {
        $temp=[];
        $res1=DB::select('select count(*) as stotal from gsk_weixiu_apply where w_fault_case is not NULL');
        if($res1){
            $stotal=$res1[0]->stotal;
        }
        $res=DB::select('select gsk_weixiu_apply.w_fault_case   as  fcase ,count(gsk_weixiu_apply.w_fault_case) AS number  from gsk_weixiu_apply  where gsk_weixiu_apply.w_fault_case is not NULL  group by gsk_weixiu_apply.w_fault_case');
        if($res){
            for($i=0;$i<count($res);$i++){
                $fcase=$res[$i]->fcase;
               /* if($fcase==1){
                    $fcase='部件老化';
                }else if ($fcase==2){
                    $fcase='人为损坏';
                }else if ($fcase==3){
                    $fcase='系统问题';
                }else if ($fcase==4){
                    $fcase='软件问题';
                }else if ($fcase==5){
                    $fcase='其他原因';
                } */

                if($fcase==1){
                    $fcase='无法开机';
                }else if ($fcase==2){
                    $fcase='系统损坏';
                }else if ($fcase==3){
                    $fcase='整机损坏';
                }else if ($fcase==4){
                    $fcase='配件遗失';
                }else if ($fcase==5){
                    $fcase='其他';
                }

                $po=$res[$i]->number;
                $temp[$i]['fcase']=$fcase;
                $temp[$i]['pcent']=round($po / $stotal , 2);

            }
        }
        else{
            $temp=[];
    }
        $response['data']=$temp;
        $response['code']=0;
        return response($response);
    }
    //故障次数
    public function tjbycase_count(Request $request)
    {
        $year=$request->input('year');
        $year_end=date('Y',strtotime("$year+1year"));
        $temp=[];
     /*   $res1=DB::select('select count(*) as stotal from gsk_weixiu_apply where w_fault_case is not NULL');
        if($res1){
            $stotal=$res1[0]->stotal;
        } */
        $res=DB::select("select gsk_weixiu_apply.w_fault_case   as  fcase ,count(gsk_weixiu_apply.w_fault_case) AS number  from gsk_weixiu_apply  where gsk_weixiu_apply.w_fault_case is not NULL and updated_at>='".$year."' and updated_at<'".$year_end."'  group by gsk_weixiu_apply.w_fault_case");

        if($res){
            for($i=0;$i<count($res);$i++){
                $fcase=$res[$i]->fcase;
              /*  if($fcase==1){
                    $fcase='部件老化';
                }else if ($fcase==2){
                    $fcase='人为损坏';
                }else if ($fcase==3){
                    $fcase='系统问题';
                }else if ($fcase==4){
                    $fcase='软件问题';
                }else if ($fcase==5){
                    $fcase='其他原因';
                }*/
                if($fcase==1){
                    $fcase='无法开机';
                }else if ($fcase==2){
                    $fcase='系统损坏';
                }else if ($fcase==3){
                    $fcase='整机损坏';
                }else if ($fcase==4){
                    $fcase='配件遗失';
                }else if ($fcase==5){
                    $fcase='其他';
                }

              //  $po=$res[$i]->number;
                $temp[$i]['fcase']=$fcase;
                $temp[$i]['number']=$res[$i]->number;
             //   $temp[$i]['pcent']=round($po / $stotal , 2);

            }
        }
        else{
            $temp=[];
        }
        $response['data']=$temp;
        $response['code']=0;
        return response($response);
    }

    //手动置为报废
    function resetbf(Request $request)
    {
        try {
            $gsk_code = $request->input('gsk_code');
            $id = $request->input('id');
            $bf_msg = $request->input('bf_msg');

            $list_s= Device::where('gsk_code',$gsk_code)->whereIn('status',[1,9])
                ->get()
                ->toArray();
            if(count($list_s)<0){
                return response(ReturnCode::error('100', '该设备正在使用请勿置为报废'));

            }

                Device::where('gsk_code', $gsk_code)->update([
                    'status' => 8, //置为报废状态
                    'bf_msg' => $bf_msg,
                    'updated_at' => Helper::datetime()
                ]);
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //维修成功或者失败占比
    public function result_percent(Request $request)
    {
        $res=DB::select('select gsk_weixiu_apply.result_status   AS status ,count(gsk_weixiu_apply.result_status) AS number  from gsk_weixiu_apply  where result_status is not NULL  group by gsk_weixiu_apply.result_status');
        $temp=[];
        if($res){
            for($i=0;$i<count($res);$i++){
                $status=$res[$i]->status;
                if($status==1){
                    $temp[$i]['status']='维修成功';
                }elseif ($status==2){
                    $temp[$i]['status']='维修失败';
                }else{
                    $temp[$i]['status']='暂无状态';
                }
                $temp[$i]['count']=$res[$i]->number;
            }
        }else{
            $temp=[];
        }
        $response['data']=$temp;
        $response['code']=0;
        return response($response);
    }



}
