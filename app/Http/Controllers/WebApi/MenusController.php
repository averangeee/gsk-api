<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/28
 * Time: 14:14
 */

namespace App\Http\Controllers\WebApi;

use App\Libs\Helper;
use App\Libs\HashKey;
use App\Libs\ReturnCode;
use App\Models\GSK\Menus;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class MenusController extends Controller
{
    public function index(Request $request)
    {
        try{
            $keyword=$request->input('keyword',null);
            $client_type=$request->input('client_type',null);

            $where=function ($query) use($keyword,$client_type){
                if(!empty($keyword)){
                    $query->where(function ($q) use($keyword){
                        $q->where('menu_name','like','%'.$keyword.'%')->orWhere('menu_name','like','%'.$keyword.'%')
                            ->orWhere('remark','like','%'.$keyword.'%');
                    });
                }
                if(!empty($client_type)){
                    $query->where('client_type',$client_type);
                }
            };

            $menus=Menus::where('parent_id',0)->where($where)
                ->orderBy('menu_sort')
                ->with(['children'])
                ->get();

            return response(ReturnCode::success($menus));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function add(Request $request)
    {
        try{
            $menu_code=$request->input('menu_code',null);
            $menu_name=$request->input('menu_name',null);
            $menu_level=$request->input('menu_level',1);
            $parent_id=$request->input('parent_id',0);
            $client_type=$request->input('client_type',1);
            $menu_type=$request->input('menu_type',1);
            $menu_icon=$request->input('menu_icon',null);
            $menu_url=$request->input('menu_url',null);
            $menu_sort=$request->input('menu_sort',null);
            $is_show=$request->input('is_show',1);
            $is_sys=$request->input('is_sys',1);
            $remark=$request->input('remark',null);
            $fun_code=$request->input('fun_code',null);

            $count=Menus::where('menu_name',$menu_name)->count();
            if($count){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'名称已存在'));
            }
      //      $count2=Menus::withTrashed()->count();
//            $menu_code=HashKey::pkcode('M',$count2,4);

            $query_string=null;
            if($parent_id){
                $pid=Menus::where('id',$parent_id)->first(['id','query_string']);
                if($pid->query_string){
                    $query_string=$pid->query_string.','.$pid->id;
                }else{
                    $query_string=$pid->id;
                }
            }

            if($menu_type<>1&&$parent_id>0){
                $menu_code=Menus::find($parent_id)->menu_code.$fun_code;
            }
            $data=[
                'menu_code'=>$menu_code,
                'menu_name'=>$menu_name,
                'menu_level'=>$menu_level,
                'parent_id'=>$parent_id,
                'client_type'=>$client_type,
                'menu_type'=>$menu_icon=='null'?1:$menu_icon,
                'menu_icon'=>$menu_icon=='null'?null:$menu_icon,
                'menu_url'=>$menu_url=='null'?null:$menu_url,
             //   'menu_sort'=>$menu_sort?$menu_sort:1,
                'menu_sort'=>$menu_sort,
                'is_show'=>$is_show=='true'?1:0,
                'is_sys'=>$is_sys=='true'?1:0,
                'remark'=>$remark=='null'?null:$remark,
                'query_string'=>$query_string,
                'fun_code'=>$fun_code=='null'?null:$fun_code,
                'created_code'=>Token::$ucode,
                'created_at'=>Helper::datetime()
            ];

            Menus::insert($data);
            return response(ReturnCode::success());
        }
        catch (\Exception $exception){

            dd($exception->getMessage());
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function edit(Request $request)
    {
        try{
            $id=$request->input('id');
            $menu=Menus::find($id);
            if(!$menu){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $menu_name=$request->input('menu_name',null);
            $count=Menus::where('id','<>',$id)->where('menu_name',$menu_name)->count();
            if($count){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'名称已存在'));
            }

            $query_string=null;
            $parent_id=$menu->parent_id;
            if($parent_id){
                $pid=Menus::where('id',$parent_id)->first(['id','query_string']);
                if($pid->query_string){
                    $query_string=$pid->query_string.','.$pid->id;
                }else{
                    $query_string=$pid->id;
                }
            }

            $is_show=$request->input('is_show',null);
            $is_sys=$request->input('is_sys',1);
            $url=$request->input('menu_url',null);
            $icon=$request->input('menu_icon',null);
            $funcode=$request->input('fun_code',null);
            $remark=$request->input('remark',null);
            $menu_code=$request->input('menu_code',null);

            $menu->menu_name=$menu_name;
            $menu->menu_code=$menu_code;
//            $menu->menu_level=$request->input('menu_level',1);
            $menu->parent_id=$request->input('parent_id',0);
//            $menu->client_type=$request->input('client_type',1);
            $menu->menu_type=1;
            $menu->menu_icon=$icon=='null'?null:$icon;
            $menu->menu_url=$url=='null'?null:$url;
            $menu->menu_sort=$request->input('menu_sort',99);
            $menu->is_show=$is_show=='true'?1:0;
            $menu->is_sys=$is_sys=='true'?1:0;
            $menu->remark=$remark=='null'?null:$remark;
            $menu->query_string=$query_string;
            $menu->fun_code=$funcode=='null'?null:$funcode;

            $menu->updated_code=Token::$ucode;
            $menu->save();

            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function delete(Request $request)
    {
        try{
            $id=$request->input('id');
            $menu=Menus::find($id);
            if(!$menu){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $where=function ($q)use($id){
                $q->orWhere('id',$id)->orWhere('query_string','like','%'.$id.'%');
            };

            DB::beginTransaction();
            Menus::where($where)->update(['deleted_code'=>Token::$ucode]);
            Menus::where($where)->delete();
            DB::commit();
            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}
