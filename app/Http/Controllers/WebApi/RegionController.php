<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/30
 * Time: 18:24
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\System\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    //懒加载使用
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $type=$request->input('type',null);
            $keyword=$request->input('keyword',null);
            $parent_id=$request->input('parent_id',0);


            $where=function ($query) use($type,$keyword,$parent_id){
                if(!empty($keyword)){
                    $query->where('name','like','%'.$keyword.'%');
                }
                if(strlen($type)>0){
                    $query->where('type',$type);
                }
                if(strlen($type)==0&&empty($keyword)){
                    $query->where('parent_id',$parent_id);
                }
            };

            $region=Region::where($where)
                ->select(['id','parent_id','name','name_en','type','query_string'])
                ->withCount('children')
                ->paginate($limit)
                ->toArray();



            foreach ($region['data'] as $key=>$item){
                if($item['children_count']>0){
                    $region['data'][$key]['hasChildren']=true;
                }
            }

            $response['data']=$region['data'];
            $response['total']=$region['total'];
            $response['code']=ReturnCode::SUCCESS;

            return response($response);
//            return response(ReturnCode::success($region));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function index2(Request $request)
    {
        try{
            $type=$request->input('type',null);
            $keyword=$request->input('keyword',null);

            $where=function ($query) use($keyword){
                if(!empty($keyword)){
                    $query->where('name','like','%'.$keyword.'%');
                }
            };

            $mm=Region::where($where)->get(['id','parent_id','query_string']);
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

            $region=new Region();
            $regions=$region->getLevel($type,$ids,['id','parent_id','name','name_en','type','query_string']);

            return response(ReturnCode::success($regions));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}