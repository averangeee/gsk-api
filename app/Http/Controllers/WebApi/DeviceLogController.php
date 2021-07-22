<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/29
 * Time: 10:17
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\ApiIot\RequestIotLog;
use App\Models\ApiIot\ResponseIotLog;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceLogController extends Controller
{
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $date=$request->input('created_at',null);
            $keyword=$request->input('keyword',null);
            $method=$request->input('method',null);
            $iot_id=$request->input('iot_id',null);

            $where=function ($query) use($date,$keyword,$method,$iot_id){
                if(!empty($date)){
                    $query->whereBetween('created_at',[date('Y-m-d 0:00:00',strtotime($date[0])),date('Y-m-d 23:59:59',strtotime($date[1]))]);
                }
                if(!empty($keyword)){
                    $query->where(function ($q) use($keyword){
                        $q->orWhere('fun','like','%'.$keyword.'%');
                    });
                }
                if(!empty($method)){
                    $query->where('method',$method);
                }

                if(!empty($iot_id)){
                    $query->where('iot_id',$iot_id);
                }
            };

            $data=RequestIotLog::where($where)
                ->select(['id','method','params','path','fun','url','type','ip','user_agent','iot_id','device_code','created_at'])
                ->with(['res'=>function($qq){
                    $qq->select(['id','request_log_id','res_code','res_msg','res_detail','created_at']);
                },'iot'=>function($q){
                    $q->select(['iot_id','note','device_name']);
                },'device'=>function($q){
                    $q->select(['iot_id','note','device_name']);
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

    public function delete(Request $request,$id)
    {
        try{
            $log=RequestIotLog::find($id);
            if(!$log){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            DB::beginTransaction();
            $log->deleted_code=Token::$ucode;
            $log->save();

            $log->delete();

            ResponseIotLog::where('request_log_id',$log->id)->delete();

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