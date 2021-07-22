<?php

namespace App\Http\Controllers\AppApi;

use App\Libs\ReturnCode;
use App\Models\GSK\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    function detail(Request $request)
    {
        print_r($request->input());
    }

    //获取设备信息
    function info(Request $request)
    {
        $code = $request->input('code');
        $detail = Device::where('py_code', $code)->first();
        return response(ReturnCode::success($detail, 'success'));
    }

}
