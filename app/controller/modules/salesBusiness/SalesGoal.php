<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-21 14:09
 */

namespace app\controller\modules\salesBusiness;

use app\controller\modules\Base;
use app\model\BaseModel;
use app\model\MenuModel;
use app\model\salesBusiness\salesGoalModel;
use app\model\UserModel;
use think\Exception;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

class SalesGoal extends Base
{

//    public function getUserId()
//    {
//        return 'S2006005';  //魏源中
//        return 'S2010009';  //孙东明
//        return 'S2012028';  //郭邦辉
//        return 'S2008018';  //孙亚周
//    }

    public function getResult()
    {
        $post = Request::post();
        $langCode = Config::get('langCode.' . Request::post('langID'));
        $gunbun = $post['gunbun'];
        $date = $post['date'];
        $type = $post['type'];
        $value = $post['value'];
        $text = $post['text'];
//        dump($type);die;
        switch ($type) {
            case 0:
                $result =  $this->leaderTarget($gunbun,$date,$text,$value,$langCode);
                break;
            case 1:
                $result =  $this->getMempIdTarget($gunbun,$date,$text,$value,$langCode);
                break;
            case 2:
                $result =  $this->getDeptIdTarget($gunbun,$date,$text,$value,$langCode);
                break;
            case 3:
                $result =  $this->getUserTarget($gunbun,$date,$text,$value,$langCode);
                break;
            default:
                break;
        }
        $result = $this->preResult($result);
        $fieldsToRound = ['BillAmt', 'InvoiceAmt', 'ReceiptAmt', 'orderAmt'];
        $lastIndex = count($result) - 1;
        foreach ($fieldsToRound as $field) {
            if (isset($result[$lastIndex][$field]['salesRecord'])) {
                $result[$lastIndex][$field]['salesRecord'] = round($result[$lastIndex][$field]['salesRecord'], 2);
            }
        }
        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>$result
        ]);
    }

    public function preResult($result)
    {
        $total = $this->initializeTotal();

        foreach ($result as &$item) {
            // 格式化和汇总
            foreach (['orderAmt', 'InvoiceAmt', 'BillAmt', 'ReceiptAmt'] as $key) {
                $this->formatAndSum($item[$key], $total[$key]);
            }
        }

        // 计算和格式化总计数据
        foreach (['orderAmt', 'InvoiceAmt', 'BillAmt', 'ReceiptAmt'] as $key) {
            $this->calculatePercentages($total[$key]);
            $this->formatTotals($total[$key]);
        }
        $result[] = $total;
        return $result;
    }

    private function initializeTotal()
    {
        $default = [
            'plan' => 0,
            'salesRecord' => 0,
            'target' => 0,
            'percent' => 0,
            'percent2' => 0,
            'percent3' => 0,
            'planFormat' => 0,
            'salesRecordFormat' => 0,
            'targetFormat' => 0,
            'percentFormat' => 0,
            'percent2Format' => 0,
            'percent3Format' => 0,
        ];

        return [
            'name' => 'Total',
            'orderAmt' => $default,
            'InvoiceAmt' => $default,
            'BillAmt' => $default,
            'ReceiptAmt' => $default,
        ];
    }

    private function formatAndSum(&$item, &$total)
    {
        // 格式化数据
        if(isset($item['plan'])){
            $item['planFormat'] = self::formatAmt($item['plan']);
        }
        if(isset($item['salesRecord'])){
            $item['salesRecordFormat'] = self::formatAmt($item['salesRecord']);
        }
        if(isset($item['target'])){
            $item['targetFormat'] = self::formatAmt($item['target']);
        }
        if(isset($item['percent'])){
            $item['percentFormat'] = self::formatAmt(substr($item['percent'], 0, -1));
        }
        if(isset($item['percent2'])){
            $item['percent2Format'] = self::formatAmt(substr($item['percent2'], 0, -1));
        }
        if(isset($item['percent3'])){
            $item['percent3Format'] = self::formatAmt(substr($item['percent3'], 0, -1));
        }

        // 汇总数据
        if(isset($item['plan'])){
            $total['plan'] += $item['plan'];
        }
        if(isset($item['salesRecord'])){
            $total['salesRecord'] += $item['salesRecord'];
        }
        if(isset($item['target'])){
            $total['target'] += $item['target'];
        }

    }

    private function calculatePercentages(&$total)
    {
        // 计算百分比，分母或分子为 0 时直接返回 0
        $total['percent'] = ($total['salesRecord'] > 0 && $total['target'] > 0)
            ? ($total['salesRecord'] / $total['target']) * 100
            : 0;

        $total['percent2'] = ($total['salesRecord'] > 0 && $total['plan'] > 0)
            ? ($total['salesRecord'] / $total['plan']) * 100
            : 0;

        $total['percent3'] = ($total['target'] > 0 && $total['plan'] > 0)
            ? ($total['target'] / $total['plan']) * 100
            : 0;
    }



    private function formatTotals(&$total)
    {
        // 格式化总计数据
        $total['planFormat'] = self::formatAmt($total['plan']);
        $total['salesRecordFormat'] = self::formatAmt($total['salesRecord']);
        $total['targetFormat'] = self::formatAmt($total['target']);

        // 百分比格式化并加上 `%`
        $total['percentFormat'] = number_format($total['percent'], 2);
        $total['percent2Format'] = number_format($total['percent2'], 2);
        $total['percent3Format'] = number_format($total['percent3'], 2);
    }




    public function leaderTarget($gunbun,$date,$leaderNm,$leaderId,$langCode)
    {
        if($gunbun=='y'){
            $date = $date.'-12-31';
            $year = date('Y',strtotime($date));
            $dateRound = [$year,$year-1];
        }elseif($gunbun=='m'){
            $currentDate = new \DateTime($date . '-01');
            $dateRound = [
                $currentDate->format('Y-m'),
                $currentDate->modify('-1 month')->format('Y-m')
            ];
            $date = $currentDate->modify('+1 month')->format('Y-m-t');
        }else{
            $currentDate = new \DateTime($date);
            $previousDay = (clone $currentDate)->modify('-1 day')->format('Y-m-d');
            $dateRound = [$date, $previousDay];
        }
        $userId = $this->getUserId();
        $auth = BaseModel::getAuth('WEI_2300', $this->getUserId());
        if ($auth !== 'SM00040001') {
            return [];
        }
        if ($gunbun == 'd') $dateDiv = 1;
        if ($gunbun == 'm') $dateDiv = 2;
        if ($gunbun == 'y') $dateDiv = 3;

        $allRecord = array();
        $returnData = array();
        if ($leaderId == 'ALL') {
            $leaders = SalesGoalModel::getSalesLeaders($langCode);
            $mempIds = SalesGoalModel::getAdminList();
            SalesGoalModel::screenLeaderTarget($userId,$leaders, $mempIds, $date, $dateDiv, $allRecord, $dateRound, $gunbun, $returnData, $langCode, '');
        } else {
            $allRecord[$leaderNm] = SalesGoalModel::$targetList;
            $mempId = SalesGoalModel::getAdminList($leaderId);
            SalesGoalModel::linkLeaderTarget($mempId, $date, $dateDiv, $allRecord, $leaderNm, $dateRound, $leaderId, $gunbun, $returnData[0], $langCode,'');
        }
        return $returnData;
    }

    public function getMempIdTarget($dateItem,$date,$mempNm,$mempId,$langCode)
    {
        if($dateItem=='y'){
            $date = $date.'-12-31';
            $year = date('Y',strtotime($date));
            $dateRound = [$year,$year-1];
        }elseif($dateItem=='m'){
            $currentDate = new \DateTime($date . '-01');
            $dateRound = [
                $currentDate->format('Y-m'),
                $currentDate->modify('-1 month')->format('Y-m')
            ];
            $date = $currentDate->modify('+1 month')->format('Y-m-t');
        }else{
            $currentDate = new \DateTime($date);
            $previousDay = (clone $currentDate)->modify('-1 day')->format('Y-m-d');
            $dateRound = [$date, $previousDay];
        }
        $currItem = '';
        $userId = $this->getUserId();
        $auth = BaseModel::getAuth('WEI_2300', $this->getUserId());
        if ($auth !== 'SM00040001' && $auth !== 'SM00040005') {
            return [];
        }
        if ($dateItem == 'd') {
            $dateDiv = '1';
        } else if ($dateItem == 'm') {
            $dateDiv = '2';
        } else {
            $dateDiv = '3';
        }
        $allRecord = array();
        $returnData = array();

        $mempids = SalesGoalModel::getMempId($userId);
        SalesGoalModel::screenMempIdTarget($userId,$mempids, $date, $dateDiv, $allRecord, $dateRound,$dateItem,$returnData,$langCode,$currItem);
        if ($mempId  == 'ALL' ) {
            return $returnData;

        } else {
            $result = [];
            foreach($returnData as $item){
                if($item['name']==$mempNm){
                    $result[] = $item;
                    break;
                }
            }
            return $result;
        }

    }

    public function getDeptIdTarget($dateItem,$date,$deptNm,$deptId,$langCode)
    {
        if($dateItem=='y'){
            $date = $date.'-12-31';
            $year = date('Y',strtotime($date));
            $dateRound = [$year,$year-1];
        }elseif($dateItem=='m'){
            $currentDate = new \DateTime($date . '-01');
            $dateRound = [
                $currentDate->format('Y-m'),
                $currentDate->modify('-1 month')->format('Y-m')
            ];
            $date = $currentDate->modify('+1 month')->format('Y-m-t');
        }else{
            $currentDate = new \DateTime($date);
            $previousDay = (clone $currentDate)->modify('-1 day')->format('Y-m-d');
            $dateRound = [$date, $previousDay];
        }
        $currItem = '';
        $userId = $this->getUserId();
        $auth = BaseModel::getAuth('WEI_2300', $userId);
        if ($auth !== 'SM00040001' && $auth !== 'SM00040005' && $auth !== 'SM00040002') {
            return [];
        }
        if ($dateItem == 'd') {
            $dateDiv = '1';
        } else if ($dateItem == 'm') {
            $dateDiv = '2';
        } else {
            $dateDiv = '3';
        }
        $allRecord = array();
        $returnData = array();
        $deptIds = SalesGoalModel::getDeptId($userId);
        SalesGoalModel::screenDeptIdTarget($userId,$deptIds, $date, $dateDiv, $allRecord, $dateRound, $dateItem, $returnData,$langCode,$currItem);
        if ($deptId == 'ALL') {
            return $returnData;

        } else {
            $result = [];
            foreach($returnData as $item){
                if($item['name']==$deptNm){
                    $result[] = $item;
                }
            }
            return $result;
        }

    }

    public function getPremission()
    {
        $userId = $this->getUserId();
        $data = BaseModel::getAuth('WEI_2300',$userId);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$data
        ]);
    }

    public function getUserTarget($dateItem,$date,$deptNm,$deptId,$langCode)
    {
        if($dateItem=='y'){
            $date = $date.'-12-31';
            $year = date('Y',strtotime($date));
            $dateRound = [$year,$year-1];
        }elseif($dateItem=='m'){
            $currentDate = new \DateTime($date . '-01');
            $dateRound = [
                $currentDate->format('Y-m'),
                $currentDate->modify('-1 month')->format('Y-m')
            ];
            $date = $currentDate->modify('+1 month')->format('Y-m-t');
        }else{
            $currentDate = new \DateTime($date);
            $previousDay = (clone $currentDate)->modify('-1 day')->format('Y-m-d');
            $dateRound = [$date, $previousDay];
        }
        $userId = $this->getUserId();
        $currItem = '';
        if ($dateItem == 'd') {
            $dateDiv = '1';
        } else if ($dateItem == 'm') {
            $dateDiv = '2';
        } else {
            $dateDiv = '3';
        }
        $allRecord = array();
        $returnData = array();
        if(!empty($deptId)){
            SalesGoalModel::linkUserIdTarget($userId,$deptId, $date, $dateDiv, $allRecord, $dateRound, $dateItem, $returnData,$langCode,$currItem);
        }
        return $returnData;
    }

    public function salesLeaders()
    {
        $post = Request::post();
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $userId = $this->getUserId();
        $auth = BaseModel::getAuth('WEI_2300',$userId);
        if($auth=='SM00040001'){        //管理
            $result = salesGoalModel::getSalesLeaders($langCode);
//        }
//        elseif($auth=='SM00040005'){        //部长
//            $leaders = salesGoalModel::getSalesLeaders($langCode);
//            $userName =UserModel::getUser($userId)['user_name'];
//            $result = [];
//            foreach($leaders as $item){
//                if(trim($item['text'])==trim($userName)){
//                    $result[] = $item;
//                    break;
//                }
//            }
        }else{  //没权限
            $result = [];
        }

        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getMempId()
    {
        $post = Request::post();
        $userId = $this->getUserId();
        $result = salesGoalModel::getMempId($userId);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getDeptId()
    {
        $post = Request::post();
        $userId = $this->getUserId();
        $result = salesGoalModel::getDeptId($userId);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function saveGoal()
    {
        $regUserId = $this->getUserId();
        $post = Request::post();
        $date = $post['date'];
        $userId = $post['userId'];
        $deptId = $post['deptId'];
        $currCd = $post['currCd'];
        $expClass = $post['expClass'];
        $orderAmt = $post['orderAmt'];
        $invoiceAmt = $post['invoiceAmt'];
        $billAmt = $post['billAmt'];
        $receiptAmt = $post['receiptAmt'];
        $huilvInfo = salesGoalModel::getHuilv($currCd);
        if(!$huilvInfo){
            //提示没有当月汇率
        }
        if($currCd=='RMB'){
            $orderForAmt = $orderAmt;
            $invoiceForAmt = $invoiceAmt;
            $billForAmt = $billAmt;
            $receiptForAmt = $receiptAmt;
        }else{
            $orderForAmt = round(bcdiv($orderAmt, $huilvInfo['BasicStdRate'], 10) * 100, 2);
            $invoiceForAmt = round(bcdiv($invoiceAmt, $huilvInfo['BasicStdRate'], 10) * 100, 2);
            $billForAmt = round(bcdiv($billAmt, $huilvInfo['BasicStdRate'], 10) * 100, 2);
            $receiptForAmt = round(bcdiv($receiptAmt, $huilvInfo['BasicStdRate'], 10) * 100, 2);
        }
        //判断是否确定
        $checkConfirm = salesGoalModel::isConfirm($currCd,$date,$expClass,$userId);
        if($checkConfirm){
            return json(['statusCode'=>self::getCode('DB_INSTALL_FAIL'),'result'=>'数据已确定']);
        }else{
            $where = [
                'CurrCd' => $currCd,
                'SAPlanDate' => $date,
                'ExpClss' => $expClass,
                'EmpID' => $userId,
                'CfmYn'=> 0
            ];
            //查找是否有本条记录,有更新,没有就添加
            $data = Db::table('TSAPlanYMD10')
                ->where($where)
                ->find();
            if($data){
                try{
                    $result = Db::table('TSAPlanYMD10')
                        ->where($where)
                        ->update([
                            'OrderAmt'   => $orderAmt,
                            'OrderForAmt' => $orderForAmt,
                            'InvoiceAmt'  => $invoiceAmt,
                            'InvoiceForAmt' => $invoiceForAmt,
                            'BillAmt' => $billAmt,
                            'BillForAmt' => $billForAmt,
                            'ReceiptAmt' => $receiptAmt,
                            'ReceiptForAmt' => $receiptForAmt,
                            'UptEmpID' => $regUserId,
                            'UptDate' => Db::raw('GETDATE()')
                        ]);
                    return json(['statusCode'=>self::getCode('DB_UPDATE_SUCCESS'),'msg'=>$result]);
                }catch(\Exception $e){
                    return json(['statusCode'=>self::getCode('DB_OPERATION_FAIL'),'msg'=>$e->getMessage()]);
                }
            }else{
                try{
                    $result = Db::table('TSAPlanYMD10')
                        ->insert([
                            'ExpClss' => $expClass,
                            'DeptCd'  => $deptId,
                            'EmpID'   => $userId,
                            'CurrCd'  => $currCd,
                            'SAPlanDate' => $date,
                            'OrderAmt'   => $orderAmt,
                            'OrderForAmt' => $orderForAmt,
                            'InvoiceAmt'  => $invoiceAmt,
                            'InvoiceForAmt' => $invoiceForAmt,
                            'BillAmt' => $billAmt,
                            'BillForAmt' => $billForAmt,
                            'ReceiptAmt' => $receiptAmt,
                            'ReceiptForAmt' => $receiptForAmt,
                            'RegEmpID' => $regUserId,
                            'RegDate' => Db::raw('GETDATE()'),
                            'UptEmpID' => $regUserId,
                            'UptDate' => Db::raw('GETDATE()')
                        ]);
                    return json(['statusCode'=>self::getCode('DB_INSERT_SUCCESS'),'msg'=>$result]);
                }catch (\Exception $e){
                    return json(['statusCode'=>self::getCode('DB_OPERATION_FAIL'),'msg'=>$e->getMessage()]);
                }
            }
        }
    }

    public function confirm()
    {
        $regUserId = $this->getUserId();
        $post = Request::post();
        $date = $post['date'];
        $userId = $post['userId'];
        $deptId = $post['deptId'];
        $currCd = $post['currCd'];
        $expClass = $post['expClass'];
        $where = [
            'ExpClss' => $expClass,
            'DeptCd'  => $deptId,
            'EmpID'   => $userId,
            'CurrCd'  => $currCd,
            'SAPlanDate' => $date,
        ];
        $data = Db::table('TSAPlanYMD10')
            ->where($where)->find();
        if(!$data){
            return json(['statusCode'=>self::getCode('DB_UPDATE_FAIL')]);
        }else{
            if($data['CfmYn']){
                return json(['statusCode'=>self::getCode('DATA_ALREADY_UPDATED')]);
            }else{
                try{
                    Db::table('TSAPlanYMD10')
                        ->where($where)
                        ->update([
                            'CfmYn'=>1,
                            'CfmEmpId'=>$regUserId,
                            'CfmDate'=>Db::raw('GETDATE()')
                        ]);
                    return json(['statusCode'=>self::getCode('DB_UPDATE_SUCCESS')]);
                }catch (\Exception $e){
                    return json(['statusCode'=>self::getCode('DB_OPERATION_FAIL')]);
                }

            }
        }
    }
}