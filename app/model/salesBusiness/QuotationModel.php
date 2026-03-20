<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-12-09 9:00
 */

namespace app\model\salesBusiness;


use think\facade\Db;

class QuotationModel extends \app\model\BaseModel
{

    public static function saleVatRate()
    {
        $sql = "select TOP 1 SaleVatRate from TMATaxm00 ";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    public static function getCustInfo($CustCd)
    {
        $sql = "SELECT CustCd, CustNm, CurrCd, Tel, Fax, EmailAddr, Nation,CurrCd, PurPayment FROM TMACust00 WHERE CustCd = '{$CustCd}'";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    public static function getquotSerl($QuotNo)
    {
        $sql = "select top 1 QuotSerl, Sort from TSAQuot10 where QuotNo = '{$QuotNo}' order by QuotSerl desc";
        $result = Db::connect(self::$Db)->query($sql);
        if (empty($result)) {
            $quotSerl = '0001'; // 初始化为 '0001'
        } else {
            $quotSerl = str_pad((int)$result[0]['QuotSerl'] + 1, 4, '0', STR_PAD_LEFT);
        }
        return $quotSerl;
    }

    public static function getQuotNo()
    {
        $date = date('Ym',time());
        $sql = "select top 1 QuotNo from TSAQuot00 where QuotNo like '$date%%' order by QuotNo desc";
        $result =  Db::connect(self::$Db)->query($sql);
        if(empty($result)){
            $quoteNo = $date.'0001';
        }else{
            $quoteNo = $result[0]['QuotNo']+1;
        }
        return $quoteNo;


    }

    public static function getJobNo($userId)
    {
        $sql = "select UB.DeptCd,UB.MDeptCd,UC.emp_code,UD.JobNo from TMAEmpy00 UA 
                left join TMADept00 UB on UB.DeptCd = UA.DeptCd              
                left join sysUserMaster UC on UA.EmpId = UC.emp_code and isnull(UC.emp_code,'') !=''
                left join TMAJobc10 UD on UD.EmpId = UC.emp_code 
                INNER join TMAJobc00 UE on UE.JobNo = UD.JobNo and UE.SaleYn = 'Y' 
                WHERE UC.user_id = '{$userId}' and UD.LastYN = 'Y'
                and getdate() between UD.STDate and UD.EDDate";
        $result =  Db::connect(self::$Db)->query($sql);
        if($result){
            return $result[0]['JobNo'];
        }else{
            return '';
    }
    }

    public static function confirm($type,$QuotNo,$userId,$langCode)
    {
        $result = Db::connect(self::$Db)->query(" SET NOCOUNT ON;EXEC yudo.SSAQuotCfm ?, ?,?", [$type, $QuotNo,$userId]);
        $msgCd = $result[0][''];
        $sql = "select top 1  MsgCd, MsgTxt  from TSMMsge10  where MsgCd = '{$msgCd}' and  LangCd = '{$langCode}'";
        $result = Db::connect(self::$Db)->query($sql);
        return $result[0];
    }

    public static function getQuoteItemList($QuotNo)
    {
        $sql = "SELECT TSAQuot10.ExpClss,   
					TSAQuot10.QuotNo,   
					TSAQuot10.QuotSerl,   
					TSAQuot10.Sort,   
					TSAQuot10.ItemCd,  
					TMAItem00.ItemNo,
					TMAItem00.ItemNm,
					TMAItem00.Spec,
					TMAItem00.Status,
					TMAItem00.ASChargeYn,
					TSAQuot10.CustItemNm,
					TSAQuot10.UnitCd,   
					TSAQuot10.Qty,   
					TSAQuot10.StdPrice,   
					TSAQuot10.StdAmt,
   					TSAQuot10.StdVat,
					--TSAQuot10.DCRate, 
					round(dcRate * 100,2) as DCRate,  
					TSAQuot10.DCPrice,   
					TSAQuot10.DCAmt,   
					TSAQuot10.DCVat,   
					TSAQuot10.StdCost,   
					TSAQuot10.TotCost,   
					TSAQuot10.DCForPrice,   
					TSAQuot10.DCForAmt,   
					TSAQuot10.DCForVat,   
					TSAQuot10.StopYn,   
					TSAQuot10.NextQty,   
					TSAQuot10.StopQty,
					TSAQuot10.ProgClss,
					TMAItem00.VatYn, 
					TSAQuot10.Nation,  
					TSAQuot10.Remark ,
					TSAQuot00.CfmYn,
					IsNull(WH.PreStockQty, 0) As PreStockQty,
					'' as AsChargeYn  
			        FROM 	TSAQuot10 
			        Left Outer Join TSAQuot00 On TSAQuot00.QuotNo = TSAQuot10.QuotNo AND TSAQuot00.ExpClss = TSAQuot10.ExpClss AND TSAQuot00.DeleteYn = 'N'
                    Left Outer Join TMAItem00 On TSAQuot10.ItemCd = TMAItem00.ItemCd
                    Left Outer Join (SELECT ItemCd, Sum(PreStockQty) As PreStockQty FROM TMEWHItem00 With (Nolock)
                                      WHERE Status = '0' And StkStatus = '0'
                                      GROUP BY ItemCd) As WH On TSAQuot10.ItemCd = WH.ItemCd
			        WHERE TSAQuot10.ExpClss = '1'
			        And TSAQuot10.QuotNo = '{$QuotNo}' order by TSAQuot10.Sort asc";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function addServiceCharge()
    {
        $sql = "SELECT TOP 1  IsNull(A.ItemCd, '') as ItemCd , 
                  IsNull(A.ItemNo, '') as ItemNo,
                  IsNull(A.ItemNm, '') as ItemNm,
                  IsNull(A.Spec, '') as Spec,
                  IsNull(A.StkUnitCd, '') as UnitCd,
                  IsNull(A.VatYn, 'N') as VatYn,
                  IsNull(A.Status, '') as Status,
                  IsNull(B.ASChargeRate, 0) as ASChargeRate   
                  FROM TMAItem00 As A With(Nolock) 
                  Inner Join TMATaxm00 As B With (Nolock) On 1 = 1  
                  WHERE A.ASChargeYn = 'Y'  
                  And A.Status = '1'  
                  And A.SaleYesOrNo = 'Y'  Order By ItemNo";
        return Db::connect(self::$Db)->query($sql)[0];

    }

    public static function getStdPrice($itemCd,$custCd,$date,$currCd)
    {
        $timeStamp = str_replace('-','',$date);
        $sql = "SELECT ISNULL(MAX(StdPrice), 0) as StdPrice
                    FROM TSAPric00 With(Nolock) 
                    WHERE ItemCd = N'{$itemCd}' 
                    AND StDate <= CONVERT(DATETIME, N'{$timeStamp}') 
                    AND EdDate >= CONVERT(DATETIME, N'{$timeStamp}')
                    AND CurrCd = N'{$currCd}'";
        return Db::connect(self::$Db)->query($sql);

    }

    public static function getItemList($itemNo,$itemNm,$count)
    {
        $sql = "select TOP 50 * from (
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
                        where MA.ItemNo like '{$itemNo}%%'
                        and MA.ItemNm like N'%%{$itemNm}%%'
                        ) t where id > $count order by id asc";
        return Db::connect(self::$Db)->query($sql);

    }

    public static function getUnitCdS()
    {
        $sql = "select UnitNm AS text,UnitCd AS value from TMAUnit00";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getQuoteJobPower($empId)
    {
        $sql = "Select IsNull(MAX(A.JobNo),'')  as JobNo
								From TMAjobc10 A With(Nolock) 
								Inner Join TMAJobc00 B With(Nolock)  On A.JobNo = B.JobNo
								Where A.EmpId = '{$empId}'
                                And B.SaleYn = 'Y'
                                And A.STDate <= GETDATE()
                                And A.EDDate >= GETDATE()";
        return  Db::connect(self::$Db)->query($sql)[0]['JobNo'];
    }

    public static function getCurrRate($dateYm,$currCd){
        $sql = "SELECT IsNull(A.BasicStdRate, 0) as BasicStdRate,
            IsNull(B.BasicAmt, 0) as BasicAmt
            From TMACurr10 A Inner Join TMACurr00 B On A.CurrCd = B.CurrCd
            Where A.YYMM = N'{$dateYm}'
            And A.CurrCd = N'{$currCd}'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getGoodClassS($langCode)
    {
        $sql = "select DictCd as value,TransNm as text from TSMDict10 where DictCd LIKE 'SA2003%%' AND LangCd = '{$langCode}'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getMarketCdS($langCode)
    {
        $sql = "select DictCd as value,TransNm as text from TSMDict10 where DictCd LIKE 'SA1025%%' AND LangCd = '{$langCode}'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getMinDict($classNm,$langCode)
    {
        $sql = "select 
                B.DictCd as value,
                B.TransNm as text from TSMSyco10 as A 
                left join TSMDict10 as B on B.DictCd = A.MinorCd AND B.LangCd = '{$langCode}'
                WHERE A.RelCd1 = '{$classNm}'";
        return Db::connect(self::$Db)->query($sql);
    }


    public static function getPrintGubunS($langCode)
    {
        $sql = "select A.RelCd1 as value,B.TransNm as text from TSMSyco10 A
                    left join TSMDict10 B on A.MinorCd = B.DictCd and B.LangCd = '{$langCode}'
                    where A.MajorCd like 'SA1029%%'";
        return Db::connect(self::$Db)->query($sql);
    }
    public static function getSrvArea($langCode)
    {
        $sql = "select DictCd as value,TransNm as text from TSMDict10 where DictCd LIKE 'SA1031%%' AND LangCd = '{$langCode}'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getQuotTypeS($langCode)
    {
        $sql = "select DictCd as value,TransNm as text from TSMDict10 where DictCd LIKE 'SA2002%%' AND LangCd = '{$langCode}'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getDelvLimitS($langCode)
    {
        $sql = "select DictCd as value,TransNm as text from TSMDict10 where DictCd LIKE 'SA1032%%' AND LangCd = '{$langCode}'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getDelvMethodS($langCode)
    {
        $sql = "select DictCd as value,TransNm as text from TSMDict10 where DictCd LIKE 'SA1033%%' AND LangCd = '{$langCode}'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function getNationS($langCode)
    {
        $sql = "select DictCd as value,TransNm as text from TSMDict10 where DictCd LIKE 'MA3003%%' AND LangCd = '{$langCode}'";
        return Db::connect(self::$Db)->query($sql);
    }



    public static function getQuoteInfo($quoteNo)
    {
        $sql = "SELECT TSAQuot00.ExpClss,   
						TSAQuot00.QuotNo,   
						TSAQuot00.QuotDate,   
						TSAQuot00.DeptCd,  
						TMADept00.DeptNm, 
						TSAQuot00.JobNo,   
						TMAJobc00.JobNm,
						TSAQuot00.EmpId, 
						TMAEmpy00.EmpNm,  
						TSAQuot00.QuotType,   
						TSAQuot00.CustCd,  
						TMACust00.CustNo, 
						TMACust00.CustNm,  
						TSAQuot00.CustomerCd,    
						TMACustomer.CustNo as CustomerNo, 
						TMACustomer.CustNm as CustomerNm,
						TSAQuot00.AgentCd,   
						TMAAgent.CustNo as AgentNo,
						TMAAgent.CustNm as AgentNm, 
						TSAQuot00.ShipToCd,  
						TMAShipTo.CustNo as ShipToNo, 
						TMAShipTo.CustNm as ShipToNm, 
						TSAQuot00.MakerCd,  
						TMAMaker.CustNo as MakerNo, 
						TMAMaker.CustNm as MakerNm, 
						TSAQuot00.CustPrsn,   
						TSAQuot00.CustTel,   
						TSAQuot00.CustFax,
						TSAQuot00.CustEmail,
						TSAQuot00.CustRemark,   
						TSAQuot00.ValidDate,   
						TSAQuot00.DelvDate,   
						TSAQuot00.Status,   
						TSAQuot00.GoodNm,   
						TSAQuot00.Payment,   
						TSAQuot00.CurrCd,   
						TSAQuot00.CurrRate,   
						TSAQuot00.StdSaleAmt,   
						TSAQuot00.StdSaleVat,   
						TSAQuot00.QuotForAmt,   
						TSAQuot00.QuotForVat,   
						TSAQuot00.QuotAmt,   
						TSAQuot00.QuotVat,
   						TSAQuot00.ProposeAmt,
						round(TSAQuot00.DisCountRate * 100,2) as DisCountRate,
						TSAQuot00.VatYn,   
						TSAQuot00.PrnAmtYn,  
						TSAQuot00.RefNo,
						TSAQuot00.Resin,
						TSAQuot00.Remark,   
						TSAQuot00.MiOrderRemark,   
						TSAQuot00.ASYn,   
						TSAQuot00.ASRecvNo,   
						TSAQuot00.GoodClass,   
						TSAQuot00.OverseaYn,
						TSAQuot00.CfmYn,   
						TSAQuot00.CfmEmpId,   
						TMACfmEmpy.EmpNm as CfmEmpNm,
						TSAQuot00.CfmDate,   
						TSAQuot00.RegEmpID,   
						TMARegEmpy.EmpNm as RegEmpNm,
						TSAQuot00.RegDate,   
						TSAQuot00.UptEmpID,   
						TSAQuot00.UptDate,
						TMACurr00.BasicAmt As BasicAmt,
						TSAQuot00.QuotAmd,
						IsNull(TSMSyco10.RelCd2,'N') As QuotNotYn,
						TSAQuot00.PrintGubun,
						TSAQuot00.MarketCd,
						TSAQuot00.PProductCd,
						TSAQuot00.PPartCd,
						TSAQuot00.PartDesc,
						TSAQuot00.SrvArea,
						TSAQuot00.DelvLimit,
						TSAQuot00.DelvMethod,
						TSAQuot00.QuotDrawNo,
						TSAQuot00.GoodSpec,
						TSAQuot00.Nation,
						IsNull(E.EmpNm,'') As Manager,
						TSAQuot00.CustPrsnHP,
						TSAQuot00.SaleVatRate
			            FROM 	    TSAQuot00
						Left Outer Join TMADept00 With(Nolock) On TSAQuot00.DeptCd = TMADept00.DeptCd
						Left Outer Join TMAEmpy00 With(Nolock) On TSAQuot00.EmpID = TMAEmpy00.EmpID
						Left Outer Join TMACust00 With(Nolock) On TSAQuot00.CustCd = TMACust00.CustCd
						Left Outer Join TMACust00 as TMACustomer With(Nolock) On TMACustomer.CustCd = TSAQuot00.CustomerCd
						Left Outer Join TMACust00 as TMAAgent With(Nolock) On TMAAgent.CustCd = TSAQuot00.AgentCd
						Left Outer Join TMACust00 as TMAShipTo With(Nolock) On TMAShipTo.CustCd = TSAQuot00.ShipToCd
						Left Outer Join TMACust00 as TMAMaker With(Nolock) On TMAMaker.CustCd = TSAQuot00.MakerCd
						Left Outer Join TMAEmpy00 as TMACfmEmpy With(Nolock) On TMACfmEmpy.EmpID = TSAQuot00.CfmEmpID
						Left Outer Join TMAEmpy00 as TMARegEmpy With(Nolock) On TMARegEmpy.EmpID = TSAQuot00.RegEmpID
						Left Outer Join TMAJobc00 With(Nolock) On TMAJobc00.JobNo = TSAQuot00.JobNo 
						Left Outer Join TMACurr00 With(Nolock) On TSAQuot00.CurrCd = TMACurr00.CurrCd
						Left Outer Join TSMSyco10 With(Nolock) On TMACust00.Status = TSMSyco10.MinorCd
						Left Outer Join TSMSyco10 As S With(Nolock) On TSAQuot00.MarketCd = S.MinorCd
						Left Outer Join TMAEmpy00 As E With(Nolock) On S.RelCd1 = E.EmpID 
                        WHERE TSAQuot00.ExpClss	= '1'
                        AND TSAQuot00.DeleteYn = 'N'
                        AND TSAQuot00.QuotNo = '{$quoteNo}'
                        Order By TSAQuot00.QuotDate,TSAQuot00.QuotNo";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    public static function getQuotList($quoteNo,$custNm ,$startDate, $endDate, $count,$authInfo)
    {
        $empId = $authInfo['empId'];
        $deptCd = $authInfo['deptCd'];
        $jobNo = $authInfo['jobNo'];
        switch ($authInfo['auth']) {
            case self::AUTH_A:
                $and = '';
                break;
            case self::AUTH_E:   //个人
                $and = " AND EMPY.EmpID = '$empId'";
                break;
            case self::AUTH_J:   //职位
                $and = " AND EMPY.JobNo = '$jobNo'";
                break;
            case self::AUTH_D:   //部门
                $and = " AND DEPT.DeptCd = '$deptCd'";
                break;
            case self::AUTH_M:   //管理
                $and = " AND DEPT.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','$empId') )";
                break;
            default:       //默认为个人
                $and = " AND EMPY.EmpID = '$empId'";
                break;
        }
        $sql = "SELECT top 50 * from
                    (SELECT Row_Number()over(order by TSAQuot00.QuotNo desc)AS id,
                         TSAQuot00.ExpClss,
                         TSAQuot00.QuotNo,
                         TSAQuot00.QuotDate,
                         DEPT.DeptCd,
                         DEPT.DeptNm,
                         TSAQuot00.EmpId,
                         TSAQuot00.ValidDate,
                         EMPY.EmpNm,
                         TSAQuot00.QuotType,
                         TSAQuot00.CustCd,
                         TMACust00.CustNo,
                         TMACust00.CustNm,
                         TSAQuot00.CustomerCd,
                         TSAQuot00.MakerCd,
                         TSAQuot00.CustPrsn,
                         TSAQuot00.DelvDate,
                         TSAQuot00.Status,
                         TSAQuot00.GoodNm,
                         TSAQuot00.Payment,
                         TSAQuot00.CurrCd,
                         TSAQuot00.CurrRate,
                         TSAQuot00.QuotForAmt,
                         TSAQuot00.QuotForVat,
                         TSAQuot00.QuotAmt,
                         TSAQuot00.QuotVat,
                         TSAQuot00.ProposeAmt,
                         TSAQuot00.RefNo,
                         TSAQuot00.GoodClass,
                         TSAQuot00.CfmYn,
                         TSAQuot00.QuotAmd,
                         TSAQuot00.QuotDrawNo,
                         TSAQuot00.GoodSpec,
                         TSAQuot00.CustPrsnHP,
                         TSAQuot00.SaleVatRate
                    FROM TSAQuot00 Left Outer
                    JOIN TMADept00 AS DEPT With(Nolock)
                        ON TSAQuot00.DeptCd = DEPT.DeptCd Left Outer
                    JOIN TMAEmpy00 AS EMPY With(Nolock)
                        ON TSAQuot00.EmpID = EMPY.EmpID Left Outer
                    JOIN TMACust00 With(Nolock)
                        ON TSAQuot00.CustCd = TMACust00.CustCd Left Outer
                    JOIN TSMSyco10 With(Nolock)
                        ON TMACust00.Status = TSMSyco10.MinorCd
                    WHERE TSAQuot00.ExpClss = '1'
                            AND TSAQuot00.DeleteYn = 'N'
                            AND TSAQuot00.QuotNo LIKE '{$quoteNo}%%'
                            AND TMACust00.CustNm LIKE N'{$custNm}%%'
                            AND TSAQuot00.QuotDate >= '{$startDate}'
                            AND TSAQuot00.QuotDate <= '{$endDate}' $and)T
                WHERE id > {$count}
                ORDER BY  id asc";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    public static function getQuotStatusList($langCode)
    {
        $sql = "select A.RelCd1 as value,B.TransNm as text from TSMSyco10 A
                    left join TSMDict10 B on A.MinorCd = B.DictCd and B.LangCd = '{$langCode}'
                    where A.MajorCd like 'SA2001%%'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function preQuotList($result,$langCode)
    {
        $statusList = self::getQuotStatusList($langCode);

        foreach($result as &$item){
            $item['QuotAmt'] = self::formatAmt($item['QuotAmt']);
            $status = $item['Status'];
            $match = array_filter($statusList, function($map) use ($status) {
                return $map['value'] === $status;
            });
            if ($match) {
                $item['StatusNm'] = array_values($match)[0]['text'];
            }
        }
        return $result;
    }

    public static function getAuthInfo($auth, $userId)
    {
        $sql = "SELECT UB.DeptCd, UB.MDeptCd, UC.emp_code, UD.JobNo
            FROM TMAEmpy00 UA
            LEFT JOIN TMADept00 UB ON UB.DeptCd = UA.DeptCd
            LEFT JOIN sysUserMaster UC ON UA.EmpId = UC.emp_code AND UC.emp_code != ''
            LEFT JOIN TMAJobc10 UD ON UD.EmpId = UC.emp_code
            INNER JOIN TMAJobc00 UE ON UE.JobNo = UD.JobNo AND UE.SaleYn = 'Y'
            WHERE UC.user_id = :userId
            AND UD.LastYN = 'Y'
            AND GETDATE() BETWEEN UD.STDate AND UD.EDDate";

        $userInfo = Db::connect(self::$Db)->query($sql, ['userId' => $userId]);
        $result = [
            'auth' => $auth,
            'deptCd' => $userInfo[0]['DeptCd'] ?? '', // 处理返回的数组，默认值为空
            'empId'  => $userInfo[0]['emp_code'] ?? '',
            'jobNo'  => $userInfo[0]['JobNo'] ?? '',
        ];
        return $result;
    }


}