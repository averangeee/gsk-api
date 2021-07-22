<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/30
 * Time: 13:32
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Base\DefineNote;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefineController extends Controller
{
    public function index(Request $request)
    {
        try{
            $type=$request->input('type',null);
            $keyword=$request->input('keyword',null);

            $where=function ($query) use($keyword){
                if(!empty($keyword)){
                    $query->where('des','like','%'.$keyword.'%');
                }
            };

            $mm=DefineNote::where($where)->get(['id','parent_id','query_string']);
            $ids=[];
            foreach ($mm as $item){
                $ids[]=$item->id;
                $queryString=$item->query_string;
                if($queryString){
                    $ll=explode(',',$queryString);
                    foreach ($ll as $k){
                        $ids[]=intval($k);
                    }
                }
            }
            $ids=array_unique($ids);

            $define=new DefineNote();
            $defines=$define->getLevel($type,$ids,['id','type','des','parent_id','query_string']);

            return response(ReturnCode::success($defines));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function add(Request $request)
    {
        try{
            $shop_id=$request->input('shop_id',null);
            $type=$request->input('type',null);
            $parent_id=$request->input('parent_id',0);
            $des=$request->input('des',null);

            $count=DefineNote::where('parent_id',$parent_id)->where('type',$type)->where('des',$des)->count();
            if($count>0){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'此名称在同级中已存在'));
            }
            $query_string=null;

            if(!empty($parent_id)){
                $pid=DefineNote::where('id',$parent_id)->first(['id','query_string']);
                if($pid->query_string){
                    $query_string=$pid->query_string.','.$pid->id;
                }else{
                    $query_string=$pid->id;
                }
            }

            $note=DefineNote::create([
                'shop_id'=>$shop_id,
                'type'=>$type,
                'parent_id'=>$parent_id,
                'des'=>$des,
                'status'=>1,
                'query_string'=>$query_string,
                'created_code'=>Token::$ucode
            ]);

            return response(ReturnCode::success($note,'添加成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function edit(Request $request,$id)
    {
        try{
            $shop_id=$request->input('shop_id',null);
            $type=$request->input('type',null);
            $parent_id=$request->input('parent_id',0);
            $des=$request->input('des',null);
            $query_string=$request->input('query_string',null);

            $define=DefineNote::find($id);
            if(!$define){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $count=DefineNote::where('id','<>',$id)
                ->where('parent_id',$parent_id)->where('type',$type)->where('des',$des)->count();
            if($count>0){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'此名称在同级中已存在'));
            }

            if(!empty($parent_id)){
                $pid=DefineNote::where('id',$parent_id)->first(['id','query_string']);
                if($pid->query_string){
                    $query_string=$pid->query_string.','.$pid->id;
                }else{
                    $query_string=$pid->id;
                }
            }

            $define->shop_id=$shop_id;
            $define->type=$type;
            $define->parent_id=$parent_id;
            $define->des=$des;
            $define->query_string=$query_string;
            $define->updated_code=Token::$ucode;
            $define->save();

            return response(ReturnCode::success([],'更新成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function delete(Request $request,$id)
    {
        try{
            $define=DefineNote::find($id);
            if(!$define){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $where=function ($q)use($id){
                $q->orWhere('id',$id)->orWhere('query_string','like','%'.$id.'%');
            };

            DB::beginTransaction();
            DefineNote::where($where)->update(['deleted_code'=>Token::$ucode]);
            DefineNote::where($where)->delete();
            DB::commit();
            return response(ReturnCode::success([],'删除成功'));
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}