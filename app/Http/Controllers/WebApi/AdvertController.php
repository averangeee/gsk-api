<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/6/12
 * Time: 18:05
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Shop\Adverts;
use App\Models\Shop\AdvertsDetail;
use App\Models\Shop\Shop;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdvertController extends Controller
{
    /**
     * 广告列表
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/12 18:15
     */
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $createdAt=$request->input('created_at',[]);
            $keyword=$request->input('keyword',null);
            $status=$request->input('status',null);

            $where=function ($q)use($createdAt,$keyword,$status){
                if(!empty($keyword)){
                    $q->where(function ($qq)use ($keyword){
                        $qq->where('name','like','%'.$keyword.'%')->orWhere('des','like','%'.$keyword.'%');
                    });
                }

                if(count($createdAt)>0){
                    $q->whereBetween('created_at',[date('Y-m-d 0:00:00',strtotime($createdAt[0])),date('Y-m-d 23:59:59',strtotime($createdAt[1]))]);
                }

                if(!empty($status)){
                    $q->where('status',$status);
                }
            };

            $advert=Adverts::where($where)
                ->with(['shop'=>function($q){
                    $q->select(['id','name','query_string']);
                }])
                ->orderByDesc('id')
                ->paginate($limit)
                ->toArray();

            $response['code']=ReturnCode::SUCCESS;
            $response['data']=$advert['data'];
            $response['total']=$advert['total'];

            return response($response);
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 添加广告
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/13 11:32
     */
    public function create(Request $request)
    {
        try{
            $shopId   = $request->input('shop_id',null);
            $name     = $request->input('name',null);
            $des      = $request->input('des',null);
            $repeat   = $request->input('repeat1',null);
            $startEnd = $request->input('start_end',[]);
            $startEnd = explode(',',$startEnd);


            $shop = Shop::find($shopId);
            if(!$shop){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'商家不存在'));
            }

            $advert = Adverts::where('shop_id',$shopId)->where('name',$name)->first();
            if($advert){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'该商家广告名称已存在'));
            }

            DB::beginTransaction();

            Adverts::create([
                'shop_id'      => $shopId,
                'name'         => $name,
                'des'          => $des,
                'repeat1'      => $repeat,
                'start_date'   => $startEnd[0],
                'end_date'     => $startEnd[1],
                'created_code' => Token::$ucode
            ]);

            DB::commit();

            return response(ReturnCode::success());
        }catch (\Exception $e){
            DB::rollBack();
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 修改广告
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/13 13:24
     */
    public function update(Request $request,$id)
    {
        try{
            $advert = Adverts::find($id);
            if(!$advert){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'广告不存在'));
            }
            $shopId   = $request->input('shop_id',null);
            $name     = $request->input('name',null);
            $des      = $request->input('des',null);
            $repeat   = $request->input('repeat1',null);
            $startEnd = $request->input('start_end',[]);
            $startEnd = explode(',',$startEnd);

            $shop = Shop::find($shopId);
            if(!$shop){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'商家不存在'));
            }

            $advert = Adverts::where('shop_id',$shopId)->where('id','<>',$id)->where('name',$name)->first();
            if($advert){
                return response(ReturnCode::error(ReturnCode::RECORD_EXIST,'该商家广告名称已存在'));
            }

            DB::beginTransaction();

            Adverts::where('id',$id)
                ->update([
                    'shop_id'      => $shopId,
                    'name'         => $name,
                    'des'          => $des,
                    'repeat1'      => $repeat,
                    'start_date'   => $startEnd[0],
                    'end_date'     => $startEnd[1],
                    'updated_code' => Token::$ucode
                ]);

            DB::commit();

            return response(ReturnCode::success());
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 启用/停用广告
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/13 14:11
     */
    public function changeStatus(Request $request,$id)
    {
        try{
            $advert = Adverts::find($id);
            if(!$advert){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'广告不存在'));
            }

            $status = $request->input('status',1);
            $advert->status = $status;
            $advert->updated_code = Token::$ucode;
            $advert->save();

            return response(ReturnCode::success());
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 删除广告
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/13 14:15
     */
    public function delete(Request $request,$id)
    {
        try{
            $advert = Adverts::find($id);
            if(!$advert){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'广告不存在'));
            }

            $advert->deleted_code = Token::$ucode;
            $advert->save();

            $advert->delete();

            return response(ReturnCode::success());
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 添加广告内容
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/13 16:23
     */
    public function addDetail(Request $request)
    {
        try{

            $adverts_id = $request->input('adverts_id',null);
            $type       = $request->input('type',null);
            $repeat     = $request->input('repeat',null);
            $attach_id  = $request->input('attach_id',null);
            $h5         = $request->input('h5',null);
            $period     = $request->input('period',null);
            $startEnd   = $request->input('start_end',[]);
            $startEnd   = explode(',',$startEnd);

            $advert     = Adverts::find($adverts_id);
            if(!$advert){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'广告不存在'));
            }

            DB::beginTransaction();

            AdvertsDetail::create([
                'adverts_id'   => $adverts_id,
                'type'         => $type,
                'repeat1'      => $repeat,
                'attach_id'    => $attach_id,
                'h5'           => $h5,
                'period'       => $period,
                'start_date'   => $startEnd[0],
                'end_date'     => $startEnd[1],
                'created_code' => Token::$ucode
            ]);

            DB::commit();
            return response(ReturnCode::success());
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 获得广告内容
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/14 13:58
     */
    public function detail(Request $request,$id)
    {
        try{
            $advert = Adverts::find($id);
            if(!$advert){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'广告不存在'));
            }

            $details = AdvertsDetail::where('adverts_id',$id)
                ->with(['attach'=>function($q){
                    $q->select(['file_url','id','file_name']);
                }])
                ->get();


            return response(ReturnCode::success($details));
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 删除广告内容
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/14 17:27
     */
    public function deleteDetail(Request $request,$id)
    {
        try{

            $detail = AdvertsDetail::find($id);
            if(!$detail){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'广告内容不存子'));
            }

            $detail->deleted_code = Token::$ucode;
            $detail->save();

            $detail->delete();

            return response(ReturnCode::success());
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }

    /**
     * 修改广告内容
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/6/14 17:46
     */
    public function editDetail(Request $request,$id)
    {
        try{

            $detail = AdvertsDetail::find($id);
            if(!$detail){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'广告内容不存在'));
            }

            $type      = $request->input('type',1);
            $repeat    = $request->input('repeat',null);
            $attach_id = $request->input('attach_id',null);
            $h5        = $request->input('h5',null);
            $period    = $request->input('period',null);
            $start_end = $request->input('start_end',[]);

            $detail->type         = $type;
            $detail->repeat1      = $repeat;
            $detail->attach_id    = $attach_id;
            $detail->h5           = $h5;
            $detail->period       = $period;
            $detail->start_date   = $start_end[0];
            $detail->end_date     = $start_end[1];
            $detail->updated_code = Token::$ucode;
            $detail->save();

            return response(ReturnCode::success());
        }catch (\Exception $e){
            Log::error($e);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$e->getMessage()));
        }
    }
}