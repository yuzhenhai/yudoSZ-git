<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-06 9:03
 */

namespace app\model\salesManage;


use app\model\BaseModel;
use app\model\UserModel;
use think\facade\Db;
use DateTime;
use think\facade\Request;

class AreaMarketAnalysisModel extends BaseModel
{
    public static function getLists($gubun,$date,$langCode)
    {
        $dateObj = new DateTime($date);
        $lastDayOfMonth = $dateObj->modify('last day of this month')->format('Ymd');
        $sameDayLastYear = $dateObj->modify('-1 year')->format('Ymd');
        return Db::connect(self::$DevDb)->query("EXEC SSAMarket_SZ_M2 ?, ?, ?, ?, ?", [$gubun,$date,$lastDayOfMonth,$sameDayLastYear,$langCode]);
    }

    public static function preLists($result, $langCode = '')
    {
        // 初始化汇总变量
        $totalOrderAmount = 0.0;
        $totalOrderAmountPre = 0.0;
        $carLightsAmount = 0.0;
        $carLightsAmountPre = 0.0;
        $packagingAmount = 0.0;
        $packagingAmountPre = 0.0;
        $medicalAmount = 0.0;
        $medicalAmountPre = 0.0;
        $packagingText = '包装领域';  // 默认值
        $medicalText = '医疗领域';   // 默认值
        $aggregatedData = [];
        $formattedList = [];

        // 遍历原始数据进行汇总和筛选
        foreach ($result as $item) {
            // 汇总 OrderforAmt 和 OrderforAmt_pre
            $totalOrderAmount += isset($item['OrderforAmt']) ? floatval($item['OrderforAmt']) : 0;
            $totalOrderAmountPre += isset($item['OrderforAmt_pre']) ? floatval($item['OrderforAmt_pre']) : 0;

            // 按 marketcd 分类进行汇总
            if (isset($item['marketcd'])) {
                self::processMarketData($item, $carLightsAmount, $carLightsAmountPre, $packagingAmount, $packagingAmountPre, $medicalAmount, $medicalAmountPre, $packagingText, $medicalText);
            }
        }

        // 汇总领域数据
        $aggregatedData[] = [
            'marketcd' => 'SA10250010',
            'OrderforAmt' => $carLightsAmount,
            'OrderforAmt_pre' => $carLightsAmountPre,
            'MinorNm' => $langCode == 'SM00010003' ? '车灯领域' : 'Car Lights',
        ];

        $aggregatedData[] = [
            'marketcd' => 'SA10250030',
            'OrderforAmt' => $packagingAmount,
            'OrderforAmt_pre' => $packagingAmountPre,
            'MinorNm' => $packagingText,
        ];

        $aggregatedData[] = [
            'marketcd' => 'SA10250040',
            'OrderforAmt' => $medicalAmount,
            'OrderforAmt_pre' => $medicalAmountPre,
            'MinorNm' => $medicalText,
        ];

        // 防止除零错误
        $totalOrderAmountPre = max($totalOrderAmountPre, 1);  // 如果为零则设为1

        // 计算百分比并整理输出数据
        foreach ($aggregatedData as $dataItem) {
            // 计算百分比
            $percentageForCurrent = $totalOrderAmount > 0 ? ($dataItem['OrderforAmt'] / $totalOrderAmount)  : 0;
            $percentageForPrevious = $totalOrderAmountPre > 0 ? ($dataItem['OrderforAmt_pre'] / $totalOrderAmountPre)  : 0;

            // 添加到结果列表
            $formattedList[] = [
                'marketcd' => $dataItem['marketcd'],
                'DeptDiv1' => $dataItem['MinorNm'],
                'OrderForAmt' => (int)$dataItem['OrderforAmt_pre'],  // 转换为整数
                'OrderAmt' => (int)$dataItem['OrderforAmt'],  // 转换为整数
                'percentFor_to' => $percentageForPrevious,
                'percentFor' => $percentageForCurrent,
            ];
        }
        foreach($formattedList as &$item){
            $item['name'] = $item['DeptDiv1'];
            $item['amt'] = self::formatAmt($item['OrderAmt'] / 10000);
            $item['amtPre'] = self::formatAmt($item['OrderForAmt'] / 10000);
            $item['growthRate'] = $item['OrderForAmt'] != 0
                ? number_format(($item['OrderAmt'] - $item['OrderForAmt']) / $item['OrderForAmt'] * 100, 2)
                : 0;
            $item['rate'] =  number_format($item['percentFor'] * 100,2) ;
            $item['ratePre'] =  number_format($item['percentFor_to'] * 100,2) ;
            $item['rateColor'] = self::percentColor($item['growthRate']);

            unset($item['DeptDiv1'], $item['OrderForAmt'], $item['OrderAmt'], $item['percentFor_to'], $item['percentFor']);
        }
        return $formattedList;
    }

