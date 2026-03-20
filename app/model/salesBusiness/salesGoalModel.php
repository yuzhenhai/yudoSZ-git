<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-21 14:31
 */

namespace app\model\salesBusiness;


use app\model\BaseModel;
use app\model\UserModel;
use think\facade\Db;


class salesGoalModel extends BaseModel
{
    //查询个人销售目标/业绩
    public static function getUserTarget($date,$dateItem,$currCd='RMB',$unit=10000){
        if($dateItem == 'y'){
            $whereDate = "CONVERT(CHAR(4),MB.SAPlanDate,23) = '$date'";
        }else if($dateItem == 'm'){
            $whereDate = "CONVERT(CHAR(7),MB.SAPlanDate,23) = '$date'";
        }else{
            $whereDate = "CONVERT(CHAR(10),MB.SAPlanDate,23) = '$date'";
        }
        $sql = "select MB.DeptCd,MB.EmpId,
                cast(sum(isnull(MB.OrderAmt,0))/$unit as numeric(20,2)) as OrderAmt,
                cast(sum(isnull(MB.OrderForAmt,0))/$unit as numeric(20,2)) as OrderForAmt,
                cast(sum(isnull(MB.InvoiceAmt,0))/$unit as numeric(20,2)) as InvoiceAmt,
                cast(sum(isnull(MB.InvoiceForAmt,0))/$unit as numeric(20,2)) as InvoiceForAmt,
                cast(sum(isnull(MB.BillAmt,0))/$unit as numeric(20,2)) as BillAmt,
                cast(sum(isnull(MB.BillForAmt,0))/$unit as numeric(20,2)) as BillForAmt,
                cast(sum(isnull(MB.ReceiptAmt,0))/$unit as numeric(20,2)) as ReceiptAmt,
                cast(sum(isnull(MB.ReceiptForAmt,0))/$unit as numeric(20,2)) as ReceiptForAmt
                from TMADept00 MA With(Nolock) 
                left join TSAPlanYMD10 MB With(Nolock) on MA.DeptCd = MB.DeptCd
                where MB.CurrCd = '$currCd' and MB.CfmYn = '1' and $whereDate group by MB.DeptCd,MB.EmpId";
        return Db::connect(self::$Db)->query($sql);
    }

    //查询部门下的员工列表
    public static function getUsersByDeptId($deptId){
        $sql = "select  a.EmpID as value,a.EmpNm as text,b.DeptCd from TMAEmpy00 a,TMADept00 b 
               where a.DeptCd = b.DeptCd AND a.RetireYn = 'N' AND b.DeptCd = '$deptId'";
        return Db::connect(self::$Db)->query($sql);
    }

