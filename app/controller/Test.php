<?php
/**
 * Class User
 * @package app\controller
 * 用户操作接口,如登录验证、获取用户数据、菜单权限等
 */

namespace app\controller;

use app\BaseController;
use app\common\Util;
use app\common\FtpUtil;
use app\model\salesBusiness\OrderModel;
class Test extends BaseController
{

    protected $ftpUtil;

    public function __construct()
    {
        // 初始化 FtpUtil 实例
        $this->ftpUtil = new FtpUtil();
    }


    public function upload()
    {
        $localFile = '/data/ftp/local_file.txt'; // 本地文件路径  //目录确保755权限 和www用户和www用户组
        $remoteFile = '4.txt'; // 远程文件路径

        if (FtpUtil::upload($localFile, $remoteFile)) {
            echo "文件上传成功\n";
        } else {
            echo "文件上传失败\n";
        }
    }

    public function download()
    {
        $remoteFile = '1.txt'; // 远程文件路径
        $localFile = 'download.txt'; // 本地文件路径 在public目录

        if (FtpUtil::download($remoteFile, $localFile)) {
            echo "文件下载成功\n";

        } else {
            echo "文件下载失败\n";
        }

    }


    public function test()
    {
        $res = Util::pdfToImage('pdf/test.pdf',null,600);
        var_dump($res);
    }

    public function test2()
    {
        return;
        // 设置要执行的命令
        $command = "gs -sstdout=%stderr -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -r600 -dAlignToPixels=0 -dGridFitTT=2 -sDEVICE=jpeg -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dPrinted=false -sOutputFile=pdf/1.jpg pdf/test.pdf";
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        if ($return_var === 0) {
            echo "命令执行成功，输出：\n";
            print_r($output);
        } else {
            echo "命令执行失败，返回码：$return_var\n";
            echo "错误输出：\n" . implode("\n", $output);
        }
    }

    public function getpic()
    {
        $pic = 'http://dev.yudosuzhou.com:9293/pdf/test2.jpg';
        return json([
            'data'=>$pic,
            'a'=>123
        ]);
    }

    public function test3()
    {
        $l1 = [9,9,9,9,9,9,9];
        $l2 = [9,9,9,9];
        $len1 = count($l1);
        $len2 = count($l2);
        $tmp = [];
        if($len1<$len2){
            $tmp = $l1;
            $l1 = $l2;
            $l2 = $tmp;
            $len1 = count($l1);
            $len2 = count($l2);
        }
        $l3 = [];
        for($i=0;$i<=$len1;$i++){
            $l3[] = 0;
        }
        $l4 = [];
        for($i=0;$i<=$len1-$len2;$i++){
            $l4[]=0;
        }
        for($i=0;$i<$len2;$i++){
            $l4[] = $l2[$i];
        }

        for($i=$len1-1;$i>=0;$i--){
            $l3[$i+1] = $l1[$i] + $l4[$i+1];
        }
        for($i=$len1;$i>=0;$i--){
            if($l3[$i]>9){
                $l3[$i-1] = $l3[$i-1] + 1;
                $l3[$i] = $l3[$i] % 10;
            }
        }
        dump($l3);

    }

    public function test4()
    {
        $str = ["eat", "tea", "tan", "ate", "nat", "bat"];

    }

    public function ceshi()
    {
        $list = OrderModel::text();
        return json($list);
    }

}
