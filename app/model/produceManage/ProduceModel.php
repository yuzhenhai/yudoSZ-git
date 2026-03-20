<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-26 13:05
 */

namespace app\model\produceManage;

use \DateTime;
use think\Exception;
use think\facade\Db;

class ProduceModel extends \app\model\BaseModel
{

    public static function getCurrList($langCode)
    {
        $sql = "select
                isnull(MULTIB.TransNm,MULTIA.MinorNm) AS text,
                isnull(MULTIB.DictCd,MULTIA.MinorCd) AS value
                from TSMSyco10 MULTIA full join TSMDict10 MULTIB on
                MULTIA.MinorCd = MULTIB.DictCd and MULTIB.LangCd = '{$langCode}'
                where DeleteYn = 'N' and MajorCd = 'SA9001'";
        return Db::connect(self::$DevDb)->query($sql);
    }

    public static function getLists($baseDate,$currId,$expClass,$langCode)
    {
        $date = date('Y',strtotime($baseDate));
        $data = Db::connect(self::$DevDb)->query("EXEC dbo.P_PM_900_1200_M ?,?,?,?,?,?,?",['Q',$baseDate,'',$currId,$expClass,'',$langCode]);
        if($data){
            return self::preLists($data[0]);
        }else{
            return [];
        }

    }

    public static function preLists($data)
    {
        $order = [];
        $prodGet = [];
        $prodTo =[];
        foreach($data as $item){
            $_order = [];
            $_prodGet = [];
            $_prodTo = [];
            $_order['Sort'] = $_prodGet['Sort'] = $_prodTo['Sort'] = $item['Sort'];
            $_order['Month'] = $_prodGet['Month'] = $_prodTo['Month'] = $item['Month'];
            $_order['lastYear'] = self::formatAmt($item['SAPreYYOrderAmt'] / 10000);
            $_order['thisYear'] = self::formatAmt($item['SADueYYOrderAmt'] / 10000);
            $_order['rate'] = self::formatAmt($item['SAOrderAchRate'] *100 -100) . '%';
            $_order['rateColor'] = self::percentColor($_order['rate']);
            $_prodGet['lastYear'] = isset($item['PMPreYYOrderAmt'])?self::formatAmt($item['PMPreYYOrderAmt'] / 10000):'0.00';
            $_prodGet['thisYear'] = isset($item['PMDueYYOrderAmt'])?self::formatAmt($item['PMDueYYOrderAmt'] / 10000):'0.00';
            $_prodGet['rate'] = isset($item['PMOrderAchRate'])?self::formatAmt($item['PMOrderAchRate'] *100 -100) . '%':'0.00%';
            $_prodGet['rateColor'] = self::percentColor($_prodGet['rate']);
            $_prodTo['lastYear'] = self::formatAmt($item['PreYYOrderAmt'] / 10000);
            $_prodTo['thisYear'] = self::formatAmt($item['DueYYOrderAmt'] / 10000);
            $_prodTo['rate'] = self::formatAmt($item['OrderAchRate'] *100 -100) . '%';
            $_prodTo['rateColor'] = self::percentColor($_prodTo['rate']);
            if($item['Sort']=='031'||$item['Sort']=='061'||$item['Sort']=='091'||$item['Sort']=='121'){
                $_order['bgColor'] = '#FFFF00';
                $_prodGet['bgColor'] = '#FFFF00';
                $_prodTo['bgColor'] = '#FFFF00';
            }elseif($item['Sort']=='999'){
                $_order['bgColor'] = '#DA70D6';
                $_prodGet['bgColor'] = '#DA70D6';
                $_prodTo['bgColor'] = '#DA70D6';
                $_order['rateColor'] = '#353535';
                $_prodGet['rateColor'] = '#353535';
                $_prodTo['rateColor'] = '#353535';
            }else{
                $_order['bgColor'] = '';
                $_prodGet['bgColor'] = '';
                $_prodTo['bgColor'] = '';
            }

            $order[] = $_order;
            $prodGet[] = $_prodGet;
            $prodTo[] = $_prodTo;
        }
        return [
          'order'=>$order,
          'prodGet'=>$prodGet,
          'prodTo'=>$prodTo
        ];
    }

    public static function getYearData($baseDate,$langCode)
    {
        $date = date('Y',strtotime($baseDate));
        $data = Db::connect(self::$DevDb)->query("EXEC dbo.P_PM_900_1200_M ?,?,?,?,?,?,?",['Q',$date,'','','','',$langCode])[0];
        return self::preYearData($data);
    }

