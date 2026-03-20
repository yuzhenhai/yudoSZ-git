<?php
/**
 * @Author: Yuzh
 * @Date: 2024-09-30 15:55
 */

namespace app\model\salesBusiness;

use app\model\BaseModel;
use think\facade\Db;
/**
 * Class DailyDataModel
 * @package app\model\businessInfo
 * 每日统计表的模型(数据库)操作
 */
//
class TestationTrialModel extends BaseModel
{
    /**
     * 获取订单区分
     * 查询 TASRecv40 表中是否有符合条件的记录
     * @param string $asid ArrivalLeaveNo编号
     * @return bool
     */
    public static function AsTsmsyco10($lang)
    {


        $sql = "SELECT ISNULL(a.RelCd1, '') AS RelCd1,
           ISNULL(b.TransNm, a.MinorNm) AS MinorNm,
           b.LangCd,
           a.MinorCd,
           ISNULL(a.Sort, 'ZZZZ') AS Sort,
           ISNULL(a.DeleteYn, 'N') AS DeleteYn,
           ISNULL(a.RelCd2, '') AS RelCd2
         FROM TSMSyco10 a With(Nolock) LEFT OUTER JOIN TSMDict10 b With(Nolock)
             ON a.MinorCd = b.DictCd
             WHERE a.MajorCd = 'AS1010'
             and b.LangCd='$lang'
             and a.DeleteYn = 'N'
             ";
            $list = Db::query($sql);


        return  $list;
    }
     /**
     * 订单信息列表
     * @param $param  安装报告订单查询POST传值
     * @return array
     */
    public static function OrderSeach($param)
    {
        $where = "";
        $nowtime = date('Y-m-d',intval(time()));
         $where = empty($param['CustNmAn']) ? '' : "AND MC.CustNm LIKE N'%".$param['CustNmAn']."%'";
        $where =  empty($param['orderAS']) ? '' : "AND MA.OrderNo LIKE '".$param['orderAS']."%'";
        if($param['count'] == 0){
            $sql = "SELECT top 50 Row_Number()over(order by MA.OrderNo desc)AS id,
                    MA.EmpId AS list,
                    MA.ExpClss,
                    MA.OrderNo,
                    MA.OrderDate,
                    MA.DelvDate,
                    MA.GateQty,
                    MA.CustomerCd,
                    MA.RefNo,
                    D1.CustNo As CustomerNo,
                    D1.CustNm As CustomerNm,
                    MA.MakerCd,
                    D2.CustNo As MakerNo,
                    D2.CustNm As MakerNm,
                    MA.AgentCd,
                    D3.CustNo As AgentNo,
                    D3.CustNm As AgentNm,

                    isnull(MC.CustNm,'') AS custname,
                    MA.CustCd,

                    isnull(FB.TransNm,FA.MinorNm) as SystemType,
                    UA.EmpID,UA.EmpNm,UB.DeptCd,UB.DeptNm,UB.MDeptCd,
                    UA.HP,UA.EmailID,
                    OA.AssmReptNo
                    from TSAOrder00 MA With(Nolock)   -- 订单信息
                    left join TSAAssmRept00 OA With(Nolock) on  MA.OrderNo = OA.OrderNo
                    left join TMACust00 MC With(Nolock) on MA.CustCd = MC.CustCd  -- 客户名称
                    left join TSMSyco10 FA With(Nolock) on FA.MinorCd = MA.SystemType

                    Left Outer Join TMACust00 D1 With(Nolock) On MA.CustomerCd = D1.CustCd
                    Left Outer Join TMACust00 D2 With(Nolock) On MA.MakerCd = D2.CustCd
                    Left Outer Join TMACust00 D3 With(Nolock) On MA.AgentCd = D3.CustCd

                    left join TSMDict10 FB With(Nolock) on FB.DictCd = MA.SystemType and FB.LangCd = '".$param['langCode']."'
                    left join TMAEmpy00 UA With(Nolock) on MA.EmpId = UA.EmpID   -- 员工信息
                    left join TMADept00 UB With(Nolock) on UA.DeptCd = UB.DeptCd -- 部门信息

                    WHERE ISNULL(MA.EmpId,'')!='' and MA.CfmYn='1' and MA.DeleteYn = 'N'  $where
                    order by MA.OrderNo desc;";
        }
        else
        {
            $sql = "SELECT top 50 * from (
                    select Row_Number()over(order by MA.OrderNo desc)AS id,
                    MA.EmpId AS list,
                    MA.ExpClss,
                    MA.OrderNo,
                    MA.OrderDate,
                    MA.DelvDate,
                    MA.GateQty,
                    MA.CustomerCd,
                    D1.CustNo As CustomerNo,
                    D1.CustNm As CustomerNm,
                    MA.MakerCd,
                    D2.CustNo As MakerNo,
                    D2.CustNm As MakerNm,
                    MA.AgentCd,
                    D3.CustNo As AgentNo,
                    D3.CustNm As AgentNm,

                    isnull(MC.CustNm,'') AS custname,
                    isnull(FB.TransNm,FA.MinorNm) as SystemType,
                    UA.EmpID,UA.EmpNm,UB.DeptCd,UB.DeptNm,UB.MDeptCd,
                    UA.HP,UA.EmailID, --用户电话
                    OA.AssmReptNo
                    from TSAOrder00 MA With(Nolock) -- 订单信息
                    left join TSAAssmRept00 OA With(Nolock) on MA.OrderNo = OA.OrderNo
                    left join TMACust00 MC With(Nolock) on MA.CustCd = MC.CustCd  -- 客户名称
                    left join TSMSyco10 FA With(Nolock) on FA.MinorCd = MA.SystemType

                    Left Outer Join TMACust00 D1 With(Nolock) On MA.CustomerCd = D1.CustCd
                    Left Outer Join TMACust00 D2 With(Nolock) On MA.MakerCd = D2.CustCd
                    Left Outer Join TMACust00 D3 With(Nolock) On MA.AgentCd = D3.CustCd

                    left join TSMDict10 FB With(Nolock) on FB.DictCd = MA.SystemType and FB.LangCd = '".$param['langCode']."'
                    left join TMAEmpy00 UA With(Nolock) on MA.EmpId = UA.EmpID   -- 员工信息
                    left join TMADept00 UB With(Nolock) on UA.DeptCd = UB.DeptCd -- 部门信息

                    where ISNULL(MA.EmpId,'')!='' and MA.CfmYn='1' and MA.DeleteYn = 'N' $where

                    )t where id > ".$param['count']." order by id asc;";

        }
        $list = Db::query($sql);
        return $list;
    }


    //订单次数
    public static function getTstInjCnt($OrderNo){
        $sql = "SELECT ISNULL(Max( TstInjCnt ),0) + 1 AS TstInjCnt FROM TSATstInjRept00  With (Nolock) WHERE OrderNo ='$OrderNo' AND OrderSysRegYn ='Y'
        ";

         $list = Db::query($sql);
        return $list[0];

    }
     /**
     * 客户信息列表
     * @param $param  安装报告订单查询POST传值
     * @return array
     */
    public static function getCustList($custNo,$custNm,$count,$langCode)
    {

        $result = DB::query("select top 50 * from (
              SELECT Row_Number()over(order by a.CustCd asc)AS id,
              a.CustNo,
              a.CustNm,
              a.CustCd,
              a.KoOrFo,
              isnull(b.TransNm,c.MinorNm) as Status,
              a.Status as StatusId
              FROM TMACust00 a
              LEFT JOIN TSMDict10 b ON a.Status = b.DictCd AND b.LangCd = '$langCode'
              LEFT JOIN TSMSyco10 c on a.Status = c.MinorCd
              where
              a.CustNo like '$custNo%%'
              AND a.CustNm like N'%$custNm%%' )T where id > '$count' order by id asc");
        return $result;
    }


     /**
     * 查询试模报告-最后一条
     * 查询 TSATstInjRept00 表中是否有符合条件的记录
     * @param array $TstInjReptNo 修改数据
     * @return bool
     */
    public static function getTstInjEnd($TstInjReptNo)
    {
        $res = Db::table("TSATstInjRept00")->where('TstInjReptNo','like',$TstInjReptNo.'%')->order('TstInjReptNo','desc')->find();
        return $res;
    }
     /**
     * 添加试模报告报告
     * 查询 TSATstInjRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addTest($data)
    {
        $res = Db::table("TSATstInjRept00")->insert($data);
        return $res;
    }

     /**
     * 查询试模报告-
     * 查询 TSATstInjRept00 表中是否有符合条件的记录
     * @param array $TstInjReptNo 修改数据
     * @return bool
     */
    public static function getTstInjList($TstInjReptNo)
    {
        $res = Db::table("TSATstInjRept00")->where('TstInjReptNo',$TstInjReptNo)->find();
        return $res;
    }

       /**
     * 修改试模报告
     * 查询 TSATstInjRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function SaveTest($data,$where)
    {
        $res = Db::table("TSATstInjRept00")->where($where)->save($data);
        return $res;
    }

    /**
     * 试模报告详情
     * 查询 TSATstInjRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function TestInfo($TstInjReptNo){
        $sql = "select
                  TS.*
                  ,CASE TS.OrderSysRegYn WHEN 'Y' THEN TS.OrderNo WHEN 'N' THEN TS.UnRegOrderNo ELSE '' END AS OrderNo
                  ,D.DeptNm As TstInjDeptNm
                  ,E.EmpNm As TstInjEmpNm
                  ,C.CustNo As CustNo
                  ,C.CustNm As CustNm
                  ,OA.OA_Status
                  ,OA.SourceType
                from TSATstInjRept00 AS TS With (Nolock)
                Left Outer Join TMADept00 As D With (Nolock) On TS.TstInjDeptCd = D.DeptCd
                  Left Outer Join TMAEmpy00 As E With (Nolock) On TS.TstInjEmpID = E.EmpID
                  Left Outer Join TMACust00 As C With (Nolock) On TS.CustCd = C.CustCd

                  Left Join TS_OA_Interface As OA With (Nolock) On OA.SourceNo = TS.TstInjReptNo AND SourceType = '016'
                where TS.TstInjReptNo = '$TstInjReptNo'";

        $list = Db::query($sql);
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }

        return $res;

    }

     /**
     * 获取组装试模销售负责人
     * @param $assReptNo  TSATstInjRept20
     * @return array
     */
    public static function getAssmTextSales($TstInjReptNo){
        $result = Db::query("
            select a.TstInjReptNo,
            a.Seq,
            a.SaleEmpID,
            b.EmpNm,
            b.Sex,
            a.Remark,
            NB.DeptNm from TSATstInjRept20 a With(Nolock)
            left join TMAEmpy00 NA With(Nolock) on a.SaleEmpID = NA.EmpID
            left join TMADept00 NB With(Nolock) on NA.DeptCd = NB.DeptCd,
            TMAEmpy00 b With (Nolock)
            where a.SaleEmpID = b.EmpID AND TstInjReptNo = :id",['id'=>$TstInjReptNo],true);
        return $result;
    }

    /**
     * 添加试模报告-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getSalesD($where)
    {
        $res = Db::table("TSATstInjRept20")->where($where)->count();
        return $res;
    }
    /**
     * 查询试模报告-同行人员是否纯在
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getSalesE($where)
    {
        $res = Db::table("TSATstInjRept20")->where($where)->find();
        return $res;
    }

    /**
     * 添加安装报告-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addSales($data)
    {
        $res = Db::table("TSATstInjRept20")->insert($data);
        return $res;
    }

    /**
     * 同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getTastFellow($TstInjReptNo)
    {
        $sql = "
            select a.TstInjReptNo,
            a.Seq,
            a.SaleEmpID as EmpID,
            b.EmpNm,
            NB.DeptNm,
            NB.DeptCd
            from TSATstInjRept20 a
            left join TMAEmpy00 NA on a.SaleEmpID = NA.EmpID
            left join TMADept00 NB on NA.DeptCd = NB.DeptCd,
            TMAEmpy00 b
            where a.SaleEmpID = b.EmpID AND TstInjReptNo = '$TstInjReptNo'";
        $list = Db::query($sql);

        return $list;
    }

    /**
     * 删除试模报告-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteTestSales($where)
    {
        $res = Db::table("TSATstInjRept20")->where($where)->delete();
        return $res;
    }
    /**
     * 获取组装照片
     * @param $assReptNo
     * @return array
     */
    public static function getTestPhotoNm($where){

        $res = Db::table("TSATstInjRept10")->where($where)->select();



        return $res;
    }

    /**
     * 获取组装照片
     * @param $assReptNo
     * @return array
     */
    public static function getTestPhoto($where){

        $res = Db::table("TSATstInjRept10")->where($where)->find();
        return $res;
    }
         /**
     *  试模报告-添加照片
     * 查询 TSAAssmRept10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addTestPhoto($data)
    {
        $res = Db::table("TSATstInjRept10")->insert($data);
        return $res;
    }

    /**
     * 试模报告-删除照片
     * 查询 TSAAssmRept10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteTestPhoto($where)
    {
        $res = Db::table("TSATstInjRept10")->where($where)->delete();
        return $res;
    }


    public static function as_jobno($uid){

        $where = array(
            'EmpId' => $uid,
            'LastYN'    => 'Y'
        );
        $nowdate = date('Y-m-d',intval(time()));
        $res = Db::table("TMAJobc10")
            ->where($where)
            ->whereTime('STDate', '<=', $nowdate)
            ->whereTime('EDDate', '>=', $nowdate)

            ->find();

        return $res;
    }

}