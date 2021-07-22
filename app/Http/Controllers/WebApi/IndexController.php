<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/30
 * Time: 13:32
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\Base\DefineNote;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        return view('index.index');
    }
}