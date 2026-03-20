<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-08 14:33
 */

namespace app\model;

use think\db\exception\DbException;
use think\Exception;
use think\facade\Config;
use think\facade\Db;
use think\Model;

/**
 * Class UserModel
 * @package app\model
 * 用户模型
 */
class UserModel extends BaseModel
{
    /**
     * 验证使用权限
     * 是否临时停用,是否有移动端使用权限,是否过期,是否辞退
     * @param $userId
     * @return bool
     */
    public static function valid($userId)
    {
        $user = UserModel::getUser($userId);
        $sql = "SELECT RetireYn FROM TMAEmpy00 where EmpID = '{$userId}'";
        $data = Db::connect(self::$Db)->query($sql);
        $citui = 'N';
        if($data){
            $citui = $data[0]['RetireYn'];
        }
        $date = date('Ymd',time());
        if($citui=='Y' || $user['hold_check_yn']=='Y' || $user['mobile_use_yn_yudo'] != 'Y' || $user['apply_end_date'] < $date){
            return false;
        }
        return true;
    }

    public static function valid2($userId)
    {
        $user = UserModel::getUser($userId);
        $sql = "SELECT RetireYn FROM TMAEmpy00 where EmpID = '{$userId}'";
        $data = Db::connect(self::$Db)->query($sql);
        $citui = 'N';
        if($data){
            $citui = $data[0]['RetireYn'];
        }
        $date = date('Ymd',time());
        if($citui=='Y' || $user['hold_check_yn']=='Y' || $user['mobile_use_yn_yudo'] != 'Y' || $user['apply_end_date'] < $date){
            return false;
        }
        return true;
    }

    /**
     * 验证数据库中的用户名和密码是否正确
     * 该方法通过查询数据库验证用户输入的账号和密码是否正确。
     * @param string $user_id 用户 ID
     * @param string $password 用户 密码
     * @return bool 返回验证结果：成功返回 true，失败返回 false
     */
    public static function verify($user_id,$password)
    {
        $user = UserModel::getUser($user_id);
        return ($user && md5($password)==$user['password']);
    }


    /**
     * 修改密码
     * @param $user_id
     * @param $newPassword
     * @return int
     * @throws DbException
     */
    public static function changePassword($user_id,$newPassword)
    {
        $result = Db::table('sysUserMaster')
            ->where('user_id', $user_id)
            ->update(['password' => md5($newPassword)]);
        return $result;

    }

    /**
     * 根据用户ID获取用户姓名|部门名|
     * @param $user_id
     * @return mixed
     */
    public static function getUserDeptInfo($user_id)
    {
        $sql = "SELECT TOP 1 UA.EmpNm,UB.DeptNm, US.emp_code,UB.DeptCd
        FROM TMAEmpy00 UA WITH (NOLOCK)
        LEFT JOIN TMADept00 UB WITH (NOLOCK) ON UA.DeptCd = UB.DeptCd
        LEFT JOIN sysUserMaster US WITH (NOLOCK) ON US.emp_code = UA.EmpID
        WHERE US.user_id='{$user_id}' OR UA.EmpID = '{$user_id}'";
        return  Db::query($sql);

    }

    /**
     * 验证设备码和用户名是否注册且使用
     * 查询 sysUserMobileDevice 表中是否有符合条件的记录
     * @param string $user_id 用户 ID
     * @param string $device_id 设备码
     * @return bool
     */
    public static function verifyDevice($user_id,$device_id)
    {
        if(self::verifyMobileConnect($user_id)){
            $result = Db::table('sysUserMobileDevice')
                ->where('user_id', $user_id)
                ->where('device_id', $device_id)
                ->where('use_yn', 'Y')
                ->find();
            return (bool) $result;
        }
        return true;
    }

    /**
     * 验证移动端是否需要验证设备码
     * @param string $user_id 用户 ID
     * @return bool 返回验证结果：成功返回 true，失败返回 false
     */
    public static function verifyMobileConnect($user_id)
    {
        $result = Db::table('sysUserMaster')
            ->where('user_id', $user_id)
            ->where('mobile_connect_yn','Y')
            ->find();
        return (bool) $result;
    }

    /**
     * 查询个人用户信息
     * @param string $user_id 用户 ID
     * @return array|false 成功返回用户信息数组，失败返回 false
     */
    public static function getUser($user_id)
    {
        $result = Db::table('sysUserMaster')
            ->where('user_id', $user_id)
            ->find();
        if ($result) {
            return $result;
        }
        return false;
    }

