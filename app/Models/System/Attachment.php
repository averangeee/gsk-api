<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/4/24
 * Time: 15:09
 */

namespace App\Models\System;


use App\Libs\OSS;
use App\Models\BaseModel;
use App\Models\Token;
use Illuminate\Support\Facades\Log;

class Attachment extends BaseModel
{
    protected $table='attachment';

    public static $filedType=[
        1=>'default',//继承Bucket
        2=>'private',//私有
        3=>'public-read',//公共读
        4=>'public-read-write'//公共读写
    ];

    // 存储类型
    const STORAGE_TYPE_LOCAL = 0;
    const STORAGE_TYPE_URL   = 1;
    const STORAGE_TYPE_OSS   = 2;

    public static function getUrl($id)
    {
        if ($res = self::find($id)) {
            return $res->file_url;
        }
        return false;
    }

    /**
     * 上传一个本地文件到oss
     *
     * @param $filePath
     * @param $fileExt
     *
     * @return array
     */
    public static function uploadToOss($filePath, $fileExt)
    {
        $ownerId  = Token::$uid ? : null;
        $ucode  = Token::$ucode ? : null;

        $fileSize = filesize($filePath) / 1024;

        /** 获取文件内容的md5字串作为文件名key */
//        $fileKey = md5(file_get_contents($filePath)) . '.' . $fileExt;
        $fileKey = md5($filePath) . '.' . $fileExt;

        /** 加上sl目录 */
        $fileKey = "sl/$fileKey";

        if ($attachment = self::where('file_key', $fileKey)->first()) {
            //删除本地文件
            unlink($filePath);

            return [
                'success' => true,
                'data'    => ['id' => $attachment->id, 'fileUrl' => $attachment->file_url]
            ];
        }
//        Log::info('开始上传OSS');
        /** 上传到OSS */
        OSS::uploadFile($fileKey, $filePath);
        $fileUrl = 'http://'.config('app.ossBucket').'.'.config('app.ossDomain') . $fileKey;
//        Log::info('结束上传OSS');
        /** 删除本地文件 */
        unlink($filePath);

        /** 保存到数据库 */
        $id = self::insertGetId([
            'owner_id'      => $ownerId,
            'storage_type' => self::STORAGE_TYPE_OSS,
            'file_key'     => $fileKey,
            'file_url'     => $fileUrl,
            'filesize'     => $fileSize,
            'file_ext'     => $fileExt,
            'created_code' => $ucode
            //'ip_upload'    => ''
        ]);
        return ['success' => true, 'data' => ['id' => $id, 'fileUrl' => $fileUrl]];
    }

}