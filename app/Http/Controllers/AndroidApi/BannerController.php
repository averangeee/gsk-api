<?php

namespace App\Http\Controllers\AndroidApi;

use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\ApiIot\Device;
use App\Models\Mh\Banner;
use App\Models\System\Employee;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BannerController extends BaseController
{

    //首页轮播图
    function index_lb(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '首页轮播')->where('status','!=',1)->get();
        return response(ReturnCode::success($list));
    }

    //首页底部
    function index_db(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '首页底部')->where('status','!=',1)->get();
        return response(ReturnCode::success($list));
    }

    //客户信息页底部
    function  user_information_db(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '客户信息底部')->get();
        return response(ReturnCode::success($list));
    }

    //付款中底部
    function  paying_db(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '付款中底部')->get();
        return response(ReturnCode::success($list));
    }

    //机器取货底部
    function  machine_goods_db(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '机器取货底部')->get();
        return response(ReturnCode::success($list));
    }

    //顾客取货底部底部
    function  user_goods_db(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '顾客取货底部')->get();
        return response(ReturnCode::success($list));
    }

    //出货失败底部
    function  goods_fail_db(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '出货失败底部')->get();
        return response(ReturnCode::success($list));
    }

    //取货成功底部
    function  goods_success_db(Request $request)
    {
        $device_id = $request->input('device_id');

        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '取货成功底部')->where('status','!=',1)->get();
        return response(ReturnCode::success($list));
    }

    //客服底部图片
    function  kf_db(Request $request)
    {
        $device_id = $request->input('device_id');
        if (!$device_id) {
            return response(ReturnCode::error(103, '参数异常'));
        }

        $list = Banner::where('type', '客服底部')->where('status','!=',1)->get();
        return response(ReturnCode::success($list));
    }

}