    /**
     * 获取用户姓名 部门 用户ID
     * @param $empID
     * @param $empName
     * @param $deptName
     * @return mixed
     */
    public static function getUsers($empID, $empName, $deptName)
    {
        // 定义查询条件
        $empIDCondition = $empNameCondition = $deptNameCondition = '';

        if ($empID) {
            $empIDCondition = " AND a.EmpID = '{$empID}' ";
        }
        if ($empName) {
            $empNameCondition = " AND a.EmpNm LIKE  N'%{$empName}%' ";
        }
        if ($deptName) {
            $deptNameCondition = " AND b.DeptNm LIKE N'%{$deptName}%' ";
        }

        // 构建 SQL 查询语句
        $sql = "SELECT TOP 2000 *
            FROM
                (SELECT ROW_NUMBER() OVER(ORDER BY a.EmpID ASC) AS id, a.EmpID, a.EmpNm, b.DeptCd, b.DeptNm
                 FROM TMAEmpy00 a
                 JOIN TMADept00 b ON a.DeptCd = b.DeptCd
                 WHERE a.RetireYn = 'N'
                 {$empIDCondition}
                 {$empNameCondition}
                 {$deptNameCondition}
                ) T
            WHERE id > 0
            ORDER BY id ASC";
        return  Db:: query($sql);
    }


    /**
     * 查询个人用户上下级部门信息
     * @param string $user_id 用户 ID
     * @return array|false 成功返回用户信息数组，失败返回 false
     */
    public static function getEmalis($DB,$langCode,$DeptCd)
    {


        $result = Db::connect($DB)->table('TMADept00')->field('E3.EmpID AS MEmpID
                          ,E3.EmpNm AS MEmpNm,E4.EmpID AS CEmpID,E4.EmpNm AS CEmpNm,D.MDeptCd,D.HDeptCd,D.DeptCd,D.DeptNm
                          ,D.DeptDiv1,T1.TransNm,D.DeptDiv2,T2.TransNm as DeptDivNm')->alias('D')
                            ->leftJoin('TMAEmpy00 E3','D.MEmpID = E3.EmpID')
                            ->leftJoin('TMADept00 D1','D1.HDeptCd = D.DeptCd')
                            ->leftJoin('TMAEmpy00 E4','D1.MEmpID = E4.EmpID')
                            ->leftJoin('TSMDict10 T1',"D.DeptDiv1 = T1.DictCd AND T1.LangCd = '$langCode'")
                            ->leftJoin('TSMDict10 T2',"D.DeptDiv2 = T2.DictCd AND T2.LangCd = '$langCode'")
                            ->where("D.DeptCd ='$DeptCd'")
                            ->find();

        if ($result) {
            return $result;
        }
        return false;




    }
    /**
     * 用户电话
     * 查询 TMAEmpy00 表中是否有符合条件的记录
     * @param array $where 修改数据
     * @return bool
     */
    public static function getEmpIDHP($EmpId)
    {
        $where = array(
            'EmpId' => $EmpId
        );
        $res = Db::table("TMAEmpy00")->field('HP,EmailID')->where($where)->find();
        return $res;
    }
    /**
     * 客户地址编码
     * 查询 TMACust00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getCustArea($CustCd)
    {
        $where = array(
            'CustCd' => $CustCd
        );
        $res = Db::table("TMACust00")->field('CustCd,Area')->where($where)->find();
        return $res;
    }

     /**
     * 客户地址
     * 查询 TMACust00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getCustAreaNm($Area)
    {
        $where = array(
            'MinorCd' => $Area
        );
        $res = Db::table("TSMSyco10")->field('MinorNm')->where($where)->find();
        return $res;
    }

     /**
     * 客户地址
     * 查询 TMACust00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getCustAreaTrNm($Area,$langCode)
    {
        $where = array(
            'DictCd' => $Area,
            'LangCd' => $langCode
        );
        $res = Db::table("TSMDict10")->field('TransNm')->where($where)->find();
        return $res;
    }

    /**
     * 判断用户$user_id是否为ADmin权限
     * @param $user_id
     * @return bool
     */
    public static function isAdminUser($user_id)
    {
        return Db::table('sysUserMaster')
            ->where('user_id', $user_id)
            ->where('user_category', 'ADMIN')
            ->count();
    }
    /**
     * 根据用户ID获取empID
     * @param $user_id
     * @return string
     */
    public static function getEmpID($user_id)
    {
        return Db::table('sysUserMaster')
            ->where('user_id', $user_id)
            ->value('emp_code');
    }

    public static function getCSDept()
    {
        $sql = "WITH DeptHierarchy AS (
                    SELECT DeptCd, DeptNm, HDeptCd, MEmpID,Status
                    FROM TMADept00
                    WHERE HDeptCd = '07000' OR HDeptCd = '16000' or DeptCd = '07000' or DeptCd = '16000'

                    UNION ALL

                    SELECT child.DeptCd, child.DeptNm, child.HDeptCd, child.MEmpID, child.Status
                    FROM TMADept00 child
                    INNER JOIN DeptHierarchy parent ON child.HDeptCd = parent.DeptCd

                )
                SELECT EmpID, EmpNm, DeptCd, DeptNm, HDeptCd,Status
                FROM (
                    SELECT
                        E.EmpID,
                        E.EmpNm ,
                        D.DeptCd,
                        D.DeptNm,
                        D.HDeptCd,
						D.Status,
                        ROW_NUMBER() OVER (PARTITION BY E.EmpID, D.DeptCd ORDER BY E.EmpID) AS RowNum
                    FROM DeptHierarchy D
                    JOIN TMAEmpy00 E ON D.MEmpID = E.EmpID
                ) AS DeptInfo
                WHERE RowNum = 1
                ORDER BY EmpID, DeptNm;";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getemalisDiv1($langCode='SM00010003',$DeptCd){
        $sql = "SELECT
                  E3.EmpID AS MEmpID
                  ,E3.EmpNm AS MEmpNm
                  ,E4.EmpID AS CEmpID
                  ,E4.EmpNm AS CEmpNm
                  ,D.MDeptCd
                  ,D.HDeptCd
                  ,D.DeptCd
                  ,D.DeptDiv1
                  ,T1.TransNm
                  ,D.DeptDiv2
                  ,T2.TransNm as DeptDivNm
              FROM TMADept00 D WITH(NOLOCK)
              Left Join TMAEmpy00 As E3 With (Nolock) On D.MEmpID = E3.EmpID
              Left Join TMADept00 As D1 With (Nolock) On D1.HDeptCd = D.DeptCd
              Left Join TMAEmpy00 As E4 With (Nolock) On D1.MEmpID = E4.EmpID
              Left Join TSMDict10 As T1 With (Nolock) On D.DeptDiv1 = T1.DictCd AND T1.LangCd = '{$langCode}'
              Left Join TSMDict10 As T2 With (Nolock) On D.DeptDiv2 = T2.DictCd AND T2.LangCd = '{$langCode}'
              WHERE D.DeptCd ='{$DeptCd}'";
        return Db::connect(self::$Db)->query($sql)[0];
    }

