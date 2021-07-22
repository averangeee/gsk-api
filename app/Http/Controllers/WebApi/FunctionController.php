<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/28
 * Time: 17:30
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\System\MenusFunction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FunctionController extends Controller
{
    public function index(Request $request)
    {
        try{
            $limit=$request->input('limit',10);
            $keyword=$request->input('keyword',null);

            
        }
        catch (\Exception $exception){
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED,$exception->getMessage()));
        }
    }
}