<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-11 9:39
 */

namespace app\model;

use think\facade\Db;

class MenuModel extends BaseModel
{
    /**
     * 根据用户查询手机端首页显示的菜单列表
     * @param $userId
     * @param $langId
     * @return mixed
     */
    public static function getMenuList($userId,$langId)
    {
        $result = Db::connect(self::$Db)->query("EXEC jlMenuList ?, ?, ?, '', ''", ['mobile', $langId, $userId]);
        if($result){
            return $result[0];
        }
        return [];

    }
    /**
     * 添加操作日志
     *
     * @param
     * @return bool
     */
    public static function fromMenuID($UserID,$FormID,$FormName,$log_pc)
    {
        $FromKey = 'M'.date('Ymd').'T'.date('His').substr(time(),-3);
        $where =array(
            'log_type'  => 'MPAGE',
            'login_key' => $FromKey
        );
        $getHistory = self::getHistory($where);
        if(!$getHistory){
            return Db::connect(self::$Db)->query("EXEC jlFormAccessLog ?, ?, ?, ?, ?, ?, ?, ?, ?, ?", ['OPEN',$UserID,$FormID,$FormName,$FromKey,$FromKey,'MPAGE',$_SERVER['REMOTE_ADDR'],$log_pc,'']);
        }
        // return array('OPEN',$UserID,$FormID,$FormName,$FromKey,$FromKey,'MPAGE');

    }
    /**
     * 修改操作日志
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function SaveHistory($where,$data)
    {
        $res = Db::table("sysLogHistory")->where($where)->save($data);
        return $res;

    }
    /**
     * 查询操作日志
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getHistory($where)
    {
        $res = Db::table("sysLogHistory")->where($where)->order('login_time','desc')->find();
        return $res;

    }


}