    public static function preYearData($data)
    {
        $result = [];
        foreach($data as $item){
            $_item = [];
            $_item['Sort'] = $item['Sort'];
            $_item['Month'] = $item['Month'];
            $_item['thisYear'] = self::formatAmt($item['DueYYOrderAmt'] / 10000);
            $_item['lastYear'] = self::formatAmt($item['PreYYOrderAmt'] / 10000);
            $_item['rate'] = self::formatAmt($item['OrderAchRate'] * 100 -100);
            $_item['rateColor'] = self::percentColor($_item['rate']);
            if($_item['Sort']=='031'||$_item['Sort']=='061'||$_item['Sort']=='091'||$_item['Sort']=='121'){
                $_item['bgColor'] = '#FFFF00';
            }elseif($_item['Sort']=='999'){
                $_item['bgColor'] ='#DA70D6';
            }else{
                $_item['bgColor'] = '';
            }
            $result[] = $_item;
        }
        $result[count($result)-1]['rateColor'] = '#353535';
        return $result;
    }

    public static function getMonthData($baseDate, $langCode)
    {
        $baseDate = date('Ymd',strtotime($baseDate));
        $thisYear = Db::connect(self::$DevDb)->query("EXEC dbo.P_PM_900_1100_M 'Q','{$baseDate}','{$langCode}'")[0];
        $lastYearMonth = date('Ymt', strtotime('-1 year', strtotime($baseDate)));
        $lastYear = Db::connect(self::$DevDb)->query("EXEC dbo.P_PM_900_1100_M 'Q','{$lastYearMonth}','{$langCode}'")[0];
        return self::preMonthData($thisYear,$lastYear);
    }

    public static function preMonthData($arr1, $arr2) {
        // 初始化合并结果
        $merged = [];
        // 按DeptCd对第一个数组进行索引
        $indexedArr1 = [];
        foreach ($arr1 as $item) {
            $indexedArr1[trim($item['DeptCd'])] = $item; // 以DeptCd为键，去除DeptCd两端的空格
        }
        // 按DeptCd对第二个数组进行索引
        $indexedArr2 = [];
        foreach ($arr2 as $item) {
            $indexedArr2[trim($item['DeptCd'])] = $item;
        }
        // 合并两个数组
        foreach ($indexedArr1 as $deptCd => $item1) {
            // 如果第二个数组也有相同的DeptCd
            if (isset($indexedArr2[$deptCd])) {
                $item2 = $indexedArr2[$deptCd];
                $merged[] = [
                    'ExternalGubn' => $item1['ExternalGubn'], // 保留 ExternalGubn
                    'DeptNm' => $item1['DeptNm'], // 保留 DeptNm
                    'thisYear' => $item1['TotOrderForAmt'], // 第一数组的 TotYYOrderForAmt 改为 thisYear
                    'lastYear' => $item2['TotOrderForAmt'], // 第二数组的 TotYYOrderForAmt 改为 lastYear
                ];
            } else {
                // 如果第二个数组没有相同的DeptCd，将lastYear设为0
                $merged[] = [
                    'ExternalGubn' => $item1['ExternalGubn'],
                    'DeptNm' => $item1['DeptNm'],
                    'thisYear' => $item1['TotOrderForAmt'],
                    'lastYear' => 0, // 没有相同DeptCd时lastYear为0
                ];
            }
        }
        // 检查第二个数组中有而第一个数组没有的DeptCd
        foreach ($indexedArr2 as $deptCd => $item2) {
            if (!isset($indexedArr1[$deptCd])) {
                $merged[] = [
                    'ExternalGubn' => $item2['ExternalGubn'],
                    'DeptNm' => $item2['DeptNm'],
                    'thisYear' => 0, // 没有相同DeptCd时thisYear为0
                    'lastYear' => $item2['TotOrderForAmt'],
                ];
            }
        }
        $total = [
            'DeptNm'=>'Total',
            'thisYear'=>0,
            'lastYear'=>0,
            'rate'=>'0.00',
            'bgColor'=>'#DA70D6'
        ];
        $totalE  = [
            'DeptNm'=>'External',
            'thisYear'=>0,
            'lastYear'=>0,
            'rate'=>'0.00',
            'bgColor'=>'#FFFF00'
        ];
        $totalI = [
            'DeptNm'=>'Internal',
            'thisYear'=>0,
            'lastYear'=>0,
            'rate'=>'0.00',
            'bgColor'=>'#FFFF00'
        ];
        $external = [];
        $internal = [];
        foreach($merged as &$item){
            $item['rate'] = self::formatAmt(($item['lastYear'] != 0 ? ($item['thisYear'] - $item['lastYear']) / $item['lastYear'] : 0)*100);
            $item['rateColor'] = self::percentColor($item['rate']);
            $total['thisYear'] += $item['thisYear'];
            $total['lastYear'] += $item['lastYear'];
            if($item['ExternalGubn']=='External'){
                $totalE['thisYear'] += $item['thisYear'];
                $totalE['lastYear'] += $item['lastYear'];
            }else{
                $totalI['thisYear'] += $item['thisYear'];
                $totalI['lastYear'] += $item['lastYear'];
            }
            $item['thisYear'] = self::formatAmt($item['thisYear'] / 10000);
            $item['lastYear'] = self::formatAmt($item['lastYear'] / 10000);
            if($item['ExternalGubn']=='External'){
                $external[] = $item;
            }else{
                $internal[] = $item;
            }

        }
        $total['rate'] = self::formatAmt(($total['lastYear'] != 0 ? ($total['thisYear'] - $total['lastYear']) / $total['lastYear'] : 0)*100);
        $total['rateColor'] = '#353535';
        $total['thisYear'] = self::formatAmt($total['thisYear'] / 10000);
        $total['lastYear'] = self::formatAmt($total['lastYear'] / 10000);
        $totalE['rate'] = self::formatAmt(($totalE['lastYear'] != 0 ? ($totalE['thisYear'] - $totalE['lastYear']) / $totalE['lastYear'] : 0)*100);
        $totalE['rateColor'] = self::percentColor($totalE['rate']);
        $totalE['thisYear'] = self::formatAmt($totalE['thisYear'] / 10000);
        $totalE['lastYear'] = self::formatAmt($totalE['lastYear'] / 10000);
        $totalI['rate'] = self::formatAmt(($totalI['lastYear'] != 0 ? ($totalI['thisYear'] - $totalI['lastYear']) / $totalI['lastYear'] : 0)*100);
        $totalI['rateColor'] = self::percentColor($totalI['rate']);
        $totalI['thisYear'] = self::formatAmt($totalI['thisYear'] / 10000);
        $totalI['lastYear'] = self::formatAmt($totalI['lastYear'] / 10000);
        return [
          'total'=>$total,
          'totalE'=>$totalE,
          'totalI'=>$totalI,
          'external'=>$external,
          'internal'=>$internal
        ];
    }

