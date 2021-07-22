<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/29
 * Time: 13:43
 */

namespace App\Http\Controllers\WebApi;

use App\Libs\ReturnCode;
use App\Models\System\RequestLog;
use App\Models\System\ResponseLog;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $date=$request->input('created_at',null);
            $keyword=$request->input('keyword',null);
            $method=$request->input('method',null);
            $type=$request->input('type',null);
            $created_code=$request->input('created_code',null);

            $where=function ($query) use($date,$keyword,$method,$type,$created_code){
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
                if(strlen($type)>0){
                    $query->where('type',$type);
                }
                if(!empty($created_code)){
                    $query->where('created_code',$created_code);
                }
            };

            $data=RequestLog::where($where)
                ->select(['id','method','params','path','fun','url','type','ip','user_agent','created_code','created_at'])
                ->with(['res'=>function($qq){
                    $qq->select(['id','request_log_id','res_code','res_msg','res_detail','created_at']);
                },'creator'=>function($q){
                    $q->select(['employee_code','employee_name']);
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
            $log=RequestLog::find($id);
            if(!$log){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            DB::beginTransaction();
            $log->deleted_code=Token::$ucode;
            $log->save();

            $log->delete();

            ResponseLog::where('request_log_id',$log->id)->delete();

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