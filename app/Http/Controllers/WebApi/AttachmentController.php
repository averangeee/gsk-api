<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/31
 * Time: 11:26
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\System\Attachment;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $created_at=$request->input('created_at',null);
            $keyword=$request->input('keyword',null);
            $storage_type=$request->input('storage_type',null);
            $filesize_where=$request->input('filesize_where','=');
            $filesize=$request->input('filesize',null);
            $file_ext=$request->input('file_ext',null);
            $filed_type=$request->input('filed_type',null);

            $where=function ($query) use($created_at,$keyword,$storage_type,$filesize_where,$filesize,$file_ext,$filed_type){
                if(!empty($created_at)){
                    $query->whereBetween('created_at',[date('Y-m-d 0:00:00',strtotime($created_at[0])),date('Y-m-d 23:59:59',strtotime($created_at[1]))]);
                }
                if(!empty($keyword)){
                    $query->where(function ($q)use($keyword){
                        $q->orWhere('file_name','like','%'.$keyword.'%')->orWhere('file_url','like','%'.$keyword.'%')
                            ->orWhere('file_path','like','%'.$keyword.'%');
                    });
                }
                if(strlen($storage_type)){
                    $query->where('storage_type',$storage_type);
                }
                if(strlen($filesize)){
                    $query->where('filesize',$filesize_where,$filesize);
                }
                if(strlen($file_ext)){
                    $query->where('file_ext',$file_ext);
                }
                if(strlen($filed_type)){
                    $query->where('filed_type',$filed_type);
                }
            };

            $data=Attachment::where($where)
                ->select(['storage_type','file_name','file_key','file_url','file_path','filesize','filed_type','file_ext','created_code','created_at'])
                ->with(['creator'=>function($qc){
                    $qc->select(['employee_code','employee_name']);
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
            $attach=Attachment::find($id);
            if(!$attach){
                return response(ReturnCode::error(ReturnCode::RECORD_NOT_EXIST));
            }
            $attach->deleted_code=Token::$ucode;
            $attach->save();
            $attach->delete();

            return response(ReturnCode::success([],'删除成功'));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}