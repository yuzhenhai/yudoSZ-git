<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-26 13:07
 */

namespace app\model\purchaseManage;


use think\facade\Db;

class PurchaseModel extends \app\model\BaseModel
{
    public static function getStatsDetail($name,$baseDate,$imp,$basic,$langCode)
    {
        $thisYearData = Db::connect(self::$DevDb)->query("EXEC dbo.P_PO_900_1400_M ?,?,?,?,?,?",['Q',$baseDate,'',$basic,$imp,$langCode])[0];
        $lastYearData = Db::connect(self::$DevDb)->query("EXEC dbo.P_PO_900_1400_M ?,?,?,?,?,?",['Q',$baseDate -1,'',$basic,$imp,$langCode])[0];
        $_thisYearData = $_lastYearData = [];
        foreach($thisYearData as $item){
            if(trim($item['Sort'])=='A' && $item['Purchaseclass'] == $name){
                $_thisYearData = $item;
            }
        }
        foreach($lastYearData as $item){
            if(trim($item['Sort'])=='A' && $item['Purchaseclass'] == $name){
                $_lastYearData = $item;
            }
        }
        if ($langCode == 'SM00010001') {
            $quarterNames = ["1분기계", "2분기계", "3분기계", "4분기계"]; // 多语言
        } elseif ($langCode == 'SM00010002') {
            $quarterNames = ["1st Quarter", "2nd Quarter", "3rd Quarter", "4th Quarter"]; // 多语言
        } else {
            $quarterNames = ["第一季度", "第二季度", "第三季度", "第四季度"]; // 多语言
        }
        return self::preStatsDetail($_thisYearData,$_lastYearData,$quarterNames);
    }

    public static function preStatsDetail($thisYearData, $lastYearData, $quarterNames)
    {
        $result = [];
        $sort = 10; // Sort的起始值，依次增加

        // 12个月的数据
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthKey = sprintf('Month%02d', $i);

            // 获取当前年份和去年的数据
            $thisYearAmount = isset($thisYearData[$monthKey]) ? $thisYearData[$monthKey] : 0;
            $lastYearAmount = isset($lastYearData[$monthKey]) ? $lastYearData[$monthKey] : 0;

            // 计算占比
            $rate = $lastYearAmount == 0 ? 0 : (($thisYearAmount - $lastYearAmount) / $lastYearAmount) * 100;

            // 设置rate颜色
            $rateColor = $rate >= 0 ? "#FF6259" : "#07BE00"; // 根据正负值选择颜色

            // 创建月份数据
            $monthlyData[] = [
                'Sort' => sprintf('%03d', $sort),  // 每个月的Sort编号
                'Month' => str_pad($i, 2, '0', STR_PAD_LEFT), // 保证月份两位数
                'thisYear' => number_format($thisYearAmount / 10000, 2, '.', ','), // 格式化为千分位
                'lastYear' => number_format($lastYearAmount / 10000, 2, '.', ','), // 格式化为千分位
                'rate' => number_format($rate, 2, '.', ','), // 格式化为两位小数
                'rateColor' => $rateColor,
                'bgColor' => ''
            ];

            $sort += 10; // 增加 Sort 数字
        }

        // 计算季度数据
        $quarterData = [];
        $quarterSort = 31;  // 设置季度 Sort 从 031 开始
        for ($i = 0; $i < 4; $i++) {
            $startMonth = $i * 3;
            $endMonth = ($i + 1) * 3 - 1;
            // 计算季度的本年和去年数据
            $thisYearQuarter = 0;
            $lastYearQuarter = 0;
            for ($j = $startMonth; $j <= $endMonth; $j++) {
                $monthKey = sprintf('Month%02d', $j + 1);
                $thisYearQuarter += isset($thisYearData[$monthKey]) ? $thisYearData[$monthKey] : 0;
                $lastYearQuarter += isset($lastYearData[$monthKey]) ? $lastYearData[$monthKey] : 0;
            }
            // 计算占比
            $rate = $lastYearQuarter == 0 ? 0 : (($thisYearQuarter - $lastYearQuarter) / $lastYearQuarter) * 100;
            // 设置rate颜色
            $rateColor = $rate >= 0 ? "#FF6259" : "#07BE00"; // 根据正负值选择颜色
            // 创建季度数据
            $quarterData[] = [
                'Sort' => sprintf('%03d', $quarterSort),  // 每个季度的Sort编号
                'Month' => $quarterNames[$i], // 取季度名称
                'thisYear' => number_format($thisYearQuarter / 10000, 2, '.', ','), // 格式化为千分位
                'lastYear' => number_format($lastYearQuarter / 10000, 2, '.', ','), // 格式化为千分位
                'rate' => number_format($rate, 2, '.', ','), // 格式化为两位小数
                'rateColor' => $rateColor,
                'bgColor' => '#FFFF00' // 每个季度的背景色
            ];

            $quarterSort += 30; // 增加季度的 Sort 数字（从 031 增加到 121）
        }
        $totalRate = ($thisYearData['YearTotal'] - $lastYearData['YearTotal']) / $lastYearData['YearTotal'];
        $result[] = [
            'Sort' => '999',  // 总计的 Sort
            'Month' => 'TOTAL',  // 总计
            'thisYear' => number_format($thisYearData['YearTotal'] / 10000, 2, '.', ','), // 总计金额
            'lastYear' => number_format($lastYearData['YearTotal'] / 10000, 2, '.', ','), // 总计去年金额
            'rate' => number_format($totalRate * 100, 2, '.', ','), // 总计的占比
            'rateColor' => '#353535', // 总计的颜色
            'bgColor' => '#DA70D6'  // 总计的背景色
        ];

