<?php

namespace App\Http\Controllers\WebApi;


use App\Libs\Helper;
use App\Libs\ReturnCode;
use App\Models\Mh\Banner;
use App\Models\System\Attachment;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OSS\OssClient;

class ImageController extends Controller
{
    //图片信息保存
    function save(Request $request)
    {
        $id = $request->input('id');
        $device_id = $request->input('device_id');
        $img_url = $request->input('img_url');
        $type = $request->input('type');
        $sort = $request->input('sort');
        $status = $request->input('status');
        $s_type = $request->input('s_type');//1修改状态


        if (!$id) {//新增
            $data['type'] = $type;
            $data['img_url'] = $img_url;
            $data['sort'] = $sort;
            $data['created_at'] = Helper::datetime();
            $result = Banner::insertGetId($data);
        } else {//修改
            $detail = Banner::where('id', $id)->first();
            if ($detail) {
                if ($s_type == 1) {//修改状态
                    $detail->status = $status;
                } else {
                    $detail->type = $type;
                    $detail->img_url = $img_url;
                    $detail->sort = $sort;
                }
                $detail->updated_at = Helper::datetime();
                $result = $detail->save();
            } else {
                return response(ReturnCode::error('102', '对象不存在！'));
            }
        }

        if ($result > 0) {
            return response(ReturnCode::success('', '保存成功！'));
        } else {
            return response(ReturnCode::error('102', '存储失败，请稍后再试！'));
        }
    }

    //图片列表
    function image_list(Request $request)
    {
        $limit = $request->input('limit');
        $status = $request->input('zt');
        $type = $request->input('type');
        $where = function ($q) use ($status, $type) {
            if ($status!=null && $status!="") {
                $q->where('status', $status);
            }
            if ($type) {
                $q->where('type', $type);
            }
        };
        $list = Banner::where($where)->paginate($limit);

        return response(ReturnCode::success($list, '保存成功！'));
    }
}