    public static function getDailyData($baseDate, $langCode)
    {
        // 格式化日期
        $baseDateObj = new DateTime($baseDate);
        $currentYear = $baseDateObj->format('Y');
        $previousYear = $currentYear - 1;
        $currentMonth = $baseDateObj->format('m');
        $currentDay = $baseDateObj->format('d');

        // 计算日期范围
        $dateRanges = self::generateDateRanges($baseDateObj);

        // 查询SQL
        $sql = "SELECT B.CloseDate, (IsNull(SUM(A.OrderForAmt), 0) + IsNull(Sum(A.OrderForVat), 0)) AS Amt
            FROM TPMWKClose00 B WITH (NOLOCK)
            INNER JOIN TSAOrder00 A WITH (NOLOCK)
            ON B.SourceNo = A.OrderNo AND B.SourceType = '1' AND A.DeleteYn = 'N'
            WHERE B.CloseDate BETWEEN '{$dateRanges['lastYearStart']}' AND '{$dateRanges['thisYearEnd']}'
            GROUP BY B.CloseDate ORDER BY B.CloseDate";

        $closeDateResults = Db::connect(self::$DevDb)->query($sql);

        // 获取各个时间段的金额数据
        $dailyData = self::getAmountData($closeDateResults, $dateRanges['lastYearToday'], $dateRanges['lastYearToday'], $dateRanges['thisYearToday'], $dateRanges['thisYearToday']);
        $monthlyData = self::getAmountData($closeDateResults, $dateRanges['lastYearThisMonthStart'], $dateRanges['lastYearThisMonthEnd'], $dateRanges['thisYearThisMonthStart'], $dateRanges['thisYearThisMonthEnd']);
        $cumulativeData = self::getAmountData($closeDateResults, $dateRanges['lastYearStart'], $dateRanges['lastYearThisMonthEnd'], $dateRanges['thisYearStart'], $dateRanges['thisYearToday']);
        $annualData = self::getAmountData($closeDateResults, $dateRanges['lastYearStart'], $dateRanges['lastYearEnd'], $dateRanges['thisYearStart'], $dateRanges['thisYearEnd']);

        // 查询未完成和未接收金额
        $sql = "SELECT
                (SELECT IsNull(SUM(A.OrderForAmt), 0)
                 FROM TPMWKReq00 W WITH (NOLOCK)
                 INNER JOIN TSAOrder00 A WITH (NOLOCK) ON W.SourceNo = A.OrderNo
                 WHERE W.SourceType = '1' AND A.DeleteYn = 'N' AND W.AptYn = '0' AND W.DeleteYn = 'N') AS unShippedAmt,
                (SELECT IsNull(SUM(A.OrderForAmt), 0)
                 FROM TPMWKPlan00 W WITH (NOLOCK)
                 INNER JOIN TSAOrder00 A WITH (NOLOCK) ON W.SourceNo = A.OrderNo
                 WHERE W.SourceType = '1' AND A.DeleteYn = 'N' AND W.Status IN ('0','1','2') AND W.CfmYn = '1') AS unReceivedAmt";

        $unfinishedData = Db::connect(self::$DevDb)->query($sql)[0];

        $totalAmountData = [
            'unShippedAmt' => self::formatAmt($unfinishedData['unShippedAmt'] / 10000), // 未出库金额
            'unReceivedAmt' => self::formatAmt($unfinishedData['unReceivedAmt'] / 10000)  // 未接收金额
        ];

        // 汇总数据
        $summaryData = [
            'daily' => $dailyData,
            'monthly' => $monthlyData,
            'cumulative' => $cumulativeData,
            'annual' => $annualData,
            'total' => $totalAmountData
        ];

        return $summaryData;
    }