    public static function linkUserIdTarget($userId,$deptId, $date, $dateDiv, &$allRecord, $dateRound,$dateItem, &$returnData,$langCode,$currItem)
    {
        // if(parent::getCookie('auth',false) == AUTH_E || parent::getCookie('auth',false) == 'NO'){
        //     $userList = $this->Worker10_model->select()->getUsersByUserId($this->loginUser);
        // }else{
        $userList = self::getUsersByDeptId($deptId);
        // }
        if(empty($userList)){
            $returnData = array('NULL');
            return false;
        }
        $targetRes = array();
        $resRecord = array();
        if ($dateItem == 'm') {
            $pDate = substr($dateRound[0], 0, 4) . substr($dateRound[0], 5, 2);
        } else {
            $pDate = $dateRound[0];
        }
        //查询业绩
        $sql = "SET NOCOUNT ON;EXEC SSADayTotal_M4 NULL,'$dateItem','RMB', '$deptId','$pDate'";
        $resRecord = Db::connect(self::$Db)->query($sql);
        //查询目标
        $targetRes = self::getUserTarget($dateRound[0],$dateItem);

        //业绩筛选合并
        foreach ($userList as $k => $v) {
            //初始化业绩map
            $_userId = str_replace(' ','',$v['value']);
            $allRecord[$v['text']] = self::$targetList;
            $targetOrder = 0;
            $targetInvoice = 0;
            $targetBill = 0;
            $targetReceipt = 0;
            //业绩
            for ($s = 0; $s < count($resRecord); $s++) {
//                $_resultsGroupId = str_replace(' ','',$resRecord[$s]['DeptCd']);
                $_resultsUserId  = trim($resRecord[$s]['EmpId']);
                if ($_userId == $_resultsUserId) {
                    $allRecord[$v['text']]['OrderForAmt'] = bcadd($allRecord[$v['text']]['OrderForAmt'],  empty($resRecord[$s]['OrderForAmt']) ? 0 :$resRecord[$s]['OrderForAmt'] , 2);
                    $allRecord[$v['text']]['InvoiceForAmt'] = bcadd($allRecord[$v['text']]['InvoiceForAmt'], empty($resRecord[$s]['InvoiceForAmt'])? 0 :$resRecord[$s]['InvoiceForAmt'], 2);
                    $allRecord[$v['text']]['BillForAmt'] = bcadd($allRecord[$v['text']]['BillForAmt'], empty($resRecord[$s]['BillForAmt'])? 0 :$resRecord[$s]['BillForAmt'], 2);
                    $allRecord[$v['text']]['ReceiptForAmt'] = bcadd($allRecord[$v['text']]['ReceiptForAmt'], empty($resRecord[$s]['ReceiptForAmt'])? 0 :$resRecord[$s]['ReceiptForAmt'], 2);
                }
            }
            $recordOrder = empty($allRecord[$v['text']]['OrderForAmt']) ? 0 : $allRecord[$v['text']]['OrderForAmt'];
            $recordInvoice = empty($allRecord[$v['text']]['InvoiceForAmt']) ? 0 :$allRecord[$v['text']]['InvoiceForAmt'];
            $recordBill = empty($allRecord[$v['text']]['BillForAmt']) ? 0 :$allRecord[$v['text']]['BillForAmt'];
            $recordReceipt = empty($allRecord[$v['text']]['ReceiptForAmt']) ? 0 : $allRecord[$v['text']]['ReceiptForAmt'];

            //获取部门的目标
            foreach ($targetRes as $t){
                if ($v['value'] == $t['EmpId']) {
                    empty($t['OrderAmt']) ? $targetOrder += 0 : $targetOrder += $t['OrderAmt'];
                    empty($t['InvoiceAmt']) ? $targetInvoice += 0 : $targetInvoice += $t['InvoiceAmt'];
                    empty($t['BillAmt']) ? $targetBill += 0 : $targetBill += $t['BillAmt'];
                    empty($t['ReceiptAmt']) ? $targetReceipt += 0 : $targetReceipt += $t['ReceiptAmt'];
                }
            }
            //组合数据,每次输出一个部门目标、业绩
            $returnData[$k]['name'] = $v['text'];
            $returnData[$k]['orderAmt'] = array(
                'target' => $targetOrder,
                'salesRecord' => round($recordOrder,2),
                'percent' => round($targetOrder == 0 ? 0 : $recordOrder / $targetOrder * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['InvoiceAmt'] = array(
                'target' => $targetInvoice,
                'salesRecord' => round($recordInvoice,2),
                'percent' => round($targetInvoice == 0 ? 0 : $recordInvoice / $targetInvoice * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['BillAmt'] = array(
                'target' => $targetBill,
                'salesRecord' => round($recordBill,2),
                'percent' => round($targetBill == 0 ? 0 : $recordBill / $targetBill * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['ReceiptAmt'] = array(
                'target' => $targetReceipt,
                'salesRecord' => round($recordReceipt,2),
                'percent' => round($targetReceipt == 0 ? 0 : $recordReceipt / $targetReceipt * 100, 2) . '%',
                'date' => $dateRound[0],
            );
        }
    }

    //查询所有组别数据
    public static function screenDeptIdTarget($userId,$deptIdList, $date, $dateDiv, &$allRecord, $dateRound, $dateItem, &$returnData,$langCode,$currItem)
    {
        //查询业绩
        $sql = "SET NOCOUNT ON; EXEC yudo.SSADayTotal_M3 '$date', '$langCode', $dateDiv, ''";
        $resRecord = Db::connect(self::$Db)->query($sql);
        //查询目标
        $targetRes = self::getTarget($date, $dateItem);
        if($dateItem != 'd') $salesPlanRes = self::getSalesPlan($dateRound[0], $dateItem);
        //业绩筛选合并
        foreach ($deptIdList as $k => $v) {
            //初始化业绩map
            $allRecord[$v['text']] = self::$targetList;
            for ($s = 0; $s < count($resRecord); $s++) {
                if (trim($v['value']) == trim($resRecord[$s]['DeptCd'])) {
                    //当前
                    $allRecord[$v['text']]['OrderAmt'] = bcadd($allRecord[$v['text']]['OrderAmt'], $resRecord[$s]['OrderAmt'], 2);
                    $allRecord[$v['text']]['InvoiceAmt'] = bcadd($allRecord[$v['text']]['InvoiceAmt'], $resRecord[$s]['InvoiceAmt'], 2);
                    $allRecord[$v['text']]['BillAmt'] = bcadd($allRecord[$v['text']]['BillAmt'], $resRecord[$s]['BillAmt'], 2);
                    $allRecord[$v['text']]['ReceiptAmt'] = bcadd($allRecord[$v['text']]['ReceiptAmt'], $resRecord[$s]['ReceiptAmt'], 2);
//                    //上一个
//                    $allRecord[$v['text']]['OrderAmt_Pre'] = bcadd($allRecord[$v['text']]['OrderAmt_Pre'], $resRecord[$s]['OrderAmt_Pre'], 2);
//                    $allRecord[$v['text']]['InvoiceAmt_Pre'] = bcadd($allRecord[$v['text']]['InvoiceAmt_Pre'], $resRecord[$s]['InvoiceAmt_Pre'], 2);
//                    $allRecord[$v['text']]['BillAmt_Pre'] = bcadd($allRecord[$v['text']]['BillAmt_Pre'], $resRecord[$s]['BillAmt_Pre'], 2);
//                    $allRecord[$v['text']]['ReceiptAmt_Pre'] = bcadd($allRecord[$v['text']]['ReceiptAmt_Pre'], $resRecord[$s]['ReceiptAmt_Pre'], 2);
                }
            }
            //今天业绩排除NULL
            empty($allRecord[$v['text']]['OrderAmt']) ? $recordOrder = 0 : $recordOrder = $allRecord[$v['text']]['OrderAmt'];
            empty($allRecord[$v['text']]['InvoiceAmt']) ? $recordInvoice = 0 : $recordInvoice = $allRecord[$v['text']]['InvoiceAmt'];
            empty($allRecord[$v['text']]['BillAmt']) ? $recordBill = 0 : $recordBill = $allRecord[$v['text']]['BillAmt'];
            empty($allRecord[$v['text']]['ReceiptAmt']) ? $recordReceipt = 0 : $recordReceipt = $allRecord[$v['text']]['ReceiptAmt'];

            $targetOrder = 0;
            $targetInvoice = 0;
            $targetBill = 0;
            $targetReceipt = 0;
            //获取部门的目标
            foreach ($targetRes as $t){
                if ($v['value'] == $t['DeptCd']) {
                    empty($t['OrderAmt']) ? $targetOrder += 0 : $targetOrder += $t['OrderAmt'];
                    empty($t['InvoiceAmt']) ? $targetInvoice += 0 : $targetInvoice += $t['InvoiceAmt'];
                    empty($t['BillAmt']) ? $targetBill += 0 : $targetBill += $t['BillAmt'];
                    empty($t['ReceiptAmt']) ? $targetReceipt += 0 : $targetReceipt += $t['ReceiptAmt'];
                }
            }
            $planOrder = 0;
            $planInvoice = 0;
            $planBill = 0;
            $planReceipt = 0;
            //获取部门的计划
            foreach ($salesPlanRes as $plan){
                if ($v['value'] == $plan['DeptCd']) {
                    empty($plan['OrderAmt']) ? $planOrder += 0 : $planOrder += $plan['OrderAmt'];
                    empty($plan['InvoiceAmt']) ? $planInvoice += 0 : $planInvoice += $plan['InvoiceAmt'];
                    empty($plan['BillAmt']) ? $planBill += 0 : $planBill += $plan['BillAmt'];
                    empty($plan['ReceiptAmt']) ? $planReceipt += 0 : $planReceipt += $plan['ReceiptAmt'];
                }
            }
            //组合数据,每次输出一个部门目标、业绩
            $returnData[$k]['name'] = $v['text'];
            $returnData[$k]['orderAmt'] = array(
                'target' => $targetOrder,
                'plan'   => $planOrder,
                'salesRecord' => $recordOrder,
                'percent' => round($targetOrder == 0 ? 0 : $recordOrder / $targetOrder * 100, 2) . '%',
                'percent2' => round($planOrder == 0 ? 0 : $recordOrder / $planOrder * 100, 2) . '%',
                'percent3' => round($planOrder == 0 ? 0 : $targetOrder / $planOrder * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['InvoiceAmt'] = array(
                'target' => $targetInvoice,
                'plan'   => $planInvoice,
                'salesRecord' => $recordInvoice,
                'percent' => round($targetInvoice == 0 ? 0 : $recordInvoice / $targetInvoice * 100, 2) . '%',
                'percent2' => round($planInvoice == 0 ? 0 : $recordInvoice / $planInvoice * 100, 2) . '%',
                'percent3' => round($planInvoice == 0 ? 0 : $targetInvoice / $planInvoice * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['BillAmt'] = array(
                'target' => $targetBill,
                'plan'   => $planBill,
                'salesRecord' => $recordBill,
                'percent' => round($targetBill == 0 ? 0 : $recordBill / $targetBill * 100, 2) . '%',
                'percent2' => round($planBill == 0 ? 0 :$recordBill / $planBill * 100, 2) . '%',
                'percent3' => round($planBill == 0 ? 0 : $targetBill / $planBill * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['ReceiptAmt'] = array(
                'target' => $targetReceipt,
                'plan'   => $planReceipt,
                'salesRecord' => $recordReceipt,
                'percent' => round($targetReceipt == 0 ? 0 : $recordReceipt / $targetReceipt * 100, 2) . '%',
                'percent2' => round($planReceipt == 0 ? 0 : $recordReceipt / $planReceipt * 100, 2) . '%',
                'percent3' => round($planReceipt == 0 ? 0 : $targetReceipt / $planReceipt * 100, 2) . '%',
                'date' => $dateRound[0],
            );
        }
    }

    public static function getTargetByMempId($date,$dateItem,$MempId,$currCd='RMB',$unit=10000){
        if($dateItem == 'd'){
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(10),MB.SAPlanDate,23) = '$date'";
        }else if($dateItem == 'm'){
            $date = substr($date,0,7);
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(7),MB.SAPlanDate,23) = '$date'";
        }else{
            $date = substr($date,0,4);
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(4),MB.SAPlanDate,23) = '$date'";
        }
        $sql = "select MB.DeptCd,MB.CurrCd,
                cast(sum(isnull(MB.OrderAmt,0))/$unit as numeric(20,2)) as OrderAmt,
                cast(sum(isnull(MB.OrderForAmt,0))/$unit as numeric(20,2)) as OrderForAmt,
                cast(sum(isnull(MB.InvoiceAmt,0))/$unit as numeric(20,2)) as InvoiceAmt,
                cast(sum(isnull(MB.InvoiceForAmt,0))/$unit as numeric(20,2)) as InvoiceForAmt,
                cast(sum(isnull(MB.BillAmt,0))/$unit as numeric(20,2)) as BillAmt,
                cast(sum(isnull(MB.BillForAmt,0))/$unit as numeric(20,2)) as BillForAmt,
                cast(sum(isnull(MB.ReceiptAmt,0))/$unit as numeric(20,2)) as ReceiptAmt,
                cast(sum(isnull(MB.ReceiptForAmt,0))/$unit as numeric(20,2)) as ReceiptForAmt
                from TMADept00 MA With(Nolock) 
                left join $_tableNm MB With(Nolock) on MA.DeptCd = MB.DeptCd
                where $_where and MB.CurrCd = '$currCd' and MA.MEmpID = '$MempId' group by MB.DeptCd,MB.CurrCd";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    //根据经理ID查询销售计划
    public static function getSalesPlanByMempId($date,$dateItem,$MempId,$currCd='RMB',$unit=10000){
        if($dateItem == 'm'){
            $date = substr($date,0,4).substr($date,5,2);
            $_where = "MB.SAPlanYM = '$date'";
        }else {
            $date = substr($date,0,4);
            $_where = 'LEFT(MB.SAPlanYM,4) = '."'$date'";
        }
        $sql = "select MB.DeptCd,MB.CurrCd,
                cast(sum(isnull(MB.OrderAmt,0))/$unit as numeric(20,2)) as OrderAmt,
                cast(sum(isnull(MB.OrderForAmt,0))/$unit as numeric(20,2)) as OrderForAmt,
                cast(sum(isnull(MB.InvoiceAmt,0))/$unit as numeric(20,2)) as InvoiceAmt,
                cast(sum(isnull(MB.InvoiceForAmt,0))/$unit as numeric(20,2)) as InvoiceForAmt,
                cast(sum(isnull(MB.BillAmt,0))/$unit as numeric(20,2)) as BillAmt,
                cast(sum(isnull(MB.BillForAmt,0))/$unit as numeric(20,2)) as BillForAmt,
                cast(sum(isnull(MB.ReceiptAmt,0))/$unit as numeric(20,2)) as ReceiptAmt,
                cast(sum(isnull(MB.ReceiptForAmt,0))/$unit as numeric(20,2)) as ReceiptForAmt
                from TMADept00 MA With(Nolock) 
                left join TSAPlanYMM00 MB With(Nolock) on MA.DeptCd = MB.DeptCd
                where $_where and MB.CurrCd = '$currCd' and MA.MEmpID = '$MempId' group by MB.DeptCd,MB.CurrCd";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    public static function linkMempIdTarget($userId,$mempId, $date, $dateDiv, &$allRecord, $dateRound,$mempNm, $dateItem, &$returnData,$langCode,$currItem)
    {
        //查询业绩
        $sql = "SET NOCOUNT ON; EXEC yudo.SSADayTotal_M3 '$date', '$langCode', $dateDiv, ''";
        $resRecord = Db::connect(self::$Db)->query($sql);
        //查询目标
        $targetRes = self::getTargetByMempId($date,$dateItem,$mempId);
        //查询计划
        if($dateItem != 'd')$salesPlanRes = self::getSalesPlanByMempId($date,$dateItem,$mempId);

        //计算某经理下所有部门的业绩总和
        for ($s = 0; $s < count($resRecord); $s++) {
            //当前
            $allRecord[$mempNm]['OrderAmt'] = bcadd($allRecord[$mempNm]['OrderAmt'], $resRecord[$s]['OrderAmt'], 2);
            $allRecord[$mempNm]['InvoiceAmt'] = bcadd($allRecord[$mempNm]['InvoiceAmt'], $resRecord[$s]['InvoiceAmt'], 2);
            $allRecord[$mempNm]['BillAmt'] = bcadd($allRecord[$mempNm]['BillAmt'], $resRecord[$s]['BillAmt'], 2);
            $allRecord[$mempNm]['ReceiptAmt'] = bcadd($allRecord[$mempNm]['ReceiptAmt'], $resRecord[$s]['ReceiptAmt'], 2);
            //上一个
            $allRecord[$mempNm]['OrderAmt_Pre'] = bcadd($allRecord[$mempNm]['OrderAmt_Pre'], $resRecord[$s]['OrderAmt_Pre'], 2);
            $allRecord[$mempNm]['InvoiceAmt_Pre'] = bcadd($allRecord[$mempNm]['InvoiceAmt_Pre'], $resRecord[$s]['InvoiceAmt_Pre'], 2);
            $allRecord[$mempNm]['BillAmt_Pre'] = bcadd($allRecord[$mempNm]['BillAmt_Pre'], $resRecord[$s]['BillAmt_Pre'], 2);
            $allRecord[$mempNm]['ReceiptAmt_Pre'] = bcadd($allRecord[$mempNm]['ReceiptAmt_Pre'], $resRecord[$s]['ReceiptAmt_Pre'], 2);
        }
        $targetOrder = 0;
        $targetInvoice = 0;
        $targetBill = 0;
        $targetReceipt = 0;

        foreach ($targetRes as $t){
            empty($t['OrderAmt']) ? $targetOrder += 0 : $targetOrder += $t['OrderAmt'];
            empty($t['InvoiceAmt']) ? $targetInvoice += 0 : $targetInvoice += $t['InvoiceAmt'];
            empty($t['BillAmt']) ? $targetBill += 0 : $targetBill += $t['BillAmt'];
            empty($t['ReceiptAmt']) ? $targetReceipt += 0 : $targetReceipt += $t['ReceiptAmt'];
        }
        //业绩当前数据转换空值
        empty($allRecord[$mempNm]['OrderAmt']) ? $recordOrder = 0 : $recordOrder = $allRecord[$mempNm]['OrderAmt'];
        empty($allRecord[$mempNm]['InvoiceAmt']) ? $recordInvoice = 0 : $recordInvoice = $allRecord[$mempNm]['InvoiceAmt'];
        empty($allRecord[$mempNm]['BillAmt']) ? $recordBill = 0 : $recordBill = $allRecord[$mempNm]['BillAmt'];
        empty($allRecord[$mempNm]['ReceiptAmt']) ? $recordReceipt = 0 : $recordReceipt = $allRecord[$mempNm]['ReceiptAmt'];

        $planOrder = 0;
        $planInvoice = 0;
        $planBill = 0;
        $planReceipt = 0;
        foreach ($salesPlanRes as $plan){
            empty($plan['OrderAmt']) ? $planOrder += 0 : $planOrder += $plan['OrderAmt'];
            empty($plan['InvoiceAmt']) ? $planInvoice += 0 : $planInvoice += $plan['InvoiceAmt'];
            empty($plan['BillAmt']) ? $planBill += 0 :$planBill += $plan['BillAmt'];
            empty($plan['ReceiptAmt']) ? $planReceipt += 0 : $planReceipt += $plan['ReceiptAmt'];
        }
        //组合数据
        $returnData['name'] = $mempNm;
        $returnData['orderAmt'] = array(
            'target' => $targetOrder,
            'plan'   => $planOrder,
            'salesRecord' => round($recordOrder,2) ,
            'percent' => round($targetOrder == 0 ? 0 : $recordOrder / $targetOrder * 100, 2) . '%',
            'percent2' => round($planOrder == 0 ? 0 : $recordOrder / $planOrder * 100, 2) . '%',
            'percent3' => round($planOrder == 0 ? 0 : $targetOrder / $planOrder * 100, 2) . '%',
            'date' => $dateRound[0],
        );
        $returnData['InvoiceAmt'] = array(
            'target' => $targetInvoice,
            'plan'   => $planInvoice,
            'salesRecord' => round($recordInvoice,2),
            'percent' => round($targetInvoice == 0 ? 0 : $recordInvoice / $targetInvoice * 100, 2) . '%',
            'percent2' => round($planInvoice == 0 ? 0 : $recordInvoice / $planInvoice * 100, 2) . '%',
            'percent3' => round($planInvoice == 0 ? 0 : $targetInvoice / $planInvoice * 100, 2) . '%',
            'date' => $dateRound[0],
        );
        $returnData['BillAmt'] = array(
            'target' => $targetBill,
            'plan'   => $planBill,
            'salesRecord' => round($recordBill,2),
            'percent' => round($targetBill == 0 ? 0 : $recordBill / $targetBill * 100, 2) . '%',
            'percent2' => round($planBill == 0 ? 0 :$recordBill / $planBill * 100, 2) . '%',
            'percent3' => round($planBill == 0 ? 0 : $targetBill / $planBill * 100, 2) . '%',
            'date' => $dateRound[0],
        );
        $returnData['ReceiptAmt'] = array(
            'target' => $targetReceipt,
            'plan'   => $planReceipt,
            'salesRecord' => round($recordReceipt,2),
            'percent' => round($targetReceipt == 0 ? 0 : $recordReceipt / $targetReceipt * 100, 2) . '%',
            'percent2' => round($planReceipt == 0 ? 0 : $recordReceipt / $planReceipt * 100, 2) . '%',
            'percent3' => round($planReceipt == 0 ? 0 : $targetReceipt / $planReceipt * 100, 2) . '%',
            'date' => $dateRound[0],
        );
    }

    public static function screenMempIdTarget($userId,$mempIdList, $date, $dateDiv, &$allRecord, $dateRound, $dateItem, &$returnData,$langCode,$currItem)
    {
        $auth = BaseModel::getAuth('WEI_2300', $userId);
        //查询经理管辖的部门
        $resDeptCd = self::getDeptIdResults($userId,$auth);
        //查询业绩
        $sql = "SET NOCOUNT ON; EXEC yudo.SSADayTotal_M3 '$date', '$langCode', $dateDiv, ''";

        $resRecord = Db::connect(self::$Db)->query($sql);

        $targetRes = self::getTarget($date,$dateItem);
        if($dateItem != 'd') $salesPlanRes = self::getSalesPlan($dateRound[0], $dateItem);
        //业绩筛选合并
        foreach ($mempIdList as $k => $v) {

            //初始化业绩map
            $allRecord[$v['text']] = self::$targetList;

            for ($s = 0; $s < count($resRecord); $s++) {
                if (trim($v['value']) == trim($resRecord[$s]['MEmpID'])) {
                    //当前
                    $allRecord[$v['text']]['OrderAmt'] = bcadd($allRecord[$v['text']]['OrderAmt'], $resRecord[$s]['OrderAmt'], 2);
                    $allRecord[$v['text']]['InvoiceAmt'] = bcadd($allRecord[$v['text']]['InvoiceAmt'], $resRecord[$s]['InvoiceAmt'], 2);
                    $allRecord[$v['text']]['BillAmt'] = bcadd($allRecord[$v['text']]['BillAmt'], $resRecord[$s]['BillAmt'], 2);
                    $allRecord[$v['text']]['ReceiptAmt'] = bcadd($allRecord[$v['text']]['ReceiptAmt'], $resRecord[$s]['ReceiptAmt'], 2);
                    //上一个
                    $allRecord[$v['text']]['OrderAmt_Pre'] = bcadd($allRecord[$v['text']]['OrderAmt_Pre'], $resRecord[$s]['OrderAmt_Pre'], 2);
                    $allRecord[$v['text']]['InvoiceAmt_Pre'] = bcadd($allRecord[$v['text']]['InvoiceAmt_Pre'], $resRecord[$s]['InvoiceAmt_Pre'], 2);
                    $allRecord[$v['text']]['BillAmt_Pre'] = bcadd($allRecord[$v['text']]['BillAmt_Pre'], $resRecord[$s]['BillAmt_Pre'], 2);
                    $allRecord[$v['text']]['ReceiptAmt_Pre'] = bcadd($allRecord[$v['text']]['ReceiptAmt_Pre'], $resRecord[$s]['ReceiptAmt_Pre'], 2);
                }
            }
            //统计今天/昨天数据
//            foreach($targetRes as $key => $values){
            //今天业绩排除NULL
            empty($allRecord[$v['text']]['OrderAmt']) ? $recordOrder = 0 : $recordOrder = $allRecord[$v['text']]['OrderAmt'];
            empty($allRecord[$v['text']]['InvoiceAmt']) ? $recordInvoice = 0 : $recordInvoice = $allRecord[$v['text']]['InvoiceAmt'];
            empty($allRecord[$v['text']]['BillAmt']) ? $recordBill = 0 : $recordBill = $allRecord[$v['text']]['BillAmt'];
            empty($allRecord[$v['text']]['ReceiptAmt']) ? $recordReceipt = 0 : $recordReceipt = $allRecord[$v['text']]['ReceiptAmt'];

            $targetOrder = 0;
            $targetInvoice = 0;
            $targetBill = 0;
            $targetReceipt = 0;
            //获取今天/昨天部门的目标
            foreach ($targetRes as $t){
                foreach($resDeptCd as $itemDeptCd){
                    //如果匹配到目标中的部门，则取出经理ID对比是否加入计算
                    if($itemDeptCd['DeptCd'] == $t['DeptCd']){
                        //如果当前部门的经理匹配，则计算
                        if ($v['value'] == $itemDeptCd['value']) {
                            empty($t['OrderAmt']) ? $targetOrder += 0 : $targetOrder += $t['OrderAmt'];
                            empty($t['InvoiceAmt']) ? $targetInvoice += 0 : $targetInvoice += $t['InvoiceAmt'];
                            empty($t['BillAmt']) ? $targetBill += 0 : $targetBill += $t['BillAmt'];
                            empty($t['ReceiptAmt']) ? $targetReceipt += 0 : $targetReceipt += $t['ReceiptAmt'];
                        }
                    }
                }
            }
            $planOrder = 0;
            $planInvoice = 0;
            $planBill = 0;
            $planReceipt = 0;
            //获取部门的计划
            foreach ($salesPlanRes as $plan){
                foreach($resDeptCd as $itemDeptCd){
                    //如果匹配到目标中的部门，则取出经理ID对比是否加入计算
                    if($itemDeptCd['DeptCd'] == $plan['DeptCd']){
                        //如果当前部门的经理匹配，则计算
                        if ($v['value'] == $itemDeptCd['value']) {
                            empty($plan['OrderAmt']) ? $planOrder += 0 : $planOrder += $plan['OrderAmt'];
                            empty($plan['InvoiceAmt']) ? $planInvoice += 0 : $planInvoice += $plan['InvoiceAmt'];
                            empty($plan['BillAmt']) ? $planBill += 0 : $planBill += $plan['BillAmt'];
                            empty($plan['ReceiptAmt']) ? $planReceipt += 0 : $planReceipt += $plan['ReceiptAmt'];
                        }
                    }
                }
            }
            //组合数据,每次输出一个经理的目标、业绩
            $returnData[$k]['name'] = $v['text'];
            $returnData[$k]['orderAmt'] = array(
                'target' => $targetOrder,
                'plan'   => $planOrder,
                'salesRecord' => $recordOrder,
                'percent' => round($targetOrder == 0 ? 0 : $recordOrder / $targetOrder * 100, 2) . '%',
                'percent2' => round($planOrder == 0 ? 0 : $recordOrder / $planOrder * 100, 2) . '%',
                'percent3' => round($planOrder == 0 ? 0 : $targetOrder / $planOrder * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['InvoiceAmt'] = array(
                'target' => $targetInvoice,
                'plan'   => $planInvoice,
                'salesRecord' => $recordInvoice,
                'percent' => round($targetInvoice == 0 ? 0 : $recordInvoice / $targetInvoice * 100, 2) . '%',
                'percent2' => round($planInvoice == 0 ? 0 : $recordInvoice / $planInvoice * 100, 2) . '%',
                'percent3' => round($planInvoice == 0 ? 0 : $targetInvoice / $planInvoice * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['BillAmt'] = array(
                'target' => $targetBill,
                'plan'   => $planBill,
                'salesRecord' => $recordBill,
                'percent' => round($targetBill == 0 ? 0 : $recordBill / $targetBill * 100, 2) . '%',
                'percent2' => round($planBill == 0 ? 0 :$recordBill / $planBill * 100, 2) . '%',
                'percent3' => round($planBill == 0 ? 0 : $targetBill / $planBill * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$k]['ReceiptAmt'] = array(
                'target' => $targetReceipt,
                'plan'   => $planReceipt,
                'salesRecord' => $recordReceipt,
                'percent' => round($targetReceipt == 0 ? 0 : $recordReceipt / $targetReceipt * 100, 2) . '%',
                'percent2' => round($planReceipt == 0 ? 0 : $recordReceipt / $planReceipt * 100, 2) . '%',
                'percent3' => round($planReceipt == 0 ? 0 : $targetReceipt / $planReceipt * 100, 2) . '%',
                'date' => $dateRound[0],
            );
        }
    }

    //根据部长ID查询销售目标
    public static function getTargetByLeader($date,$dateItem,$leader,$currCd='RMB',$unit=10000){
        if($dateItem == 'd'){
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(10),MB.SAPlanDate,23) = '$date'";
        }else if($dateItem == 'm'){
            $date = substr($date,0,7);
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(7),MB.SAPlanDate,23) = '$date'";
        }else{
            $date = substr($date,0,4);
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(4),MB.SAPlanDate,23) = '$date'";
        }
        $sql = "select MB.DeptCd,MB.CurrCd,
                cast(sum(isnull(MB.OrderAmt,0))/$unit as numeric(20,2)) as OrderAmt,
                cast(sum(isnull(MB.OrderForAmt,0))/$unit as numeric(20,2)) as OrderForAmt,
                cast(sum(isnull(MB.InvoiceAmt,0))/$unit as numeric(20,2)) as InvoiceAmt,
                cast(sum(isnull(MB.InvoiceForAmt,0))/$unit as numeric(20,2)) as InvoiceForAmt,
                cast(sum(isnull(MB.BillAmt,0))/$unit as numeric(20,2)) as BillAmt,
                cast(sum(isnull(MB.BillForAmt,0))/$unit as numeric(20,2)) as BillForAmt,
                cast(sum(isnull(MB.ReceiptAmt,0))/$unit as numeric(20,2)) as ReceiptAmt,
                cast(sum(isnull(MB.ReceiptForAmt,0))/$unit as numeric(20,2)) as ReceiptForAmt
                from TMADept00 MA With(Nolock) 
                left join $_tableNm MB With(Nolock) on MA.DeptCd = MB.DeptCd
                where $_where and MB.CurrCd = '$currCd' and MA.DeptDiv2 = '$leader' group by MB.DeptCd,MB.CurrCd";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    //根据部长ID查询销售计划
    public static function getSalesPlanByLeader($date,$dateItem,$leader,$currCd='RMB',$unit=10000){
        if($dateItem == 'm'){
            $date = substr($date,0,4).substr($date,5,2);
            $_where = "MB.SAPlanYM = '$date'";
        }else {
            $date = substr($date,0,4);
            $_where = 'LEFT(MB.SAPlanYM,4) = '."'$date'";
        }
        $sql = "select MB.DeptCd,MB.CurrCd,
                cast(sum(isnull(MB.OrderAmt,0))/$unit as numeric(20,2)) as OrderAmt,
                cast(sum(isnull(MB.OrderForAmt,0))/$unit as numeric(20,2)) as OrderForAmt,
                cast(sum(isnull(MB.InvoiceAmt,0))/$unit as numeric(20,2)) as InvoiceAmt,
                cast(sum(isnull(MB.InvoiceForAmt,0))/$unit as numeric(20,2)) as InvoiceForAmt,
                cast(sum(isnull(MB.BillAmt,0))/$unit as numeric(20,2)) as BillAmt,
                cast(sum(isnull(MB.BillForAmt,0))/$unit as numeric(20,2)) as BillForAmt,
                cast(sum(isnull(MB.ReceiptAmt,0))/$unit as numeric(20,2)) as ReceiptAmt,
                cast(sum(isnull(MB.ReceiptForAmt,0))/$unit as numeric(20,2)) as ReceiptForAmt
                from TMADept00 MA With(Nolock) 
                left join TSAPlanYMM00 MB With(Nolock) on MA.DeptCd = MB.DeptCd
                where $_where and MB.CurrCd = '$currCd' and MA.DeptDiv2 = '$leader' group by MB.DeptCd,MB.CurrCd";
        $result = Db::connect(self::$Db)->query($sql);
        return $result;

    }

    public static function linkLeaderTarget($res, $date, $dateDiv, &$allRecord, $leaderNm, $dateRound, $leaderId, $dateItem, &$returnData,$langCode,$currItem)
    {

        //查询业绩
        $date = $date.'-12-31';
        $sql = "SET NOCOUNT ON; EXEC yudo.SSADayTotal_M3 '$date', '$langCode', $dateDiv, ''";
        $resRecord = Db::connect(self::$Db)->query($sql);
        //查询目标
        $targetRes = self::getTargetByLeader($date, $dateItem,$leaderId);
        //查询计划
        if($dateItem != 'd')$salesPlanRes = self::getSalesPlanByLeader($date, $dateItem,$leaderId);

        for ($i = 0; $i < count($res); $i++) {
            //计算某经理下所有部门的业绩总和
            for($s = 0;$s <count($resRecord);$s++){
                if(trim($res[$i]['MEmpID']) == trim($resRecord[$s]['MEmpID'])){
                    //当前
                    $allRecord[$leaderNm]['OrderAmt'] = bcadd($allRecord[$leaderNm]['OrderAmt'], $resRecord[$s]['OrderAmt'], 2);
                    $allRecord[$leaderNm]['InvoiceAmt'] = bcadd($allRecord[$leaderNm]['InvoiceAmt'], $resRecord[$s]['InvoiceAmt'], 2);
                    $allRecord[$leaderNm]['BillAmt'] = bcadd($allRecord[$leaderNm]['BillAmt'], $resRecord[$s]['BillAmt'], 2);
                    $allRecord[$leaderNm]['ReceiptAmt'] = bcadd($allRecord[$leaderNm]['ReceiptAmt'], $resRecord[$s]['ReceiptAmt'], 2);
                }
            }
        }
        //业绩当前数据转换空值
        empty($allRecord[$leaderNm]['OrderAmt']) ? $recordOrder = 0 : $recordOrder = $allRecord[$leaderNm]['ReceiptAmt'];
        empty($allRecord[$leaderNm]['InvoiceAmt']) ? $recordInvoice = 0 : $recordInvoice = $allRecord[$leaderNm]['InvoiceAmt'];
        empty($allRecord[$leaderNm]['BillAmt']) ? $recordBill = 0 : $recordBill = $allRecord[$leaderNm]['BillAmt'];
        empty($allRecord[$leaderNm]['ReceiptAmt']) ? $recordReceipt = 0 : $recordReceipt = $allRecord[$leaderNm]['ReceiptAmt'];
        //查询当前单位时间和前一个单位时间的目标
//        for ($i = 0; $i < count($dateRound); $i++) {
        $targetOrder = 0;
        $targetInvoice = 0;
        $targetBill = 0;
        $targetReceipt = 0;
        foreach ($targetRes as $t){
            empty($t['OrderAmt']) ? $targetOrder += 0 : $targetOrder += $t['OrderAmt'];
            empty($t['InvoiceAmt']) ? $targetInvoice += 0 : $targetInvoice += $t['InvoiceAmt'];
            empty($t['BillAmt']) ? $targetBill += 0 : $targetBill += $t['BillAmt'];
            empty($t['ReceiptAmt']) ? $targetReceipt += 0 : $targetReceipt += $t['ReceiptAmt'];
        }
        $planOrder = 0;
        $planInvoice = 0;
        $planBill = 0;
        $planReceipt = 0;
        foreach ($salesPlanRes as $plan){
            empty($plan['OrderAmt']) ? $planOrder += 0 : $planOrder += $plan['OrderAmt'];
            empty($plan['InvoiceAmt']) ? $planInvoice += 0 : $planInvoice += $plan['InvoiceAmt'];
            empty($plan['BillAmt']) ? $planBill += 0 :$planBill += $plan['BillAmt'];
            empty($plan['ReceiptAmt']) ? $planReceipt += 0 : $planReceipt += $plan['ReceiptAmt'];
        }
        //组合数据
        $returnData['name'] = $leaderNm;
        $returnData['orderAmt'] = array(
            'target' => $targetOrder,
            'plan'   => $planOrder,
            'salesRecord' => $recordOrder,
            'percent' => round($targetOrder == 0 ? 0 : $recordOrder / $targetOrder * 100, 2) . '%',
            'percent2' => round($planOrder == 0 ? 0 : $recordOrder / $planOrder * 100, 2) . '%',
            'percent3' => round($planOrder == 0 ? 0 : $targetOrder / $planOrder * 100, 2) . '%',
            'date' => $dateRound[0],
        );
        $returnData['InvoiceAmt'] = array(
            'target' => $targetInvoice,
            'plan'   => $planInvoice,
            'salesRecord' => $recordInvoice,
            'percent' => round($targetInvoice == 0 ? 0 : $recordInvoice / $targetInvoice * 100, 2) . '%',
            'percent2' => round($planInvoice == 0 ? 0 : $recordInvoice / $planInvoice * 100, 2) . '%',
            'percent3' => round($planInvoice == 0 ? 0 : $targetInvoice / $planInvoice * 100, 2) . '%',
            'date' => $dateRound[0],
        );
        $returnData['BillAmt'] = array(
            'target' => $targetBill,
            'plan'   => $planBill,
            'salesRecord' => $recordBill,
            'percent' => round($targetBill == 0 ? 0 : $recordBill / $targetBill * 100, 2) . '%',
            'percent2' => round($planBill == 0 ? 0 :$recordBill / $planBill * 100, 2) . '%',
            'percent3' => round($planBill == 0 ? 0 : $targetBill / $planBill * 100, 2) . '%',
            'date' => $dateRound[0],
        );
        $returnData['ReceiptAmt'] = array(
            'target' => $targetReceipt,
            'plan'   => $planReceipt,
            'salesRecord' => $recordReceipt,
            'percent' => round($targetReceipt == 0 ? 0 : $recordReceipt / $targetReceipt * 100, 2) . '%',
            'percent2' => round($planReceipt == 0 ? 0 : $recordReceipt / $planReceipt * 100, 2) . '%',
            'percent3' => round($planReceipt == 0 ? 0 : $targetReceipt / $planReceipt * 100, 2) . '%',
            'date' => $dateRound[0],
        );
//        }
    }

    //查询所有销售目标
    public static function getTarget($date,$dateItem,$currCd='RMB',$unit=10000){
        if($dateItem == 'd'){
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(10),MB.SAPlanDate,23) = '$date'";
        }else if($dateItem == 'm'){
            $date = substr($date,0,7);
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(7),MB.SAPlanDate,23) = '$date'";
        }else{
            $date = substr($date,0,4);
            $_tableNm = 'TSAPlanYMD00';
            $_where = "convert(varchar(4),MB.SAPlanDate,23) = '$date'";
        }
        $sql = "select MB.DeptCd,MB.CurrCd,
                cast(sum(isnull(MB.OrderAmt,0))/$unit as numeric(20,2)) as OrderAmt,
                cast(sum(isnull(MB.OrderForAmt,0))/$unit as numeric(20,2)) as OrderForAmt,
                cast(sum(isnull(MB.InvoiceAmt,0))/$unit as numeric(20,2)) as InvoiceAmt,
                cast(sum(isnull(MB.InvoiceForAmt,0))/$unit as numeric(20,2)) as InvoiceForAmt,
                cast(sum(isnull(MB.BillAmt,0))/$unit as numeric(20,2)) as BillAmt,
                cast(sum(isnull(MB.BillForAmt,0))/$unit as numeric(20,2)) as BillForAmt,
                cast(sum(isnull(MB.ReceiptAmt,0))/$unit as numeric(20,2)) as ReceiptAmt,
                cast(sum(isnull(MB.ReceiptForAmt,0))/$unit as numeric(20,2)) as ReceiptForAmt
                from TMADept00 MA 
                left join $_tableNm MB on MA.DeptCd = MB.DeptCd
                where $_where and MB.CurrCd = '$currCd' group by MB.DeptCd,MB.CurrCd";
        return Db::connect(self::$Db)->query($sql);
    }

    //查询所有销售计划
    public static function getSalesPlan($date,$dateItem,$currCd='RMB',$unit=10000){
        if($dateItem == 'm'){
            $date = substr($date,0,4).substr($date,5,2);
            $_where = "MB.SAPlanYM = '$date'";
        }else {
            $date = substr($date,0,4);
            $_where = 'LEFT(MB.SAPlanYM,4) = '."'$date'";
        }
        $sql = "select MB.DeptCd,MB.CurrCd,
                cast(sum(isnull(MB.OrderAmt,0))/$unit as numeric(20,2)) as OrderAmt,
                cast(sum(isnull(MB.OrderForAmt,0))/$unit as numeric(20,2)) as OrderForAmt,
                cast(sum(isnull(MB.InvoiceAmt,0))/$unit as numeric(20,2)) as InvoiceAmt,
                cast(sum(isnull(MB.InvoiceForAmt,0))/$unit as numeric(20,2)) as InvoiceForAmt,
                cast(sum(isnull(MB.BillAmt,0))/$unit as numeric(20,2)) as BillAmt,
                cast(sum(isnull(MB.BillForAmt,0))/$unit as numeric(20,2)) as BillForAmt,
                cast(sum(isnull(MB.ReceiptAmt,0))/$unit as numeric(20,2)) as ReceiptAmt,
                cast(sum(isnull(MB.ReceiptForAmt,0))/$unit as numeric(20,2)) as ReceiptForAmt
                from TMADept00 MA 
                left join TSAPlanYMM00 MB on MA.DeptCd = MB.DeptCd
                where $_where and MB.CurrCd = '$currCd' group by MB.DeptCd,MB.CurrCd";
        return Db::connect(self::$Db)->query($sql);
    }

    public static $targetList = array(
        'OrderAmt' => 0,
        'InvoiceAmt' => 0,
        'BillAmt' => 0,
        'ReceiptAmt' => 0,
        'OrderForAmt' => 0,
        'InvoiceForAmt' => 0,
        'BillForAmt' => 0,
        'ReceiptForAmt' => 0,
        'OrderAmt_Pre' => 0,
        'InvoiceAmt_Pre' => 0,
        'BillAmt_Pre' => 0,
        'ReceiptAmt_Pre' => 0,
    );

    public static function screenLeaderTarget($userId,$leaders, $mempIds, $date, $dateDiv, &$allRecord, $dateRound, $dateItem, &$returnData, $langCode, $currItem)
    {
        $auth = BaseModel::getAuth('WEI_2300', $userId);
        //查询经理管辖的部门
        $resDeptCd = SalesGoalModel::getDeptIdResults($userId, $auth);
        //查询业绩
        $sql = "SET NOCOUNT ON; EXEC yudo.SSADayTotal_M3 '$date', '$langCode', $dateDiv, ''";
        $resRecord = Db::connect(self::$Db)->query($sql);

        $targetRes = self::getTarget($dateRound[0], $dateItem);
        if ($dateItem != 'd') $salesPlanRes = self::getSalesPlan($dateRound[0], $dateItem);

        //遍历领导
        foreach ($leaders as $Lkey => $Lv) {
            //初始化目标map
            $targetRecord[$Lv['text']] = self::$targetList;
            //初始化计划map
            $planRecord[$Lv['text']] = self::$targetList;
            //初始化业绩map
            $allRecord[$Lv['text']] = self::$targetList;
            //遍历经理
            $n = 0;
            foreach ($mempIds as $k => $v) {
                //遍历业绩
                for ($s = 0; $s < count($resRecord); $s++) {
                    if ((trim($v['MEmpID']) == trim($resRecord[$s]['MEmpID'])) && trim($v['DeptDiv2']) == trim($Lv['value'])) {
                        $allRecord[$Lv['text']]['OrderAmt'] = bcadd($allRecord[$Lv['text']]['OrderAmt'], $resRecord[$s]['OrderAmt'], 2);
                        $allRecord[$Lv['text']]['InvoiceAmt'] = bcadd($allRecord[$Lv['text']]['InvoiceAmt'], $resRecord[$s]['InvoiceAmt'], 2);
                        $allRecord[$Lv['text']]['BillAmt'] = bcadd($allRecord[$Lv['text']]['BillAmt'], $resRecord[$s]['BillAmt'], 2);
                        $allRecord[$Lv['text']]['ReceiptAmt'] = bcadd($allRecord[$Lv['text']]['ReceiptAmt'], $resRecord[$s]['ReceiptAmt'], 2);
                        //上一个
                        $allRecord[$Lv['text']]['OrderAmt_Pre'] = bcadd($allRecord[$Lv['text']]['OrderAmt_Pre'], $resRecord[$s]['OrderAmt_Pre'], 2);
                        $allRecord[$Lv['text']]['InvoiceAmt_Pre'] = bcadd($allRecord[$Lv['text']]['InvoiceAmt_Pre'], $resRecord[$s]['InvoiceAmt_Pre'], 2);
                        $allRecord[$Lv['text']]['BillAmt_Pre'] = bcadd($allRecord[$Lv['text']]['BillAmt_Pre'], $resRecord[$s]['BillAmt_Pre'], 2);
                        $allRecord[$Lv['text']]['ReceiptAmt_Pre'] = bcadd($allRecord[$Lv['text']]['ReceiptAmt_Pre'], $resRecord[$s]['ReceiptAmt_Pre'], 2);
                    }
                }
                //获取目标
                foreach ($targetRes as $t) {
                    foreach ($resDeptCd as $itemDeptCd) {
                        //如果匹配到目标中的部门，则取出经理ID对比是否加入计算
                        if ($itemDeptCd['DeptCd'] == $t['DeptCd']) {
                            //如果当前部门的经理匹配，则计算
                            if ($v['MEmpID'] == $itemDeptCd['value'] && $v['DeptDiv2'] == $Lv['value']) {
                                empty($t['OrderAmt']) ? $targetRecord[$Lv['text']]['OrderAmt'] += 0 : $targetRecord[$Lv['text']]['OrderAmt'] += $t['OrderAmt'];
                                empty($t['InvoiceAmt']) ? $targetRecord[$Lv['text']]['InvoiceAmt'] += 0 : $targetRecord[$Lv['text']]['InvoiceAmt'] += $t['InvoiceAmt'];
                                empty($t['BillAmt']) ? $targetRecord[$Lv['text']]['BillAmt'] += 0 : $targetRecord[$Lv['text']]['BillAmt'] += $t['BillAmt'];
                                empty($t['ReceiptAmt']) ? $targetRecord[$Lv['text']]['ReceiptAmt'] += 0 : $targetRecord[$Lv['text']]['ReceiptAmt'] += $t['ReceiptAmt'];
                            }
                        }
                    }
                }
                //获取计划
                foreach ($salesPlanRes as $plan) {
                    foreach ($resDeptCd as $itemDeptCd) {
                        //如果匹配到计划中的部门，则取出经理ID对比是否加入计算
                        if ($itemDeptCd['DeptCd'] == $plan['DeptCd']) {
                            //如果当前部门的经理匹配，则计算
                            if ($v['MEmpID'] == $itemDeptCd['value'] && $v['DeptDiv2'] == $Lv['value']) {
                                empty($plan['OrderAmt']) ? $planRecord[$Lv['text']]['OrderAmt'] += 0 : $planRecord[$Lv['text']]['OrderAmt'] += $plan['OrderAmt'];
                                empty($plan['InvoiceAmt']) ? $planRecord[$Lv['text']]['InvoiceAmt'] += 0 : $planRecord[$Lv['text']]['InvoiceAmt'] += $plan['InvoiceAmt'];
                                empty($plan['BillAmt']) ? $planRecord[$Lv['text']]['BillAmt'] += 0 : $planRecord[$Lv['text']]['BillAmt'] += $plan['BillAmt'];
                                empty($plan['ReceiptAmt']) ? $planRecord[$Lv['text']]['ReceiptAmt'] += 0 : $planRecord[$Lv['text']]['ReceiptAmt'] += $plan['ReceiptAmt'];
                            }
                        }
                    }
                }
            }

            //今日数据组合--------------
            $targetOrder = $targetRecord[$Lv['text']]['OrderAmt'];
            $targetInvoice = $targetRecord[$Lv['text']]['InvoiceAmt'];
            $targetBill = $targetRecord[$Lv['text']]['BillAmt'];
            $targetReceipt = $targetRecord[$Lv['text']]['ReceiptAmt'];

            $planOrder = $planRecord[$Lv['text']]['OrderAmt'];
            $planInvoice = $planRecord[$Lv['text']]['InvoiceAmt'];
            $planBill = $planRecord[$Lv['text']]['BillAmt'];
            $planReceipt = $planRecord[$Lv['text']]['ReceiptAmt'];

            $recordOrder = $allRecord[$Lv['text']]['OrderAmt'];
            $recordInvoice = $allRecord[$Lv['text']]['InvoiceAmt'];
            $recordBill = $allRecord[$Lv['text']]['BillAmt'];
            $recordReceipt = $allRecord[$Lv['text']]['ReceiptAmt'];
            //组合数据,每次输出一个经理的目标、业绩,0今日，1昨日
            $returnData[$Lkey]['name'] = $Lv['text'];
            $returnData[$Lkey]['orderAmt'] = array(
                'target' => $targetOrder,
                'plan' => $planOrder,
                'salesRecord' => $recordOrder,
                'percent' => round($targetOrder == 0 ? 0 : $recordOrder / $targetOrder * 100, 2) . '%',
                'percent2' => round($planOrder == 0 ? 0 : $recordOrder / $planOrder * 100, 2) . '%',
                'percent3' => round($planOrder == 0 ? 0 : $targetOrder / $planOrder * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$Lkey]['InvoiceAmt'] = array(
                'target' => $targetInvoice,
                'plan' => $planInvoice,
                'salesRecord' => $recordInvoice,
                'percent' => round($targetInvoice == 0 ? 0 : $recordInvoice / $targetInvoice * 100, 2) . '%',
                'percent2' => round($planInvoice == 0 ? 0 : $recordInvoice / $planInvoice * 100, 2) . '%',
                'percent3' => round($planInvoice == 0 ? 0 : $targetInvoice / $planInvoice * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$Lkey]['BillAmt'] = array(
                'target' => $targetBill,
                'plan' => $planBill,
                'salesRecord' => $recordBill,
                'percent' => round($targetBill == 0 ? 0 : $recordBill / $targetBill * 100, 2) . '%',
                'percent2' => round($planBill == 0 ? 0 : $recordBill / $planBill * 100, 2) . '%',
                'percent3' => round($planBill == 0 ? 0 : $targetBill / $planBill * 100, 2) . '%',
                'date' => $dateRound[0],
            );
            $returnData[$Lkey]['ReceiptAmt'] = array(
                'target' => $targetReceipt,
                'plan' => $planReceipt,
                'salesRecord' => $recordReceipt,
                'percent' => round($targetReceipt == 0 ? 0 : $recordReceipt / $targetReceipt * 100, 2) . '%',
                'percent2' => round($planReceipt == 0 ? 0 : $recordReceipt / $planReceipt * 100, 2) . '%',
                'percent3' => round($planReceipt == 0 ? 0 : $targetReceipt / $planReceipt * 100, 2) . '%',
                'date' => $dateRound[0],
            );
        }
    }

    public static function getDeptIdResults($userId,$auth)
    {
        if($auth== 'SM00040002'){
            $sql = "select A.DeptNm as text,A.MEmpID as value,A.DeptCd from TMADept00 A
                left join TMAEmpy00 B ON A.MEmpID = B.EmpID
                where A.MEmpID = '$userId'
                group by A.MEmpID,A.DeptCd,A.DeptNm";
        }else if($auth == 'SM00040005'){
            $sql = "select A.DeptNm as text,A.MEmpID as value,A.DeptCd from TMADept00 A
                left join TMAEmpy00 B ON A.MEmpID = B.EmpID
                where A.DeptDiv2 = (select MinorCd from TSMSyco10 where RelCd1 = '$userId')
                group by A.MEmpID,A.DeptCd,A.DeptNm";
        }else if($auth == 'SM00040001' ){
            $sql = "select A.DeptNm as text,A.MEmpID as value,A.DeptCd from TMADept00 A
                left join TMAEmpy00 B ON A.MEmpID = B.EmpID
                where left(A.DeptDiv2,6) = 'MA1004'
                group by A.MEmpID,A.DeptCd,A.DeptNm";
        }
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    public static function getHuilv($currCd,$currentYm=''){
        if(!$currentYm){
            $currentYm = date('Ym', time());
        }
        $result = Db::table('TMACurr10')
            ->where([
                'CurrCd' => $currCd,
                'YYMM' => $currentYm,
            ])
            ->find();
        return $result;
    }

    public static function isConfirm($currCd,$date,$expClass,$userId){
        $result = Db::table('TSAPlanYMD10')
            ->where([
                'CurrCd' => $currCd,
                'SAPlanDate' => $date,
                'ExpClss' => $expClass,
                'EmpID' => $userId,
                'CfmYn'=> 1
            ])
            ->find();
        return (bool) $result;
    }

    public static function getAdminList($mEmpId=''){
        if(empty($mEmpId)){
            $sql = "select MEmpID,DeptDiv2 from TMADept00 where DeptDiv2 Like 'MA1004%%'  group by MEmpID,DeptDiv2";
        }else{
            $sql = "select MEmpID,DeptDiv2 from TMADept00 where DeptDiv2 = '$mEmpId' group by MEmpID,DeptDiv2";
        }
        $result = Db::connect(self::$Db)->query($sql);
        return $result;
    }

    public static function getSalesLeaders($langCode)
    {
        $sql = "SELECT 
            ISNULL(MULTIB.TransNm, MULTIA.MinorNm) AS text,
            ISNULL(MULTIB.DictCd, MULTIA.MinorCd) AS value 
            FROM TSMSyco10 MULTIA
            FULL JOIN TSMDict10 MULTIB ON MULTIA.MinorCd = MULTIB.DictCd AND MULTIB.LangCd = '{$langCode}'
            WHERE DeleteYn = 'N' 
            AND MULTIA.MinorCd != 'MA10040300'
            AND MULTIA.MinorCd != 'MA10040400'
            AND MULTIA.MinorCd != 'MA10040600'
            AND LEFT(MULTIA.MinorCd,6) = 'MA1004'";
        try {
            $result = Db::connect(self::$Db)->query($sql);
            return $result;
        } catch (\InvalidArgumentException $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    public static function getMempId($userId)
    {
        $auth = parent::getAuth('WEI_2300',$userId);
        if($auth=='NO' || $auth=='SM00040003' || $auth=='SM00040002'){
            return [];
        }
        if($auth=='SM00040001'){
            $sql = "select B.EmpNm as text,A.MEmpID as value from TMADept00 A
                    left join TMAEmpy00 B ON A.MEmpID = B.EmpID
                    where left(A.DeptDiv2,6) = 'MA1004' group by B.EmpNm,A.MEmpID";
        }elseif($auth=='SM00040005'){
            $sql = "select B.EmpNm as text,A.MEmpID as value from TMADept00 A
                    left join TMAEmpy00 B ON A.MEmpID = B.EmpID
                    where A.DeptDiv2 = (select MinorCd from TSMSyco10 where RelCd1 = '$userId') 
                    group by B.EmpNm,A.MEmpID";
        }
        return  Db::connect(self::$Db)->query($sql);
    }

    public static function getDeptId($userId,$flag=true)
    {
        $auth = parent::getAuth('WEI_2300',$userId);
        if($auth=='NO' || $auth=='SM00040003'){
            if($flag){
                $info = UserModel::getUserDeptInfo($userId);
                $data['text'] = $info[0]['DeptNm'];
                $data['value'] = $info[0]['DeptCd'];
                return [$data];
            }
            return [];
        }
        if($auth == 'SM00040002'){
//            $sql = "select A.DeptCd AS value,B.DeptNm AS text from dbo.fnMDeptCd('y','{$userId}') A
//                    left join TMADept00 B on A.DeptCd = B.DeptCd";
                $sql = "select A.DeptNm as text,A.DeptCd as value from TMADept00 A
                left join TMAEmpy00 B ON A.MEmpID = B.EmpID
                where A.MEmpID = '{$userId}'
                group by A.DeptCd,A.DeptNm";


        }else if($auth == 'SM00040005'){
            $sql = "select A.DeptNm as text,A.DeptCd as value from TMADept00 A
                    left join TMAEmpy00 B ON A.MEmpID = B.EmpID
                    where A.DeptDiv2 = (select MinorCd from TSMSyco10 where RelCd1 = '{$userId}')
                    group by A.DeptCd,A.DeptNm";
        }else if($auth == 'SM00040001' ){
            $sql = "select DeptNm as text,DeptCd as value from TMADept00
                    where LEFT(DeptDiv2,6) = 'MA1004' GROUP BY DeptNm,DeptCd";
        }
        return  Db::connect(self::$Db)->query($sql);
    }
}