    public static function getHSDept(){
        $sql = "SELECT D.DeptCd,D.DeptNm,D.HDeptCd,D.MDeptCd,D.MEmpID,E.EmpNm
                FROM TMADept00 D WITH(NOLOCK)
                Left Join TMAEmpy00 As E With (Nolock) On D.MEmpID = E.EmpID
                where HDeptCd = '05000'";
    }

    public static function getLLSZDept(){
        $sql = "WITH DeptHierarchy AS (
                    SELECT DeptCd, DeptNm, HDeptCd, MEmpID,Status
                    FROM TMADept00
                    WHERE HDeptCd = '07000' OR HDeptCd = '50000' or DeptCd = '07000' or DeptCd = '50000'

                    UNION ALL

                    SELECT child.DeptCd, child.DeptNm, child.HDeptCd, child.MEmpID, child.Status
                    FROM TMADept00 child
                    INNER JOIN DeptHierarchy parent ON child.HDeptCd = parent.DeptCd

                )
                SELECT EmpID, EmpNm, DeptCd, DeptNm, HDeptCd,Status
                FROM (
                    SELECT
                        E.EmpID,
                        E.EmpNm ,
                        D.DeptCd,
                        D.DeptNm,
                        D.HDeptCd,
						D.Status,
                        ROW_NUMBER() OVER (PARTITION BY E.EmpID, D.DeptCd ORDER BY E.EmpID) AS RowNum
                    FROM DeptHierarchy D
                    JOIN TMAEmpy00 E ON D.MEmpID = E.EmpID
                ) AS DeptInfo
                WHERE RowNum = 1
                ORDER BY EmpID, DeptNm;";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getDiv1ept($langCode)
    {
        $sql = "SELECT
            isnull(MULTIB.TransNm,MULTIA.MinorNm) AS TransNm,
            isnull(MULTIB.DictCd,MULTIA.MinorCd) AS DictCd
            FROM TSMSyco10 MULTIA WITH(NOLOCK)
            FULL Join  TSMDict10 MULTIB WITH(NOLOCK) On MULTIA.MinorCd = MULTIB.DictCd AND MULTIB.LangCd = '{$langCode}'
             WHERE MULTIA.DeleteYn = 'N' AND MULTIA.MajorCd = 'MA1003'";
        return Db::connect(self::$Db)->query($sql);
    }


}