    // 计算金额数据
    private static function getAmountData($res, $lastYearStart, $lastYearEnd, $thisYearStart = null, $thisYearEnd = null)
    {
        // 计算金额总和
        $lastYearAmt = self::sumAmtBetweenDates($res, $lastYearStart, $lastYearEnd);
        $thisYearAmt = $thisYearStart && $thisYearEnd
            ? self::sumAmtBetweenDates($res, $thisYearStart, $thisYearEnd)
            : $lastYearAmt;

        // 计算增长率
        $rate = ($lastYearAmt != 0)
            ? number_format(($thisYearAmt - $lastYearAmt) / $lastYearAmt * 100, 2)
            : "0.00";

        if ($rate == '-100.00') $rate = '0.00';
        $color = self::percentColor($rate);

        // 返回格式化的结果
        return [
            'lastYear' => self::formatAmt($lastYearAmt / 10000),
            'thisYear' => self::formatAmt($thisYearAmt / 10000),
            'rate' => $rate,
            'color' => $color
        ];
    }

    // 生成日期范围
    private static function generateDateRanges($today)
    {
        $thisYear = $today->format('Y');
        $lastYear = $thisYear - 1;
        $month = $today->format('m');
        $day = $today->format('d');
        return [
            'lastYearStart' => "{$lastYear}-01-01",
            'lastYearEnd' => "{$lastYear}-12-31",
            'thisYearStart' => "{$thisYear}-01-01",
            'thisYearEnd' => "{$thisYear}-12-31",
            'lastYearThisMonthStart' => "{$lastYear}-{$month}-01",
            'thisYearThisMonthStart' => "{$thisYear}-{$month}-01",
            'lastYearToday' => "{$lastYear}-{$month}-{$day}",
            'thisYearToday' => "{$thisYear}-{$month}-{$day}",
            'lastYearThisMonthEnd' => "{$lastYear}-{$month}-" . (new DateTime("{$lastYear}-{$month}-01"))->format('t'),
            'thisYearThisMonthEnd' => "{$thisYear}-{$month}-" . (new DateTime("{$thisYear}-{$month}-01"))->format('t')
        ];
    }

    public static function sumAmtBetweenDates($array, $startDate, $endDate) {
        $totalAmt = 0;
        foreach ($array as $item) {
            $closeDate = strtotime($item['CloseDate']);
            if ($closeDate >= strtotime($startDate) && $closeDate <= strtotime($endDate)) {
                $totalAmt += floatval($item['Amt']);
            }
        }
        return $totalAmt;
    }
}