    // 处理特定 marketcd 的数据
    private static function processMarketData($item, &$carLightsAmount, &$carLightsAmountPre, &$packagingAmount, &$packagingAmountPre, &$medicalAmount, &$medicalAmountPre, &$packagingText, &$medicalText)
    {
        switch ($item['marketcd']) {
            case 'SA10250010':
                // 车灯领域的条件判断
                if (isset($item['PProductCd']) && isset($item['PPartCd'])) {
                    if ($item['PProductCd'] == 'SA10260010' && $item['PPartCd'] == 'SA10274280' ||
                        $item['PProductCd'] == 'SA10260020' && in_array($item['PPartCd'], ['SA10270150', 'SA10272740']) ||
                        $item['PProductCd'] == 'SA10261010') {
                        $carLightsAmount += floatval($item['OrderforAmt']);
                        $carLightsAmountPre += floatval($item['OrderforAmt_pre']);
                    }
                }
                break;

            case 'SA10250030':
                $packagingAmount += floatval($item['OrderforAmt']);
                $packagingAmountPre += floatval($item['OrderforAmt_pre']);
                $packagingText = $item['MinorNm'] ?: $packagingText; // 保持原值或使用默认值
                break;

            case 'SA10250040':
                $medicalAmount += floatval($item['OrderforAmt']);
                $medicalAmountPre += floatval($item['OrderforAmt_pre']);
                $medicalText = $item['MinorNm'] ?: $medicalText; // 保持原值或使用默认值
                break;
        }
    }


    public static function queryDetail($marketCd,$gunbun,$baseDate,$langCode)
    {
        $dateObj = new DateTime($baseDate);
        $lastDayOfMonth = $dateObj->modify('last day of this month')->format('Ymd');
        $sameDayLastYear = $dateObj->modify('-1 year')->format('Ymd');
        if($marketCd == 'SA10250010'){
            $result = Db::connect(self::$DevDb)->query(
                "EXEC SSAMarket_SZ_M3
                @pWorkingTag = '{$gunbun}',
                @pBaseDt = '{$baseDate}',
                @wToYear = '{$lastDayOfMonth}',
                @wLastyear = '{$sameDayLastYear}',
                @Marketcd = '{$marketCd}',
                @LangCd = '{$langCode}'"
            )[0];
        }else{
            $result = Db::connect(self::$DevDb)->query(
                "EXEC SSAMarket_SZ_M4
                @pWorkingTag = '{$gunbun}',
                @pBaseDt = '{$baseDate}',
                @wToYear = '{$lastDayOfMonth}',
                @wLastyear = '{$sameDayLastYear}',
                @Marketcd = '{$marketCd}',
                @LangCd = '{$langCode}'"
            )[0];
        }
        $result =  self::preResult($result,$langCode);
        return self::preResult2($result);
    }

