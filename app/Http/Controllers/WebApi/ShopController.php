<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/16
 * Time: 13:27
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Gashapon\Store;
use App\Models\Shop\Shop;
use App\Models\Shop\ShopManager;
use App\Models\Shop\ShopStore;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    /*public function jtest(Request $request){


        $keyword=$request->input('keyword',null);
        $limit=$request->input('limit',1);

        $where=function ($q)use($keyword){
            if(!empty($keyword)){
                $q->where(function ($qq)use ($keyword){
                    $qq->orWhere('name','like','%'.$keyword.'%');
                    $qq->orWhere('name_cn','like','%'.$keyword.'%');
                    $qq->orWhere('name_en','like','%'.$keyword.'%');
                    $qq->orWhere('code','like','%'.$keyword.'%');
                });
            }

            $q->where('status_dz','正常');
            $q->where('id','<','30000');

        };

        DB::enableQueryLog();
        $data=Store::where($where)->paginate($limit)->toArray();


       // print_r(  DB::getQueryLog());

        return response($data);
    }*/

    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $keyword=$request->input('keyword',null);
            $status=$request->input('status',null);

            $where=function ($q)use($keyword,$status){
                if(!empty($keyword)){
                    $q->where(function ($qq)use ($keyword){
                        $qq->orWhere('name','like','%'.$keyword.'%');
                        $qq->orWhere('name_cn','like','%'.$keyword.'%');
                        $qq->orWhere('name_en','like','%'.$keyword.'%');
                        $qq->orWhere('code','like','%'.$keyword.'%');
                    });
                }

                if(!empty($status)){
                    $q->where('status',$status);
                }
            };

            $data=Shop::where($where)->where('parent_id',0)
                ->with(['children'=>function($q){
                    $q->orderBy('sort');
                }])
                ->orderBy('sort')
                ->orderBy('id')
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
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function add(Request $request)
    {
        try{
            $code     = $request->input('code',null);
            $name     = $request->input('name',null);
            $nameCn   = $request->input('name_cn',null);
            $nameEn   = $request->input('name_en',null);
            $parentId = $request->input('parent_id',0);
            $intro    = $request->input('intro',null);
            $remarks  = $request->input('remarks',null);
            $image_id = $request->input('image_id',null);
            $sort     = $request->input('sort',null);

            $shop=Shop::where('code',$code)->first();
            if($shop){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'商家编号已存在'));
            }

            $shop=Shop::where('name',$name)->first();
            if($shop){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'商家名称已存在'));
            }

            if(empty($sort)){
                $sort=Shop::count()+1;
            }

            $query_string=null;

            if(!empty($parentId)){
                $pid=Shop::where('id',$parentId)->first(['id','query_string']);
                if($pid->query_string){
                    $query_string=$pid->query_string.','.$pid->id;
                }else{
                    $query_string=$pid->id;
                }
            }

            Shop::create([
                'code'         => $code,
                'name'         => $name,
                'name_cn'      => $nameCn,
                'name_en'      => $nameEn,
                'parent_id'    => $parentId?$parentId:0,
                'intro'        => $intro,
                'remarks'      => $remarks,
                'image_id'     => $image_id,
                'sort'         => $sort,
                'query_string'=> $query_string,
                'created_code' => Token::$ucode
            ]);

            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function edit(Request $request,$id)
    {
        try{
            $shop=Shop::find($id);
            if(!$shop){
                return response(ReturnCode::error(ReturnCode::NOT_FOUND,'未找到该商家'));
            }

            $code     = $request->input('code',null);
            $name     = $request->input('name',null);
            $nameCn   = $request->input('name_cn',null);
            $nameEn   = $request->input('name_en',null);
            $intro    = $request->input('intro',null);
            $remarks  = $request->input('remarks',null);
            $image_id = $request->input('image_id',null);
            $sort     = $request->input('sort',null);

            $shop2=Shop::where('code',$code)->where('id','<>',$id)->first();
            if($shop2){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'商家编号已存在'));
            }

            $shop2=Shop::where('name',$name)->where('id','<>',$id)->first();
            if($shop2){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'商家名称已存在'));
            }
//            $query_string=null;
//            if(!empty($shop2->parent_id)){
//                $pid=Shop::where('id',$shop2->parent_id)->first(['id','query_string']);
//                if($pid->query_string){
//                    $query_string=$pid->query_string.','.$pid->id;
//                }else{
//                    $query_string=$pid->id;
//                }
//            }

            DB::beginTransaction();

            if($sort!=$shop->sort){
                //大排序改小排序
                if($sort<$shop->sort){
                    Shop::where('parent_id',$shop->parent_id)
                        ->where('sort','<',$shop->sort)
                        ->where('sort','>=',$sort)
                        ->increment('sort');
                }
                if($sort>$shop->sort){
                    Shop::where('parent_id',$shop->parent_id)
                        ->where('sort','>',$shop->sort)
                        ->where('sort','<=',$sort)
                        ->decrement('sort');
                }
            }

            Shop::where('id',$id)->update([
                'code'         => $code,
                'name'         => $name,
                'name_cn'      => $nameCn,
                'name_en'      => $nameEn,
                'intro'        => $intro,
                'remarks'      => $remarks,
                //'image_id'     => $image_id,
                'sort'         => $sort,
                'updated_code' => Token::$ucode
            ]);

            DB::commit();

            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function delete(Request $request,$id)
    {
        try{
            $shop=Shop::find($id);
            if(!$shop){
                return response(ReturnCode::error(ReturnCode::NOT_FOUND,'未找到该商家'));
            }

            DB::beginTransaction();

            $shop->deleted_code=Token::$ucode;
            $shop->save();

            $shop->delete();

            //同时删除负责人
            ShopManager::where('shop_id',$id)->delete();

            ShopStore::where('shop_id',$id)->update(['status'=>2]);

            DB::commit();
            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public function setMain(Request $request,$id)
    {
        try{
            $shop=Shop::find($id);
            if(!$shop){
                return response(ReturnCode::error(ReturnCode::NOT_FOUND,'未找到该商家'));
            }

            DB::beginTransaction();

            Shop::where('is_main',1)->update(['is_main'=>0]);
            $shop->is_main=1;
            $shop->save();

            DB::commit();
            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * 修改商家状态
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/29 14:13
     */
    public function changeStatus(Request $request,$id)
    {
        try{

            $shop=Shop::find($id);
            if(!$shop){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'商家不存在'));
            }

            $status=$request->input('status',0);

            DB::beginTransaction();
            if($status==1){
                $shop->status=1;
                $shop->save();

                if($shop->parent_id!=0){
                    Shop::where('id',$shop->parent_id)->update(['status'=>1]);
                }
            }

            if($status==2){
                $shop->status=2;
                $shop->save();

                if($shop->parent_id==0){
                    Shop::where('parent_id',$id)->where('status',1)->update(['status'=>2]);
                }
            }
            DB::commit();

            return response(ReturnCode::success());
        }catch (\Exception $e){
            DB::rollBack();
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }
}