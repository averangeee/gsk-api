<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 17:27
 */

namespace App\Http\Controllers\WebApi;
use Maatwebsite\Excel\Facades\Excel;

use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\GSK\Employee;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    //员工管理，账号管理
    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', null);
            $keyword = $request->input('keyword', null);
            $states = $request->input('states', null);

            $where = function ($query) use ($keyword, $states) {
                if (!empty($keyword)) {
                    $query->where(function ($qq) use ($keyword) {
                        $qq->orWhere('employee_code', 'like', '%' . $keyword . '%')
                            ->orWhere('employee_name', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
                if (!empty($states)) {
                    $query->where('states', $states);
                }
            };
            $data = Employee::where($where)
              /*  ->where('id', '>', '1')
                ->with(['creator' => function ($qc) {
                    $qc->select(['employee_code', 'employee_name']);
                }, 'modifier' => function ($qc) {
                    $qc->select(['employee_code', 'employee_name']);
                }])*/
                  ->with(['role','city'])
                ->orderBy('sort')
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
            $count = Employee::where('employee_name', $employeeName)->count();
            if ($count) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '用户名已存在'));
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
                'role_id' => $role_id,
                'is_sl' => 1,
                'flag'=>1,
                'sort' => $sort,
                'remark' => $remark,
                'created_code' => Token::$ucode,
                'power_type' =>1,
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

    /**
     * 修改人员
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/19 18:55
     */
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
            $count = Employee::where('employee_code', $employeeCode)->where('id','!=',$id)->count();
            if ($count) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '编码已存在'));
            }

            $count = Employee::where('employee_name', $employeeName)->where('id','!=',$id)->count();
            if ($count) {
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '用户名已存在'));
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

    /**
     * 删除人员
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/19 18:55
     */
    public function delete(Request $request)
    {
        try {
            $id= $request->input('id');
            $employee = Employee::find($id);
            if (!$employee) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
         //   $employee->deleted_code = Token::$ucode;
          //  $employee->save();

         //   $employee->delete();
            DB::delete('delete from gsk_employee where id='.$id);

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function status(Request $request, $id)
    {
        try {
            $states = $request->input('states', 1);
            $employee = Employee::find($id);
            if (!$employee) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $employee->updated_code = '';
            $employee->states = $states;
            $employee->save();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    /**
     * 修改是否登录
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/7/2 18:03
     */
    public function isLog(Request $request, $id)
    {
        try {
            $isLog = $request->input('is_log');

            $employee = Employee::find($id);
            if (!$employee) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $employee->updated_code = Token::$ucode;
            $employee->is_log = $isLog;
            $employee->updated_at = Helper::datetime();
            $employee->save();
            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function resetPwd(Request $request)
    {
        try {
            $id=$request->input('id');
            $employee = Employee::find($id);
            if (!$employee) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $employee->updated_code = '';
            $employee->password = Hash::make(Employee::$pwd);
            $employee->save();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function upPwd(Request $request, $id)
    {
        try {
            $pwd = $request->input('pwd');
            $employee = Employee::find($id);

            if (!$employee) {
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $employee->updated_code = Token::$ucode;
            $employee->password = Hash::make($pwd);
            $employee->save();

            return response(ReturnCode::success());
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //获取负责人
    public function manager_list(Request $request)
    {
        $keyword=$request->input('keyword');
        $where = function ($q) use ($keyword) {
          if ($keyword) {
                $q->where('employee_code','like','%'. $keyword.'%');
                $q->orwhere('employee_name','like'.'%'. $keyword.'%');
            }
        };
        $list = Employee::where($where)->where('role_id','=',1)->where('flag',1)
            ->with(['city'])
            ->orderByDesc('id')
            ->get()
            ->toArray();
        $response['data']=$list;
        $response['code']=0;
        return response($response);
    }

    //修改密码
    public function changePwd(Request $request)
    {
        try {
            $id=$request->input('id');
          //  $oldPwd = $request->input('oldPwd', '');
            $newPwd = $request->input('newPwd');
            $user = Employee::find($id);
            if (!$user) {
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

          /*  if (!password_verify($oldPwd, $user->password)) {
                return response(ReturnCode::error(ReturnCode::OLD_PASSWORD_NOT_MATCH));
            }*/

            $user->password = Hash::make($newPwd);
            $user->save();
            return response(ReturnCode::success([], '修改成功'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
  //上传图片
    public function uploadImgOss(Request $request)
    {
        $file = $request->file('file');
        $accessKeyId = env('AccessKeyId');
        $accessKeySecret = env('AccessKeySecret');
        $endpoint = 'oss-cn-shanghai.aliyuncs.com';
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);


        $fileExt = $file->getClientOriginalExtension();
        $fileName = date('YmdHis') . str_random(16) . '.' . $fileExt; //重命名
        $bucket = 'gsk';
        $object = 'user_image/' . $fileName;
        $result = $ossClient->uploadFile($bucket, $object, $file);
        $info = $result['info'];

        return response(ReturnCode::success($info['url']));
    }

    //导入员工表
    function export_employee(Request $request)
    {
        try {
            $list1 = Employee::orderByDesc('id')
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

            for ($i = 1; $i < $len; $i++) {

                $data[$j]['is_sl'] = 1;//登录名称
                $data[$j]['is_log'] = 0;//登录名称
                $data[$j]['states'] = 1;//登录名称

                $data[$j]['flag'] = 1;//登录名称
                $sort = Employee::max('sort') + 1;
                $data[$j]['sort'] = $sort;//登录名称
                $data[$j]['employee_code'] = $res[$i][0];//登录名称
                $data[$j]['employee_name'] = $res[$i][1];//用户名

                //判断是否存在
                $count = Employee::where('employee_code', $res[$i][0])->count();
                if ($count) {
                    return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '编码已存在'));
                }
                $count = Employee::where('employee_name', $res[$i][1])->count();
                if ($count) {
                    return response(ReturnCode::error(ReturnCode::RECORD_EXIST, '用户名已存在'));
                }


                if($res[$i][2]=='南区' || $res[$i][2]=='北区' || $res[$i][2]=='西区' || $res[$i][2]=='东区'){
                    $data[$j]['area'] = $res[$i][2];//区域
                } else{
                    return response(ReturnCode::error('100', '第'.$i.'行请填入正确的区域'));
                }


                $city=DB::select("select id from gsk_city where pid not in (452,453,454,455) and city_name='". $res[$i][3]."'");
                if($city){
                    $data[$j]['city_id'] = $city[0]->id;
                }else{
                    return response(ReturnCode::error('100', '第'.$i.'行请填入正确的所属城市'));
                }


              /*  if(strpos($data[$j]['content'],"总部")!==false){


                }*/

                if($res[$i][4]=='admin' || $res[$i][4]=='总部' || $res[$i][4]=='市场/区域' || $res[$i][2]=='执行方' || $res[$i][2]=='维修公司'){
                    $rr=DB::select("select id from gsk_role where name='". $res[$i][4]."'");
                    if($rr){
                        $data[$j]['role_id'] = $rr[0]->id;//角色
                    }

                } else{
                    return response(ReturnCode::error('100', '第'.$i.'行请填入正确的角色'));
                }
                $data[$j]['phone'] = $res[$i][5];//电话
                 //   DB::table('gsk_device')->insert($data);

                Employee::create([
                    'employee_code' => $data[$j]['employee_code'],
                    'employee_name' => $data[$j]['employee_name'],
                    'password' => Hash::make(Employee::$pwd),
                    'area' => $data[$j]['area'],
                    'city_id' => $data[$j]['city_id'],
                    'phone' => $data[$j]['city_id'],
                    'region' => NULL,
                    'tel' => NULL,
                    'dept' => NULL,
                    'role_id' => $data[$j]['role_id'],
                    'is_sl' => 1,
                    'flag'=>1,
                    'sort' => NULL,
                    'remark' => NULL,
                    'created_code' => Token::$ucode,
                    'power_type' =>1,
                    'power_str' => NULL
                ]);

            }


            $list2 = Employee::orderByDesc('id')
                ->get()
                ->toArray();
            $total2=count($list2);
            $response['total'] =$len-1;
            $response['sum'] =$total2-$total1;
            return response(ReturnCode::success($response));
        } catch (\Exception $exception) {
            dd($exception);
            return response(ReturnCode::error('101', '请检查excel格式'));
        }

    }


}
