<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/5
 * Time: 14:57
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Message\Messages;
use App\Models\Message\MessagesStatus;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    //消息列表
    /**
     * @des 消息列表-主表
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try{
            $limit   = $request->input('limit',20);
            $date=$request->input('created_at',null);//创建时间
            $keyword=$request->input('keyword',null);
            $type=$request->input('msg_type',null);
            $level=$request->input('msg_level',null);

            $where=function ($query) use($date,$keyword,$type,$level){
                if(!empty($date)){
                    $query->whereBetween('created_at',[date('Y-m-d 0:00:00',strtotime($date[0])),date('Y-m-d 23:59:59',strtotime($date[1]))]);
                }
                if(!empty($keyword)){
                    $query->where(function ($q)use($keyword){
                        $q->orWhere('msg_title','like','%'.$keyword.'%')->orWhere('msg_content','like','%'.$keyword.'%');
                    });
                }
                if(strlen($type)){
                    $query->where('msg_type',$type);
                }
                if(strlen($level)){
                    $query->where('msg_level',$level);
                }
            };

            $data=Messages::where($where)
                ->select(['id','parent_id','iot_id','send_type','msg_type','msg_level','msg_title',
                    'msg_content','msg_html','send','status','created_code','updated_code','created_at','updated_at'])
                ->with(['creator'=>function($q){
                    $q->select(['employee_code','employee_name']);
                },'modifier'=>function($q){
                    $q->select(['employee_code','employee_name']);
                },'device'=>function($qq){
                    $qq->select(['iot_id','device_name','note']);
                }])
                ->orderByDesc('created_at')
                ->orderByDesc('msg_level')
                ->orderByDesc('msg_type')
                ->paginate($limit)
                ->toArray();

            $response['data']=$data['data'];
            $response['total']=$data['total'];
            $response['code']=ReturnCode::SUCCESS;

            return response($response);
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$exception->getMessage()));
        }
    }

    /**
     * @des 加载消息明细
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getMessage(Request $request,$id)
    {
        try{
            $limit   = $request->input('limit',100);
            $status=$request->input('status',null);

            $where=function ($q) use($status){
                if(strlen($status)>0){
                    $q->where('status',$status);
                }
            };

            $data=MessagesStatus::where('messages_id',$id)->where($where)
                ->with(['toId'=>function($q){
                    $q->select(['id','employee_code','employee_name']);
                }])
                ->orderByDesc('msg_level')
                ->orderByDesc('msg_type')
                ->orderByDesc('created_at')
                ->orderByDesc('sign')
                ->paginate($limit)
                ->toArray();

            $response['data']=$data['data'];
            $response['total']=$data['total'];
            $response['code']=ReturnCode::SUCCESS;
            $response['ttl']=[
                'read1'=>MessagesStatus::where('messages_id',$id)->where('status',0)->count(),
                'read2'=>MessagesStatus::where('messages_id',$id)->where('status',1)->count(),
            ];

            return response($response);
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$exception->getMessage()));
        }
    }

    //添加消息，是否推送
    public function add(Request $request)
    {
        try{
            $send_type=$request->input('send_type',0);
            $msg_type=$request->input('msg_type',2);
            $msg_level=$request->input('msg_level',1);
            $msg_title=$request->input('msg_title',null);
            $msg_content=$request->input('msg_content',null);
            $msg_html=$request->input('msg_html',null);

            $sort=Messages::count()+1;
//            DB::beginTransaction();
            Messages::create([
                'send_type'=>$send_type,
                'msg_type'=>$msg_type,
                'msg_level'=>$msg_level,
                'msg_title'=>$msg_title,
                'msg_content'=>$msg_content,
                'msg_html'=>$msg_html,
                'form_id'=>Token::$uid,
                'created_code'=>Token::$ucode,
                'send'=>0,
                'sort'=>$sort
            ]);
//            DB::commit();
            return response(ReturnCode::success([],'添加成功'));
        }
        catch (\Exception $exception){
//            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$exception->getMessage()));
        }
    }
    //编辑-未推送，可以编辑
    public function edit(Request $request,$id)
    {
        try{
            $message=Messages::find($id);
            if(!$message){
                return response(ReturnCode::error(1001,'记录不存在'));
            }

            $send_type=$request->input('send_type',0);
            $msg_type=$request->input('msg_type',2);
            $msg_level=$request->input('msg_level',1);
            $msg_title=$request->input('msg_title',null);
            $msg_content=$request->input('msg_content',null);
            $msg_html=$request->input('msg_html',null);

            $message->send_type=$send_type;
            $message->msg_type=$msg_type;
            $message->msg_level=$msg_level;
            $message->msg_title=$msg_title;
            $message->msg_content=$msg_content;
            $message->msg_html=$msg_html;
            $message->updated_code=Token::$ucode;
            $message->save();

            return response(ReturnCode::success([],'修改成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$exception->getMessage()));
        }
    }

    //删除消息-未推送，可以删除
    public function delete(Request $request,$id)
    {
        try{
            $message=Messages::find($id);
            if(!$message){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $where=function ($q)use($id){
                $q->orWhere('id',$id)->orWhere('query_string','like','%'.$id.'%');
            };

            DB::beginTransaction();
            Messages::where($where)->update(['deleted_code'=>Token::$ucode]);
            Messages::where($where)->delete();

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
     * @des 推送消息
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function send(Request $request,$id)
    {
        try{
            $message=Messages::find($id);
            if(!$message){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $send=$request->input('send',null);
            $send2=explode(',',$send);
            $insert=[];
            foreach ($send2 as $key=>$item){
                $insert[]=[
                    'messages_id'=>$id,
                    'to_id'=>$item,
                    'msg_type'=>$message->msg_type,
                    'msg_level'=>$message->msg_level,
                    'created_code'=>Token::$ucode
                ];
            }
            DB::beginTransaction();
            $count=MessagesStatus::insert($insert);
            $message->send=MessagesStatus::where('messages_id',$id)->count();
            $message->save();
            DB::commit();

            return response(ReturnCode::success($count));
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }


    //个人消息=================================================
    /**
     * @des 个人消息列表
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index2(Request $request)
    {
        try{
            $limit   = $request->input('limit',20);
            $date=$request->input('created_at',null);//创建时间
            $keyword=$request->input('keyword',null);
            $type=$request->input('msg_type',null);
            $level=$request->input('msg_level',null);
            $status=$request->input('status',null);

            $where=function ($query) use($date,$keyword,$type,$level,$status){
                if(!empty($date)){
                    $query->whereBetween('created_at',[date('Y-m-d 0:00:00',strtotime($date[0])),date('Y-m-d 23:59:59',strtotime($date[1]))]);
                }
                if(!empty($keyword)){
//                    $query->where();
                }
                if(strlen($type)){
                    $query->where('msg_type',$type);
                }
                if(strlen($level)){
                    $query->where('msg_level',$level);
                }
                if(strlen($status)){
                    $query->where('status',$status);
                }
            };

            $data=MessagesStatus::where('to_id',Token::$uid)->where($where)
                ->select(['id','messages_id','msg_type','msg_level','sign','warn_count',
                    'warn_date','status','detail','created_code','updated_code','created_at','updated_at'])
                ->with(['message'=>function($q){
                    $q->select(['id','iot_id','form_id','msg_title','msg_content','msg_html'])->with(['device'=>function($qq){
                        $qq->select(['iot_id','device_name','note']);
                    }]);
                },'creator'=>function($q){
                    $q->select(['employee_code','employee_name']);
                },'modifier'=>function($q){
                    $q->select(['employee_code','employee_name']);
                }])
                ->orderByDesc('msg_level')
                ->orderByDesc('msg_type')
                ->orderByDesc('created_at')
                ->orderByDesc('sign')
                ->paginate($limit)
                ->toArray();

            $response['data']=$data['data'];
            $response['total']=$data['total'];
            $response['code']=ReturnCode::SUCCESS;

            return response($response);
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$exception->getMessage()));
        }
    }

    /**
     * @des 信息详情
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function detail(Request $request,$id)
    {
        try{
            $data=MessagesStatus::where('id',$id)
                ->select(['id','messages_id','msg_type','msg_level','sign','warn_count',
                    'warn_date','status','detail','created_code','updated_code','created_at','updated_at'])
                ->with(['message'=>function($q){
                    $q->select(['id','iot_id','form_id','msg_title','msg_content','msg_html'])->with(['device'=>function($qq){
                        $qq->select(['iot_id','device_name','note']);
                    }]);
                },'creator'=>function($q){
                    $q->select(['employee_code','employee_name']);
                },'modifier'=>function($q){
                    $q->select(['employee_code','employee_name']);
                }])->first();

            return response(ReturnCode::success($data));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::SYSTEM_FAIL,$exception->getMessage()));
        }
    }
    /**
     * @des 标记已读
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function read(Request $request,$id)
    {
        try{
            $messageStatus=MessagesStatus::find($id);
            if(!$messageStatus){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            DB::beginTransaction();

            $messageStatus->status=1;
            $messageStatus->updated_code=Token::$ucode;
            $messageStatus->save();

            $msg_id=$messageStatus->messages_id;
            $count=MessagesStatus::where('messages_id',$msg_id)->where('status',1)->count();
            Messages::where('id',$msg_id)->update(['status'=>$count]);

            DB::commit();
            return response(ReturnCode::success([],'标记已读'));
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 标记所选，或所有
     * @param Request $request
     * @param $type
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function readAll(Request $request,$type)
    {
        try{
            $msgArr=[];
            DB::beginTransaction();
            if($type=='all'){
                $msgArr=MessagesStatus::where('status',0)->pluck('messages_id');
                if(count($msgArr->toArray())==0){
                    return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'无未读记录'));
                }
                MessagesStatus::where('status',0)->update(['status'=>1,'updated_code'=>Token::$ucode]);
            }else{
                $ids=$request->input('ids',null);
                $ids=explode(',',$ids);

                $msgArr=MessagesStatus::whereIn('id',$ids)->where('status',0)->pluck('messages_id');
                if(count($msgArr->toArray())==0){
                    return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST,'无未读记录'));
                }
                MessagesStatus::whereIn('id',$ids)->where('status',0)->update(['status'=>1,'updated_code'=>Token::$ucode]);
            }
            $msgArr=array_unique($msgArr);
            foreach ($msgArr as $item){
                $count=MessagesStatus::where('messages_id',$item)->where('status',1)->count();
                Messages::where('id',$item)->update(['status'=>$count]);
            }

            DB::commit();
            return response(ReturnCode::success([],'标记完成'));
        }
        catch (\Exception $exception){
            DB::rollBack();
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
    public function delete2(Request $request,$id)
    {
        try{
            $messageStatus=MessagesStatus::find($id);
            if(!$messageStatus){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }

            $messageStatus->deleted_code=Token::$ucode;
            $messageStatus->save();

            $messageStatus->delete();

            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 星级邮件
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function star(Request $request,$id)
    {
        try{
            $sign=$request->input('sign2',null);
            $messageStatus=MessagesStatus::find($id);
            if(!$messageStatus){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $messageStatus->updated_code=Token::$ucode;
            $messageStatus->sign=$sign;
            $messageStatus->save();

            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    /**
     * @des 首页消息加载
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function home(Request $request)
    {
        try{
            $data=[
                'num'=>MessagesStatus::where('to_id',Token::$uid)->where('status',0)->count(),
                'warn'=>MessagesStatus::getMsg(3),
                'psn'=>MessagesStatus::getMsg(1),
                'sys'=>MessagesStatus::getMsg(2)
            ];

            return response(ReturnCode::success($data));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}