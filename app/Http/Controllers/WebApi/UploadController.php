<?php
/**
 * Created by PhpStorm.
 * User: shkjadmin
 * Date: 2019/5/30
 * Time: 13:29
 */

namespace App\Http\Controllers\WebApi;


use App\Libs\ReturnCode;
use App\Models\System\Attachment;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OSS\OssClient;

class UploadController extends Controller
{
    /**
     * 上传文件
     * @param Request $request
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @author yss
     * @date  2019/5/30 13:39
     */
    public function index(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return ['success' => false, 'msg' => '上传文件为空'];
            }
            $file = $request->file('file');
            //判断文件上传过程中是否出错
            if (!$file->isValid()) {
                return ['success' => false, 'msg' => '文件上传出错'];
            }
            $fileSize = ceil($file->getClientSize() / 1024);
            $fileExt = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();

            $isOSS = $request->input('is_oss', 2);


            if ($fileExt) {
                $fileExt = strtolower($fileExt);
            }
            $path = 'app/public/upload/' . date('Ymd') . '/';

            $tempName = date('YmdHis') . str_random(16) . '.' . $fileExt; //重命名

            // 临时存储文件夹
            $storagePath = storage_path($path);
            // 创建目录
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }
            // 转存
            $file->move($storagePath, $tempName);

            $filePath = $storagePath . $tempName;
            $fileKey = md5(file_get_contents($filePath)) . '.' . $fileExt;

            if ($isOSS == 1) {
                return response(Attachment::uploadToOss($filePath, $fileExt));
            } else {
                $attachment = Attachment::create([
                    'owner_id' => Token::$uid,
                    'storage_type' => Attachment::STORAGE_TYPE_LOCAL,
                    'file_key' => $fileKey,
                    'file_path' => $path . $tempName,
                    'file_name' => $fileName,
                    'filesize' => $fileSize,
                    'file_ext' => $fileExt,
                    'created_code' => Token::$ucode
                    //'ip_upload'    => ''
                ]);

                $attachment->file_url = config('app.url') . '/api/show/attachment/' . $attachment->id;
                $attachment->save();

                return ['success' => true, 'url' => $attachment->file_url, 'data' => ['id' => $attachment->id, 'file_path' => $path . $tempName, 'fileUrl' => $attachment->file_url]];
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 展示文件
     * @param Request $request
     * @param $id
     * @author yss
     * @date  2019/5/31 15:04
     */
    public function fileDownloadShow(Request $request, $id)
    {
        try {

            $file = Attachment::find($id);
            if (!$file) {
                header("HTTP/1.1 404 Not Found");
                header("Status: 404 Not Found");
                exit;
            }
            if (!file_exists(storage_path($file->file_path))) {
                //报404错误
                header("HTTP/1.1 404 Not Found");
                header("Status: 404 Not Found");
                exit;
            }

            header('Content-type: image/jpg');
            header('Content-type: *');
            $fileContents = file_get_contents(storage_path($file->file_path));
            $size = filesize(storage_path($file->file_path));
            $length = strlen($fileContents);
            header('Content-Length: ' . $length);
            header('Content-Size: ' . $size);
            echo $fileContents;
            exit;
        } catch (\Exception $e) {
            Log::error($e);

            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
            exit;
        }
    }

    public function indexs(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return ['success' => false, 'msg' => '上传文件为空'];
            }
            $file = $request->file('file');
            //判断文件上传过程中是否出错
            if (!$file->isValid()) {
                return ['success' => false, 'msg' => '文件上传出错'];
            }
            $fileSize = ceil($file->getClientSize() / 1024);
            $fileExt = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();

            $isOSS = $request->input('is_oss', 2);


            if ($fileExt) {
                $fileExt = strtolower($fileExt);
            }
            $path = 'app/public/upload/' . date('Ymd') . '/';

            $tempName = date('YmdHis') . str_random(16) . '.' . $fileExt; //重命名

            // 临时存储文件夹
            $storagePath = storage_path($path);
            // 创建目录
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }
            // 转存
            $file->move($storagePath, $tempName);

            $filePath = $storagePath . $tempName;
            $fileKey = md5(file_get_contents($filePath)) . '.' . $fileExt;

            if ($isOSS == 1) {
                return response(Attachment::uploadToOss($filePath, $fileExt));
            } else {
                $attachment = Attachment::create([
                    'owner_id' => Token::$uid,
                    'storage_type' => Attachment::STORAGE_TYPE_LOCAL,
                    'file_key' => $fileKey,
                    'file_path' => $path . $tempName,
                    'file_name' => $fileName,
                    'filesize' => $fileSize,
                    'file_ext' => $fileExt,
                    'created_code' => Token::$ucode
                    //'ip_upload'    => ''
                ]);

                $attachment->file_url = config('app.url') . '/api/show/upload/' . $attachment->id;
                $attachment->save();

                return ['success' => true, 'url' => $attachment->file_url, 'data' => ['id' => $attachment->id, 'file_path' => $path . $tempName, 'fileUrl' => $attachment->file_url]];
            }
        } catch (\Exception $e) {
            Log::error($e);
            return response(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function uploadOss(Request $request)
    {

        $file = $request->file('file');

        $accessKeyId = env('AccessKeyId');
        $accessKeySecret = env('AccessKeySecret');
        $endpoint = 'oss-cn-hangzhou.aliyuncs.com';
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket = 'sl-mh-az';
        $object = 'product/'.$file->getClientOriginalName();
        $result = $ossClient->uploadFile($bucket, $object, $file);
        $info = $result['info'];
        if ($info['http_code'] == 200) {
            return ['success' => true, 'url' => $info['url']];
        }
    }

    public function uploadOssRandom(Request $request)
    {

        $index = $request->input('index');
        $file = $request->file('file');
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
            return ['success' => true, 'url' => $info['url'], 'index' => $index];
        }
    }

    public function fileDownload(Request $request, $id)
    {
        try {

            $file = Attachment::find($id);
            if (!$file) {
                header("HTTP/1.1 404 Not Found");
                header("Status: 404 Not Found");
                exit;
            }
            if (!file_exists(storage_path($file->file_path))) {
                //报404错误
                header("HTTP/1.1 404 Not Found");
                header("Status: 404 Not Found");
                exit;
            }

            header('Content-type: *');
            response()->download(storage_path($file->file_path));
            $fileContents = file_get_contents(storage_path($file->file_path));
            $size = filesize(storage_path($file->file_path));
            $length = strlen($fileContents);
            header('Content-Length: ' . $length);
            header('Content-Size: ' . $size);
            header('Content-Disposition: attachment; filename=' . $file->file_name);
            header('Content-Type: application/octet-stream; name=' . $file->file_name);

            echo $fileContents;
            exit;
        } catch (\Exception $e) {
            Log::error($e);

            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
            exit;
        }
    }
}