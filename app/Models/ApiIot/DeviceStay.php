<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/23
 * Time: 16:26
 */

namespace App\Models\ApiIot;


use App\Libs\ReturnCode;
use App\Models\BaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceStay extends BaseModel
{
    protected $table='device_stay';


    //查询
    public static function query(Request $request)
    {
        try{
            $limit=$request->input('limit',10);


        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public static function single(Request $request)
    {
        try{
           $data=self::where('status',1)
               ->first(['id','buy_stay','supply_stay','maintain_stay']);

            return response(ReturnCode::success($data));
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

    public static function add(Request $request)
    {
        try{
            $buy=$request->input('buy_stay',60);
            $supply=$request->input('supply_stay',60);
            $maintain=$request->input('maintain_stay',120);
            $check=$request->input('is_check',false);

            $count=self::where('status',1)
                ->where('buy_stay',$buy)
                ->where('supply_stay',$supply)
                ->where('maintain_stay',$maintain)->count();

            if($count){
                return response(ReturnCode::success('','未更新'));
            }else{
                DB::beginTransaction();
                self::where('status',1)->update(['status'=>2]);
                $data=[
                    'buy_stay'=>$buy,
                    'supply_stay'=>$supply,
                    'maintain_stay'=>$maintain,
                    'status'=>1
                ];
                self::create($data);

                $msg='';
                //更新未撤机的设备时间，where('status2',1)->
                if($check){
                    $dd=Device::update([
                        'buy_stay'=>$buy,
                        'supply_stay'=>$supply,
                        'maintain_stay'=>$maintain
                    ]);
                    $msg='更新设备数'.$dd.'台';
                }

                DB::commit();
                return response(ReturnCode::success($msg,'设置成功'));
            }
        }
        catch (\Exception $exception){
            DB::rollBack();
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }

}