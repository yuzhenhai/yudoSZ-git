<?php
/**
 * @Author: Yuzh
 * @Date: 2024-10-08 15:55
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
class InstallationTrialModel extends BaseModel
{
    /**
     * 获取当日最后一条定位信息
     * 查询 TASRecv40 表中是否有符合条件的记录
     * @param string $asid ArrivalLeaveNo编号
     * @return bool
     */
    public static function getAddress($asid)
    {
        $address = Db::table("TASRecv40")->where('ArrivalLeaveNo','LIKE',$asid."%")->order('ArrivalLeaveNo','desc')->find();
        return  $address;
    }

     /**
     * 获取当日最后一条定位信息
     * 查询 TASRecv40 表中是否有符合条件的记录
     * @param string $asid ArrivalLeaveNo编号
     * @return bool
     */
    public static function getAddressInfo($asid)
    {
        $address = Db::table("TASRecv40")->where('ArrivalLeaveNo','=',$asid)->find();
        return  $address;
    }

    /**
     * 添加定位信息
     * 查询 TASRecv40 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addAddress($data)
    {
        $res = Db::table("TASRecv40")->insert($data);
        return $res;
    }
    /**
     * 保存定位信息
     * 查询 TASRecv40 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @param string $where where判断
     * @return bool
     */
    public static function saveAddress($data,$where)
    {
        $res = Db::table("TASRecv40")->where($where)->save($data);
        return $res;
    }

    /**
     * 查询定位信息
     * 查询 TASRecv40 表中是否有符合条件的记录
     * @userID string $userID 用户ID
     * @return bool
     */
    public static function getUserAddress($userID,$ArrivalNo='')
    {
        $startTime = date('Y-m-d H:i:s', strtotime('-1 day'));
        $endTime = date('Y-m-d H:i:s');
        $sql = "select  TS.*,UA.EmpNm,UB.DeptCd,UB.DeptNm from TASRecv40 TS With(Nolock)
                left join TMAEmpy00 UA With(Nolock) on TS.EmpID = UA.EmpID
                left join TMADept00 UB With(Nolock) on UA.DeptCd = UB.DeptCd
                WHERE UA.EmpID = '$userID' AND TS.ArrivalLeaveNo LIKE '%$ArrivalNo%' AND TS.ArrivalDate Between '".$startTime."' AND '".$endTime."' ORDER BY TS.ArrivalLeaveNo DESC";

        $list = Db::query($sql);
        return $list;
    }

    /**
     * 查询最后一个定位
     * 查询 TASRecv40 表中是否有符合条件的记录
     * @userID string $userID 用户ID
     * @return bool
     */
    public static function getAddressEnd($userID)
    {
        $list = self::getUserAddress($userID);
        $address = array();
        if(!empty($list)){
          $address = Db::table("TASRecv40")->where('EmpId','=',$userID)->order('ArrivalLeaveNo','desc')->find();
        }
        return $address;
    }


    public static function Users($userID)
    {
        $sql = "select UB.DeptCd,UB.MDeptCd,UC.emp_code from TMAEmpy00 UA  With(Nolock)
                left join TMADept00 UB With(Nolock) on UB.DeptCd = UA.DeptCd
                left join sysUserMaster UC With(Nolock) on UA.EmpId = UC.emp_code and isnull(UC.emp_code,'')!=''
                 WHERE UC.user_id = '$userID'";

        $list = Db::query($sql);
        $sql = "select UD.JobNo from sysUserMaster UC With(Nolock)
                left join TMAJobc10 UD With(Nolock) on UD.EmpId = UC.emp_code
                INNER join TMAJobc00 UE With(Nolock) on UE.JobNo = UD.JobNo and UE.SaleYn = 'Y'
                WHERE UC.user_id = '$userID' and UD.LastYN = 'Y'
                and getdate() between UD.STDate and UD.EDDate";
        $JobNos = Db::query($sql);

        $res = array(
            'DeptCd'    => $list[0]['DeptCd'],
            'MDeptCd'    => $list[0]['MDeptCd'],
            'empId'    => $list[0]['emp_code']
        );
        if($JobNos){
            $res['JobNo'] = $JobNos[0]['JobNo'];
        }else{
            $res['JobNo'] = '';
        }
        return $res;
    }
    /**
     * 查询安装报告
     * 查询 TSAAssmRept00 表中是否有符合条件的记录
     * @param array $param POST传值
     * @return bool
     */
    public static function getInstallSearch($param)
    {
        $where = '';
            $where .= empty($param['orderID']) ? '' : " AND OA.OrderNo like '".$param['orderID']."%'";
            $where .= empty($param['CustNm']) ? '' :" AND CA.CustNm like N'%".$param['CustNm']."%'";
            if(empty($where)){
              $where .= empty($param['startDate']) ? '' : " AND MA.AssmReptDate Between '".$param['startDate']."' AND '".$param['endDate']."'";
            }
            $res = self::Users($param['userID']);

        switch ($param['auth']){
            case 'SM00040001':   //全部
                $authwhere = '';
                break;
            case 'SM00040003':   //个人加部门
                $authwhere = " AND UA.EmpId = '".$res['empId']."'";
                break;
            case 'SM00040004':
                $authwhere = " AND OA.JobNo = '".$res['jobNo']."'";
                break;
            case 'SM00040002':
                $authwhere = " AND UB.DeptCd = '".$res['DeptCd']."'";
                break;
            case 'SM00040005':   //管理
                $authwhere = "AND UB.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','".$res['empId']."') )";
//                $authwhere = " AND UB.MDeptCd = '$MDeptCd'";
                break;
            default:  //默认为个人
                $authwhere = " AND UB.DeptCd = '".$res['DeptCd']."'";
                break;
        }

        if($param['count'] == 0){
            $sql = "select top 50  Row_Number()over(order by MA.AssmReptNo desc)AS id,
                    MA.AssmReptNo,
                    MA.AssmReptDate,
                    MA.AssmDate,
                    MA.CfmYn,
                    UA.EmpNm,
                    UB.DeptNm,
                    MA.OrderNo,
                    ISNULL(CA.CustNm,'')  as custnm
                    from TSAAssmRept00 MA With (Nolock)
                    left join TSAOrder00 OA With(Nolock) on MA.OrderNo = OA.OrderNo AND OA.DeleteYn = 'N'
                    left join TMACust00 CA With(Nolock) on OA.CustCd = CA.CustCd
                    left join TMAEmpy00 UA With(Nolock) on MA.AssmEmpID = UA.EmpID
                    left join TMADept00 UB With(Nolock) on MA.AssmDeptCd = UB.DeptCd
                    where 1=1   $authwhere $where;";

        }
        else
        {
            $sql = "select top 50 * from (
                    select Row_Number()over(order by MA.AssmReptNo desc)AS id,
                    MA.AssmReptNo,
                    MA.AssmReptDate,
                    MA.AssmDate,
                    MA.CfmYn,
                    UA.EmpNm,
                    UB.DeptNm,
                    MA.OrderNo,
                    ISNULL(CA.CustNm,'') as custnm
                    from TSAAssmRept00 MA With (Nolock)
                    left join TSAOrder00 OA With(Nolock) on MA.OrderNo = OA.OrderNo  AND OA.DeleteYn = 'N'
                    left join TMACust00 CA With(Nolock) on OA.CustCd = CA.CustCd
                    left join TMAEmpy00 UA With(Nolock) on MA.AssmEmpID = UA.EmpID
                    left join TMADept00 UB With(Nolock) on MA.AssmDeptCd = UB.DeptCd
                    where 1=1 $authwhere $where)A
                    WHERE id > '".$param['count']."' order by id asc";
        }
        $list = Db::query($sql);
        return $list;
    }
      /**
     * 试模报告列表
     * @param $param  安装报告订单查询POST传值
     * @return array
     */
    public static function TestSearch($param)
    {
         $where = '1=1 ';
        // empty($get_orderid) ? $where .= '' : $where .= " AND A.OrderNo LIKE '$get_orderid%'";
        // empty($get_TstInjEmpID) ? $where .= '' : $where .= " AND A.TstInjReptNo = '$get_TstInjEmpID'";
        // empty($StartDate) ? $where .= '' : $where .= " AND A.TstInjReptDate Between '$StartDate' AND '$EndDate'";

            $where .= empty($param['orderID']) ? '' : " AND A.OrderNo like '".$param['orderID']."%'";
            $where .= empty($param['TstInjReptNo']) ? '' :" AND A.TstInjReptNo = '".$param['TstInjReptNo']."'";
            if(empty($param['orderID']) && empty($param['TstInjReptNo'])){
              $where .= empty($param['startDate']) ? '' : " AND A.TstInjReptDate Between '".$param['startDate']."' AND '".$param['endDate']."'";
            }
        if($param['count'] == 0){
            $sql = "SELECT top 50
                  Row_Number()over(order by A.TstInjReptNo desc)AS id
                  ,A.TstInjReptNo
                  ,A.TstInjReptDate
                  ,A.TstInjDeptCd
                  ,D.DeptNm As TstInjDeptNm
                  ,A.TstInjEmpID
                  ,E.EmpNm As TstInjEmpNm
                  ,A.JobNo
                  ,A.TstInjDate
                  ,A.AssmReptNo
                  ,A.TstInjCnt
                  ,A.OrderGubun
                  ,A.ExpClss
                  ,CASE A.OrderSysRegYn WHEN 'Y' THEN A.OrderNo WHEN 'N' THEN A.UnRegOrderNo ELSE '' END AS OrderNo
                  ,A.UnRegOrderNo
                  ,A.OrderSysRegYn
                  ,A.GoodNm
                  ,A.RefNo
                  ,A.SupplyScope
                  ,A.HRSystem
                  ,A.ManifoldType
                  ,A.SystemSize
                  ,A.SystemType
                  ,A.GateQty
                  ,A.CustCd
                  ,C.CustNo As CustNo
                  ,C.CustNm As CustNm
                  ,A.Material
                  ,A.TstInjPlace
                  ,A.InjModel
                  ,A.SysTemp
                  ,A.BeforTemp
                  ,A.AfterTemp
                  ,A.DryTemp
                  ,A.IDCardYn
                  ,A.TstInjResult
                  ,A.ProblemDes
                  ,A.CauseAnalysis
                  ,A.ProposeSolut
                  ,A.InjProcess
                  ,A.Remark
                  ,A.SysRemark
                  ,A.CfmYn
                  ,A.CfmEmpID
                  ,A.CfmDate
                  ,A.RegEmpID
                  ,A.RegDate
                  ,A.UptEmpID
                  ,A.UptDate FROM TSATstInjRept00 As A With (Nolock)
                  Left Outer Join TMADept00 As D With (Nolock) On A.TstInjDeptCd = D.DeptCd
                  Left Outer Join TMAEmpy00 As E With (Nolock) On A.TstInjEmpID = E.EmpID
                  Left Outer Join TMACust00 As C With (Nolock) On A.CustCd = C.CustCd
                  WHERE $where
                  ";
        }else{

            $sql = "SELECT top 50 * from (SELECT  Row_Number()over(order by A.TstInjReptNo desc)AS id
                    ,A.TstInjReptNo
                  ,A.TstInjReptDate
                  ,A.TstInjDeptCd
                  ,D.DeptNm As TstInjDeptNm
                  ,A.TstInjEmpID
                  ,E.EmpNm As TstInjEmpNm
                  ,A.JobNo
                  ,A.TstInjDate
                  ,A.AssmReptNo
                  ,A.TstInjCnt
                  ,A.OrderGubun
                  ,A.ExpClss
                  ,CASE A.OrderSysRegYn WHEN 'Y' THEN A.OrderNo WHEN 'N' THEN A.UnRegOrderNo ELSE '' END AS OrderNo
                  ,A.UnRegOrderNo
                  ,A.OrderSysRegYn
                  ,A.GoodNm
                  ,A.RefNo
                  ,A.SupplyScope
                  ,A.HRSystem
                  ,A.ManifoldType
                  ,A.SystemSize
                  ,A.SystemType
                  ,A.GateQty
                  ,A.CustCd
                  ,C.CustNo As CustNo
                  ,C.CustNm As CustNm
                  ,A.Material
                  ,A.TstInjPlace
                  ,A.InjModel
                  ,A.SysTemp
                  ,A.BeforTemp
                  ,A.AfterTemp
                  ,A.DryTemp
                  ,A.IDCardYn
                  ,A.TstInjResult
                  ,A.ProblemDes
                  ,A.CauseAnalysis
                  ,A.ProposeSolut
                  ,A.InjProcess
                  ,A.Remark
                  ,A.SysRemark
                  ,A.CfmYn
                  ,A.CfmEmpID
                  ,A.CfmDate
                  ,A.RegEmpID
                  ,A.RegDate
                  ,A.UptEmpID
                  ,A.UptDate FROM TSATstInjRept00 As A With (Nolock)
                  Left Outer Join TMADept00 As D With (Nolock) On A.TstInjDeptCd = D.DeptCd
                  Left Outer Join TMAEmpy00 As E With (Nolock) On A.TstInjEmpID = E.EmpID
                  Left Outer Join TMACust00 As C With (Nolock) On A.CustCd = C.CustCd
                  WHERE $where )A
                    WHERE id > '".$param['count']."' order by id asc
                  ";
        }
        $list = Db::query($sql);
        return $list;

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

                    where ISNULL(MA.EmpId,'')!='' and MA.CfmYn='1' and MA.DeleteYn = 'N'
                    and MA.OrderNo Not IN (SELECT TSS.OrderNo FROM TSAAssmRept00 TSS With(Nolock))
                     $where

                    )t where id > '".$param['count']."' order by id asc;";


        $list = Db::query($sql);
        return $list;
    }

    /**
     * 订单信息
     * @param $OrderNo  订单号码
     * @param $langCode 语言

     * @return array
     */
    public static function orderMinute($OrderNo,$langCode)
    {
        $sql = "SELECT
                MB.EmpId AS list,
                MB.CustCd,
                MB.ExpClss,
                MB.SystemType AS systype,
                MB.DrawNo,
                MB.SpecNo,
                MB.DrawAmd,
                MB.RefNo,
                MB.GoodNm,
                MB.SpecType,
                MB.OrderNo,MB.OrderDate,MB.DelvDate,MB.GateQty,MC.CustNm AS custname,isnull(FB.TransNm,FA.MinorNm) as systemtype,
                isnull(FD.TransNm,FC.MinorNm) as MarketNm,
                MB.MarketCd,
                LA.EmpID,LA.EmpNm,LB.DeptCd,LB.DeptNm,

                -- SYSTEM
                MB.SupplyScope,   ISNULL(SupplyScopeA.TransNm,SupplyScopeB.MinorNm)   AS SupplyScopeNm,
                MB.HRSystem,      ISNULL(HRSystemA.TransNm,HRSystemB.MinorNm)         AS HRSystemNm,
                MB.ManifoldType,  ISNULL(ManifoldTypeA.TransNm,ManifoldTypeB.MinorNm) AS ManifoldTypeNm,
                MB.SystemSize,    ISNULL(SystemSizeA.TransNm,SystemSizeB.MinorNm)        AS SystemSizeNm,
                MB.SystemType,    ISNULL(SystemTypeA.TransNm,SystemTypeB.MinorNm)     AS SystemTypeNm,
                MB.GateType,      ISNULL(GateTypeA.TransNm,GateTypeB.MinorNm)         AS GateTypeNm,
                -- Resin
                ISNULL(ResinA.TransNm,ResinB.MinorNm)+' '
                +ISNULL(ResinAddA.TransNm,ResinAddB.MinorNm)+' '
                +convert(Nvarchar(10),Spec.ResinRate)
                +'%'
                AS Resin
                -- 客户表
                from TSAOrder00 MB With(Nolock)
                left join TMACust00 MC With(Nolock) on MB.CustCd = MC.CustCd  -- 客户名称
                left join TSMSyco10 FA With(Nolock) on FA.MinorCd = MB.SystemType
                left join TSMDict10 FB With(Nolock) on FB.DictCd = MB.SystemType and FB.LangCd = '$langCode'
                left join TSMSyco10 FC With(Nolock) on FA.MinorCd = MB.MarketCd
                left join TSMDict10 FD With(Nolock) on FD.DictCd = MB.MarketCd and FD.LangCd = '$langCode'
                left join TMAEmpy00 LA With(Nolock) on MB.EmpId = LA.EmpID   -- 员工信息
                left join TMADept00 LB With(Nolock) on MB.DeptCd = LB.DeptCd -- 部门信息
                 -- SYSTEM
                left join TSMDict10 SupplyScopeA  With(Nolock) on SupplyScopeA.DictCd   = MB.SupplyScope  and SupplyScopeA.LangCd  = '$langCode'
                left join TSMSyco10 SupplyScopeB  With(Nolock) on SupplyScopeB.MinorCd  = MB.SupplyScope
                left join TSMDict10 HRSystemA     With(Nolock) on HRSystemA.DictCd      = MB.HRSystem     and HRSystemA.LangCd     = '$langCode'
                left join TSMSyco10 HRSystemB     With(Nolock) on HRSystemB.MinorCd     = MB.HRSystem
                left join TSMDict10 ManifoldTypeA With(Nolock) on ManifoldTypeA.DictCd  = MB.ManifoldType and ManifoldTypeA.LangCd = '$langCode'
                left join TSMSyco10 ManifoldTypeB With(Nolock) on ManifoldTypeB.MinorCd = MB.ManifoldType

                left join TSMDict10 SystemSizeA   With(Nolock) on SystemSizeA.DictCd    = MB.SystemSize   and SystemSizeA.LangCd   = '$langCode'
                left join TSMSyco10 SystemSizeB   With(Nolock) on SystemSizeB.MinorCd   = MB.SystemSize

                left join TSMDict10 SystemTypeA   With(Nolock) on SystemTypeA.DictCd    = MB.SystemType   and SystemTypeA.LangCd   = '$langCode'
                left join TSMSyco10 SystemTypeB   With(Nolock) on SystemTypeB.MinorCd   = MB.SystemType
                left join TSMDict10 GateTypeA     With(Nolock) on GateTypeA.DictCd      = MB.GateType     and GateTypeA.LangCd     = '$langCode'
                left join TSMSyco10 GateTypeB     With(Nolock) on GateTypeB.MinorCd     = MB.GateType

                left join TSASpec30 Spec With(Nolock) on Spec.SpecNo = MB.SpecNo and Spec.SpecType = MB.SpecType

                left join TSMDict10 ResinA  With(Nolock) on ResinA.DictCd   = Spec.Resin  and ResinA.LangCd     = '$langCode'
                left join TSMSyco10 ResinB  With(Nolock) on ResinB.MinorCd = Spec.Resin
                left join TSMDict10 ResinAddA  With(Nolock) on ResinAddA.DictCd   = Spec.ResinAdd  and ResinAddA.LangCd     = '$langCode'
                left join TSMSyco10 ResinAddB  With(Nolock) on ResinAddB.MinorCd = Spec.ResinAdd

                where MB.OrderNo = '$OrderNo' AND MB.CfmYn='1' AND MB.DeleteYn = 'N'";


            $list = Db::query($sql);
            if(!empty($list)){
                $res = $list[0];
            }else{
                $res = array();
            }
            return $res;
    }

    /**
     * 安装报告信息信息
     * @param $OrderNo  订单号码

     * @return array
     */
    public static function AssmReptMinute($OrderNo)
    {
        $sql = " SELECT ma.AssmReptNo,ma.AssmReptDate,ma.AssmDate,ma.OrderNo,
                 mb.EmpNm as AssmEmpNm,
                 mc.DeptNm as AssmDeptNm,
                 ma.AssmEmpID,
                 ma.AssmDeptCd,
                 ma.CfmYn,
                 ma.Remark,
                 ma.AssmContents,
                 ma.TrialDate,
                 ma.TrialEmpID,
                 md.EmpNm as TrialEmpNm,
                 ma.TrialDeptCd,
                 me.DeptNm as TrialDeptNm,
                 ma.TrialContents,
                 ma.SupplyScope,
                 ma.HRSystem,
                 ma.ManifoldType,
                 ma.SystemSize,
                 ma.SystemType,
                 -- ma.AssmSize,
                 ma.AssmWiriMode,
                 ma.CustSignYn,
                 ma.SendEmailYn,
                 ma.CustPrsn,
                 ma.CustTell,
                 ma.CustEmail,
                 ma.AssmWiriType,
                 ma.SystemClass,
                 ma.ArrivalTime,
                 ma.LeaveTime,
                 OA.OA_Status,
                 OA.SourceType,

                 ma.ApprUseYn,
                 ma.ArrivalLeaveNo,
                 ma.ArrivalLat,
                 ma.ArrivalLng,
                 ma.ArrivalLocationAddr,
                 ma.CustSignDate,
                 ma.CustGpsLat,
                 ma.CustGpsLng,
                 ma.CustLocationAddr,
                 ma.LeaveLat,
                 ma.LeaveLng,
                 ma.LeaveLocationAddr,
                 ma.facilityYn
                 from
                 TSAAssmRept00 ma  With(Nolock)--组装信息
                 left join TMAEmpy00 mb With(Nolock) on ma.AssmEmpID = mb.EmpID --组装人员
                 left join TMADept00 mc With(Nolock) on mb.DeptCd = mc.DeptCd --组装部门
                  left join TMAEmpy00 md With(Nolock) on ma.TrialEmpID = md.EmpID --试模人员
                left join TMADept00 me With(Nolock) on md.DeptCd = me.DeptCd --试模部门
                left join TS_OA_Interface OA With(Nolock) on OA.SourceNo = ma.AssmReptNo AND OA.SourceType = '015'
                 where ma.OrderNo = '$OrderNo'";
        $list = Db::query($sql);
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;
    }
     /**
     * 查询职员与部门
     * @param $empId  职员工号
     * @param $empNm  职员姓名
     * @param $deptNm 部门名称
     * @param $count  查询次数
     * @return array
     */
    public static function getEmpyList($empId,$empNm,$deptNm,$count=0){
        if($count == 0){
            $sql = "
                select top 50 * from
                (select
                Row_Number()over(order by A.EmpID desc)AS id,
                A.EmpID,A.EmpNm,B.DeptCd,B.DeptNm
                from TMAEmpy00 A WITH(NOLOCK)
                left join TMADept00 B WITH(NOLOCK) on A.DeptCd = B.DeptCd
                where A.EmpID LIKE '%". $empId ."%'
                AND A.EmpNm LIKE N'%". $empNm ."%'
                AND B.DeptNm LIKE '%". $deptNm ."%'
                AND A.RetireYn = 'N')T  order by id asc
            ";
        }else{
            $sql = "
                select top 50 * from
                (select
                Row_Number()over(order by A.EmpID desc)AS id,
                A.EmpID,A.EmpNm,B.DeptCd,B.DeptNm
                from TMAEmpy00 A WITH(NOLOCK)
                left join TMADept00 B WITH(NOLOCK) on A.DeptCd = B.DeptCd
                where A.EmpID LIKE '%". $empId ."%'
                AND A.EmpNm LIKE N'%". $empNm ."%'
                AND B.DeptNm LIKE '%". $deptNm ."%'
                AND A.RetireYn = 'N')T where id >'". $count . "'  order by id asc
            ";
        }
        $list = Db::query($sql);
        return $list;
    }
    /**
     * 系统分类 详细信息
     * @param $RelCd1
     * @return array
     */
    public static function getInfoxinxi($RelCd1)
    {
        $sql = "select
            MajorCd,
            MinorCd,
            MinorNm,
            MinorTransNm,
            SysFlag,
            RelCd1,
            DeleteYn
            from TSMSyco10 With(Nolock) where MajorCd = 'SA3402' AND RelCd1 = '$RelCd1' order by MinorCd ASC";
        $list = Db::query($sql);
        return $list;
    }

    /**
     * 默认当日最后一条到达时间数据
     * @param $RelCd1
     * @return array
     */
    public static function getArrival($login_id)
    {
        $sql = "select top 1 * from TASRecv40 With(Nolock)
                WHERE EmpID = '$login_id' AND CONVERT(DATE, ArrivalDate) = CONVERT(DATE, GETDATE()) ORDER BY ArrivalDate DESC ";

        $list = Db::query($sql);
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;
    }

    public static function OrderAssmRept($OrderNo)
    {
        $sql = "select AssmReptNo,facilityYn from TSAAssmRept00 where OrderNo = '$OrderNo'";
        $list = Db::query($sql);
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;

    }

    public static function AssmReptNo()
    {
        $post_mtid = date('Ym',intval(time()));
        $sql = "select top 1 AssmReptNo from TSAAssmRept00 where AssmReptNo LIKE '$post_mtid%%' order by AssmReptNo desc";
        $list = Db::query($sql);

        if(empty($list))
        {
            $post_mtid = $post_mtid.'0001';
        }
        else
        {
            $result_mtid = substr($list[0]['AssmReptNo'],6);
            $post_mtid .= $result_mtid;
            $post_mtid = $post_mtid +1;
        }
        return $post_mtid;

    }

    /**
     * 添加安装报告
     * 查询 TSAAssmRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addInstall($data)
    {
        $res = Db::table("TSAAssmRept00")->insert($data);
        return $res;
    }

    /**
     * 添加安装报告-系统分类
     * 查询 TSAAssmRept40 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addSystemClass($data)
    {
        $res = Db::table("TSAAssmRept40")->insert($data);
        return $res;
    }
     /**
     * 修改安装报告
     * 查询 TSAAssmRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function SaveInstall($data,$where)
    {
        $res = Db::table("TSAAssmRept00")->where($where)->save($data);
        return $res;
    }

     /**
     * 查询系统分类
     * 查询 TSAAssmRept40 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getSystemClass($AssmReptNo)
    {
        $res = Db::table("TSAAssmRept40")->where('AssmReptNo',$AssmReptNo)->select();
        return $res;
    }

     /**
     * 删除系统分类
     * 查询 TSAAssmRept40 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteSystemClass($AssmReptNo)
    {
        $res = Db::table("TSAAssmRept40")->where('AssmReptNo',$AssmReptNo)->delete();
        return $res;
    }


     /**
     * 同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getAssmFellow($AssmReptNo)
    {
        $sql = "
            select a.AssmReptNo,
            a.Seq,
            a.SaleEmpID as EmpID,
            b.EmpNm,

            NB.DeptNm,
            NB.DeptCd

            from TSAAssmRept30 a
            left join TMAEmpy00 NA on a.SaleEmpID = NA.EmpID
            left join TMADept00 NB on NA.DeptCd = NB.DeptCd,
            TMAEmpy00 b
            where a.SaleEmpID = b.EmpID AND AssmReptNo = '$AssmReptNo'";
        $list = Db::query($sql);

        return $list;
    }

     /**
     * 添加安装报告-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addSales($data)
    {
        $res = Db::table("TSAAssmRept30")->insert($data);
        return $res;
    }
    /**
     * 查询安装报告-同行人员是否纯在
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getSalesE($where)
    {
        $res = Db::table("TSAAssmRept30")->where($where)->find();
        return $res;
    }
    /**
     * 添加安装报告-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getSalesD($where)
    {
        $res = Db::table("TSAAssmRept30")->where($where)->count();
        return $res;
    }
    /**
     * 删除安装报告-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteSales($where)
    {
        $res = Db::table("TSAAssmRept30")->where($where)->delete();
        return $res;
    }


     /**
     * 获取组装照片
     * @param $assReptNo
     * @return array
     */
    public static function getAssmPhotoNm($where){

        $res = Db::table("TSAAssmRept10")->where($where)->select();



        return $res;
    }
     /**
     * 获取组装照片
     * @param $assReptNo
     * @return array
     */
    public static function getAssmPhoto($where){

        $res = Db::table("TSAAssmRept10")->where($where)->find();
        return $res;
    }

       /**
     * 修改安装报告照片
     * 查询 TSAAssmRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function SaveInstallPhoto($data,$where)
    {
        $res = Db::table("TSAAssmRept10")->where($where)->save($data);
        return $res;
    }
    /**
     * 安装报告-删除照片
     * 查询 TSAAssmRept10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeletePhoto($where)
    {
        $res = Db::table("TSAAssmRept10")->where($where)->delete();
        return $res;
    }

    /**
     * 查询安装报告-同行人员是否纯在
     * 查询 TSAAssmRept10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getPhotoE($where)
    {
        $res = Db::table("TSAAssmRept10")->where($where)->order('AssmReptNo','desc')->find();
        return $res;
    }
     /**
     * 安装报告-添加照片
     * 查询 TSAAssmRept10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addPhoto($data)
    {
        $res = Db::table("TSAAssmRept10")->insert($data);
        return $res;
    }

        /**
     * 安装报告信息信息
     * @param $where  查询条件

     * @return array
     */
    public static function AssmRept($where){
        $res = Db::table("TSAAssmRept00")->where($where)->find();
        return $res;
    }

    /**
     * 安装报告-发送邮件的邮件信息
     * @param $where  查询条件

     * @return array
     */
    public static function getemalis($AssmReptNo = '2022010005'){


            $sql = "SELECT A.AssmReptNo
                    ,E.EmailID
                  ,(SELECT TOP 1 E.EmailID FROM TSMSyco10 A With (Nolock)
                  Left Outer Join TMAEmpy00 As E With (Nolock) ON A.RelCd1 = E.EmpID
                  WHERE A.MajorCd='MA1004' AND A.MinorCd = D.DeptDiv2) AS DEmail
                  ,E3.EmailID AS MEmail
                  ,E4.EmailID AS CEmail
                  ,E5.EmailID AS GMEmail
              FROM TSAAssmRept00 As A With (Nolock)
              Left Outer Join TSAOrder00 As O With (Nolock) On A.ExpClss = O.ExpClss And A.OrderNo = O.OrderNo  And O.DeleteYn = 'N'
              Left Outer Join TMADept00 As D With (Nolock) On A.AssmDeptCd = D.DeptCd
              Left Outer Join TMADept00 As D1 With (Nolock) On A.TrialDeptCd = D1.DeptCd
              Left Outer Join TMADept00 As D2 With (Nolock) On D2.DeptCd = '02000'
              Left Outer Join TMAEmpy00 As E With (Nolock) On A.AssmEmpID = E.EmpID
              Left Outer Join TMAEmpy00 As E1 With (Nolock) On A.TrialEmpID = E1.EmpID
              --Left Outer Join TMAEmpy00 As E2 With (Nolock) On E2.EmpID = (SELECT RelCd1 FROM TSMSyco10 With (Nolock) WHERE MajorCd='MA1004' AND MinorCd = D.DeptDiv2)
              Left Outer Join TMAEmpy00 As E3 With (Nolock) On D.MEmpID = E3.EmpID
              Left Outer Join TMAEmpy00 As E4 With (Nolock) On D.CustPerson = E4.EmpID
              Left Outer Join TMAEmpy00 As E5 With (Nolock) On D2.MEmpID = E5.EmpID
              Left Outer Join TMACust00 As C With (Nolock) On O.CustCd = C.CustCd
              WHERE A.AssmReptNo ='".$AssmReptNo."'";
        $list = Db::query($sql);
        return $list[0];
    }


     /**
     * 安装报告-添加OA审核
     * 查询 TSAAssmRept10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addOAInterface($data)
    {
        $res = Db::table("TS_OA_Interface")->insert($data);
        return $res;
    }

      /**
     * 安装报告-删除OA审核
     * 查询 TSAAssmRept10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteOAInterface($where)
    {
        $res = Db::table("TS_OA_Interface")->where($where)->delete();
        return $res;
    }

}