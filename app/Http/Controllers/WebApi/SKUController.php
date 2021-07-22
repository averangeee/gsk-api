<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/28
 * Time: 17:47
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\Helper;
use App\Libs\ImportExcel;
use App\Libs\ReturnCode;
use App\Libs\ZipLib;
use App\Models\Gashapon\Sku;
use App\Models\Mh\SkuType;
use App\Models\Mh\SkuTypes;
use App\Models\Shop\SkuCover;
use App\Models\Shop\SkuImg;
use App\Models\Shop\ShopStore;
use App\Models\System\Attachment;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use OSS\OssClient;
use App\Models\ApiIot\Device;

class SKUController extends Controller
{

    //产品绑定分类
    function bing_type(Request $request)
    {
        $sku_id = $request->input('sku_id');
        $type_id = $request->input('type_id');

        $types_detail = SkuTypes::where('sku_id', $sku_id)->first();
        if ($types_detail) {//修改
            $types_detail->type_id = $type_id;
            $result = $types_detail->save();

        } else {//新增
            $data['type_id'] = $type_id;
            $data['sku_id'] = $sku_id;
            $data['created_at'] = Helper::datetime();
            $result = SkuTypes::insertGetId($data);

        }
        if ($result > 0) {
            return response(ReturnCode::success('', '保存成功！'));
        } else {
            return response(ReturnCode::error('102', '存储失败，请稍后再试！'));
        }
    }

