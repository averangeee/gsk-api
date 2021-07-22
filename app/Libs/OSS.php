<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/5/16
 * Time: 13:31
 */

namespace App\Libs;
use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Support\Facades\Log;

/**
 * @des 阿里Oss
 * Class OSS
 * @package App\Libs
 */
//$ossKey=$object (阿里文档的$object)
class OSS
{
    private $ossClient;

    public function __construct($isInternal = false)
    {
        $accessKeyId = config('app.AccessKeyId');
        $accessKeySecret = config('app.AccessKeySecret');
        $endpoint = $isInternal ? config('app.ossServerInternal') : config('app.ossServer');
        try {
            $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        } catch (OssException $e) {
            Log::error($e);
        }
    }

    /**
     * @des 判断文件是否存在
     * @param $object
     * @param string $bucket
     * @return mixed
     */
    public static function existObject($object,$bucket='')
    {
        $isInternal = config('app.isInternal');
        !$bucket && $bucket = config('app.ossBucket');
        $oss = new OSS($isInternal); // 上传文件使用内网，免流量费
        return $oss->ossClient->doesObjectExist($bucket, $object);
    }

    /**
     * @des 上传文件
     * @param $ossKey
     * @param $filePath
     * @param string $bucket
     * @throws OssException
     */
    public static function uploadFile($ossKey, $filePath, $bucket = '')
    {
        $isInternal = config('app.isInternal');
        !$bucket && $bucket = config('app.ossBucket');
        $oss = new OSS($isInternal); // 上传文件使用内网，免流量费
        $oss->ossClient->uploadFile($bucket,$ossKey, $filePath);
    }

    /**
     * 直接把变量内容上传到oss
     *
     * @param        $osskey
     * @param        $content
     * @param string $bucket
     */
    public static function uploadContent($osskey, $content, $bucket = '')
    {
        $isInternal = config('app.isInternal');
        !$bucket && $bucket = config('app.ossBucket');
        $oss = new OSS($isInternal); // 上传文件使用内网，免流量费
        $oss->ossClient->putObject($bucket,$osskey, $content);
    }

    /**
     * 删除存储在oss中的文件，单个文件删除
     *
     * @param string $ossKey 存储的key（文件路径和文件名）
     * @param string $bucket
     *
     * @return bool
     */
    public static function deleteObject($ossKey, $bucket = '')
    {
        $isInternal = config('app.isInternal');
        !$bucket && $bucket = config('app.ossBucket');
        $oss = new OSS($isInternal); // 上传文件使用内网，免流量费
        return $oss->ossClient->deleteObject($bucket, $ossKey);
    }

    /**
     * @des 批量删除文件
     * @param array $objects
     * @param string $bucket
     * @return \OSS\Http\ResponseCore
     * @throws null
     */
    public static function deleteObjects($objects=array(),$bucket = '')
    {
        $isInternal = config('app.isInternal');
        !$bucket && $bucket = config('app.ossBucket');
        $oss = new OSS($isInternal); // 上传文件使用内网，免流量费
        return $oss->ossClient->deleteObjects($bucket, $objects);
    }

    /**
     * @des 复制存储在阿里云OSS中的Object
     *
     * @param string $fromBucket 复制的源Bucket
     * @param string $fromObject   - 复制的的源Object的Key
     * @param string $toBucket  - 复制的目的Bucket
     * @param string $toObject     - 复制的目的Object的Key
     * @return null
     * @throws OssException
     */
    public function copyObject($fromBucket, $fromObject, $toBucket, $toObject)
    {
        $oss = new OSS(true); // 上传文件使用内网，免流量费
        $oss->ossClient->copyObject($fromBucket, $fromObject, $toBucket, $toObject);

    }

    /**
     * @des 移动文件
     * @param $fromBucket
     * @param $fromObject
     * @param $toBucket
     * @param $toObject
     * @throws OssException
     */
    public function moveObject($fromBucket, $fromObject, $toBucket, $toObject)
    {
        self::copyObject($fromBucket, $fromObject, $toBucket, $toObject);
        self::deleteObject($fromObject,$fromBucket);
    }

    /**
     * @des 判断存储空间是否存在
     * @param $bucket
     * @return bool
     * @throws OssException
     */
    public static function existBucket($bucket)
    {
        $oss = new OSS();
        return $oss->ossClient->doesBucketExist($bucket);
    }

    /**
     * @param $bucketName
     * @return null
     */
    public static function createBucket($bucketName)
    {
        $oss = new OSS();
        return $oss->ossClient->createBucket($bucketName);
    }

    /**
     * @des 删除bucket
     * @param $bucket
     * @return null
     */
    public static function deleteBucket($bucket)
    {
        $oss = new OSS();
        return $oss->ossClient->deleteBucket($bucket);
    }

    /**
     * @des 获得访问地址
     * @param $ossKey
     * @param string $bucket
     * @param int $timeout
     * @return string
     * @throws OssException
     */
    public static function getUrl($ossKey, $bucket = '',$timeout=60)
    {
        !$bucket && $bucket = config('app.ossBucket');
        $oss = new OSS();
        return $oss->ossClient->signUrl($bucket,$ossKey, $timeout);
    }

    /**
     * @des 设置文件访问权限
     * @param $ossKey
     * @param int $acl
     * @param string $bucket
     * @return null
     * @throws OssException
     */
    public static function setObjectAcl($ossKey,$acl=3, $bucket = '')
    {
        $aclArr=[
            1=>'default',//继承Bucket
            2=>'private',//私有
            3=>'public-read',//公共读
            4=>'public-read-write'//公共读写
        ];

        !$bucket && $bucket = config('app.ossBucket');
        $oss = new OSS();
        return $oss->ossClient->putObjectAcl($bucket, $ossKey, $aclArr[$acl]);
    }


}