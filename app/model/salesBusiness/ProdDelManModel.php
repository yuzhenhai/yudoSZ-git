<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-12-04 13:22
 */

namespace app\model\salesBusiness;

use think\facade\Db;

class ProdDelManModel extends \app\model\BaseModel
{
    public static function preLists($result)
    {
        // 1111
        foreach($result as &$item){
            $item['WDelvDate'] = date('Y-m-d',strtotime($item['WDelvDate']));
            $item['OutDate'] = date('Y-m-d',strtotime($item['OutDate']));
            $item['DelvDate'] = date('Y-m-d',strtotime($item['DelvDate']));
        }
        return $result;
    }

    public static function getLists($auth,$date,$accordingClass,$accordingNo,$custNm,$RefNo)
    {
        $empId = $auth['empId'];
        $deptCd = $auth['deptCd'];
        $jobNo = $auth['jobNo'];
        switch ($auth['auth']) {
            case 'SM00040001':
                $_auth = '';
                break;
            case 'SM00040003':   //个人
                $_auth = " AND Empy.EmpId = '$empId'";
                break;
            case 'SM00040004':   //职位
                $_auth = " AND B.JobNo = '$jobNo'";
                break;
            case 'SM00040002':   //部门
                $_auth = " AND Dept.DeptCd = '$deptCd'";
                break;
            case 'SM00040005':   //管理
                $_auth = " AND Dept.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','$empId') )";
                break;
            default:       //默认为部门
                $_auth = " AND Dept.DeptCd = '$deptCd'";
                break;
        }
//        $_auth = " AND Dept.DeptCd = '$deptCd'";
        $sql = "SELECT '0' As U_Select
                ,A.OrderDiv
                ,A.WPlanNo
                ,A.WPlanDate
                ,A.WDelvDate
                ,Empy.EmpID
                ,Empy.EmpNm
                ,Dept.DeptCd
                ,Dept.DeptNm
                ,A.Status
                ,A.SourceType
                ,A.SourceNo
                ,B.ExpClss
                ,B.OrderNo
                ,B.OrderType
                ,B.OrderDate
                ,B.DelvDate
                ,B.CustCd
                ,C.CustNo
                ,C.CustNm ,C1.CustNo as MakerNo
                ,C1.CustNm as MakerNm
                ,ISNULL(B.DrawNo,'') AS DrawNo
                ,ISNULL(B.DrawAmd,'') AS DrawAmd
                ,D1.AptDate
                ,D.OutDate 
                ,B.SpecNo
                ,B.SpecType
                ,B.CustPONo
                ,B.RefNo
                ,B1.GateQty
                ,A.ProductMUptEmpID
                ,A.ProductMUptDate
                ,A.ProductUptEmpID
                ,A.ProductUptDate
                ,GetDate() As ToDay
                ,A.Sort
                ,CASE WHEN A.Sort Is Null Or LTrim(RTrim(A.Sort)) = '' THEN 'ZZZ' ELSE LTrim(RTrim(A.Sort)) END As U_Sort
                ,IsNull(B.CustPONo,'') As CustPONo
                ,IsNull(A.WDelvChRemark, '') As WDelvChRemark
                ,IsNull(C.Custtype2,'') As CustType2
                ,A.ProductClass
                ,B.Status As OrderStatus
                ,A.SDelvChDate As SDelvChDate
                ,A.SDelvChRemark As SDelvChRemark
                ,A.WDelvChUptDate As WDelvChUptDate
                ,A.SDelvChUptDate As SDelvChUptDate
                ,IsNull(H.ModifyCnt,0) As ModifyCnt
                ,IsNull(B.SupplyScope,'') As SupplyScope
                ,A.CfmDate As WPlanCfmDate
                ,B.CfmDate As OrderCfmDate
                ,R.AptDate As WkAptDate
                ,IsNull(B.ShortDelvYn,'N') As ShortDelvYn
                ,IsNull(S1.ProductDay,0) As ProductDay
                ,CASE WHEN S1.ProductDay is Not Null THEN DATEADD(Day, S1.ProductDay, B.OrderDate) ELSE Null END As STProductDate
                ,B.OrderForAmt
                FROM TPMWkPlan00 As A With (Nolock) Left Outer Join TSAOrder00 As B With (Nolock)
                On A.OrderNo = B.OrderNo And A.SourceType = '1' And B.DeleteYn = 'N' Left Outer Join TSASpec30 As B1 With (Nolock)
                On B.SpecNo = B1.SpecNo And B.SpecType = B1.SpecType	And B1.MainSysYn = 'Y'
                Left Join TMAEmpy00 Empy on B.EmpID = Empy.EmpID
                Left Join TMADept00 Dept on Empy.DeptCd = Dept.DeptCd
                Left Outer Join TMACust00 As C With (Nolock)
                On B.CustCd = C.CustCd
                Left Outer Join TMACust00 As C1 With (Nolock)
                On B.MakerCd = C1.CustCd
                Left Outer Join TDEDwReg00 As D With (Nolock)
                On B.DrawNo = D.DrawNo And B.DrawAmd = D.DrawAmd Left Outer Join TDEDwReq00 As D1 With (Nolock)
                On D.ReqNo = D1.ReqNo AND D1.DeleteYn = 'N'
                Left Outer Join TPMWKReq00 As R With (Nolock)
                On A.ReqNo = R.ReqNo AND R.DeleteYn = 'N'
                LEFT OUTER JOIN (select WPlanNo, COUNT(*) As ModifyCnt from TPMWKDelv_His GROUP BY WPlanNo
                ) As H ON A.WPlanNo = H.WPlanNo
                Left Outer Join TSMSyco10 S With(Nolock)
                On B.SystemType = S.MinorCd
                Left Outer Join TSADelv00_SZ S1 With(Nolock)
                On B.ExpClss = '1' And B.OrderType = S1.OrderType And B.SupplyScope = S1.SupplyScope And B.HRSystem = S1.HRSystem
                And LTRIM(RTRIM(S.RelCd10)) = S1.SystemType
                And (B.GateQty Between S1.GateQty_Min And S1.GateQty_Max)	WHERE A.Status In ('0','1','2')
                AND A.WDelvDate <= '{$date}'
                AND A.CfmYn = '1' AND (A.SourceType = '1' 
                And A.SourceType Like '%{$accordingClass}%'
                )	AND A.StopYn = 'N'
                AND IsNull(A.SourceNo,'') Like '%{$accordingNo}%'
                AND IsNull(C.CustNm,'') Like '%{$custNm}%'
                AND B.RefNo LIKE '%{$RefNo}%'
                $_auth
                UNION
                SELECT '0' As U_Select
                ,A.OrderDiv
                ,A.WPlanNo
                ,A.WPlanDate
                ,A.WDelvDate
                ,Empy.EmpID
                ,Empy.EmpNm
                ,Dept.DeptCd
                ,Dept.DeptNm
                ,A.Status
                ,A.SourceType
                ,A.SourceNo
                ,B.ExpClss
                ,IsNull(B.OrderNo,'')
                ,''
                ,B.ASRecvDate
                ,B.ASDelvDate
                ,B.CustCd
                ,C.CustNo
                ,C.CustNm ,'' as MakerNo
                ,'' as MakerNm
                ,ISNULL(B.DrawNo,'') AS DrawNo
                ,ISNULL(B.DrawAmd,'') AS DrawAmd
                ,D1.AptDate
                ,D.OutDate
                ,B.SpecNo
                ,B.SpecType
                ,'' As CustPONo
                ,B.RefNo
                ,B1.GateQty
                ,A.ProductMUptEmpID
                ,A.ProductMUptDate
                ,A.ProductUptEmpID
                ,A.ProductUptDate
                ,GetDate() As ToDay
                ,A.Sort
                ,CASE WHEN A.Sort Is Null Or LTrim(RTrim(A.Sort)) = '' THEN 'ZZZ' ELSE LTrim(RTrim(A.Sort)) END As U_Sort
                ,'' As CustPONo
                ,IsNull(A.WDelvChRemark, '') As WDelvChRemark
                ,IsNull(C.Custtype2,'') As CustType2
                ,A.ProductClass
                ,'' As OrderStatus
                ,A.SDelvChDate As SDelvChDate
                ,A.SDelvChRemark As SDelvChRemark
                ,A.WDelvChUptDate As WDelvChUptDate
                ,A.SDelvChUptDate As SDelvChUptDate
                ,IsNull(H.ModifyCnt,0) As ModifyCnt
                ,IsNull(B.SupplyScope,'') As SupplyScope
                ,A.CfmDate As WPlanCfmDate
                ,B.CfmDate As OrderCfmDate
                ,R.AptDate As WkAptDate
                ,'N' As ShortDelvYn
                , 0 ,Null
                ,0
                FROM TPMWkPlan00 As A With (Nolock) Left Outer Join TASRecv00 As B With (Nolock)
                On A.SourceNo = B.ASRecvNo And A.SourceType = '2' Left Outer Join TSASpec30 As B1 With (Nolock)
                On B.SpecNo = B1.SpecNo And B.SpecType = B1.SpecType	And B1.MainSysYn = 'Y'
                Left Join TMAEmpy00 Empy on B.EmpID = Empy.EmpID
                Left Join TMADept00 Dept on Empy.DeptCd = Dept.DeptCd
                Left Outer Join TMACust00 As C With (Nolock)
                On B.CustCd = C.CustCd
                Left Outer Join TDEDwReg00 As D With (Nolock)
                On B.DrawNo = D.DrawNo And B.DrawAmd = D.DrawAmd Left Outer Join TDEDwReq00 As D1 With (Nolock)
                On D.ReqNo = D1.ReqNo AND D1.DeleteYn = 'N'
                Left Outer Join TPMWKReq00 As R With (Nolock)
                On A.ReqNo = R.ReqNo AND R.DeleteYn = 'N'
                LEFT OUTER JOIN (select WPlanNo, COUNT(*) As ModifyCnt from TPMWKDelv_His GROUP BY WPlanNo
                ) As H ON A.WPlanNo = H.WPlanNo
                WHERE A.Status In ('0','1','2')
                AND A.WDelvDate <= '{$date}'
                AND A.CfmYn = '1' AND (A.SourceType = '2' 
                And A.SourceType Like '%{$accordingClass}%'
                )	AND A.StopYn = 'N'
                AND IsNull(A.SourceNo,'') LIKE '%{$accordingNo}%'
                AND IsNull(C.CustNm,'') LIKE '%{$custNm}%'
                AND B.RefNo LIKE '%{$RefNo}%'
                $_auth
                ORDER BY A.WDelvDate DESC";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getAuthData($auth, $userId)
    {
        // 使用参数化查询，防止SQL注入
        $sql = "SELECT UB.DeptCd, UB.MDeptCd, UC.emp_code, UD.JobNo 
            FROM TMAEmpy00 UA 
            LEFT JOIN TMADept00 UB ON UB.DeptCd = UA.DeptCd 
            LEFT JOIN sysUserMaster UC ON UA.EmpId = UC.emp_code AND ISNULL(UC.emp_code, '') != ''
            LEFT JOIN TMAJobc10 UD ON UD.EmpId = UC.emp_code 
            INNER JOIN TMAJobc00 UE ON UE.JobNo = UD.JobNo AND UE.SaleYn = 'Y' 
            WHERE UC.user_id = :userId 
            AND UD.LastYN = 'Y' 
            AND GETDATE() BETWEEN UD.STDate AND UD.EDDate";
        $userInfo = Db::connect(self::$Db)->query($sql, ['userId' => $userId]);
        if (empty($userInfo)) {
            return [
                'auth' => $auth,
                'deptCd' => '',
                'empId' => '',
                'jobNo' => ''
            ];
        }
        $userInfo = $userInfo[0];

        return [
            'auth' => $auth,
            'deptCd' => $userInfo['DeptCd'] ?? '',
            'empId' => $userInfo['emp_code'] ?? '',
            'jobNo' => $userInfo['JobNo'] ?? '',
        ];
    }

    public static function getDetail($planNo)
    {
        $sql = "SELECT A.WPlanNo, A.DeptCd, C.DeptNm As DeptNm,
                A.WCCd,
                B.WCNm As WCNm,
                A.Sort,
                A.StartYn,
                A.EndYn,
                A.QCYn,
                A.WCDelvDate,
                A.WCStartDate,
                A.WCEndDate,
                A.QCDate,
                A.LastYn,
                A.StopYn,
                A.StopDate,
                AA.CfmYn As CfmYn
                FROM TPMWKPlan20 As A With (Nolock) Inner Join TPMWKPlan00 As AA With (Nolock) On A.WPlanNo = AA.WPlanNo
                Inner Join TPMWC00 As B With (Nolock) On A.DeptCd = B.DeptCd And A.WCCd = B.WCCd And B.PlanYn = 'N' And B.Status = '0'
                Inner Join TMADept00 As C With (Nolock)
                On A.DeptCd = C.DeptCd
                WHERE
                (
                    A.WCDelvDate != ''
                    OR A.WCStartDate != ''
                    OR A.WCEndDate != ''
                    OR A.QCDate != '' )
                AND
                A.WPlanNo = '{$planNo}' ORDER BY A.Sort ASC";
        return Db::connect(self::$Db)->query($sql);

    }

    public static function getInfo($planNo,$wccd)
    {
        return Db::connect(self::$Db)
            ->query("EXEC dbo.P_PM_400_1000_WORKPROCQRY_M ?, ?, ?, ?, ?", ['Q', $planNo, $wccd, '', ''])[0];
    }

    public static function preInfo($data,$langCode)
    {
        foreach($data as &$item){
            if(!empty($item['EndDate'])) {
                $item['EndDate'] = date('Y-m-d H:i:s', strtotime($item['EndDate']));
            }
            if(!empty($item['QCDate'])){
                $item['QCDate'] = date('Y-m-d H:i:s',strtotime($item['QCDate']));
            }
            $item['Qty'] = number_format($item['Qty'],2);
            $item['MiOutQty'] = number_format($item['MiOutQty'],2);
            $item['PreStockQty'] = number_format($item['PreStockQty'],2);
            $item['UnitCd'] = self::getUnitCdNm($item['UnitCd']);
            $item['ProcessType'] = self::getProcessTypeNm($item['ProcessType'],$langCode);
        }
        return $data;
    }

    public static function getProcessTypeNm($value,$langCode)
    {
        $data = Db::connect(self::$Db)
            ->table('TSMDict10')
            ->where('DictCd', $value)
            ->where('LangCd',$langCode)
            ->value('TransNm');
        if(!$data){
            $data = Db::connect(self::$Db)
                ->table('TSMSyco10')
                ->where('MajorCd','MA4007')
                ->where('MinorCd', $value)
                ->value('MinorNm');
        }
        return $data;
    }

    public static function getUnitCdNm($value)
    {
        return Db::connect(self::$Db)
            ->table('TMAUnit00')
            ->where('UnitCd', $value)
            ->value('UnitNm');
    }
}