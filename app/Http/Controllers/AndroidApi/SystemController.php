<?php

namespace App\Http\Controllers\AndroidApi;

use App\Models\GSK\AllocationLog;
use App\Models\GSK\WeixiuApply;
use App\Models\Token;
use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\GSK\Employee;
use App\Models\GSK\ManagerReset;
use App\Models\GSK\City;
use App\Models\GSK\Device;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Null_;

class SystemController extends BaseController
{
    //心跳统计
    function sync(Request $request)
    {
        $device_id = $request->input('device_id');
        $version = $request->input('version');

        if (!$device_id || !$version) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $detail = Device::where('iot_id', $device_id)->first();

        if ($detail) {
            //存在更新时间和版本
            $detail->version = $version;
            $detail->type = 2;
            $detail->line_at = Helper::datetime();
            $result = $detail->save();
        } else {
            //不存在新建
            $data['type'] = 2;
            $data['iot_id'] = $device_id;
            $data['device_name'] = '';
            $data['store_code'] = '';
            $data['device_code'] = $device_id;
            $data['line_at'] = Helper::datetime();
            $result = Device::insertGetId($data);
        }
        //处理结果
        if ($result) {
            Log::info($device_id . '同步成功' . Helper::datetime());
            return response(ReturnCode::success([], '同步成功'));
        } else {
            Log::info($device_id . '同步失败' . Helper::datetime());
            return response(ReturnCode::error(102, '同步失败'));
        }
    }

    //小程序登录
    function login(Request $request)
    {
        $login_name = $request->input('login_name');
        $pwd = $request->input('login_pwd');
        $lang = $request->input('lang', 'zhCN');
    //    $device_id = $request->input('device_id');

        Log::info(json_encode($request->input()));
        if (!$login_name || !$pwd ) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $employee = Employee::where('employee_code', $login_name)->where('flag',1)
            ->with(['city','role'])
            ->where('is_log', 1)->first();
        if($employee){
            if (!Hash::check($pwd, $employee->password)) {
                return response(ReturnCode::error(102, '密码错误'));
            }else{

                $data['data']['employee_name'] = $employee->employee_name;
                $data['data']['employee_code'] = $employee->employee_code;
                $data['data']['city_name'] = $employee->city['city_name'];
                $data['data']['region'] = $employee->region;
                $data['data']['area'] = $employee->area;
                $data['data']['user_id'] = $employee->id;
                $data['data']['role_id'] = $employee->role_id;
                $data['data']['role_name'] = $employee->role['name'];
                $data['data']['token']=Token::generate(0, $employee->id, $employee->employee_code, $lang);
                $data['code']=0;
                return response($data);
            }
        }else{
            return response(ReturnCode::error(102, '不存在该用户或者你的账号已被冻结'));
        }
    }
    //个人中心修改密码
    public function changePwd(Request $request)
    {
        try {
            $id=$request->input('user_id');
            $newPwd = $request->input('newPwd');
            $oldpwd = $request->input('oldpwd');
            $user = Employee::find($id);
            if (!$user) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

              if (!password_verify($oldpwd, $user->password)) {
                  return response(ReturnCode::error(ReturnCode::OLD_PASSWORD_NOT_MATCH));
              }

            $user->password = Hash::make($newPwd);
            $user->save();
            return response(ReturnCode::success([], '修改成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //个人中心执行方管理列表
    public function operater_list(Request $request)
    {
        $list = Employee::where('role_id',3)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        return response($response);
    }

    //编辑执行方
    public function edit(Request $request)
    {
        try {
            $id= $request->input('id');

            $employeeCode = $request->input('employee_code', null);
            $employeeName = $request->input('employee_name', null);
            $city_id = $request->input('city_id', null);
            $area = $request->input('area', null);
            $phone = $request->input('phone', null);
            $email = $request->input('email', null);
            $tel = $request->input('tel', null);
            $dept = $request->input('dept', null);
            $region = $request->input('region', null);
            $role_id = $request->input('role_id', null);
            $isSL = $request->input('is_sl', 1);
            $remark = $request->input('remark', null);
            $power_type = $request->input('power_type');
            $power_str = $request->input('power_str');

            $power_str = str_replace("，", ",", $power_str);
            $power_str = str_replace(" ", "", $power_str);
            //

            $employee = Employee::find($id);
            if (!$employee) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST, '人员不存在'));
            }

            //判断是否存在
            $count = Employee::where('employee_code', $employeeCode)->where('id', '<>', $id)->count();
            if ($count) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '编码已存在'));
            }

            DB::beginTransaction();