        // 合并12个月的数据和4个季度的数据，并且排序
        $allData = array_merge($monthlyData, $quarterData, [$result[count($result) - 1]]);

        // 按照 Sort 排序
        usort($allData, function($a, $b) {
            return intval($a['Sort']) - intval($b['Sort']);
        });
        return $allData;
    }


    public static function getStats($baseDate,$imp,$basic,$langCode)
    {
        $data = Db::connect(self::$DevDb)->query("EXEC dbo.P_PO_900_1400_M ?,?,?,?,?,?",['Q',$baseDate,'',$basic,$imp,$langCode]);
        if($data){
            return self::preStats($data[0]);
        }else{
            return [];
        }

    }

    public static function preStats($data)
    {
        $_data = [];
        foreach($data as $item){
            if(trim($item['Sort'])=='A'){
                $_data[] = $item;
            }
        }
        $result = [];
        $count = count($_data);
        $lastElement = array_pop($_data);
        usort($_data, function($a, $b) {
            return $b['YearTotal'] - $a['YearTotal']; // 降序
        });
        $_data[] = $lastElement;
        $charts = [];
        foreach($_data as $item){
            $_item = [];
            $_charts = [];
            $_item['name'] = $item['Purchaseclass'];
            if(trim($item['Status'])!=='A'){
                $_charts['name']  = $item['Purchaseclass'];
                $_charts['value'] = $item['YearTotal'] / 10000;
                $charts[] = $_charts;
            }
            $_item['monthAvg'] = self::formatAmt($item['MonthAvg'] / 10000);
            $_item['yearTotal'] = self::formatAmt($item['YearTotal'] / 10000);
            $_item['rate'] = self::formatAmt(($item['YearTotal'] / $_data[$count - 1]['YearTotal']) * 100);
            $result[] = $_item;

        }
        return [
            'data'=>$result,
            'charts'=>$charts
        ];
    }

    public static function getLists($baseDate,$imp,$type,$langCode)
    {
        $data = Db::connect(self::$DevDb)->query("EXEC dbo.P_PO_900_1100_M ?,?,?,?,?,?",['Q1',$baseDate,$type,'',$imp,$langCode]);
        if($data){
            return self::preLists($data[0]);
        }else{
            return [];
        }

    }

    public static function getDetail($baseDate,$imp,$type,$basic,$langCode)
    {
        $data = Db::connect(self::$DevDb)->query("EXEC dbo.P_PO_900_1100_M ?,?,?,?,?,?",['Q',$baseDate,$type,$basic,$imp,$langCode])[0];
        return self::preDetail($data);
    }

    public static function preDetail($data)
    {
        $result = [];
        foreach($data as $item){
            $_item = [];
            $_item['Sort'] = $item['Sort'];
            $_item['Month'] = $item['Month'];
            $_item['thisYear'] = self::formatAmt($item['BaseYY'] / 10000);
            $_item['lastYear'] = self::formatAmt($item['PYear2'] / 10000);
            $_item['rate'] = self::formatAmt($item['BPRate']*100);
            $_item['rateColor'] = self::percentColor($item['BPRate']);
            if($_item['Sort']=='999'){
                $_item['rateColor'] = '#353535';
                $_item['bgColor'] = '#DA70D6';
            }elseif(in_array($_item['Sort'], ['031','061','091','121'])){
                $_item['bgColor'] = '#FFFF00';
            }else{
                $_item['bgColor'] = '';
            }
            $result[] = $_item;
        }
        return $result;
    }

    public static function preLists($data)
    {
        $result = [];
        foreach($data as $item){
            $_item = [];
            $_item['thisYear'] = self::formatAmt($item['BaseYY'] / 10000);
            $_item['lastYear'] = self::formatAmt($item['PYear'] / 10000);
            $_item['rate'] = self::formatAmt($item['BPRate'] * 100);
            $_item['rateColor'] = self::percentColor($_item['rate']);
            $result[] = $_item;
        }
        return $result;
    }

    public static function getSelectList($type,$langCode)
    {
        $sql = "select
            isnull(MULTIB.TransNm,MULTIA.MinorNm) AS text,
            isnull(MULTIB.DictCd,MULTIA.MinorCd) AS value
            from TSMSyco10 MULTIA
            full join TSMDict10 MULTIB on MULTIA.MinorCd = MULTIB.DictCd and MULTIB.LangCd = '{$langCode}'
            where DeleteYn = 'N' AND MULTIA.MajorCd = '{$type}'";
        return Db::connect(self::$DevDb)->query($sql);
    }

    public static function getDefaultOption($langCode)
    {
        $sql = "select TransNm from TSMDict10 where LangCd = '{$langCode}' AND DictCd='POResultGubn'";
        return Db::connect(self::$DevDb)->query($sql)[0]['TransNm'];
    }
}