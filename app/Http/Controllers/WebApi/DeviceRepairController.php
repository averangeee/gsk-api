<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/20
 * Time: 16:22
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\ApiIot\DeviceRepair;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceRepairController extends Controller
{
    /**
     * @des 报修记录查询
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $date=$request->input('created_at',null);
            $store_code=$request->input('store_code',null);
            $repair_type_id=$request->input('repair_type_id',null);//报修类型
            $keyword=$request->input('keyword',null);
            $created_code=$request->input('created_code',null);
            $is_complete=$request->input('is_complete',null);

            $where=function ($query) use($date,$store_code,$repair_type_id,$keyword,$created_code,$is_complete){
                if(!empty($date)){
                    $query->whereBetween('created_at',[date('Y-m-d 0:00:00',strtotime($date[0])),date('Y-m-d 23:59:59',strtotime($date[1]))]);
                }
                if(!empty($store_code)){
                    $query->where('store_code',$store_code);
                }
                if(!empty($repair_type_id)){
                    $query->where('repair_type_arr','like','%'.$repair_type_id.'%');
                }
                if(!empty($keyword)){
                    $query->where('repair_note','like',$keyword);
                }
                if(!empty($created_code)){
                    $query->where('created_code',$created_code);
                }

                if(strlen($is_complete)>0){
                    $query->where('is_complete',$is_complete);
                }
            };

            $data=DeviceRepair::where($where)
                ->with(['modifier'=>function($q){
                    $q->select(['employee_code','employee_name']);
                },'note'=>function($q){
                    $q->select(['id','parent_id','des']);
                },'creator'=>function($q){
                    $q->select(['employee_code','employee_name']);
                },'iot'=>function($q){
                    $q->select(['iot_id','note']);
                },'store'=>function($q){
                    $q->select(['store_code','store_name'])->orderByDesc('version_id');
                }])
                ->orderByDesc('id')
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
     * @des 加载图片
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function loadImg(Request $request)
    {
        $rf=new RefundController();
        return $rf->getImage($request);
    }

    /**
     * @des 标记完成
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function isComplete(Request $request,$id)
    {
        try{
            $repair=DeviceRepair::find($id);
            if(!$repair){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $remarks=$request->input('remarks',null);
            $repair->updated_code=Token::$ucode;
            $repair->remarks=$remarks;
            $repair->is_complete=1;
            $repair->active_time=date('Y-m-d H:i:s');
            $repair->save();

            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 删除记录
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request,$id)
    {
        try{
            $repair=DeviceRepair::find($id);
            if(!$repair){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $repair->deleted_code=Token::$ucode;
            $repair->save();

            $repair->delete();
            return response(ReturnCode::success([],'删除成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}