            Employee::where('id', $id)
                ->update([
                    'employee_code' => $employeeCode,
                    'employee_name' => $employeeName,
                    'city_id' => $city_id,
                    'area' => $area,
                    'phone' => $phone,
                    'region' => $region,
                    'tel' => $tel,
                    'dept' => $dept,
                    'role_id' => $role_id,
                    'is_sl' => $isSL,
                    'remark' => $remark,
                    'updated_code' => Token::$ucode,
                    'power_type' => $power_type,
                    'power_str' => $power_str
                ]);

            DB::commit();
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
      //个人中心新增执行方
    public function create(Request $request)
    {
        try {
            $employeeCode = $request->input('employee_code', null);
            $employeeName = $request->input('employee_name', null);
            $city_id = $request->input('city_id', null);
            $area = $request->input('area', null);
            $phone = $request->input('phone', null);
            $email = $request->input('email', null);
            $tel = $request->input('tel', null);
            $dept = $request->input('dept', null);
            $region = $request->input('region', null);
            $role_id = $request->input('role_id', null);
            $states = $request->input('states', 1);
            $isLogin = $request->input('is_log', 0);
            $isSL = $request->input('is_sl', 1);
            $remark = $request->input('remark', null);
            $power_type = $request->input('power_type');
            $power_str = $request->input('power_str');
            $flag=1;

            //判断是否存在
            $count = Employee::where('employee_code', $employeeCode)->count();
            if ($count) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '编码已存在'));
            }

            $sort = Employee::max('sort') + 1;


            DB::beginTransaction();

            Employee::create([
                'employee_code' => $employeeCode,
                'employee_name' => $employeeName,
                'password' => Hash::make(Employee::$pwd),
                'area' => $area,
                'city_id' => $city_id,
                'phone' => $phone,
                'region' => $region,
                'tel' => $tel,
                'dept' => $dept,
                'role_id' => 3,
                'is_sl' => $isSL,
                'sort' => $sort,
                'remark' => $remark,
                'created_code' => Token::$ucode,
                'power_type' =>1,
                'flag'=>1,
                'power_str' => $power_str
            ]);

            DB::commit();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //个人中心重置密码
    public function resetPwd(Request $request)
    {
        try {
            $id= $request->input('id');
        //    $oldPwd = $request->input('oldPwd', '');
        //    $newPwd = $request->input('newPwd', '88888888');
            $newPwd = '888888';
            $user = Employee::find($id);
            if (!$user) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

         /*   if (!password_verify($oldPwd, $user->password)) {
                return response(ReturnCode::error(ReturnCode::OLD_PASSWORD_NOT_MATCH));
            } */

            $user->password = Hash::make($newPwd);
            $user->save();
            return response(ReturnCode::success([], '修改成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }



    //冻结账号
    public function stop_user(Request $request)
    {
        try {
            $id=$request->input('id');
            $user = Employee::find($id);
            if (!$user) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

            /*  if (!password_verify($oldPwd, $user->password)) {
                  return response(ReturnCode::error(ReturnCode::OLD_PASSWORD_NOT_MATCH));
              }*/

            Employee::where('id', $id)->update([
                'flag' => 2,
                'updated_at' =>Helper::datetime()
            ]);

            return response(ReturnCode::success([], '冻结成功'));
        } catch (\Exception $exception) {
            dd($exception);
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //解封账号
    public function restart_user(Request $request)
    {
        try {
            $id=$request->input('id');
            $user = Employee::find($id);
            if (!$user) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

            /*  if (!password_verify($oldPwd, $user->password)) {
                  return response(ReturnCode::error(ReturnCode::OLD_PASSWORD_NOT_MATCH));
              }*/

            Employee::where('id', $id)->update([
                'flag' => 1,
                'updated_at' =>Helper::datetime()
            ]);

            return response(ReturnCode::success([], '解封成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //个人中心仪器入库获取仪器列表
    public function device_list(Request $request)
    {
        try {
            $gsk_code=$request->input('gsk_code');
            $list = Device::where('gsk_code',$gsk_code)
                ->with(['city','employee','address'])
                ->orderByDesc('id')
                ->get()
                ->toArray();
            $response['data']=$list;
            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
       //保存仪器入库保存
    public function save(Request $request)
    {
        try{
            $user_id=$request->input('user_id');
            $gsk_code=$request->input('gsk_code');
            $is_normal=$request->input('is_normal');
            $now_component=$request->input('now_component');
            if($gsk_code==null){
                return response(ReturnCode::error('100', '请填写完整信息'));
            }

            $list = Device::where('gsk_code',$gsk_code)->where('yq_manager_id',$user_id)->whereNotIn('status',[1,2,3,4,5,6,7])
            ->orderByDesc('id')
            ->get()
            ->toArray();

                if(count($list)<1){
                      return response(ReturnCode::error('100', '设备已入仓请勿重复操作'));
                }

            Device::where('gsk_code', $gsk_code)->update([
                'status' => 1,
                'gsk_code' =>$gsk_code,
                'is_normal' =>$is_normal,
                'now_component' =>$now_component,
                'updated_at' => Helper::datetime()
            ]);
            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    //仪器负责人变更
    public function manager_reset(Request $request)
    {
        try{
            $gsk_code=$request->input('gsk_code');
            $channel=$request->input('channel');
            $area=$request->input('area');
            $city_id=$request->input('city_id');
            $ck_id=$request->input('ck_id');
            $yq_manager_id=$request->input('yq_manager_id');
            $user_id=$request->input('user_id');
            $msg=$request->input('msg');
            if($gsk_code==null){
                return response(ReturnCode::error('100', '请填写完整信息'));
            }
            ManagerReset::insert([
                'gsk_code' =>$gsk_code,
                'user_id' => $user_id,
                'channel' => $channel,
                'ck_id' => $ck_id,
                'area' => $area,
                'city_id' => $city_id,
                'yq_manager_id' => $yq_manager_id,
                'msg' => $msg,
                'status' => 0,
                'created_at' => Helper::datetime()
            ]);
            Device::where('gsk_code', $gsk_code)->update([
                'gh_flag' => 1,
                'status'=>6,
              //  'yq_manager_id' => $yq_manager_id,
                'yq_manager_id_gh' => $yq_manager_id,
                'updated_at' => Helper::datetime()
            ]);
            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    //判断是否有变更消息
    public function biangeng_msg(Request $request)
    {
        try{
            $user_id=$request->input('user_id');
            $list = ManagerReset::where('user_id',$user_id)
                ->orWhere('yq_manager_id',$user_id)
                ->where('status','=',0)
                ->orderByDesc('id')
                ->get()->toArray();
            if($list){
                if(($list[0]['yq_manager_id']==$user_id)){//接收人
                    $reset_beford= Device::where('yq_manager_id',$list[0]['user_id']) //之前负责人
                    ->Where('gsk_code',$list[0]['gsk_code'])
                        ->with(['city','employee'])
                        ->orderByDesc('id')
                        ->get()->toArray();
                    $reset_accept= ManagerReset::where('yq_manager_id',$user_id) //接收人
                       ->Where('gsk_code',$list[0]['gsk_code'])
                        ->with(['city','employee_jieshou'])
                        ->orderByDesc('id')
                        ->get()->toArray();
                    $response['before']=$reset_beford;
                    $response['after']=$reset_accept;
                    $response['code']=0;
                    $response['flag']=1;
                    return response($response);

                }else if($list[0]['user_id']==$user_id){//申请者
                    $reset_beford= Device::where('yq_manager_id',$user_id) //之前负责人
                    ->Where('gsk_code',$list[0]['gsk_code'])
                        ->with(['city','employee'])
                        ->orderByDesc('id')
                        ->get()->toArray();
                    $reset_accept= ManagerReset::where('yq_manager_id',$list[0]['yq_manager_id']) //接收人
                    ->Where('gsk_code',$list[0]['gsk_code'])
                        ->with(['city','employee_before'])
                        ->orderByDesc('id')
                        ->get()->toArray();
                    $response['before']=$reset_beford;
                    $response['after']=$reset_accept;
                    $response['code']=0;
                    $response['flag']=1;
                    return response($response);

                }else{
                    $response['before']=[];
                    $response['after']=[];
                    $response['code']=0;
                    $response['flag']=2;
                    return response($response);
                }
            }


        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    //仪器变更消息列表
    public function message_list(Request $request)
    {
        try {
            $yq_manager_id=$request->input('user_id');
            $user_id=$request->input('user_id');
            $list1 = ManagerReset::where('yq_manager_id',$yq_manager_id)->where('status',0)
            //    ->orWhere('user_id',$user_id)->where('status',0)
                ->with(['city','employee_jieshou','employee_before'])
                ->orderByDesc('id')
                ->get()
                ->toArray();
                 $list2 = ManagerReset::where('user_id',$yq_manager_id)->where('status',0)
            //    ->orWhere('user_id',$user_id)->where('status',0)
                ->with(['city','employee_jieshou','employee_before'])
                ->orderByDesc('id')
                ->get()
                ->toArray();
            if(count($list1)>0){
            $flag=1; //接收人
            $response['data']=$list1;
            $response['flag']=$flag;
            $response['code']=0;
             return response($response);
            }else if(count($list2)>0){
            $flag=2; //发送人
            $response['data']=$list2;
            $response['flag']=$flag;
            $response['code']=0;
             return response($response);
            }else{
            $flag='';
            $response['data']=$list2;
            $response['flag']=$flag;
            $response['code']=0;
             return response($response);
            }
          //  $before=DB::select('select id from gsk_manager_reset where yq_manager_id='.$user_id.' and status=0');
         //   $after=DB::select('select id from gsk_manager_reset where user_id='.$user_id.' and status=0');



        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //接收人确认
    function accept_check(Request $request)
    {
        try {
            $status = $request->input('status');
            $gsk_code = $request->input('gsk_code');
         //   $before_manager_id=$request->input('before_manager_id');
            if($status==1){ //同意
                $device_status=6;
                ManagerReset::where('gsk_code', $gsk_code)->where('status','=',0)->update([
                    'device_status' =>6,
                    'status'=>$status,
                    'updated_at' => Helper::datetime()
                ]);
                Device::where('gsk_code', $gsk_code)->where('gh_flag','=',1)->update([
                 //   'status'=>$device_status,
                    'status'=>6,
                    'gh_flag'=>2, //同意更换
                    'updated_at' => Helper::datetime()
                ]);
                return response(ReturnCode::success());
            }else{
                $device_status='';
                ManagerReset::where('gsk_code', $gsk_code)->where('status','=',0)->update([
                    'status'=>$status,
                    'updated_at' => Helper::datetime()
                ]);
                Device::where('gsk_code', $gsk_code)->where('gh_flag','=',1)->update([
                    'status'=>1,
                    'gh_flag'=>3,
                  //  'yq_manager_id'=>$before_manager_id,
                    'yq_manager_id_gh'=>Null,
                    'updated_at' => Helper::datetime()
                ]);
                return response(ReturnCode::success());
            }

        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }


    //执行公司执行人列表/区域负责人
    public function alluser_list(Request $request)
    {
        $role_id=$request->input('role_id');
        $where = function ($q) use ($role_id) {
            if ($role_id) {
                $q->where('role_id', $role_id);
            }
        };
        $list = Employee::where($where)->where('flag',1)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        return response($response);
    }

    //执行公司执行人列表/区域负责人
    public function alluser_list_yqmanger(Request $request)
    {
        $keyword=$request->input('keyword');
        $where = function ($q) use ($keyword) {
            if ($keyword) {
                $q->where('employee_code','like','%'. $keyword.'%');
            }
        };
        $list = Employee::where('role_id',1)->where($where)
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        return response($response);
    }

    //维修公司仪器列表
    public function weixiu_device_list(Request $request)
    {
        $limit = $request->input('limit');//分页条数
        $user_id=$request->input('user_id');
        $keyword=$request->input('keyword');
        $status=$request->input('status');
        $gsk_code=$request->input('gsk_code');
        $where = function ($q) use ($gsk_code,$status,$keyword) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            } if ($keyword) {
                $q->where('fault_body', $keyword);
                $q->orwhere('gsk_code', $keyword);
                $q->orwhere('fault_part', $keyword);
            } if ($status) {
                if($status==1){
                    $q->whereNotIn('device_status', [7,8]);
                }else{
                    $q->whereIn('device_status', [7,8]);
                }
            }
        };
        $cf=DB::select("select max(id) as id  from gsk_weixiu_apply  group by  gsk_code  having (count(gsk_code) > 0)");
     //   $cf=DB::select("select  id  from `gsk_weixiu_apply` group by `gsk_code` )");
        $cf_ids='';
        for($i=0;$i<count($cf);$i++){
            $cf_ids.=','.$cf[$i]->id;
        }
        $temp=[];
        $ss=ltrim($cf_ids,',');
        $ids=explode(',',$ss);
        $list = WeixiuApply::where($where)->where('gsk_weixiu_apply.is_show',1)->where('weixiu_uid',$user_id)
          //  ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_weixiu_apply.gsk_code')
              ->with(['device'])
            ->whereIn('gsk_weixiu_apply.id',$ids)
            ->paginate($limit)->toArray();

        if($list){
            for($i=0;$i<count($list['data']);$i++){
                $temp[$i]['gsk_code']=$list['data'][$i]['gsk_code'];
                $temp[$i]['code']=$list['data'][$i]['device']['code'];
                $temp[$i]['device_status']=$list['data'][$i]['device_status'];
                $temp[$i]['type']=$list['data'][$i]['device']['type'];
                $temp[$i]['w_fault_body']=$list['data'][$i]['w_fault_body'];
                $temp[$i]['fault_body']=$list['data'][$i]['fault_body'];
                $temp[$i]['fault_date']=$list['data'][$i]['fault_date'];

                $city_name=DB::select("select city_name as name  from gsk_city where id='".$list['data'][$i]['city_id']."'");
                    $temp[$i]['area']=$list['data'][$i]['device']['area'];
            }
            $response['current_page']=$list['current_page'];
            $response['last_page']=$list['last_page'];
            $response['from']=$list['from'];
            $response['to']=$list['to'];
            $response['total']=$list['total'];
            $response['per_page']=$list['per_page'];
            $response['data']=$temp;

        }else{
            $temp=[];
            $response['current_page']=$list['current_page'];
            $response['last_page']=$list['last_page'];
            $response['from']=$list['from'];
            $response['to']=$list['to'];
            $response['total']=$list['total'];
            $response['per_page']=$list['per_page'];

        }



        $weixiu_ing = WeixiuApply::where($where)->where('weixiu_uid',$user_id)->where('gsk_weixiu_apply.is_show',1)->whereIn('gsk_weixiu_apply.device_status',[5,6])
           // ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_weixiu_apply.gsk_code')
           ->with(['device'])
            ->whereIn('gsk_weixiu_apply.id',$ids)
            ->get()
            ->toArray();
        $weixiu_ed = WeixiuApply::where($where)->where('weixiu_uid',$user_id)->where('gsk_weixiu_apply.is_show',1)->whereIn('gsk_weixiu_apply.device_status',[7,8])
          //  ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_weixiu_apply.gsk_code')
          ->with(['device'])
            ->whereIn('gsk_weixiu_apply.id',$ids)
            ->get()
            ->toArray();

        $response['code']=0;
        $response['total_count']=$list['total'];
        $response['xiu_ing']=count($weixiu_ing);
        $response['xiu_ed']=count($weixiu_ed);
        return response($response);
    }

    //区域提交报修获取详情
    public function quyu_weixiu_Infolist(Request $request)
    {
        $id=$request->input('id');
        $gsk_code=$request->input('gsk_code');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
        $list = WeixiuApply::where($where)->where('id',$id)
            ->with(['city','employee','device'])
            ->orderByDesc('id')
            ->limit(1)
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //区域设备管理维修详情
    public function get_weixiu_Info(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        $id=$request->input('id');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
      //  $list1 = WeixiuApply::where($where)->where('flag','=',2)->where('apply_id',$id)
        $list1 = WeixiuApply::where($where)
            ->with(['city','employee','device'])
            ->orderByDesc('id')
            ->limit(1)
            ->get()
            ->toArray();
      /*  if($list1){
            $list2 = WeixiuApply::where('apply_id','=',$list1[0]['id'])
                ->orderByDesc('id')
                ->get()
                ->toArray();
            $response['apply']=$list1;
            $response['weixiu']=$list2;
            $response['code']=0;
        }else{
            $response['apply']=[];
            $response['weixiu']=[];
            $response['code']=0;
        } */
        $response['code']=0;
        $response['data']=$list1;
        return response($response);
    }

    //区域设备管理维修详情(另写)
    public function get_weixiu_detail(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
        //  $list1 = WeixiuApply::where($where)->where('flag','=',2)->where('apply_id',$id)
        $list1 = WeixiuApply::where($where)->where('submit_status',1)
            ->with(['city','employee','device'])
            ->orderByDesc('id')
            ->limit(1)
            ->get()
            ->toArray();
        /*  if($list1){
              $list2 = WeixiuApply::where('apply_id','=',$list1[0]['id'])
                  ->orderByDesc('id')
                  ->get()
                  ->toArray();
              $response['apply']=$list1;
              $response['weixiu']=$list2;
              $response['code']=0;
          }else{
              $response['apply']=[];
              $response['weixiu']=[];
              $response['code']=0;
          } */
        $response['code']=0;
        $response['data']=$list1;
        return response($response);
    }


    //区域列表详情
    public function get_list_Info(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
        $list1 = Device::where($where)
            ->with(['city','employee','address','jyemployee'])
            ->orderByDesc('id')
            ->get()
            ->toArray();
            $response['data']=$list1;
            $response['code']=0;
        return response($response);
    }

    //执行公司设备列表
    public function zx_device_list(Request $request)
    {
        $user_id=$request->input('user_id');
        $keyword=$request->input('keyword');
        $serache_status=$request->input('status');
        $where = function ($q) use ($keyword,$serache_status) {
            if ($keyword) {
                $q->where('gsk_allocation_log.gsk_code','like','%'. $keyword.'%');
            }
            if ($keyword) {
                $q->Where('gsk_device.brand','like','%'.$keyword.'%');

            }
            if ($serache_status) {
                if($serache_status==0){
                    $q->whereIn('gsk_allocation_log.device_status', [2,3,4,5,6]);
                }elseif ($serache_status==2){
                    $q->where('gsk_allocation_log.status', 2);
                }elseif ($serache_status==3){
                    $q->where('gsk_allocation_log.device_status', 3);
                }elseif ($serache_status==4){
                    $q->where('gsk_allocation_log.device_status', 4);
                }elseif ($serache_status==5){
                    $q->where('gsk_allocation_log.device_status', 5);
                }elseif ($serache_status==6){
                    $q->where('gsk_allocation_log.device_status', 6);
                }else{

                }

            }
        };

      /*  $result1=DB::select('SELECT
                                SUM(CASE WHEN status in (2,3,4)  THEN 1 ELSE 0 END) as total,
                                SUM(CASE WHEN status = "2"  THEN 1 ELSE 0 END) as accept,
                                SUM(CASE WHEN status = "3"  THEN 1 ELSE 0 END) as used,
                                SUM(CASE WHEN status = "4"  THEN 1 ELSE 0 END) as weixiu
                                FROM gsk_allocation_log where operater='.$user_id); */
       // $list2 = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where($where)->where('gh_status',null)->orWhere('gh_status',1)
        $list2 = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where($where)
            ->leftJoin('gsk_device','gsk_allocation_log.gsk_code','=','gsk_device.gsk_code')
            ->with(['city','employee_two','device'])
            ->orderByDesc('gsk_allocation_log.id')
            ->get()
            ->toArray();

      


        if($list2){
            for($i=0;$i<count($list2);$i++){
                $temp[$i]['gsk_code']=$list2[$i]['gsk_code'];
                $temp[$i]['code']=$list2[$i]['code'];
                $temp[$i]['status']=$list2[$i]['device_status'];
                $temp[$i]['type']=$list2[$i]['type'];
                $temp[$i]['user_city']=$list2[$i]['real_city'];
                $temp[$i]['allocation_date']=$list2[$i]['allocation_date'];
                //  $temp[$i]['days']=round(($list2[$i]['end_date']-$list2[$i]['start_date'])/3600/24);
                $temp[$i]['days']=round((strtotime($list2[$i]['end_date'])-strtotime($list2[$i]['start_date']))/3600/24);
                $zx_name=DB::select("select employee_name as name from gsk_employee where id='".$list2[$i]['operater']."'");
                if($zx_name){
                    $temp[$i]['zx_name']=$zx_name[0]->name;
                }else{
                    $temp[$i]['zx_name']='';
                }
                $manager_name=DB::select("select employee_name as name from gsk_employee where id='".$list2[$i]['yq_manager_id']."'");
                if($manager_name){
                    $temp[$i]['manager_name']=$manager_name[0]->name;
                }else{
                    $temp[$i]['manager_name']='';
                }
            }
        }else{
            $temp=[];
        }




        $list1 = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where($where)->where('gsk_allocation_log.device_status',2)
            ->leftJoin('gsk_device','gsk_allocation_log.gsk_code','=','gsk_device.gsk_code')
            ->with(['city','employee_two'])
            ->orderByDesc('gsk_allocation_log.id')
            ->get()
            ->toArray();
        $list3 = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where($where)->where('gsk_allocation_log.device_status',3)
            ->leftJoin('gsk_device','gsk_allocation_log.gsk_code','=','gsk_device.gsk_code')
            ->with(['city','employee_two'])
            ->orderByDesc('gsk_allocation_log.id')
            ->get()
            ->toArray();

        $list5 = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where($where)->where('gsk_allocation_log.device_status',5)
            ->leftJoin('gsk_device','gsk_allocation_log.gsk_code','=','gsk_device.gsk_code')
            ->with(['city','employee_two'])
            ->orderByDesc('gsk_allocation_log.id')
            ->get()
            ->toArray();

        $list4 = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where($where)->where('gsk_allocation_log.device_status',4)
            ->leftJoin('gsk_device','gsk_allocation_log.gsk_code','=','gsk_device.gsk_code')
            ->with(['city','employee_two'])
            ->orderByDesc('gsk_allocation_log.id')
            ->get()
            ->toArray();

        $list6 = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where($where)->where('gsk_allocation_log.device_status',6)
            ->leftJoin('gsk_device','gsk_allocation_log.gsk_code','=','gsk_device.gsk_code')
            ->with(['city','employee_two'])
            ->orderByDesc('gsk_allocation_log.id')
            ->get()
            ->toArray();

        $response['total']=count($list2); //总数据
        $response['accept']=count($list1); //待接收
        $response['used']=count($list3); //正在使用中
        $response['weixiu']=count($list4); //维修中
        $response['gh']=count($list5); //已归还
        $response['inner']=count($list6); //内部使用中
        $response['data']=$temp;
        $response['code']=0;
        return response($response);
    }

    //维修公司归还公司归还gsk
    function weixiu_company_replay(Request $request)
    {
        try {
            $gsk_code = $request->input('gsk_code');
            $now_component = $request->input('now_component');
            $replay_way_1 = $request->input('replay_way');
            $replay_msg_1 = $request->input('replay_msg');
            $gsk_code_temp=explode(',',$gsk_code);

            $list_s = WeixiuApply::where('gsk_code',$gsk_code)->whereIn('device_status',[7,8])->where('flag',2)
                ->orderByDesc('id')
                ->get()->toArray();
            if(count($list_s)<1){
                return response(ReturnCode::error('100', '请确认该仪器是否符合归还'));
            }
            $list_s = WeixiuApply::where('gsk_code',$gsk_code)->whereIn('device_status',[7,8])->where('gh_status',1)
                ->orderByDesc('id')
                ->get()->toArray();

            if(count($list_s)>0){
                return response(ReturnCode::error('100', '该仪器已操作归还请勿重复操作'));
            }
            $count=count($gsk_code_temp);
            for($i=0;$i<$count;$i++){
                WeixiuApply::where('gsk_code', $gsk_code_temp[$i])->update([
                    'now_component_1' => $now_component,
                    'replay_way_1' => $replay_way_1,
                    'replay_msg_1' => $replay_msg_1,
                    'gh_status' => 1,
                    'updated_at' => Helper::datetime()
                ]);

            }
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //获取不同状态下的gsk_code列表
    function getall_status_code(Request $request)
    {
        try {
        $limit = $request->input('limit', 10);
        $keyword= $request->input('keyword');
        $user_id= $request->input('user_id');
        $flag= $request->input('flag');
            $where = function ($query) use ($keyword) {
                if (!empty($keyword)) {
                    $query->where('gsk_code', $keyword);
                }
            };
        if($flag==1){ //flag=1 区域申请调拨
            $list = Device::where('yq_manager_id',$user_id)->where('status',1)
                ->orderByDesc('id')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==2){ //区域申请更换负责人
            $list = Device::where('yq_manager_id',$user_id)->where('status',1)
                ->orderByDesc('id')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==3){ //执行公司验收
            $list = AllocationLog::where('gsk_allocation_log.operater',$user_id)->whereIn('gsk_allocation_log.device_status',[2,6])
                ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_allocation_log.gsk_code')
                ->orderByDesc('gsk_allocation_log.id')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==4){//执行公司调拨
            $list = AllocationLog::where('gsk_allocation_log.operater',$user_id)->whereIn('gsk_allocation_log.device_status',[3,6])
                ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_allocation_log.gsk_code')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==5){//执行公司维修
            $list = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where('gsk_allocation_log.device_status',3)
                ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_allocation_log.gsk_code')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==6){ //执行公司归还
            $list = AllocationLog::where('gsk_allocation_log.operater',$user_id)->where('gsk_allocation_log.device_status',3)->where('gsk_allocation_log.gh_status',NULL)
                ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_allocation_log.gsk_code')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==7){ //维修公司维修
            $list = WeixiuApply::whereNotIn('gsk_weixiu_apply.device_status',['7,8'])->where('gsk_weixiu_apply.flag',1)
                ->leftJoin('device','gsk_device.gsk_code','=','gsk_weixiu_apply.gsk_code')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==8){ //维修公司归还
            $list = WeixiuApply::whereIn('gsk_device_status',['7','8'])->where('flag',2)->where('gh_status',null)
                ->leftJoin('gsk_device','gsk_device.gsk_code','=','gsk_weixiu_apply.gsk_code')
                ->paginate($limit)
                ->toArray();
        }elseif ($flag==9){ //一件入库的设备
            $list = Device::where('status',9)->where('yq_manager_id',$user_id)
                ->orderByDesc('id')
                ->paginate($limit)
                ->toArray();
        }else{
            $list=[];
        }
        $response['code']=0;
        $response['data']=$list;
            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //仪器变更消息详情渲染
    public function message_list_info(Request $request)
    {
        try {
          /*  $yq_manager_id=$request->input('user_id');
            $user_id=$request->input('user_id');
            $list = ManagerReset::where('yq_manager_id',$yq_manager_id)
                ->orWhere('user_id',$user_id)->where('status',0)
                ->with(['city','employee_jieshou','employee_before'])
                ->orderByDesc('id')
                ->get()
                ->toArray();
            if(count($list)>0){
                $flag=1;
            }else{
                $flag=2;
            }
            $response['data']=$list;
            $response['flag']=$flag;
            $response['code']=0;
            return response($response);*/
            $gsk_code=$request->input('gsk_code');
            $user_id=$request->input('user_id');
            $before=DB::select("select id from gsk_device where yq_manager_id='".$user_id."' and gh_flag=1 and gsk_code='".$gsk_code."'");
            $after=DB::select("select id from gsk_device where yq_manager_id_gh='".$user_id."' and gh_flag=1 and gsk_code='".$gsk_code."'");
            if($before){
                $before_info=Device::where('yq_manager_id',$user_id)->where('gh_flag',1)->where('gsk_code',$gsk_code)
                    ->with(['city','address','employee'])
                    ->get()->toArray();
                if($before_info){
                    $aft_id=$before_info[0]['yq_manager_id_gh'];
                }

                $after_info=ManagerReset::where('yq_manager_id',$aft_id)->where('status',0)->where('gsk_code',$gsk_code)
                    ->with(['city','employee','address'])
                    ->get()->toArray();
                $response['before']=$before_info;
                $response['after']=$after_info;
                $response['code']=0;
                $response['tag']=1; //发送人
                return response($response);
            }else if($after){
                $after_info=ManagerReset::where('yq_manager_id',$user_id)->where('status',0)->where('gsk_code',$gsk_code)
                    ->with(['city','employee','address'])
                    ->get()->toArray();
                if($after_info){
                    $beforeId=$after_info[0]['user_id'];
                }

                $before_info=Device::where('yq_manager_id',$beforeId)->where('gh_flag',1)->where('gsk_code',$gsk_code)
                    ->with(['city','address','employee'])
                    ->get()->toArray();
                $response['before']=$before_info;
                $response['after']=$after_info;
                $response['tag']=2; //接收人
                $response['code']=0;
                return response($response);

            }else{
                $response['before']=[];
                $response['after']=[];
                $response['tag']="";
                $response['code']=0;
                return response($response);

            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //获取所属仓库渲染
    function get_ck_info(Request $request)
    {
        try {
            $gsk_code = $request->input('gsk_code');
            $res=DB::select("select address_id from gsk_device where gsk_code='".$gsk_code."'");
            $res_id=$res[0]->address_id;
            $res_info=DB::select("select address,name from gsk_ck where id='".$res_id."'");
            $name=$res_info[0]->address;
            $address=$res_info[0]->address;
            $response['name']=$name;
            $response['address']=$address;
            $response['code']=0;
            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //区域设备管理维修详情
    public function get_wxsuccess_info(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
        //  $list1 = WeixiuApply::where($where)->where('flag','=',2)->where('apply_id',$id)
        $list1 = WeixiuApply::where($where)->where('gh_status',2)
            ->with(['city','employee'])
            ->orderByDesc('id')
            ->limit(1)
            ->get()
            ->toArray();
        /*  if($list1){
              $list2 = WeixiuApply::where('apply_id','=',$list1[0]['id'])
                  ->orderByDesc('id')
                  ->get()
                  ->toArray();
              $response['apply']=$list1;
              $response['weixiu']=$list2;
              $response['code']=0;
          }else{
              $response['apply']=[];
              $response['weixiu']=[];
              $response['code']=0;
          } */
        $response['code']=0;
        $response['data']=$list1;
        return response($response);
    }

    //区域调拨状态下取消按钮
    public function quyu_escape(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        $user_id=$request->input('user_id');
        $id=$request->input('id');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
        $list1 = WeixiuApply::where('id',$id)
            ->orderByDesc('id')
            ->limit(1)
            ->get()
            ->toArray();
        if(count($list1)<0){
            return response(ReturnCode::error('100', '不存在该条数据'));
        }else{
            DB::delete("delete gsk_allocation_log where id='".$id."'");
            Device::where('gsk_code', $gsk_code)->update([
                'status' => 1,
                'updated_at' => Helper::datetime()
            ]);
            return response(ReturnCode::success());
        }

    }

    //执行方查看更多消息
    public function zxfList(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        $user_id=$request->input('user_id');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
        $list1 = AllocationLog::where('gsk_code',$gsk_code)
            ->orderByDesc('id')
            ->with(['device','employee'])
            ->limit(1)
            ->get()
            ->toArray();

        $response['data']=$list1;
        $response['code']=0;
        return response($response);
    }

    //维修方查看更多消息
    public function wxList(Request $request)
    {
        $gsk_code=$request->input('gsk_code');
        $user_id=$request->input('user_id');
        $where = function ($q) use ($gsk_code) {
            if ($gsk_code) {
                $q->where('gsk_code', $gsk_code);
            }
        };
        $list1 = WeixiuApply::where('gsk_code',$gsk_code)
            ->orderByDesc('id')
            ->with(['device','employee'])
            ->limit(1)
            ->get()
            ->toArray();

        $response['data']=$list1;
        $response['code']=0;
        return response($response);
    }




}
