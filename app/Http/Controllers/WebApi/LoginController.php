<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/25
 * Time: 19:03
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Language;
use App\Models\GSK\Qx;
use App\Models\GSK\Employee;
use App\Models\GSK\Menus;
use App\Models\GSK\Role;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    //登录
    public function login(Request $request)
    {
        //  try {
        $username = $request->input('username', null);
        $password = $request->input('password', null);
        $lang = $request->input('lang', 'zhCN');
        $employee = Employee::where('employee_code', $username)->where('is_log', 1)->whereNull('deleted_at')->with('role')->first();
        if($employee){
            if ($employee->role_id == 3 || $employee->role_id == 4) {
                return response(ReturnCode::error(ReturnCode::AUTHORIZE_FAIL, '该账户限制登录后台'));
            }


            if (!Hash::check($password, $employee->password)) {
                return response(ReturnCode::error(ReturnCode::AUTHORIZE_FAIL, '密码错误'));
            }

            if ($employee->flag != 1) {
                return response(ReturnCode::error(ReturnCode::AUTHORIZE_FAIL, '您账号被冻结登录'));
            }

            /*    $powerList = explode(',', $employee->power_str);
                $stroe_code = [];
                if ($employee->power_type == 2) {//简称
                    $storeSalesList = Store::where('version_id', date('Ym', time()))->whereIn('customer', $powerList)->select('store_code')->get()->toArray();
                    foreach ($storeSalesList as $k => $v) {
                        $stroe_code[$k] = $v['store_code'];
                    }
                }
                if ($employee->power_type == 3) {//地区
                    $storeSalesList = Store::where('version_id', date('Ym', time()))->whereIn('region', $powerList)->select('store_code')->get()->toArray();
                    foreach ($storeSalesList as $k => $v) {
                        $stroe_code[$k] = $v['store_code'];
                    }
                }
                if ($employee->power_type == 4) {//门店权限
                    $storeSalesList = explode(',', $employee->power_str);
                    foreach ($storeSalesList as $k => $v) {
                        $stroe_code[$k] = $v;
                    }
                } */

            /*     $detail = Qx::where('user_id', $employee->id)->first();
                 if ($detail) {
                     $detail->cache_power_type = $employee->power_type;
                     $detail->cache_power_str = $employee->power_str;
                     $detail->cache_stroe_code = json_encode($stroe_code);
                   //  $detail->save();
                 } else {
                     Qx::create([
                         'cache_power_type' => $employee->power_type,
                         'cache_power_str' => $employee->power_str,
                         'cache_stroe_code' => json_encode($stroe_code),
                         'user_id' => $employee->id
                     ]);
                 } */


            $role = Role::find($employee->role_id);
           // $role=$role['attributes'];
            $userResource = $role ? $role['fun_resource'] : [];

            if($userResource!=null && $userResource!=""){
                //   $list = Menus::whereIn('menu_code', $userResource)->where('menu_level', 3)->select('parent_id')->distinct()->get()->toArray();

                $list = Menus::whereIn('menu_code', $userResource)->where('parent_id','0')->select('id')->distinct()->get()->toArray();



                $list = Menus::whereIn('id', $list)->orderBy('menu_sort')->get()->toArray();


                foreach ($list as $k => $v) {
                    $list[$k]['children'] = Menus::where('parent_id', $v['id'])->whereIn('menu_code', $userResource)->orderBy('menu_sort')->get()->toArray();
                }
                $menu = $list;
            }else{
                $menu=[];
            }


            return response(['code' => 0, 'token' => Token::generate(0, $employee->id, $employee->employee_code, $lang), 'user' => $employee, 'menu' => $menu]);

            /* } catch (\Exception $exception) {
                 Log::error($exception);
                 return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
             }*/

        }else{

                return response(ReturnCode::error(ReturnCode::AUTHORIZE_FAIL, '用户不存在'));


        }

    }

    public function language(Request $request)
    {
        $language = Language::where('status', 1)->get(['language_code', 'language_name']);

        return response(ReturnCode::success($language));
    }

    public function test()
    {
        $encrypter = app('Illuminate\Encryption\Encrypter');//调用csrf中间件的加密方法
        $encrypted_token = $encrypter->encrypt(csrf_token()); //对csrf token 加密

        return $encrypted_token;
    }
}
