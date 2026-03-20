<?php
/**
 * @Author: Yuzh
 * @Date: 2024-10-05 08:55
 */

namespace app\model\salesBusiness;

use app\model\BaseModel;
use think\facade\Db;
/**
 * Class DailyDataModel
 * @package app\model\businessInfo
 * AS接受
 */
//
class RecvHandleModel extends BaseModel
{
    /**
     * 获取As申请
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param string $data 判断sql值
     * @return bool
     */
    public static function aslist($data){
        switch ($data['auth']){
            case $data['config']['AUTH_A']:
                $authsql = '';
                break;
            case $data['config']['AUTH_E']:
                $authsql = " AND MA.EmpId = '".$data['EmpID']."'";
                break;
            case $data['config']['AUTH_J']:
                $authsql = " AND MA.JobNo = '".$data['JobNo']."'";
                break;
            case $data['config']['AUTH_D']:
                   $authsql = " AND UB.DeptCd = '".$data['DeptCd']."'";
                break;
            case $data['config']['AUTH_M']:
                $authsql = " AND UB.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','".$data['EmpID']."') )";
                break;
            default:
                $authsql = " AND UB.DeptCd = '".$data['DeptCd']."'";
                break;
        }
        if($data['count'] == 0){
            $sql = "select top 50
                    MA.ASRecvNo,
                    MA.OrderNo,
                    MA.ASType,
                    MA.ASRecvDate,
                    MA.ASDelvDate,
                    MA.Status,
                    MA.OrderCnt,
                    MA.ChargeYn,
                    CASE WHEN MC.Reqno IS NULL THEN 0
                    WHEN MC.AptYn = '0' THEN 1
                    WHEN MC.AptYn = '1' And MA.ProductYn = 'N' THEN 2
                    WHEN MA.ProductYn = 'Y' THEN 3
                    END AS ProductStatus,
                    CASE WHEN MD.ReqNo IS NULL THEN 0
                    ELSE 1
                    END AS DrawStatus,
                    UA.EmpNm,
                    UB.DeptNm,
                    MA.ProductYn,
                    MA.CfmYn,
                    MA.AptYn,
                    MA.RefNo,
                    MB.CustNm,
                    MA.CustomerCd,
                    MA.MakerCd,
                    MA.AgentCd,
                    MA.OrderSysRegYn,
                    MA.UnRegOrderNo
                    from TASRecv00 MA With(Nolock)
                    left join TMACust00 MB With(Nolock) on MA.CustCd = MB.CustCd
                    left join TPMWKReq00 MC With(Nolock) on MA.ASRecvNo = MC.SourceNo AND MC.SourceType = '2' AND MC.DeleteYn = 'N'
                    left join TDEDwReq00 MD With(Nolock) on MA.ASRecvNo = MD.SourceNo AND MD.SourceType = 'A' AND MD.DeleteYn = 'N'
                    left join TMAEmpy00 UA With(Nolock) on MA.EmpId = UA.EmpID
                    left join TMADept00 UB With(Nolock) on MA.DeptCd = UB.DeptCd
                    where 1=1 $authsql";
                if(!empty($data['orderNo'])){
                    $sql .= " and MA.OrderNo LIKE '".$data['orderNo']."%'";
                }
                if(!empty($data['asRecvNo'])){
                    $sql .= " and MA.ASRecvNo LIKE '".$data['asRecvNo']."%'";
                }

                if(empty($data['asRecvNo']) && empty($data['orderNo'])){
                    $sql .= " AND convert(char(10) ,MA.ASRecvDate, 120) between '".$data['startDate']."' and '".$data['endDate']."'";
                }
                if(!empty($data['CustNm'])){
                    $sql .= " and MB.CustNm LIKE N'%".$data['CustNm']."%'";
                }
                // if(!empty($get_userNm)){
                //     $sql .= " and UA.EmpNm LIKE N'%$get_userNm%'";
                // }
                if(!empty($data['Status'])){
                    if($data['Status']=='-1'){
                        $sql .= " and MA.Status = '0'";
                    }else{
                        $sql .= " and MA.Status = '" . $data['Status'] . "'";
                    }
                }
                if(!empty($data['CfmYn'])){
                    if($data['CfmYn']=='-1'){
                        $sql .= " and MA.CfmYn = '0'";
                    }else{
                        $sql .= " and MA.CfmYn = '" . $data['CfmYn'] . "'";
                    }
                }
                if(!empty($data['ASType'])){
                    $sql .= " and MA.ASType = '" . $data['ASType'] . "'";
                }
                $sql .= "order by MA.ASRecvNo desc";
            }else{
                $sql = "select top 50 * from
                (
                select Row_Number()over(order by MA.ASRecvNo desc)AS id,
                MA.ASRecvNo,
                MA.OrderNo,
                MA.ASType,
                MA.ASRecvDate,
                MA.ASDelvDate,
                MA.Status,
                MA.OrderCnt,
                MA.ChargeYn,
                CASE WHEN MC.Reqno Is Null THEN 0
                WHEN MC.AptYn = '0' THEN 1
                WHEN MC.AptYn = '1' And MA.ProductYn = 'N' THEN 2
                WHEN MA.ProductYn = 'Y' THEN 3
                END AS ProductStatus,

                CASE WHEN MD.ReqNo IS NULL THEN 0
                ELSE 1
                END AS DrawStatus,

                UA.EmpNm,
                UB.DeptNm,
                MA.ProductYn,
                MA.CfmYn,
                MA.AptYn,
                MB.CustNm,
                MA.RefNo,
                MA.CustomerCd,
                MA.MakerCd,
                MA.AgentCd,
                MA.OrderSysRegYn,
                MA.UnRegOrderNo
                from TASRecv00 MA With(Nolock)
                left join TMACust00 MB With(Nolock) on MA.CustCd = MB.CustCd
                left join TPMWKReq00 MC With(Nolock) On MA.ASRecvNo = MC.SourceNo And MC.SourceType = '2' AND MC.DeleteYn = 'N'
                left join TDEDwReq00 MD With(Nolock) on MA.ASRecvNo = MD.SourceNo AND MD.SourceType = 'A' AND MD.DeleteYn = 'N'
                left join TMAEmpy00 UA With(Nolock) on MA.EmpId = UA.EmpID
                left join TMADept00 UB With(Nolock) on MA.DeptCd = UB.DeptCd
                where 1=1 $authsql ";
                if(!empty($data['orderNo'])){
                    $sql .= " and MA.OrderNo LIKE '".$data['orderNo']."%'";
                }
                if(!empty($data['asRecvNo'])){
                    $sql .= " and MA.ASRecvNo LIKE '".$data['asRecvNo']."%'";
                }
                if(!empty($data['CustNm'])){
                    $sql .= " and MB.CustNm LIKE N'%".$data['CustNm']."%'";
                }
                if(empty($data['asRecvNo']) && empty($data['orderNo'])){
                    $sql .= " AND convert(char(10) ,MA.ASRecvDate, 120) between '".$data['startDate']."' and '".$data['endDate']."'";
                }
                // if(!empty($get_userNm)){
                //     $sql .= " and UA.EmpNm LIKE N'%$get_userNm%'";
                // }
                if(!empty($data['Status'])){
                    if($data['Status']=='-1'){
                        $sql .= " and MA.Status = '0'";
                    }else{
                        $sql .= " and MA.Status = '" . $data['Status'] . "'";
                    }
                }
                if(!empty($data['CfmYn'])){
                    if($data['CfmYn']=='-1'){
                        $sql .= " and MA.CfmYn = '0'";
                    }else{
                        $sql .= " and MA.CfmYn = '" . $data['CfmYn'] . "'";
                    }
                }
                if(!empty($data['ASType'])){
                    $sql .= " and MA.ASType = '" . $data['ASType'] . "'";
                }
                 $sql .= ")T where id > ".$data['count']." order by id asc";

            }
        $list = Db::query($sql);

        return $list;
    }
     /**
     * 注塑厂 和最终客户
     * 查询 TMACust00 表中是否有符合条件的记录
     * @param string $data 判断sql值
     * @return bool
     */
    public static function getCustZZList($data)
    {
        $result = DB::query("select top 50 * from (
              SELECT Row_Number()over(order by a.CustCd asc)AS id,
              a.CustNo,
              a.CustNm,
              a.CustCd,
              a.KoOrFo,
              isnull(b.TransNm,c.MinorNm) as Status,

              case IsNull(a.SaleVatRate,0) when 0 then (select TOP 1 SaleVatRate from TMATaxm00 With (Nolock) ) else a.SaleVatRate end AS SaleVatRate,
              a.Status as StatusId
              FROM TMACust00 a
              LEFT JOIN TSMDict10 b ON a.Status = b.DictCd AND b.LangCd = '".$data['langCode']."'
              LEFT JOIN TSMSyco10 c on a.Status = c.MinorCd
              where

                a.CustNo like '%".$data['CustNo']."%%'
              AND a.CustNm like N'%".$data['CustNm']."%%' )T where id > '".$data['count']."' order by id asc");



        // a.CustKind = '00000001100000000000' AND   a.CustKind = '00000001100000000000' AND
        return $result;
    }

    /**
     * 一级供应商
     * 查询 TMACust00 表中是否有符合条件的记录
     * @param string $data 判断sql值
     * @return bool
     */
    public static function getCustYJList($data)
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
              LEFT JOIN TSMDict10 b ON a.Status = b.DictCd AND b.LangCd = '".$data['langCode']."'
              LEFT JOIN TSMSyco10 c on a.Status = c.MinorCd

              where

               a.CustNo like '%".$data['CustNo']."%%'
              AND a.CustNm like N'%".$data['CustNm']."%%' )T where id > '".$data['count']."' order by id asc");
        return $result;
    }// a.Status='MA30020010' AND



    public static function getDeptsList($data){


        $where = '1=1';
        if(!empty($data['DeptCd'])){
            $where .= "AND DeptCd = '".$data['DeptCd']."'";
        }
        if(!empty($data['DeptNm'])){
            $where .= "AND DeptNm LIKE '%".$data['DeptNm']."%'";
        }
        $res = Db::query("SELECT DeptCd,DeptNm from TMADept00 where $where");

        return $res;
    }


    public static function getASMinute($ASRecvNo,$langCode)
    {
        $sql = "SELECT MA.ASRecvNo,
                MA.ASRecvDate,
                MA.ASDelvDate,
                MA.OrderGubun,
                MA.EmpId,
                MA.DeptCd,
                MA.Status,
                MA.CustPrsn,
                MA.CustTell,
                MA.CustEmail,
                MA.OrderCnt,
                UA.EmpNm,
                UGA.DeptNm,
                MA.SpecNo,
                MA.SpecType,
                MA.ProductYn,
                MA.CfmYn,
                MA.AptYn,
                MA.ChargeYn,
                MA.CustCd,
                MB.CustNm,
                MA.CustPrsn,
                MA.CustTell,
                MA.CustEmail,
                MA.OrderNo,
                MA.ExpClss,
                MA.RefNo,
                MA.GateQty,
                MA.OldDrawNo,
                MA.OldDrawAmd,
                MA.DrawNo,
                MA.DrawAmd,
                MA.ASType,        ISNULL(AStypeA.TransNm,AStypeB.MinorNm)             AS AStypeNm,

                -- SYSTEM
                MA.SupplyScope,   ISNULL(SupplyScopeA.TransNm,SupplyScopeB.MinorNm)   AS SupplyScopeNm,
                MA.HRSystem,      ISNULL(HRSystemA.TransNm,HRSystemB.MinorNm)         AS HRSystemNm,
                MA.ManifoldType,  ISNULL(ManifoldTypeA.TransNm,ManifoldTypeB.MinorNm) AS ManifoldTypeNm,
                MA.SystemSize,    ISNULL(SystemSizeA.TransNm,SystemSizeB.MinorNm)     AS SystemSizeNm,
                MA.SystemType,    ISNULL(SystemTypeA.TransNm,SystemTypeB.MinorNm)     AS SystemTypeNm,
                MA.GateType,      ISNULL(GateTypeA.TransNm,GateTypeB.MinorNm)         AS GateTypeNm,

                -- ASCLASS
                MA.GoodNm,        -- 客户产品名称
                MA.MarketCd,      ISNULL(MarketCdA.TransNm,MarketCdB.MinorNm)         AS MarketCdNm,
                MA.PProductCd,      ISNULL(PProductCdA.TransNm,PProductCdB.MinorNm)         AS PProductCdNm,
                MA.Resin,         -- 塑胶
                MA.OCCpoint,      ISNULL(OCCpointA.TransNm,OCCpointB.MinorNm)         AS OCCpointNm,
                MA.ASBadType,     ISNULL(ASBadTypeA.TransNm,ASBadTypeB.MinorNm)       AS ASBadTypeNm,
                MA.ASCauseDonor,  ISNULL(ASCauseDonorA.TransNm,ASCauseDonorB.MinorNm) AS ASCauseDonorNm,
                MA.DutyGubun,     ISNULL(DutyGubunA.TransNm,DutyGubunB.MinorNm)          AS DutyGubunNm,
                MA.ASClass1,      ISNULL(ASClass1A.TransNm,ASClass1B.MinorNm)            AS ASClass1Nm,
                MA.ASClass2,      ISNULL(ASClass2A.TransNm,ASClass2B.MinorNm)            AS ASClass2Nm,
                MA.ASAreaGubun,   ISNULL(ASAreaGubunA.TransNm,ASAreaGubunB.MinorNm)   AS ASAreaGubunNm,
                MA.ASArea,
                MA.ItemReturnYn,
                isNull(CONVERT(VARCHAR(16), MA.ProductDate, 120), '0000-00-00 00:00') as ProductDate,

                MA.ASStateRemark,
                MA.ASCauseRemark,
                MA.ASSolve,
                MA.Remark,

                MB.SalePayment as Payment,

                MA.CustomerCd,
                MA.MakerCd,
                MA.AgentCd,

                MA.TransYn,
                MA.TransDeptCd,
                UGB.DeptNm AS TransDeptNm,
                IT.ItemNm,
                MA.OrderSysRegYn,
                MA.UnRegOrderNo,
                MA.ApprUseYn,
                MA.CustSignYn,
                MA.SendEmailYn,
                MA.ResTest,
                MA.ResTestDesc,
                MA.TempRiseTest,
                MA.TempRiseTestDesc,
                MA.AccUseYn,
                MA.AccUseDesc,
                MA.ArrivalTime,
                MA.LeaveTime,
                MA.ArrivalLeaveNo,
                MA.LeaveArrivalNo,
                MA.CustSignDate,
                MA.CustGpsLat,
                MA.CustGpsLng,
                MA.CustLocationAddr,
                MA.LeaveLat,
                MA.LeaveLng,
                MA.LeaveLocationAddr,
                MA.facilityYn,
                MA.ArrivalLat,
                MA.ArrivalLng,
                MA.ArrivalLocationAddr

                from TASRecv00 MA With(Nolock)
                left join TMACust00 MB            With(Nolock) on MA.CustCd             = MB.CustCd
                left join TMAEmpy00 UA            With(Nolock) on MA.EmpId              = UA.EmpID
                left join TMADept00 UGA           With(Nolock) on MA.DeptCd             = UGA.DeptCd
                left join TMADept00 UGB           With(Nolock) on MA.TransDeptCd        = UGB.DeptCd
                -- DEFAULT
                left join TSMDict10 AStypeA       With(Nolock) on AStypeA.DictCd        = MA.AStype        and AStypeA.LangCd      = '$langCode'
                left join TSMSyco10 AStypeB       With(Nolock) on AStypeB.MinorCd       = MA.AStype

                -- SYSTEM
                left join TSMDict10 SupplyScopeA  With(Nolock) on SupplyScopeA.DictCd   = MA.SupplyScope  and SupplyScopeA.LangCd  = '$langCode'
                left join TSMSyco10 SupplyScopeB  With(Nolock) on SupplyScopeB.MinorCd  = MA.SupplyScope
                left join TSMDict10 HRSystemA     With(Nolock) on HRSystemA.DictCd      = MA.HRSystem     and HRSystemA.LangCd     = '$langCode'
                left join TSMSyco10 HRSystemB     With(Nolock) on HRSystemB.MinorCd     = MA.HRSystem
                left join TSMDict10 ManifoldTypeA With(Nolock) on ManifoldTypeA.DictCd  = MA.ManifoldType and ManifoldTypeA.LangCd = '$langCode'
                left join TSMSyco10 ManifoldTypeB With(Nolock) on ManifoldTypeB.MinorCd = MA.ManifoldType
                left join TSMDict10 SystemSizeA   With(Nolock) on SystemSizeA.DictCd    = MA.SystemSize   and SystemSizeA.LangCd   = '$langCode'
                left join TSMSyco10 SystemSizeB   With(Nolock) on SystemSizeB.MinorCd   = MA.SystemSize
                left join TSMDict10 SystemTypeA   With(Nolock) on SystemTypeA.DictCd    = MA.SystemType   and SystemTypeA.LangCd   = '$langCode'
                left join TSMSyco10 SystemTypeB   With(Nolock) on SystemTypeB.MinorCd   = MA.SystemType
                left join TSMDict10 GateTypeA     With(Nolock) on GateTypeA.DictCd      = MA.GateType     and GateTypeA.LangCd     = '$langCode'
                left join TSMSyco10 GateTypeB     With(Nolock) on GateTypeB.MinorCd     = MA.GateType


                left join TSMDict10 MarketCdA     With(Nolock) on MarketCdA.DictCd      = MA.MarketCd     and MarketCdA.LangCd  = '$langCode'
                left join TSMSyco10 MarketCdB     With(Nolock) on MarketCdB.MinorCd     = MA.MarketCd

                left join TSMDict10 PProductCdA     With(Nolock) on PProductCdA.DictCd      = MA.PProductCd     and PProductCdA.LangCd  = '$langCode'
                left join TSMSyco10 PProductCdB     With(Nolock) on PProductCdB.MinorCd     = MA.PProductCd

                left join TSMDict10 OCCpointA     With(Nolock) on OCCpointA.DictCd      = MA.OCCpoint     and OCCpointA.LangCd  = '$langCode'
                left join TSMSyco10 OCCpointB     With(Nolock) on OCCpointB.MinorCd     = MA.OCCpoint
                left join TSMDict10 ASBadTypeA    With(Nolock) on ASBadTypeA.DictCd     = MA.ASBadType    and ASBadTypeA.LangCd     = '$langCode'
                left join TSMSyco10 ASBadTypeB    With(Nolock) on ASBadTypeB.MinorCd    = MA.ASBadType
                left join TSMDict10 ASCauseDonorA With(Nolock) on ASCauseDonorA.DictCd  = MA.ASCauseDonor and ASCauseDonorA.LangCd = '$langCode'
                left join TSMSyco10 ASCauseDonorB With(Nolock) on ASCauseDonorB.MinorCd = MA.ASCauseDonor
                left join TSMDict10 DutyGubunA    With(Nolock) on DutyGubunA.DictCd     = MA.DutyGubun    and DutyGubunA.LangCd    = '$langCode'
                left join TSMSyco10 DutyGubunB    With(Nolock) on DutyGubunB.MinorCd    = MA.DutyGubun
                left join TSMDict10 ASClass1A     With(Nolock) on ASClass1A.DictCd      = MA.ASClass1     and ASClass1A.LangCd     = '$langCode'
                left join TSMSyco10 ASClass1B     With(Nolock) on ASClass1B.MinorCd     = MA.ASClass1
                left join TSMDict10 ASClass2A     With(Nolock) on ASClass2A.DictCd      = MA.ASClass2     and ASClass2A.LangCd     = '$langCode'
                left join TSMSyco10 ASClass2B     With(Nolock) on ASClass2B.MinorCd     = MA.ASClass2
                left join TSMDict10 ASAreaGubunA  With(Nolock) on ASAreaGubunA.DictCd   = MA.ASAreaGubun  and ASAreaGubunA.LangCd  = '$langCode'
                left join TSMSyco10 ASAreaGubunB  With(Nolock) on ASAreaGubunB.MinorCd  = MA.ASAreaGubun

                left join TASRecv10 RE            on RE.ASRecvNo           = MA.ASRecvNo
                left join TMAItem00 IT            on IT.ItemCd             = RE.ItemCd

                where MA.ASRecvNo='$ASRecvNo'
                ";
        $list = Db::query($sql);
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;
    }
    /**
     * 获取客户名称
     * 查询 TMACust00 表中是否有符合条件的记录
     * @param string $CustCd CustCd编号
     * @return bool
     */
    public static function getCustCd($CustCd)
    {
        $list = Db::table("TMACust00")->field("CustNm AS text,CustCd AS value ")->where('CustCd','=',$CustCd)->find();
        return  $list;
    }

    /**
     * 获取AS申请
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param string $ASRecvNo ASRecvNo编号
     * @return bool
     */
    public static function getLikeTASRecv($ASRecvNo)
    {
        $list = Db::table("TASRecv00")->where('ASRecvNo','like',$ASRecvNo."%")->order('ASRecvNo','desc')->find();
        return  $list;
    }

    /**
     * 获取AS申请
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param string $ASRecvNo ASRecvNo编号
     * @return bool
     */
    public static function getTASRecv00($where)
    {
        $list = Db::table("TASRecv00")->where($where)->find();
        return  $list;
    }

    /**
     * 获取AS申请
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param string $ASRecvNo ASRecvNo编号
     * @return bool
     */
    public static function getTASRecv00New($where)
    {
        $list = Db::table("TASRecv00")->where($where)->order('ASRecvNo','desc')->find();
        return  $list;
    }
      /**
     * 修改AS申请
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function SaveASRecv($data,$where)
    {
        $res = Db::table("TASRecv00")->where($where)->save($data);
        return $res;
    }

    /**
     * 添加AS申请
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function AddASRecv($data)
    {
        $res = Db::table("TASRecv00")->insert($data);
        return $res;
    }

    /**
     * 查询客户电话信息
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getCustTel($CustCd)
    {
        $where = array(
            'CustCd'    => $CustCd,
            'PurchaseYn'    => 'Y'
        );
        $result = Db::table("TMACust10")->where($where)->order('Seq','desc')->find();

        return $result;
    }

    /**
     * 查询订单详情
     * 查询 TSAOrder00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getOrderMinute($OrderNo,$langCode)
    {
         $sql = "select
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

                MB.CustomerCd,
                D1.CustNo As CustomerNo,
                D1.CustNm As CustomerNm,
                MB.MakerCd,
                D2.CustNo As MakerNo,
                D2.CustNm As MakerNm,
                MB.AgentCd,
                D3.CustNo As AgentNo,
                D3.CustNm As AgentNm,

                MB.OrderNo,MB.OrderDate,MB.DelvDate,MB.GateQty,MC.CustNm AS custname,isnull(FB.TransNm,FA.MinorNm) as systemtype,
                isnull(FD.TransNm,FC.MinorNm) as MarketNm,
                MB.MarketCd,
                MB.PProductCd,
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
                -- +'%'
                AS Resin

                from TSAOrder00 MB With(Nolock)
                left join TMACust00 MC With(Nolock) on MB.CustCd = MC.CustCd
                left join TSMSyco10 FA With(Nolock) on FA.MinorCd = MB.SystemType
                left join TSMDict10 FB With(Nolock) on FB.DictCd = MB.SystemType and FB.LangCd = '$langCode'
                left join TSMSyco10 FC With(Nolock) on FA.MinorCd = MB.MarketCd
                left join TSMDict10 FD With(Nolock) on FD.DictCd = MB.MarketCd and FD.LangCd = '$langCode'
                left join TMAEmpy00 LA With(Nolock) on MB.EmpId = LA.EmpID
                left join TMADept00 LB With(Nolock) on MB.DeptCd = LB.DeptCd
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

                Left Outer Join TMACust00 D1 With(Nolock) On MB.CustomerCd = D1.CustCd
                Left Outer Join TMACust00 D2 With(Nolock) On MB.MakerCd = D2.CustCd
                Left Outer Join TMACust00 D3 With(Nolock) On MB.AgentCd = D3.CustCd

                where MB.OrderNo = '$OrderNo'  AND MB.DeleteYn = 'N' AND MB.CfmYn='1'";
        $list = Db::query($sql);
        return $list[0];
    }
    /**
     * AS订单使用次数
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $OrderNo 订单ID
     * @return bool
     */

    public static function as_count($OrderNo){
        $where = array(
            'OrderNo'   => $OrderNo,
            'OrderGubun'    => '1'
        );
        $list = Db::table("TASRecv00")->where($where)->count();

        return $list;
    }
     /**
     * AS订单使用次数 OA审核查看
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $OrderNo 订单ID
     * @return bool
     */

    public static function as_countOA($OrderNo){

        $list = Db::table("TASRecv00")->whereRaw("OrderNo = '$OrderNo' AND OrderGubun = '1' AND (Status = 'A' OR CfmYn = '1')")->count();

        return $list;
    }

     /**
     * AS申请 同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $ASRecvNo AS申请ID
     * @return bool
     */
    public static function as_sales($ASRecvNo){
        $sql = "select UA.ASRecvNo,UA.SaleEmpID,UA.Seq,UB.EmpNm,UC.DeptNm from TASRecv30 UA With(Nolock)
                left join TMAEmpy00 UB With(Nolock) on UA.SaleEmpID = UB.EmpID
                left join TMADept00 UC With(Nolock) on UB.DeptCd = UC.DeptCd
                where UA.ASRecvNo = '$ASRecvNo'
                ";
        $list = Db::query($sql);
        return $list;
    }

    /**
     * 查询AS申请-同行人员是否纯在
     * 查询 TASRecv30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getSalesE($where)
    {
        $res = Db::table("TASRecv30")->where($where)->find();
        return $res;
    }
    /**
     * 添加安装报告-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addRecvSales($data)
    {
        $res = Db::table("TASRecv30")->insert($data);
        return $res;
    }
    /**
     * 删除AS申请-同行人员
     * 查询 TSAAssmRept30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteRecvSales($where)
    {
        $res = Db::table("TASRecv30")->where($where)->delete();
        return $res;
    }



     /**
     * 查询AS申请-图片查询
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getASPhoto($where)
    {
        $res = Db::table("TASRecv20")->where($where)->order('Seq','asc')->select();
        return $res;
    }

    public static function getASPhotoF($where)
    {
        $res = Db::table("TASRecv20")->where($where)->order('Seq','asc')->find();
        return $res;
    }
    /**
     *  AS申请-添加照片
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addRecvPhoto($data)
    {
        $res = Db::table("TASRecv20")->insert($data);
        return $res;
    }


       /**
     * AS申请-删除照片
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteRecvPhoto($where)
    {
        $res = Db::table("TASRecv20")->where($where)->delete();
        return $res;
    }


     /**
     * AS申请-删除照片
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function ASMinuteTable($ASRecvNo){
        $sql = "select
                a.ASRecvNo,
                a.ASRecvSerl,
                a.Sort,
                a.SpareYn,
                b.ItemCd,
                b.ItemNm,
                b.ItemNo,
                b.Spec,
                c.UnitCd,
                c.UnitNm,
                a.Qty,
                a.ChargeYn,
                d.PreStockQty,
                a.Remark,
                a.NextQty,
                a.StopQty
                from TASRecv10 a With(Nolock)
                left join TMAItem00 b With(Nolock) on b.ItemCd = a.ItemCd
                left join TMAUnit00 c With(Nolock) on a.UnitCd = c.UnitCd
                left join TMEWHItem00 d on a.ItemCd = d.ItemCd and d.WHCd = '01'
                and d.StkStatus = '0'
                where a.ASRecvNo = '$ASRecvNo'
                "; // --WHCd=01??ϲֿ? --StkStatus=0??? 9????
        $list = Db::query($sql);

        return $list;
    }

    /**
     * AS申请-品目单位
     * 查询 TMAUnit00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function ASUnit(){

        $list = Db::table('TMAUnit00')->field('UnitNm AS text,UnitCd AS value')->select();

        return $list;
    }


     /**
     * 获取品目列表
     * @param $itemNo
     * @param $itemNm
     * @param $count
     * @return array
     */
    public static function getItemList($itemNo,$itemNm,$count){
        $result = DB::query("select TOP 50 * from (
                        select
                        Row_Number()over(order by MA.ItemCd) as id,
                        MA.ItemCd,
                        MA.ItemNo,
                        MA.ItemNm,
                        MA.Spec,
                        MD.UnitNm,
                        MA.SaleUnitCd as UnitCd,
                        MA.Status,
                        MC.PreStockQty,
                        MA.VatYn
                        from TMAItem00 MA
                        left join TMAUnit00 MB on MA.StkUnitCd = MB.UnitCd
                        left join TMAUnit00 MD on MA.SaleUnitCd = MD.UnitCd
                        left join TMEWHItem00 MC on MA.ItemCd = MC.ItemCd
                        where MA.ItemNo like '$itemNo%%'
                        and MA.ItemNm like N'%%$itemNm%%'
                        ) t where id > $count order by id asc");
        return $result;
    }


     /**
     * 获取单条品目
     * @param $itemNo
     * @param $itemNm
     * @param $count
     * @return array
     */
    public static function getASItem($where)
    {


        $res = Db::table("TASRecv10")->where($where)->order('ASRecvSerl','desc')->find();
        return $res;
    }

        /**
     * 获取单条品目
     * @param $itemNo
     * @param $itemNm
     * @param $count
     * @return array
     */
    public static function getASItemS($ASRecvNo,$ASRecvSerl)
    {

        $sql = "select
                a.ASRecvNo,
                a.ASRecvSerl,
                a.Sort,
                a.SpareYn,
                b.ItemCd,
                b.ItemNm,
                b.ItemNo,
                b.Spec,
                c.UnitCd,
                c.UnitNm,
                a.Qty,
                a.ChargeYn,
                d.PreStockQty,
                a.Remark,
                a.NextQty,
                a.StopQty
                from TASRecv10 a With(Nolock)
                left join TMAItem00 b With(Nolock) on b.ItemCd = a.ItemCd
                left join TMAUnit00 c With(Nolock) on a.UnitCd = c.UnitCd
                left join TMEWHItem00 d on a.ItemCd = d.ItemCd and d.WHCd = '01'
                and d.StkStatus = '0'
                where a.ASRecvNo = '$ASRecvNo' AND a.ASRecvSerl = '$ASRecvSerl'
                ";
        $list = DB::query($sql);
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;
    }

    /**
     *  AS申请-添加品目
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addRecvItem($data)
    {
        $res = Db::table("TASRecv10")->insert($data);
        return $res;
    }

     /**
     * 修改AS申请品目
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function SaveASItem($data,$where)
    {
        $res = Db::table("TASRecv10")->where($where)->save($data);
        return $res;
    }
      /**
     * AS申请-删除品目
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteRecvItem($where)
    {
        $res = Db::table("TASRecv10")->where($where)->delete();
        return $res;
    }
     /**
     * AS申请-技术规范
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getSpecList($SpecNo,$CustNm,$count)
    {
        $where = '';
        if(!empty($SpecNo)){
            $where .= " AND MA.SpecNo like '$SpecNo%'";
        }
        if(!empty($CustNm)){
            $where .= " AND MA.CustNm like '$CustNm%'";
        }
        $sql = "select top 50 * from
                (select Row_Number()over(order by MA.SpecNo desc)AS id,MA.SpecNo,MA.SpecType,MA.SpecDate,MA.ExpClss,CA.CustNm,UA.EmpNm,UB.DeptNm from TSASpec00 MA With(Nolock)
                left join TMAEmpy00 UA With(Nolock) on MA.EmpId = UA.EmpID
                left join TMADept00 UB With(Nolock) on MA.DeptCd = UB.DeptCd
                left join TMACust00 CA With(Nolock) on MA.CustCd = CA.CustCd
                where MA.DeleteYn = 'N' $where
                ) T where id > '$count'  order by id asc
                ";
        $list = Db::query($sql);
        return $list;
    }

      /**
     * AS申请-发送邮件
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getEmalis($ASRecvNo){
        $sql = "SELECT A.ASRecvNo
                    ,E.EmailID
                  ,(SELECT TOP 1 E.EmailID FROM TSMSyco10 A With (Nolock)
                  Left Outer Join TMAEmpy00 As E With (Nolock) ON A.RelCd1 = E.EmpID
                  WHERE A.MajorCd='MA1004' AND A.MinorCd = D.DeptDiv2) AS DEmail
                  ,E3.EmailID AS MEmail
                  ,E4.EmailID AS CEmail
                  ,E5.EmailID AS GMEmail
              FROM TASRecv00 As A With (Nolock)
              -- Left Outer Join TSAOrder00 As O With (Nolock) On A.ExpClss = O.ExpClss And A.OrderNo = O.OrderNo
              Left Outer Join TMADept00 As D With (Nolock) On A.DeptCd = D.DeptCd
              -- Left Outer Join TMADept00 As D1 With (Nolock) On A.TrialDeptCd = D1.DeptCd
              Left Outer Join TMADept00 As D2 With (Nolock) On D2.DeptCd = '02000'
              Left Outer Join TMAEmpy00 As E With (Nolock) On A.EmpId = E.EmpID
              -- Left Outer Join TMAEmpy00 As E1 With (Nolock) On A.TrialEmpID = E1.EmpID
              --Left Outer Join TMAEmpy00 As E2 With (Nolock) On E2.EmpID = (SELECT RelCd1 FROM TSMSyco10 With (Nolock) WHERE MajorCd='MA1004' AND MinorCd = D.DeptDiv2)
              Left Outer Join TMAEmpy00 As E3 With (Nolock) On D.MEmpID = E3.EmpID
              Left Outer Join TMAEmpy00 As E4 With (Nolock) On D.CustPerson = E4.EmpID
              Left Outer Join TMAEmpy00 As E5 With (Nolock) On D2.MEmpID = E5.EmpID
              -- Left Outer Join TMACust00 As C With (Nolock) On O.CustCd = C.CustCd
              WHERE A.ASRecvNo ='".$ASRecvNo."'";
        $result = Db::query($sql);
        // var_dump($result);
        $list = array(
            'EmailID'   => $result[0]['EmailID'],
            'DEmail'    => $result[0]['DEmail'],
            'MEmail'    => $result[0]['MEmail'],
            'CEmail'    => $result[0]['CEmail'],
            'GMEmail'   => $result[0]['GMEmail'],
        );

        return $list;
    }

    /**
     * AS?? ͼֽ???
     * ??? TASRecv10 ???Ƿ?з???????ļ??
     * @param array $data ??????     * @return bool
     */
    public static function ASTDEDwReq($ASRecvNo){
            $sql = "Select IsNull ( A.DeptCd , '' ) ReqDeptCd,
                   IsNull ( D.DeptNm , '' ) ReqDeptNm,
                   IsNull ( A.CustCd , '' ) CustCd,
                   IsNull ( B.CustNo , '' ) CustNo,
                   IsNull ( B.CustNm , '' ) CustNm,
                   IsNull ( A.EmpID , '' ) ReqEmpID,
                   IsNull ( C.EmpNm , '' ) ReqEmpNm,
                   IsNull ( A.ExpClss , '' ) ExpClss,
                   DATEADD ( Day , 1 , A.CfmDate ) DwReqDate,
                   0 RevCnt,
                   IsNull ( ( SELECT M.MinorNm FROM TSMSyco10 M With ( Nolock ) WHERE A.HRSystem =M.MinorCd ) , '' ) + IsNull ( ( SELECT '-' + M.MinorNm FROM TSMSyco10 M With ( Nolock ) WHERE A.SystemSize =M.MinorCd ) , '' ) + IsNull ( ( SELECT '-' + M.MinorNm FROM TSMSyco10 M With ( Nolock ) WHERE A.SystemType =M.MinorCd ) , '' ) + IsNull ( ( SELECT '-' + M.MinorNm FROM TSMSyco10 M With ( Nolock ) WHERE A.GateType =M.MinorCd ) , '' )  NozzleType,
                   A.SysClass1 ,
                   IsNull ( A.SupplyScope , '' ) SupplyScope,
                   IsNull ( A.OldDrawNo , '' ) OldDrawNo,
                   A.ASRecvNo SourceNo,
                   'A' SourceType,
                   A.Status,
                   Getdate() ReqDate,
                   Getdate() RegDate
            From TASRecv00 A WITH ( NOLOCK )
            Left Outer Join TMACust00 B WITH ( NOLOCK ) On A.CustCd =B.CustCd
            Left Outer Join TMAEmpy00 C WITH ( NOLOCK ) On A.EmpId =C.EmpId
            Left Outer Join TMADept00 D WITH ( NOLOCK ) On A.DeptCd =D.DeptCd
            Where A.ASRecvNo = '$ASRecvNo' And A.CfmYn = '1'";



        $list = Db::query($sql);
        if(!empty($list[0])){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;
    }

    public static function TDEDwReg00($OldDrawNo){
        if(trim($OldDrawNo) != ''){
            $sql = "Select TOP 1 ISNULL ( A.DCCd , '' ) DCCd, ISNULL ( D.DCNm , '' ) DCNm, ISNULL ( A.DrawEmpID , '' ) DwEmpID, ISNULL ( E.EmpNm , '' ) DwEmpNm From TDEDwReg00 As A Left Outer Join TMAEmpy00 As E   With ( Nolock )   On A.DrawEmpId =E.EmpID Left Outer Join TDEDCenter00 As D   With ( Nolock )   On A.DCCd =D.DCCd Where A.DrawNo = '$OldDrawNo' Order By A.DrawAmd Desc";
            $list = Db::query($sql);
            return $list[0];
        }else{
            $list = array(
                'DCCd'  => '',
                'DCNm'  => '',
                'DwEmpID'  => '',
                'DwEmpNm'  => '',

            );
            return $list;
        }

    }

     public static function ASTDEDwReg00($ReqNo){
        $list = Db::table('TDEDwReq00')->where('ReqNo','like',"$ReqNo%")->order('ReqNo','desc')->find();
        return $list;
    }

    public static function TDEDwReq00($ASRecvNo){
        $sql = "select top 1 T.*,
                        IsNull ( D.DeptNm , '' ) ReqDeptNm,
                        IsNull ( B.CustNo , '' ) CustNo,
                        IsNull ( B.CustNm , '' ) CustNm,
                        IsNull ( C.EmpNm , '' ) ReqEmpNm
                        from TDEDwReq00 T With ( Nolock )
                        Left Outer Join TMACust00 B WITH ( NOLOCK ) On T.CustCd =B.CustCd
                        Left Outer Join TMAEmpy00 C WITH ( NOLOCK ) On T.ReqEmpID =C.EmpId
                        Left Outer Join TMADept00 D WITH ( NOLOCK ) On T.ReqDeptCd =D.DeptCd
                        where T.SourceNo = '$ASRecvNo' AND T.DeleteYn = 'N' AND T.SourceType = 'A'";

        $list = Db::query($sql);
        if(!empty($list[0])){
            $res = $list[0];
        }else{
            $res = array();
        }

        return $res;
    }


    public static function ASTDEDwReg($ReqNo){
        $list = Db::table('TDEDwReq00')->where('ReqNo','=',$ReqNo)->find();
        return $list;
    }
    /**
     *  AS申请-添加图纸依赖
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addRecvDwReq($data)
    {
        $res = Db::table("TDEDwReq00")->insert($data);
        return $res;
    }

    /**
     * 修改AS申请图纸依赖
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function SaveASDwReq($data,$where)
    {
        $res = Db::table("TDEDwReq00")->where($where)->save($data);
        return $res;
    }





    //AS????
       /**
     * AS处理-查询
     * 查询 TASRecv10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getAsHandle($data){
        $langCode = $data['langCode'];
        $empId = $data['users']['empId'];
        $deptCd = $data['users']['DeptCd'];
        $jobNo = $data['users']['JobNo'];
        $count = $data['count'];
         switch ($data['auth']){
            case 'SM00040001':   //全部
                $authwhere = '';
                break;
            case 'SM00040003':   //个人加部门
                $authwhere = " AND UA.EmpId = ''$empId''";
                break;
            case 'SM00040004'://职位
                $authwhere = " AND OA.JobNo = '$jobNo'";
                break;
            case 'SM00040002'://部门
                $authwhere = " AND UB.DeptCd = '$deptCd'";
                break;
            case 'SM00040005':   //管理
                $authwhere = "AND UB.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','$empId') )";
//                $authwhere = " AND UB.MDeptCd = '$MDeptCd'";
                break;
            default:  //默认为个人
                $authwhere = " AND EMPY.EmpID = '$empId'";
                break;
        }
        $where = '';
            $where .= empty($data['ASNo']) ? '' : " AND A.ASNo like '%".$data['ASNo']."%'";
            $where .= empty($data['CustNm']) ? '' :" AND B.CustNm like N'%".$data['CustNm']."%'";
            $where .= empty($data['startDate']) ? '' : " AND A.ASDate Between '".$data['startDate']."' AND '".$data['endDate']."'";


            $sql = "select top 50 * from
                                (select Row_Number()over(order by A.ASNo desc)AS id,
                                A.ASNo,
                                A.ASDate,
                                A.ASRecvNo,
                                A.CustCd,
                                A.CfmYn,
                                B.CustNm,
                                EMPY.EmpId,
                                EMPY.EmpNm,
                                DEPT.DeptCd,
                                DEPT.DeptNm,
                                C.OrderNo,
                                C.OrderSysRegYn,
                                C.UnRegOrderNo,
                                C.RefNo,
                                C.ChargeYn,
                                A.ProcResult,
                                LA.TransNm as ProcResultNm
                                from TASProc00 A WITH(NOLOCK)
                                left join TMACust00 B WITH(NOLOCK) on A.CustCd = B.CustCd
                                left join TASRecv00 C WITH(NOLOCK) on A.ASRecvNo = C.ASRecvNo
                                left join TMAEmpy00 EMPY WITH(NOLOCK) on A.EmpId = EMPY.EmpID
                                left join TMADept00 DEPT WITH(NOLOCK) on A.DeptCd = DEPT.DeptCd
                                left join TSMDict10 LA WITH(NOLOCK) on A.ProcResult = LA.DictCd And LangCd = '$langCode'

                                where 1=1 $where $authwhere
                                )T where id > $count order by id asc
                                ";

        $result = DB::query($sql);
        return $result;
    }
    /**
     * AS处理查询 AS申请
     * ??? TASRecv00 ???Ƿ?з???????ļ??
     * @param array $data ??????     * @return bool
     */
    public static function ASHandlePrc($data){

            $where = '';
            $where .= empty($data['OrderNo']) ? '' : " AND MA.OrderNo like '%".$data['OrderNo']."%'";
            $where .= empty($data['CustNm']) ? '' :" AND MA.CustNm like N'%".$data['CustNm']."%'";
            $where .= empty($data['ASRecvNo']) ? '' : " AND MA.ASRecvNo like '%".$data['ASRecvNo']."%'";
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];
            $count = $data['count'];
            $sql = "select top 50 * from
                (
                select Row_Number()over(order by MA.ASRecvNo desc)AS id,
                MA.ASRecvNo,
                MA.OrderNo,
                MA.ASRecvDate,
                MA.ASDelvDate,
                MA.Status,
                MA.OrderCnt,
                MA.ChargeYn,
                CASE WHEN MC.Reqno Is Null THEN 0
                WHEN MC.AptYn = '0' THEN 1
                WHEN MC.AptYn = '1' And MA.ProductYn = 'N' THEN 2
                WHEN MA.ProductYn = 'Y' THEN 3
                END AS ProductStatus,

                CASE WHEN MD.ReqNo IS NULL THEN 0
                ELSE 1
                END AS DrawStatus,
                MA.ASType,
                UA.EmpNm,
                UB.DeptNm,
                MA.ProductYn,
                MA.CfmYn,
                MA.AptYn,
                MB.CustNm,
                MA.RefNo,
                MA.CustomerCd,
                MA.MakerCd,
                MA.AgentCd,
                MA.OrderSysRegYn,
                MA.UnRegOrderNo
                from TASRecv00 MA With(Nolock)
                left join TMACust00 MB With(Nolock) on MA.CustCd = MB.CustCd
                left join TPMWKReq00 MC With(Nolock) On MA.ASRecvNo = MC.SourceNo And MC.SourceType = '2'  AND MC.DeleteYn = 'N'
                left join TDEDwReq00 MD With(Nolock) on MA.ASRecvNo = MD.SourceNo AND MD.SourceType = 'A'  AND MD.DeleteYn = 'N'
                left join TMAEmpy00 UA With(Nolock) on MA.EmpId = UA.EmpID
                left join TMADept00 UB With(Nolock) on MA.DeptCd = UB.DeptCd
                where MA.Status NOT IN ('F','1') AND MA.CfmYn = '1' AND MA.AptYn = '1' AND MA.ProductYn = 'Y' AND MA.ASType != 'AS10020030' AND convert(char(10) ,MA.ASRecvDate, 120) between '$startDate' and '$endDate'
                $where
                )T where id > $count order by id asc
                ";

        $result = DB::query($sql);
        return $result;
    }



    public static function getAsHandleByAsRecvNo($where){

        $result = Db::table('TASProc00')->where($where)->find();
        return $result;
    }
    public static function getASKindList($langCode){

        $result = Db::table('TSMDict10')->where('DictCd','like','AS2001%%')->where('LangCd','=',$langCode)->select();
        return $result;
    }

    public static function getASProcKindList($langCode){

        $result = Db::table('TSMDict10')->where('DictCd','like','AS2002%%')->where('LangCd','=',$langCode)->select();
        return $result;
    }
    public static function getASProcResultList($langCode){

        $result = Db::table('TSMDict10')->where('DictCd','like','AS2003%%')->where('LangCd','=',$langCode)->select();
        return $result;
    }

    public static function getASKindsLists($where){

        $result = Db::table('TSMDict10')->field('DictCd as value,TransNm as text')->where($where)->find();
        return $result;
    }

    public static function getItemReturnList($langCode){
        $sql = "select A.RelCd1 as value,B.TransNm as text from TSMSyco10 A WITH(NOLOCK)
                    left join TSMDict10 B WITH(NOLOCK) on A.MinorCd = B.DictCd and B.LangCd = '$langCode'
                    where A.MajorCd like 'AS1014%%'";
        $list = Db::query($sql);
        return $list;
    }


    public static function getLastAsHandle(){
        $date = date('Ym',time());

        $info = Db::table('TASProc00')->where('ASNo','like',$date.'%%')->order('ASNo','desc')->find();
        return $info;
    }


    /**
     *  AS处理 添加
     * @param array $data ??????     * @return bool
     */
    public static function addProc($data)
    {
        $res = Db::table("TASProc00")->insert($data);
        return $res;
    }

     /**
     * AS处理 修改
     * @param array $data ??????     * @return bool
     */
    public static function SaveProc($data,$where)
    {
        $res = Db::table("TASProc00")->where($where)->save($data);
        return $res;
    }

    /**
     * AS处理 详情
     * @param array $data ??????     * @return bool
     */
    public static function getAsHandleInfo($ASNo,$langCode){
        $result = DB::query("SELECT top 1
                                A.ASNo,
                                A.ASDate,
                                A.ASRecvNo,
                                A.CustCd,
                                A.CfmYn,
                                A.ASKind,
                                A.ASProcKind,
                                A.ASNote,
                                A.ProcResult,
                                A.ProcResultReason,
                                A.ASAmt,
                                A.ASRepairAmt,
                                A.ASArea,
                                A.ASAreaGubun,
                                A.ChargeYn,
                                A.CfmYn,
                                A.ItemReturnYn,
                                A.ItemReturnGubun,
                                B.CustNm,
                                A.CustCd,
                                A.JobNo,
                                A.Remark,
                                A.ASNote,
                                A.CustOpinion,
                                A.TransLine,
                                A.ProcPerson,
                                A.ArrivalTime,
                                A.StartTime,
                                A.ApprUseYn,

                                A.ResTest,
                                A.ResTestDesc,
                                A.TempRiseTest,
                                A.TempRiseTestDesc,
                                A.CustPrsn,
                                A.CustTell,
                                A.CustEmail,
                                A.CustSignYn,
                                A.SendEmailYn,
                                A.facilityYn,




                                EMPY.EmpId,
                                EMPY.EmpNm,
                                DEPT.DeptCd,
                                DEPT.DeptNm,
                                C.OrderNo,
                                C.RefNo,
                                C.AStype,
                                C.ExpClss,
                                C.SpecNo,
                                C.DrawNo,
                                C.DrawAmd,
                                C.Resin,
                                C.GoodNm,
                                -- SYSTEM
                                C.SupplyScope,   ISNULL(SupplyScopeA.TransNm,SupplyScopeB.MinorNm)   AS SupplyScopeNm,
                                C.HRSystem,      ISNULL(HRSystemA.TransNm,HRSystemB.MinorNm)         AS HRSystemNm,
                                C.ManifoldType,  ISNULL(ManifoldTypeA.TransNm,ManifoldTypeB.MinorNm) AS ManifoldTypeNm,
                                C.SystemSize,    ISNULL(SystemSizeA.TransNm,SystemSizeB.MinorNm)     AS SystemSizeNm,
                                C.SystemType,    ISNULL(SystemTypeA.TransNm,SystemTypeB.MinorNm)     AS SystemTypeNm,
                                C.GateType,      ISNULL(GateTypeA.TransNm,GateTypeB.MinorNm)         AS GateTypeNm,
                                C.GateQty,
                                A.ArrivalLocationAddr,
                                A.ArrivalLat,
                                A.ArrivalLng,
                                A.ArrivalLeaveNo,
                                A.CustSignDate,
                                A.CustGpsLat,
                                A.CustGpsLng,
                                A.CustLocationAddr,
                                A.LeaveLat,
                                A.LeaveLng,
                                A.LeaveLocationAddr,

                                LA.TransNm as ASTypeNm
                                from TASProc00 A WITH(NOLOCK)
                                left join TASRecv00 C WITH(NOLOCK)  on A.ASRecvNo = C.ASRecvNo
                                left join TSMDict10 SupplyScopeA WITH(NOLOCK)  on SupplyScopeA.DictCd   = C.SupplyScope  and SupplyScopeA.LangCd  = '$langCode'
                                left join TSMSyco10 SupplyScopeB WITH(NOLOCK)  on SupplyScopeB.MinorCd  = C.SupplyScope
                                left join TSMDict10 HRSystemA    WITH(NOLOCK)  on HRSystemA.DictCd      = C.HRSystem     and HRSystemA.LangCd     = '$langCode'
                                left join TSMSyco10 HRSystemB    WITH(NOLOCK)  on HRSystemB.MinorCd     = C.HRSystem
                                left join TSMDict10 ManifoldTypeA WITH(NOLOCK)  on ManifoldTypeA.DictCd  = C.ManifoldType and ManifoldTypeA.LangCd = '$langCode'
                                left join TSMSyco10 ManifoldTypeB WITH(NOLOCK) on ManifoldTypeB.MinorCd = C.ManifoldType
                                left join TSMDict10 SystemSizeA   WITH(NOLOCK) on SystemSizeA.DictCd    = C.SystemSize   and SystemSizeA.LangCd   = '$langCode'
                                left join TSMSyco10 SystemSizeB   WITH(NOLOCK) on SystemSizeB.MinorCd   = C.SystemSize
                                left join TSMDict10 SystemTypeA   WITH(NOLOCK) on SystemTypeA.DictCd    = C.SystemType   and SystemTypeA.LangCd   = '$langCode'
                                left join TSMSyco10 SystemTypeB   WITH(NOLOCK) on SystemTypeB.MinorCd   = C.SystemType
                                left join TSMDict10 GateTypeA     WITH(NOLOCK) on GateTypeA.DictCd      = C.GateType     and GateTypeA.LangCd     = '$langCode'
                                left join TSMSyco10 GateTypeB     WITH(NOLOCK) on GateTypeB.MinorCd     = C.GateType


                                left join TMACust00 B WITH(NOLOCK) on A.CustCd = B.CustCd

                                left join TMAEmpy00 EMPY WITH(NOLOCK) on A.EmpId = EMPY.EmpID
                                left join TMADept00 DEPT WITH(NOLOCK) on A.DeptCd = DEPT.DeptCd
                                left join TSMDict10 LA WITH(NOLOCK) on C.ASType = LA.DictCd And LA.LangCd = '$langCode'
                                WHERE ASNo = '$ASNo' order by ASNo desc");

        return $result[0];
    }

    /**
     * AS处理 条件判断
     * @param array $data ??????     * @return bool
     */
    public static function getAsHandleProc($where){

        $info = Db::table('TASProc00')->where($where)->find();
        return $info;
    }




    /**
     * AS申请 同行人员
     * 查询 TASProc30 表中是否有符合条件的记录
     * @param array $ASNo AS处理ID
     * @return bool
     */
    public static function ASHandleSales($ASNo){
        $sql = "SELECT UA.ASNo,UA.SaleEmpID,UA.Seq,UB.EmpNm,UC.DeptNm,UA.RegDate,UA.UptDate from TASProc30 UA With(Nolock)
                left join TMAEmpy00 UB With(Nolock) on UA.SaleEmpID = UB.EmpID
                left join TMADept00 UC With(Nolock) on UB.DeptCd = UC.DeptCd
                where UA.ASNo = '$ASNo'
                ";
        $list = Db::query($sql);
        return $list;
    }

    /**
     * 查询AS处理-同行人员是否纯在
     * 查询 TASProc30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getHandleSalesE($where)
    {
        $res = Db::table("TASProc30")->where($where)->find();
        return $res;
    }
    /**
     * 添加处理-同行人员
     * 查询 TASProc30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addHandleSales($data)
    {
        $res = Db::table("TASProc30")->insert($data);
        return $res;
    }
    /**
     * 删除AS处理-同行人员
     * 查询 TASProc30 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteHandleSales($where)
    {
        $res = Db::table("TASProc30")->where($where)->delete();
        return $res;
    }


     /**
     * AS申请 照片列表
     * 查询 TASProc20 表中是否有符合条件的记录
     * @param array $where 条件判断
     * @return bool
     */
    public static function ASHandlephoto($where){

        $list = Db::table('TASProc20')->where($where)->order('Seq','asc')->select();
        return $list;
    }
    /**
     *  AS处理-单条照片
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
     public static function getASHandlePhotoF($where)
    {
        $res = Db::table("TASProc20")->where($where)->order('Seq','asc')->find();
        return $res;
    }
    /**
     *  AS处理-添加照片
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addHandlePhoto($data)
    {
        $res = Db::table("TASProc20")->insert($data);
        return $res;
    }


       /**
     * AS处理-删除照片
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function DeleteHandlePhoto($where)
    {
        $res = Db::table("TASProc20")->where($where)->delete();
        return $res;
    }


    /**
     * AS处理-品目
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getAsHandleItem($asHandleNo,$serl=''){
        $result = DB::query("SELECT
                                A.ASNo,
                                A.ASSerl,
                                A.SpareYn,
                                A.StopYn,
                                B.Spec,
                                A.UnitCd,
                                C.UnitNm,
                                A.Remark,
                                A.Amt,
                                A.Qty,
                                A.Sort,
                                A.ASRepairAmt,
                                A.ReUseYn,
                                A.ChargeYn,
                                A.ASRecvSerl,
                                A.ItemCd,
                                B.ItemNo,
                                B.ItemNm from TASProc10 as A WITH(NOLOCK)
                                left join TMAItem00 as B WITH(NOLOCK) on A.ItemCd = B.ItemCd
                                left join TMAUnit00 as C WITH(NOLOCK) on A.UnitCd = C.UnitCd
                                where A.ASNo = '$asHandleNo' and A.ASSerl LIKE '$serl%%'");
        return $result;
    }
    /**
     * AS处理-品目
     * 查询 TASRecv20 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getAsHItem($where){
        $result = Db::table('TASProc10')->alias('A')->field('A.*,B.ItemNo,B.ItemNm,B.Spec')
        ->leftJoin('TMAItem00 B','A.ItemCd = B.ItemCd')
        ->leftJoin('TMAUnit00 C','A.UnitCd = C.UnitCd')
        ->where($where)->find();
        return $result;
    }

    public static function addAsHandleItem($itemInfo)
    {

        if(isset($itemInfo['ASRecvSerl'])) $itemInfo['ASRecvSerl'] = $itemInfo['ASSerl'];
        $res = Db::table("TASProc10")->insert($itemInfo);

        return $res;
    }

    public static function setAsHandleItem($where,$data){
        $res = Db::table("TASProc10")->where($where)->save($data);
        return $res;
    }


    public static function getASEmalis($ASNo){
            $sql = "SELECT A.ASNo
                    ,E.EmailID
                    ,(SELECT TOP 1 E.EmailID FROM TSMSyco10 A With (Nolock)
                    Left Outer Join TMAEmpy00 As E With (Nolock) ON A.RelCd1 = E.EmpID
                    WHERE A.MajorCd='MA1004' AND A.MinorCd = D.DeptDiv2) AS DEmail
                    ,E3.EmailID AS MEmail
                    ,E4.EmailID AS CEmail
                    ,E5.EmailID AS GMEmail
              FROM TASProc00 As A With (Nolock)
              Left Outer Join TMADept00 As D With (Nolock) On A.DeptCd = D.DeptCd
              Left Outer Join TMADept00 As D2 With (Nolock) On D2.DeptCd = '02000'
              Left Outer Join TMAEmpy00 As E With (Nolock) On A.EmpId = E.EmpID
              Left Outer Join TMAEmpy00 As E3 With (Nolock) On D.MEmpID = E3.EmpID
              Left Outer Join TMAEmpy00 As E4 With (Nolock) On D.CustPerson = E4.EmpID
              Left Outer Join TMAEmpy00 As E5 With (Nolock) On D2.MEmpID = E5.EmpID
              -- Left Outer Join TMACust00 As C With (Nolock) On O.CustCd = C.CustCd
              WHERE A.ASNo ='$ASNo'";
        $result = Db::query($sql);

        $list = array(
            'EmailID'   => $result[0]['EmailID'],
            'DEmail'    => $result[0]['DEmail'],
            'MEmail'    => $result[0]['MEmail'],
            'CEmail'    => $result[0]['CEmail'],
            'GMEmail'   => $result[0]['GMEmail'],
        );
        return $list;
    }



    public static function getTSMSyco10($MajorCd,$MinorCd,$langCode){
        $sql = "select
                isnull(MULTIB.TransNm,MULTIA.MinorNm) AS text,
                isnull(MULTIB.DictCd,MULTIA.MinorCd) AS value,
                MULTIA.DeleteYn AS status
                from TSMSyco10 MULTIA With(Nolock)
                full join  TSMDict10 MULTIB With(Nolock) on MULTIA.MinorCd = MULTIB.DictCd and MULTIB.LangCd = '$langCode'
                where MULTIA.MajorCd = '".$MajorCd."' AND MULTIA.MinorCd = '" . $MinorCd ."'
                ";
        $list = Db::query($sql);
        return $list;
    }

    public static function updataASREcv(){
        // $where = array();
        $list = Db::table('TASRecv00')->where('ASRecvNo','>=','202412090001')->where('facilityYn','=','1')->select();
        return $list;
    }
    public static function updataASOrder($OrderNo){
        // $where = array();
        $list = Db::table('TSAOrder00')->where('OrderNo','=',$OrderNo)->find();
        return $list;
    }

    public static function updataANzhucv(){
        // $where = array();
        $list = Db::table('TSAAssmRept00')->where('AssmReptNo','>=','2024120273')->where('facilityYn','=','1')->select();
        return $list;
    }
    public static function updataTestucv(){
        // $where = array();
        $list = Db::table('TSATstInjRept00')->where('TstInjReptNo','>=','2024120024')->where('facilityYn','=','1')->select();
        return $list;
    }

}