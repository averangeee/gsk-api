<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/17
 * Time: 17:34
 */

namespace App\Http\Controllers\WebApi;

use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\GSK\Menus;
use App\Models\GSK\Role;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * @des 查询
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $keyword=$request->input('keyword',null);
            $type=$request->input('type',null);//1 系统 2 私有 3 通用

            $where=function ($query) use($keyword,$type){
                if(!empty($keyword)){
                    $query->where('employee_name','like','%'.$keyword.'%')->orWhere('employee_code','like','%'.$keyword.'%');
                }
                if(!empty($type)){
                    $query->where('type',$type);
                }
            };

            $data=Role::where($where)
              /*  ->with(['creator'=>function($q){
                    $q->select(['employee_code','employee_name']);
                },'modifier'=>function($q){
                    $q->select(['employee_code','employee_name']);
                }])
                ->withCount('user')*/
                ->orderBy('sort')
                ->orderByDesc('created_at')
                ->paginate($limit)
                ->toArray();

            $response['data']=$data['data'];
            $response['total']=$data['total'];
            $response['code']=ReturnCode::SUCCESS;

            return response($response);
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 添加权限
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function add(Request $request)
    {
        try{
            $name=$request->input('name',null);
            $source=$request->input('fun_resource',null);
            $source2=$request->input('fun_resource_str',null);

            $role=Role::where('name',$name)->first();
            if($role){
                return response(ReturnCode::error(1001,'名称已存在'));
            }

            Role::create([
                'name'=>$name,
            /*    'type'=>$request->input('type',null),*/
                'des'=>$request->input('des',null),
                'sort'=>$request->input('sort',99),
                'fun_resource'=>explode(',',$source),
                'fun_resource_str'=>$source2,
                'created_code'=>Token::$ucode
            ]);

            return response(ReturnCode::success([],'添加成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 编辑权限
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(Request $request)
    {
        try{
            $id=$request->input('id');
            $role=Role::find($id);
            if(!$role){
                return response(ReturnCode::error(1001,'记录不存在'));
            }

            $name=$request->input('name',null);
            $source=$request->input('fun_resource',null);

            $source2=$request->input('fun_resource_str',null);
            $role2=Role::where('name',$name)->where('id','<>',$id)->first();
            if($role2){
                return response(ReturnCode::error(1002,'名称已存在'));
            }
         //   Log::info( DB::getquerylog());
            $role->name=$name;
            $role->fun_resource=explode(',',$source);

            $role->fun_resource_str=$source2;

            $role->des=$request->input('des',null);
            $role->sort=$request->input('sort',null);
            $role->updated_code=Token::$ucode;



           $role->save();
          /*   Log::info('==>sql=' .$role->save());
                $fun_resource_str=$source2;
                $fun_resource=explode(',',$source);
                $fun_resource=$source;
                $des=$request->input('des',null);
                $sort=$request->input('sort',null);
                $updated_code=Token::$ucode;
                Role::where('id', $id)->update([
                    'name' => $name,
                    'fun_resource' => $fun_resource,
                    'fun_resource_str' => $fun_resource_str,
                    'des' => $des,
                    'sort' => $sort,
                    'updated_code' => Token::$ucode
                ]); */


            return response(ReturnCode::success([],'修改成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 删除
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request)
    {
        try{
            $id=$request->input('id');
            $role=Role::find($id);
            if(!$role){
                return response(ReturnCode::error(1001,'记录不存在'));
            }
            $role->deleted_code=Token::$ucode;
            $role->save();

            $role->delete();

            return response(ReturnCode::success([],'删除成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 筛选菜单和功能
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function filter(Request $request)
    {
        try{
            $menus=Menus::where('parent_id',0)->where('is_show',1)
                ->select(['id','parent_id','menu_code','menu_name'])
                ->orderBy('menu_sort')
                ->with(['child'=>function($q){
                    $q->where('is_show',1)->select(['id','parent_id','menu_code','menu_name']);
                }])
                ->get();
            return response(ReturnCode::success($menus));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}
