<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/1/9
 * Time: 9:55
 */

namespace App\Libs;

use App\Models\System\Attachment;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportExcel
{
    /**
     * @des 上传文件
     * @param Request $request
     * @return array|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public static function uploadFile(Request $request)
    {
        try{
            if (!$request->hasFile('file')) {
                return ['code'=>4,'msg'=>'上传文件为空'];
            }
            $file = $request->file('file');
            //判断文件上传过程中是否出错
            if (!$file->isValid()) {
                return ['code'=>404,'msg'=>'文件上传出错'];
            }
            $fileSize = ceil($file->getClientSize() / 1024);
            $fileExt  = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();

            $isOSS    = $request->input('is_oss',2);

            if ($fileExt) {
                $fileExt = strtolower($fileExt);
            }
            $path = 'app/public/upload/' . date('Ymd') . '/';

            $tempName = date('YmdHis') . str_random(16) .'.'. $fileExt; //重命名

            // 临时存储文件夹
            $storagePath = storage_path($path);
            // 创建目录
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }
            // 转存
            $file->move($storagePath, $tempName);

            $filePath=$storagePath.$tempName;
            $fileKey = md5(file_get_contents($filePath)) . '.' . $fileExt;

            if($isOSS==1){
                return Attachment::uploadToOss($filePath,$fileExt);
            }else{
                $attachment = Attachment::create([
                    'owner_id'     => Token::$uid,
                    'storage_type' => Attachment::STORAGE_TYPE_LOCAL,
                    'file_key'     => $fileKey,
                    'file_path'    => $path.$tempName,
                    'file_name'    => $fileName,
                    'filesize'     => $fileSize,
                    'file_ext'     => $fileExt,
                    'created_code' => Token::$ucode
                ]);

                $attachment->file_url = config('app.url').'/api/show/attachment/'.$attachment->id;
                $attachment->save();

                return ['code' => ReturnCode::SUCCESS,'data' => ['id' => $attachment->id, 'file_path' => $path.$tempName,'file_url'=>$attachment->file_url]];
            }
        }
        catch (\Exception $exception){
            return ['code'=>ReturnCode::FAILED,'msg'=>$exception->getMessage()];
        }
    }
    /**
     * @des 导入Excel
     * @param Request $request
     * @param array $checkSize
     * @param string $sheetName
     * @return array
     */
    public static function importExcel(Request $request,$checkSize=['isCheck'=>false,'size'=>0],$sheetName='Sheet1')
    {
        try{
            //判断请求中是否包含name=file的上传文件
            if (!$request->hasFile('file')) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'上传文件为空'];
            }
            $file = $request->file('file');
            //判断文件上传过程中是否出错
            if (!$file->isValid()) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'文件上传出错'];
            }
            $fileSize = ceil($file->getClientSize() / 1024);
            $fileExt = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();

            if ($fileExt) {
                $fileExt = '.' . strtolower($fileExt);
            }
            // 限制大小
            if($checkSize['isCheck']){
                if ($fileSize > $checkSize['size']*1024) {
                    return ['success' => false, 'msg' => '文件超过限制大小'.$checkSize['size'].'M'];
                }
            }

            if ($fileExt !== '.xls' && $fileExt !== '.xlsx' && $fileExt !== '.xlsm') {
                return ['code'=>ReturnCode::FAILED,'msg'=>'文件格式错误'];
            }

            $path = 'app/public/upload/' . date('Ymd') . '/';

            $tempName = date('YmdHis') . str_random(16) . $fileExt; //重命名
            // 临时存储文件夹
            $storagePath = storage_path($path);
            // 创建目录
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }
            // 转存
            $file->move($storagePath, $tempName);

            $excelData=Excel::selectSheets($sheetName)->load(iconv('UTF-8','GBK',$storagePath.$tempName),
                function ($reader){
                    return $reader->all();
                })->parsed;

            if (count($excelData)==0) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'数据为空，或者工作表中无‘'.$sheetName.'’'];
            }

            $attachment=Attachment::create([
                'owner_id'=>Token::$uid,
                'storage_type'=>Attachment::STORAGE_TYPE_LOCAL,
                'file_name'=>$fileName,
                'file_key'=>$tempName,
                'file_path'=>$path.$tempName,
                'filesize'=>$fileSize,
                'file_ext'=>$fileExt,
                'created_code'=>Token::$ucode
            ]);
            $attachment->file_url = config('app.url').'/api/show/attachment/'.$attachment->id;
            $attachment->save();

            return ['code'=>ReturnCode::SUCCESS,'data'=>$excelData];
        }
        catch (\Exception $exception){
            return ['code'=>ReturnCode::FAILED,'msg'=>$exception->getMessage()];
        }
    }

    /**
     * @des 指定工作表导入，单一导入
     * @param Request $request
     * @return array
     */
    public static function ImportExcelFun(Request $request,$checkSize=['isCheck'=>false,'size'=>0],$sheetName='Sheet1')
    {
        $fileLog=[
            'file_name'     => null,
            'file_size'     => null,
            'file_path'     => null,
            'new_file_name' => null,
            'upload_url'    => $request->url(),
            'u_id'          => Token::$uid,
            'upload_result' => 'success'
        ];
        try {
            //判断请求中是否包含name=file的上传文件
            if (!$request->hasFile('file')) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'上传文件为空'];
            }
            $file = $request->file('file');
            //判断文件上传过程中是否出错
            if (!$file->isValid()) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'文件上传出错'];
            }
            $fileSize = ceil($file->getClientSize() / 1024);
            $fileExt = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();

            $fileLog['file_name']=$fileName;
            $fileLog['file_size']=$fileSize;

            if ($fileExt) {
                $fileExt = '.' . strtolower($fileExt);
            }
            // 限制大小
            if($checkSize['isCheck']){
                if ($fileSize > $checkSize['size']*1024) {
                    $fileLog['upload_result']='error=>文件超过限制大小'.$checkSize['size'];
                    Attachment::create($fileLog);
                    return ['success' => false, 'msg' => '文件超过限制大小'.$checkSize['size'].'M'];
                }
            }

            if ($fileExt !== '.xls' && $fileExt !== '.xlsx' && $fileExt !== '.xlsm') {
                $fileLog['upload_result']='error=>文件格式错误'.$checkSize['size'];
                Attachment::create($fileLog);
                return ['code'=>ReturnCode::FAILED,'msg'=>'文件格式错误'];
            }

            $path = 'app/public/upload/' . date('Ymd') . '/';

            $tempName = date('YmdHis') . str_random(16) . $fileExt; //重命名
            $fileLog['new_file_name']=$tempName;
            $fileLog['file_path']=$path;
            // 临时存储文件夹
            $storagePath = storage_path($path);
            // 创建目录
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }
            // 转存
            $file->move($storagePath, $tempName);

            $excelData=Excel::selectSheets($sheetName)->load(iconv('UTF-8','GBK',$storagePath.$tempName),
                function ($reader){
                    return $reader->all();
            })->parsed;