    public static function preResult2($result)
    {
        $dept = $result['Dept'];
        $data = $result['data'];
        $div = $result['div'];
        $external = [];
        $internal = [];
        foreach($data as $item){
            if($item['ExternalGubun']==1){
                $external[] = $item;
            }else{
                $internal[] = $item;
            }
        }
        $_external = [];

        foreach ($dept as $item) {
            // 复制每个部门项，避免引用
            $newItem = $item;
            // 遍历数据，检查每个部门是否有子项
            foreach ($external as $value) {
                if ($value['MDeptCd'] == $item['DeptCd']) {
                    // 将符合条件的数据加入到 children 数组中
                    $newItem['children'][] = $value;
                }
            }
            // 如果有 children 数据才将部门项添加到 $_dept
            if (!empty($newItem['children'])) {
                $newItem['EmpID'] = rtrim($newItem['EmpID']);
                $_external[] = $newItem;
            }
        }
        $arr = [];
        foreach($_external as $item){
            foreach($item['children'] as $child){
                if($child['MEmpID']!=$item['EmpID']){
                    $item['parent'] = $child['MEmpNm'];
                    $item['parentId'] = $child['MEmpID'];
                }
            }
            $arr[] = $item;
        }

        $_external = $arr;
        $_internal = [];
        foreach($div as $item){
            $newItem = $item;
            foreach ($internal as $value) {
                if ($value['DeptDiv1'] == $item['DictCd']) {
                    $newItem['children'][] = $value;
                    $newItem['parent'] = $value['MEmpNm'];
                }
            }
            if (!empty($newItem['children'])) {
                $_internal[] = $newItem;
            }
        }

        foreach($_external as &$item){
            $item['Amt'] = $item['AmtPre'] = 0;
            foreach ($item['children'] as &$value) {
                $item['Amt'] += $value['Amt'];
                $item['AmtPre'] += $value['AmtPre'];
            }
            foreach($item['children'] as &$value){
                if (isset($value['AmtPre']) && $value['AmtPre'] != 0) {
                    $value['rate'] = self::formatAmt(($value['Amt'] - $value['AmtPre']) / $value['AmtPre'] * 100);
                } else {
                    // 如果 AmtPre 为零，设置默认值
                    $value['rate'] = '0.00';
                }
                $value['Amt'] = self::formatAmt($value['Amt']  / 10000);
                $value['AmtPre'] = self::formatAmt($value['AmtPre']  / 10000);
                $value['color'] = self::percentColor($value['rate']);

            }
            if (isset($item['AmtPre']) && $item['AmtPre'] != 0) {
                $item['rate'] = self::formatAmt(($item['Amt'] - $item['AmtPre']) / $item['AmtPre'] * 100);
            } else {
                // 如果 AmtPre 为零或未设置，设置默认值
                $item['rate'] = '0.00';
            }
            $item['Amt'] = self::formatAmt($item['Amt'] / 10000);
            $item['AmtPre'] = self::formatAmt($item['AmtPre']  / 10000);
            $item['color'] = self::percentColor($item['rate']);
        }
        unset($result['Dept'],$result['div'],$result['data'],$item,$value);
        foreach($_internal as &$item){
            $item['Amt'] = $item['AmtPre'] = 0;
            foreach ($item['children'] as &$value) {
                $item['Amt'] += $value['Amt'];
                $item['AmtPre'] += $value['AmtPre'];
            }
            foreach($item['children'] as &$value){
                if (isset($value['AmtPre']) && $value['AmtPre'] != 0) {
                    $value['rate'] = self::formatAmt(($value['Amt'] - $value['AmtPre']) / $value['AmtPre'] * 100);
                } else {
                    // 如果 AmtPre 为零，设置默认值
                    $value['rate'] = '0.00';
                }
                $value['Amt'] = self::formatAmt($value['Amt']  / 10000);
                $value['AmtPre'] = self::formatAmt($value['AmtPre']  / 10000);
                $value['color'] = self::percentColor($value['rate']);

            }
            if (isset($item['AmtPre']) && $item['AmtPre'] != 0) {
                $item['rate'] = self::formatAmt(($item['Amt'] - $item['AmtPre']) / $item['AmtPre'] * 100);
            } else {
                // 如果 AmtPre 为零或未设置，设置默认值
                $item['rate'] = '0.00';
            }
            $item['Amt'] = self::formatAmt($item['Amt'] / 10000);
            $item['AmtPre'] = self::formatAmt($item['AmtPre']  / 10000);
            $item['color'] = self::percentColor($item['rate']);
        }

        return [
            'sumExternal' => $result['sumExternal'],
            'sumInternal' => $result['sumInternal'],
            'sumTotal' => $result['sumTotal'],
            'external'=>$_external,
            'internal'=>$_internal
        ];
    }

