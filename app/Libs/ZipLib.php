<?php
/**
 * Created by PhpStorm.
 * User: hongpo
 * Date: 2019/6/27
 * Time: 16:46
 */

namespace App\Libs;

use Illuminate\Support\Facades\Log;
use \ZipArchive;
abstract class ZipLib
{
    //https://blog.csdn.net/zhwxl_zyx/article/details/47606821
    //解压-解压缩zip文件
    public static function UnZip($zipFile,$targetPath)
    {
        $zip = new ZipArchive();//新建一个ZipArchive的对象
        /*
        通过ZipArchive的对象处理zip文件
        $zip->open这个方法的参数表示处理的zip文件名。
        如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
        */
        if ($zip->open($zipFile) === TRUE){
            $zip->extractTo($targetPath);//假设解压缩到在当前路径下images文件夹的子文件夹php
            $zip->close();//关闭处理的zip文件
            return true;
        }

        return false;
    }

    //压缩-将文件压缩成zip文件
    public static function Zip($zipFIle,$file)
    {
        $zip = new ZipArchive();
        /*
        $zip->open这个方法第一个参数表示处理的zip文件名。
        第二个参数表示处理模式，ZipArchive::OVERWRITE表示如果zip文件存在，就覆盖掉原来的zip文件。
        如果参数使用ZIPARCHIVE::CREATE，系统就会往原来的zip文件里添加内容。
        如果不是为了多次添加内容到zip文件，建议使用ZipArchive::OVERWRITE。
        使用这两个参数，如果zip文件不存在，系统都会自动新建。
        如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
        */
        if ($zip->open($zipFIle, ZipArchive::OVERWRITE) === TRUE)
        {
            $zip->addFile($file);//假设加入的文件名是image.txt，在当前路径下
            $zip->close();
            return true;
        }
        return false;
    }

    //压缩-文件追加内容添加到zip文件
    public static function ZipAdd()
    {
        $zip = new ZipArchive();
        $res = $zip->open('test.zip', ZipArchive::CREATE);
        if ($res === TRUE) {
            $zip->addFromString('test.txt', 'file content goes here');
            $zip->close();
            return false;
        }
        return false;
    }

    //压缩-将文件夹打包成zip文件
    public static function ZipFolder()
    {
        function addFileToZip($path, $zip) {
            $handler = opendir($path); //打开当前文件夹由$path指定。
            /*
            循环的读取文件夹下的所有文件和文件夹
            其中$filename = readdir($handler)是每次循环的时候将读取的文件名赋值给$filename，
            为了不陷于死循环，所以还要让$filename !== false。
            一定要用!==，因为如果某个文件名如果叫'0'，或者某些被系统认为是代表false，用!=就会停止循环
            */
            while (($filename = readdir($handler)) !== false) {
                if ($filename != "." && $filename != "..") {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
                    if (is_dir($path . "/" . $filename)) {// 如果读取的某个对象是文件夹，则递归
                        addFileToZip($path . "/" . $filename, $zip);
                    } else { //将文件加入zip对象
                        $zip->addFile($path . "/" . $filename);
                    }
                }
            }
            @closedir($path);
        }

        $zip = new ZipArchive();
        if ($zip->open('images.zip', ZipArchive::OVERWRITE) === TRUE) {
            addFileToZip('images/', $zip); //调用方法，对要打包的根目录进行操作，并将ZipArchive的对象传递给方法
            $zip->close(); //关闭处理的zip文件
        }

    }

}