//            $excelData = Excel::load(iconv('UTF-8', 'GBK', $storagePath . $tempName),
//                function ($reader) {
//                    return $reader->all();
//                })->parsed;

            if (count($excelData)==0) {
                $fileLog['upload_result']='error=>数据为空';
                Attachment::create($fileLog);
                return ['code'=>ReturnCode::FAILED,'msg'=>'数据为空，或者工作表中无‘'.$sheetName.'’'];
            }

            Attachment::create($fileLog);
            return ['code'=>ReturnCode::SUCCESS,'data'=>$excelData];
        }
        catch (\Exception $exception){
            Log::error($exception);
            $fileLog['upload_result']='error=>'.$exception->getMessage();
            Attachment::create($fileLog);
            return ['code'=>ReturnCode::FAILED,'msg'=>$exception->getMessage()];
        }
    }

    /**
     * @des 导入多个Sheet使用
     * @param Request $request
     * @return array
     */
    public static function ImportExcelFunMore(Request $request){
        try {
            //判断请求中是否包含name=file的上传文件
            if (!$request->hasFile('file')) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'上传文件为空'];
            }
            $file = $request->file('file');
            //判断文件上传过程中是否出错
            if (!$file->isValid()) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'文件上传出错'];
            }
            $fileSize = ceil($file->getClientSize() / 1024);
            $fileExt = $file->getClientOriginalExtension();
            $fileName = $file->getClientOriginalName();

            if ($fileExt) {
                $fileExt = '.' . strtolower($fileExt);
            }
            // 限制大小
            /*if ($fileSize > 4096) {
                return ['success' => false, 'msg' => '文件超过限制大小4M'];
            }*/

            if ($fileExt !== '.xls' && $fileExt !== '.xlsx' && $fileExt !== '.xlsm') {
                return ['code'=>ReturnCode::FAILED,'msg'=>'文件格式错误'];
            }

            $path = 'app/public/upload/' . date('Ymd') . '/';

            $tempName = date('YmdHis') . str_random(16) . $fileExt; //重命名
            // 临时存储文件夹
            $storagePath = storage_path($path);
            // 创建目录
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0777, true);
            }
            // 转存
            $file->move($storagePath, $tempName);

            $excelData = Excel::load(iconv('UTF-8', 'GBK', $storagePath . $tempName),
                function ($reader) {
                    return $reader->all();
                })->parsed;

            if (count($excelData)==0) {
                return ['code'=>ReturnCode::FAILED,'msg'=>'数据为空'];
            }
            return ['code'=>ReturnCode::SUCCESS,'data'=>$excelData];
        }
        catch (\Exception $exception){
            Log::error($exception);
            return ['code'=>ReturnCode::FAILED,'msg'=>$exception->getMessage()];
        }
    }

    /**
     * @param $key 数组index
     * @param $msg 消息
     * @return array
     */
    public static function ImportErrorList($key,$msg){
        return ['row'=>$key+1,'note'=>$msg];
    }

    /**
     * @param $data Excel数据
     * @param $import 导入成功条数
     * @param $update 更新成功条数
     * @param $repeat 失败条数
     * @return string
     */
    public static function ImportBackMsg($data,$import,$update,$repeat){

        return '共'.count($data).'条数据，导入'.$import.'条，更新'.$update.'条，失败'.$repeat.'条';
    }
}