    public static function preResult($result, $langCode)
    {
        $data = [];
        $externalAmount = 0;
        $externalAmountPrev = 0;
        $internalAmount = 0;
        $internalAmountPrev = 0;
        $totalAmount = 0;
        $totalAmountPrev = 0;

        foreach ($result as $entry) {
            if (isset($entry['DeptCd'])) {
                if ($entry['Sort'] !== '000') {
                    $department = UserModel::getemalisDiv1($langCode, trim($entry['DeptCd']));
                }

                if ($entry['Sort'] !== '000') {
                    $level = '2';
                    $managerEmpId = isset($department['MEmpID']) ? $department['MEmpID'] : '';
                    $managerEmpName = isset($department['MEmpNm']) ? $department['MEmpNm'] : '';
                    $clerkEmpId = isset($department['CEmpID']) ? $department['CEmpID'] : '';
                    $clerkEmpName = isset($department['CEmpNm']) ? $department['CEmpNm'] : '';
                    $headDeptCode = isset($department['HDeptCd']) ? $department['HDeptCd'] : '';
                    $managerDeptCode = isset($department['MDeptCd']) ? $department['MDeptCd'] : '';
                } else {
                    $level = '1';
                    $managerEmpId = $managerEmpName = $clerkEmpId = $clerkEmpName = $managerDeptCode = $headDeptCode = '';
                }

                $data[] = [
                    'Sort' => $entry['Sort'],
                    'ExternalGubun' => $entry['ExternalGubun'],
                    'AmtPre' => $entry['OrderforAmt_pre'],
                    'Amt' => $entry['OrderforAmts'],
                    'DeptNm' => $entry['DeptNm'],
                    'MEmpID' => trim($managerEmpId),
                    'MEmpNm' => trim($managerEmpName),
                    'CEmpID' => trim($clerkEmpId),
                    'CEmpNm' => trim($clerkEmpName),
                    'DeptCd' => trim($entry['DeptCd']),
                    'MDeptCd' => trim($managerDeptCode),
                    'HDeptCd' => trim($headDeptCode),
                    'DeptDiv1' => isset($department['DeptDiv1']) ? trim($department['DeptDiv1']) : '',
                    'TransNm' => isset($department['TransNm']) ? trim($department['TransNm']) : '',
                    'level' => $level
                ];
            }
            if (isset($entry['ExternalGubun'])) {
                if ($entry['ExternalGubun'] == 1) {
                    $externalAmount += $entry['OrderforAmts'];
                    $externalAmountPrev += $entry['OrderforAmt_pre'];
                } elseif ($entry['ExternalGubun'] == 2) {
                    $internalAmount += $entry['OrderforAmts'];
                    $internalAmountPrev += $entry['OrderforAmt_pre'];
                }
            }
            if (isset($entry['Sort']) && $entry['Sort'] !== '000') {
                $totalAmount += $entry['OrderforAmts'];
                $totalAmountPrev += $entry['OrderforAmt_pre'];
            }
        }
        $totalPercent = self::formatAmt((($totalAmount - $totalAmountPrev) / ($totalAmountPrev == 0 ? 100 : $totalAmountPrev)) * 100);
        $externalPercent = self::formatAmt((($externalAmount - $externalAmountPrev) / ($externalAmountPrev == 0 ? 100 : $externalAmountPrev)) * 100);
        $internalPercent = self::formatAmt((($internalAmount - $internalAmountPrev) / ($internalAmountPrev == 0 ? 100 : $internalAmountPrev)) * 100);
        $externalAmount = self::formatAmt($externalAmount / 10000);
        $externalAmountPrev = self::formatAmt($externalAmountPrev / 10000);
        $internalAmount = self::formatAmt($internalAmount / 10000);
        $internalAmountPrev = self::formatAmt($internalAmountPrev / 10000);
        $totalAmount = self::formatAmt($totalAmount / 10000);
        $totalAmountPrev = self::formatAmt($totalAmountPrev / 10000);

        $sumTotal = [
            'Amt' => $totalAmount,
            'AmtPre' => $totalAmountPrev,
            'rate' => $totalPercent,
            'color' => self::percentColor($totalPercent)
        ];

        $sumExternal = [
            'Amt' => $externalAmount,
            'AmtPre' => $externalAmountPrev,
            'rate' => $externalPercent,
            'color' => self::percentColor($externalPercent)
        ];

        $sumInternal = [
            'Amt' => $internalAmount,
            'AmtPre' => $internalAmountPrev,
            'rate' => $internalPercent,
            'color' => self::percentColor($internalPercent)
        ];

        $resultData = [
            'sumExternal' => $sumExternal,
            'sumInternal' => $sumInternal,
            'sumTotal' => $sumTotal,
            'data' => $data,
            'Dept' => UserModel::getCSDept(),
            'div' => UserModel::getDiv1ept($langCode)
        ];

        return $resultData;
    }



}