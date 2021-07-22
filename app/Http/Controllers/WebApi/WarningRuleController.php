<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/7/5
 * Time: 17:57
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Token;
use App\Models\Warn\WarningRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WarningRuleController extends Controller
{
    /**
     * @des 加载规则
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $status=$request->input('status',null);
            $keyword=$request->input('keyword',null);


            $where=function ($query) use($status,$keyword){
                if(strlen($status)>0){
                    $query->where('status',$status);
                }
                if(!empty($keyword)){
                    $query->where(function ($q) use($keyword){
                        $q->orWhere('name','like','%'.$keyword.'%')->orWhere('des','like','%'.$keyword.'%');
                    });
                }
            };

            $data=WarningRule::where($where)
                ->orderBy('sort')
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

    //编辑规则
    public function edit(Request $request,$id)
    {
        try{
            $warn=WarningRule::find($id);
            if(!$warn){
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
    //1 启用，0 停用
    public function stop(Request $request,$id)
    {
        try{
            $warn=WarningRule::find($id);
            if(!$warn){
                return response(ReturnCode::error(ReturnCode::NOT_FOUND));
            }

            $status=$request->input('status',1);

            $warn->status=$status;
            $warn->updated_code=Token::$ucode;
            $warn->save();

            return response(ReturnCode::success());
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

}