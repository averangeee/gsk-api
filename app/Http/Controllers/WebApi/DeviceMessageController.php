<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/27
 * Time: 15:02
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\ApiIot\DeviceMessage;
use App\Models\ApiIot\DeviceMessageRead;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceMessageController extends Controller
{
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $date=$request->input('created_at',null);//创建时间
            $keyword=$request->input('keyword',null);
            $type=$request->input('type',null);
            $result=$request->input('result',null);
            $status=$request->input('status',null);

            $where=function ($query) use($date,$keyword,$type,$result,$status){
                if(!empty($date)){
                    $query->whereBetween('created_at',[date('Y-m-d 0:00:00',strtotime($date[0])),date('Y-m-d 23:59:59',strtotime($date[1]))]);
                }
                if(!empty($keyword)){
                    $query->where(function ($q) use($keyword){
                        $q->where('device_name','like','%'.$keyword.'%')->orWhere('msg','like','%'.$keyword.'%');
                    });
                }
                if(!empty($type)){
                    $query->where('type',$type);
                }
                if(strlen($result)){
                    $query->where('result',$result);
                }
                if(strlen($status)){
                    $query->where('status',$status);
                }
            };

            $data=DeviceMessage::where($where)
                ->select(['id','msg_id','device_name','topic_short','type','msg','con','content',
                    'qos','result','message_id','result_content','status','created_at'])
                ->with(['device'=>function($q){
                    $q->select(['device_name','note','store_code'])->with(['store'=>function($qq){
                        $qq->select(['store_code','store_name','version_id'])->orderBy('version_id');
                    }]);
                }])
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

    public function read(Request $request,$id)
    {
        try{
            $deviceMessage=DeviceMessage::find($id);
            if(!$deviceMessage){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            DB::beginTransaction();
            $deviceMessage->updated_code=Token::$ucode;
            $deviceMessage->status=1;
            $deviceMessage->save();

            $count=DeviceMessageRead::where('msg_id',$deviceMessage->msg_id)
                ->where('device_name',$deviceMessage->device_name)->count();

            if($count==0){
                DeviceMessageRead::create([
                    'msg_id'=>$deviceMessage->msg_id,
                    'device_name'=>$deviceMessage->device_name,
                    'device_name'=>$deviceMessage->device_name,
                    'created_code'=>Token::$ucode
                ]);
            }

            DB::commit();
            return response(ReturnCode::success([],'执行成功'));
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
            $deviceMessage=DeviceMessage::find($id);
            if(!$deviceMessage){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            DB::beginTransaction();
            $deviceMessage->deleted_code=Token::$ucode;
            $deviceMessage->save();

            $deviceMessage->delete();

            DeviceMessageRead::where('msg_id',$deviceMessage->msg_id)
                ->where('device_name',$deviceMessage->device_name)->delete();

            DB::commit();
            return response(ReturnCode::success([],'执行成功'));
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}