    //产品分类保存
    function type_save(Request $request)
    {
        $id = $request->input('id');
        $title = $request->input('title');
        $img_url = $request->input('img_url');
        $sort = $request->input('sort');
        $status = $request->input('status');
        $s_type = $request->input('s_type');//1修改状态


        if (!$id) {//新增
            $data['title'] = $title;
            $data['img_url'] = $img_url;
            $data['sort'] = $sort;
            $data['created_at'] = Helper::datetime();
            $result = SkuType::insertGetId($data);
        } else {//修改
            $detail = SkuType::where('id', $id)->first();
            if ($detail) {
                if ($s_type == 1) {//修改状态
                    $detail->status = $status;
                } else {
                    $detail->title = $title;
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

    //产品分类列表
    function type_list(Request $request)
    {
        $limit = $request->input('limit');

        $list = SkuType:: paginate($limit);

        return response(ReturnCode::success($list, '保存成功！'));
    }

    //门店列表
    function Store_list(Request $request)
    {
        $limit = $request->input('limit');

        $list = ShopStore:: paginate($limit);

        return response(ReturnCode::success($list, '保存成功！'));
    }

    //设备绑定门店
    function StoreBind_save(Request $request)
    {
        $device_id= $request->input('device_id');
        $store_code = $request->input('store_code');
        $device_name = $request->input('device_name');
            $updated_at = Helper::datetime();
            $result=DB::update('update sl_device set store_code="'.$store_code.'" , device_name="'.$device_name.'" , updated_at ="'.$updated_at. '" where id='.$device_id);
        if ($result > 0) {
            return response(ReturnCode::success('', '保存成功！'));
        } else {
            return response(ReturnCode::error('102', '存储失败，请稍后再试！'));
        }
    }


    public function index(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $keyword = $request->input('keyword', null);
            $version = $request->input('version', null);

            $where = function ($query) use ($keyword) {
                if (!empty($keyword)) {
                    $query->orWhere('sku_id', 'like', '%' . $keyword . '%')
                        ->orWhere('sku_name', 'like', '%' . $keyword . '%');
                }
            };

            if (empty($version)) {
                $version = Sku::max('version_id');
            }

            $data = Sku::where('is_del', 0)
                ->where('version_id', $version)
                ->where($where)
                ->orderByDesc('price')
                ->paginate($limit)
                ->toArray();
            $list = $data['data'];
            foreach ($list as $k => $v) {
                $imgDetail = SkuImg::where('sku_code', $v['sku_id'])->first();
                $list[$k]['file_url'] = $imgDetail;
                $types_detail = SkuTypes::where('sku_id', $v['sku_id'])->first();
                $type_name = '未分类';
                if ($types_detail) {
                    $type_detail = SkuType::where('id', $types_detail->type_id)->first();
                    if ($type_detail) {
                        $type_name = $type_detail->title;
                    }
                }
                $list[$k]['type_name'] = $type_name;
            }

            $response['data'] = $list;
            $response['total'] = $data['total'];
            $response['code'] = ReturnCode::SUCCESS;

            return response($response);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    public function test()
    {
        $path = 'app/public/upload/20190627170058';
        $path = storage_path($path);
        $file = scandir($path);

        $fileSku = null;
        foreach ($file as $item) {
            if ($item != '.' && $item != '..') {
                $fileSku = $item;
            }
        }
        if (!empty($fileSku)) {
            $pathSub = $path . '/' . $fileSku;
            $file = scandir($pathSub);
            foreach ($file as $item) {
                if ($item != '.' && $item != '..') {
                    $fileImg = $pathSub . '/' . $item;
                    $mimetype = exif_imagetype($fileImg);
                    if ($mimetype == IMAGETYPE_GIF || $mimetype == IMAGETYPE_JPEG || $mimetype == IMAGETYPE_PNG || $mimetype == IMAGETYPE_BMP) {
                        Log::info($mimetype);
                    }
                }

            }
        }


        return response($file);
    }

    /**
     * @des 解压图册
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadZip(Request $request)
    {
        try {
            $res = ImportExcel::uploadFile($request);
            if ($res['code'] === 0) {
                $file = storage_path($res['data']['file_path']);
                if (!file_exists($file)) {
                    return response(ReturnCode::error(ReturnCode::NOT_FOUND));
                }
                $path = 'app/public/upload/' . date('Ymd') . '/' . time() . chr(rand(65, 90)) . '/';
                $storagePath = storage_path($path);
                $bool = ZipLib::UnZip($file, $storagePath);
                if ($bool) {
                    //判断压缩文件
                    $file = scandir($storagePath);
                    $fileSku = null;
                    foreach ($file as $item) {
                        if ($item != '.' && $item != '..') {
                            $fileSku = $item;
                        }
                    }
                    //判断解压文件的目录
                    if (!empty($fileSku)) {
                        $pathSub = $path . '/' . $fileSku;
                        $storagePath2 = storage_path($pathSub);
                        $file = scandir($storagePath2);
                        $list = 0;
                        foreach ($file as $item) {
                            if ($item != '.' && $item != '..') {
                                $fileImg = $storagePath2 . '/' . $item;
                                $mimetype = exif_imagetype($fileImg);
                                if ($mimetype == IMAGETYPE_GIF || $mimetype == IMAGETYPE_JPEG || $mimetype == IMAGETYPE_PNG || $mimetype == IMAGETYPE_BMP) {
                                    $fileItem = explode('.', $item);
                                    if (count($fileItem) == 2) {
                                        if (file_exists($fileImg)) {
                                            $sku_code = $fileItem[0];
                                            $ext = $fileItem[1];
                                            $imgage_path = $pathSub . $item;
                                            $fileSize = filesize($fileImg);

                                            //判断是否是sku编号
                                            $count = Sku::where('sku_id', $sku_code)->count();
                                            if ($count == 0) {
                                                continue;
                                            }
                                            //导入附件表
                                            $attachment = Attachment::create([
                                                'owner_id' => Token::$uid,
                                                'storage_type' => Attachment::STORAGE_TYPE_LOCAL,
                                                'file_key' => $item,
                                                'file_path' => $imgage_path,
                                                'file_name' => $item,
                                                'filesize' => $fileSize / 1024,
                                                'file_ext' => $ext,
                                                'created_code' => Token::$ucode,
                                                'upload_fun' => $fileSku . '文件解压'
                                            ]);

                                            $attachment->file_url = config('app.url') . '/api/show/attachment/' . $attachment->id;
                                            $attachment->save();
                                            $attach_id = $attachment->id;

                                            $skuCover = SkuCover::where('sku_code', $sku_code)->first();
                                            if ($skuCover) {
                                                $skuCover->attach_id = $attach_id;
                                                $skuCover->status = 1;
                                                $skuCover->updated_code = Token::$ucode;
                                                $skuCover->save();
                                                $list++;
                                            } else {
                                                SkuCover::create([
                                                    'sku_code' => $sku_code,
                                                    'attach_id' => $attach_id,
                                                    'status' => 1,
                                                    'created_code' => Token::$ucode
                                                ]);
                                                $list++;
                                            }

                                        }
                                    }
                                }
                            }
                        }

                        return response(['code' => 0, 'msg' => '有效图片' . $list . '张']);
                    } else {
                        return response(ReturnCode::error(ReturnCode::NOT_FOUND, '未找到文件'));
                    }
                } else {
                    return response(ReturnCode::error(ReturnCode::PARAMS_ERROR, '解压失败'));
                }

                return response(['code' => 0, 'data' => $bool]);
            }
            return response($res);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }

    //上传首图到oss
    public function uploadOss(Request $request)
    {
        $file = $request->file('file');
        $sku_code = $request->input('sku_code');

        $accessKeyId = env('AccessKeyId');
        $accessKeySecret = env('AccessKeySecret');
        $endpoint = 'oss-cn-hangzhou.aliyuncs.com';
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $fileExt = $file->getClientOriginalExtension();
        $fileName = date('YmdHis') . str_random(16) . '.' . $fileExt; //重命名
        $bucket = 'sl-nd';
        $object = 'productImg/' . $fileName;
        $result = $ossClient->uploadFile($bucket, $object, $file);
        $info = $result['info'];
        if ($info['http_code'] == 200) {
            $detail = SkuImg::where('sku_code', $sku_code)->first();
            if ($detail) {
                $detail->image_url = $info['url'];
                $detail->save();
            } else {
                $data['sku_code'] = $sku_code;
                $data['image_url'] = $info['url'];
                SkuImg::insertGetId($data);
            }
            return ['success' => true, 'url' => $info['url']];
        }
    }

    /**
     * @des 上传首图
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadImg(Request $request)
    {
        try {
            $res = ImportExcel::uploadFile($request);
            if ($res['code'] === 0) {
                $sku_code = $request->input('sku_code', null);
                if (empty($sku_code)) {
                    return response(ReturnCode::error(ReturnCode::PARAMS_ERROR));
                }
                $attach_id = $res['data']['id'];

                $skuCover = SkuCover::where('sku_code', $sku_code)->first();
                if ($skuCover) {
                    $skuCover->attach_id = $attach_id;
                    $skuCover->status = 1;
                    $skuCover->updated_code = Token::$ucode;
                    $skuCover->save();
                } else {
                    SkuCover::create([
                        'sku_code' => $sku_code,
                        'attach_id' => $attach_id,
                        'status' => 1,
                        'created_code' => Token::$ucode
                    ]);
                }
                return response($res);
            }
            return response($res);
        } catch (\Exception $exception) {
            Log::error($exception);
            return response(ReturnCode::error(ReturnCode::FAILED, $exception->getMessage()));
        }
    }
}