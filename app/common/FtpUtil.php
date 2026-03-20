<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-30 9:30
 */

namespace app\common;

class FtpUtil
{
    protected static $connId;

    /**
     * 初始化 FTP 连接
     *
     * @throws \Exception
     */
    public static function init()
    {
        // 获取 FTP 配置信息
        $config = config('ftp');

        // 建立 FTP 连接
        self::$connId = ftp_connect($config['host'], $config['port']);

        // 登录到 FTP 服务器
        if (!self::$connId || !ftp_login(self::$connId, $config['username'], $config['password'])) {
            throw new \Exception("无法连接到 FTP 服务器");
        }

        // 设置被动模式
        ftp_pasv(self::$connId, $config['passive']);
    }

    /**
     * 上传文件到 FTP 服务器
     *
     * @param string $localFile 本地文件路径
     * @param string $remoteFile 远程文件路径
     * @return bool 成功返回 true，失败返回 false
     */
    public static function upload($localFile, $remoteFile)
    {
        // 确保 FTP 连接已初始化
        if (!self::$connId) {
            self::init();
        }
        // 使用 FTP 上传文件
        $result =  ftp_put(self::$connId, $remoteFile, $localFile, FTP_BINARY);
        self::close();
        return $result;
    }

    /**
     * 从 FTP 服务器下载文件
     *
     * @param string $remoteFile 远程文件路径
     * @param string $localFile 本地文件路径
     * @return bool 成功返回 true，失败返回 false
     */
    public static function download($remoteFile, $localFile)
    {
        // 确保 FTP 连接已初始化
        if (!self::$connId) {
            self::init();
        }
        // 使用 FTP 下载文件
        $result =  ftp_get(self::$connId, $localFile, $remoteFile, FTP_BINARY);
        self::close();
        return $result;
    }

    /**
     * 关闭 FTP 连接
     */
    public static function close()
    {
        if (self::$connId) {
            ftp_close(self::$connId);
            self::$connId = null; // 断开连接后清空连接 ID
        }
    }


    /**
     * 关闭 FTP 连接
     */
    public static function ftp_photo($mt_id,$fun,$sign=null)
    {

        if (!self::$connId) {
            self::init();
        }

        $dirname_year = substr($mt_id,0,4);
        $dirname_month = substr($mt_id,4,2);
        $exist_dir = ftp_rawlist(self::$connId,ftp_pwd(self::$connId).$fun);
        //检测年
        foreach ($exist_dir as $k => $v){
            $exist_dir[$k] =    substr($v,-4,4);
        }
        if(!in_array($dirname_year,$exist_dir)){
            if(!ftp_mkdir(self::$connId, ftp_pwd(self::$connId).$fun."/$dirname_year")){
                return false;
            }
        }
        //检测月
        $exist_dir = ftp_rawlist(self::$connId,ftp_pwd(self::$connId).$fun."/$dirname_year");
        foreach ($exist_dir as $k => $v){
            $exist_dir[$k] =    substr($v,-2,2);
        }
        if(!in_array($dirname_month,$exist_dir)){
            if(!ftp_mkdir(self::$connId, ftp_pwd(self::$connId).$fun."/$dirname_year/$dirname_month")){
                return false;
            }
        }
        //检测组装号文件夹
        $exist_dir = ftp_rawlist(self::$connId,ftp_pwd(self::$connId).$fun."/$dirname_year/$dirname_month");
        foreach ($exist_dir as $k => $v){
            $exist_dir[$k] =    substr($v,-10,10);
        }
        if(!in_array($mt_id,$exist_dir)){
            if(!ftp_mkdir(self::$connId, ftp_pwd(self::$connId).$fun."/$dirname_year/$dirname_month/$mt_id")) {
                return false;
            }
        }
        //检测组装号文件夹

        if(!empty($sign)){
            $exist_dir = ftp_rawlist(self::$connId,ftp_pwd(self::$connId).$fun."/$dirname_year/$dirname_month/$mt_id");
            foreach ($exist_dir as $k => $v){
                $exist_dir[$k] =    substr($v,-10,10);
            }
            if(!in_array($sign,$exist_dir)){
                if(!ftp_mkdir(self::$connId, ftp_pwd(self::$connId).$fun."/$dirname_year/$dirname_month/$mt_id".$sign)) {
                    return false;
                }
            }
        }

        self::close();
        return true;

    }

}



