<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-04 10:56
 */

namespace app\model\salesManage;


use app\model\BaseModel;
use think\facade\Db;

class YearlyMarketsModel extends BaseModel
{
    /**
     *  根据date获取Markets该年度1月1日至date的数据
     * @param $date
     * @param $expClass
     * @param $type
     * @param $langCode
     * @return mixed
     */
    public static function getLists($date, $expClass, $type, $langCode)
    {
        // 构建条件字符串
        $expClassCondition = !empty($expClass) ? "AND A.ExpClss = '$expClass'" : "";
        $year = date('Y', strtotime($date));

        // SQL 公共部分
        $commonMarketNames = "
        SELECT
            S.MinorCd AS MarketCd,
            ISNULL(Z.TransNm, S.MinorNm) AS Market
        FROM TSMSyco10 S WITH (NOLOCK)
        LEFT OUTER JOIN TSMDict10 Z WITH (NOLOCK)
            ON S.MinorCd = Z.DictCd
            AND Z.DictType = 'SM00030002'
            AND Z.LangCd = '{$langCode}'
    ";

        if ($type == 0) {
            // 订单数据查询
            $sql = sprintf("
            WITH OrderData AS (
                SELECT
                    A.MarketCd,
                    A.OrderDate,
                    SUM(A.OrderForAmt) AS OrderForAmt
                FROM TSAOrder00 A WITH (NOLOCK)
                WHERE A.OrderDate BETWEEN '%s-01-01' AND '%s'
                    AND A.DeleteYn = 'N'
                    %s
                    AND A.CfmYn = '1'
                    AND LEN(A.MarketCd) > 5
                GROUP BY A.MarketCd, A.OrderDate
            ),
            MarketNames AS (%s)
            SELECT
                OD.MarketCd,
                MN.Market,
                SUM(OD.OrderForAmt) AS Amt
            FROM OrderData OD
            LEFT JOIN MarketNames MN ON OD.MarketCd = MN.MarketCd
            GROUP BY OD.MarketCd, MN.Market
            ORDER BY OD.MarketCd;",
                $year, $date, $expClassCondition, $commonMarketNames
            );
        } else {
            // 发票数据查询
            $sql = sprintf("
            WITH InvoiceData AS (
                SELECT
                    D.MarketCd,
                    ISNULL(SUM(B.ForAmt), 0) AS Amt
                FROM TSAInvoice00 A WITH (NOLOCK)
                INNER JOIN TSAInvoice10 B WITH (NOLOCK) ON A.InvoiceNo = B.InvoiceNo
                INNER JOIN TSAOrder10 C WITH (NOLOCK) ON B.OrderNo = C.OrderNo AND B.OrderSerl = C.OrderSerl
                INNER JOIN TSAOrder00 D WITH (NOLOCK) ON C.OrderNo = D.OrderNo
                LEFT OUTER JOIN TMADept00 E WITH (NOLOCK) ON D.DeptCd = E.DeptCd
                WHERE CONVERT(CHAR(4), A.InvoiceDate, 112) = '%s'
                    AND A.InvoiceDate <= '%s'
                    %s
                    AND A.SourceType = '1'
                    AND A.CfmYn = '1'
                GROUP BY D.MarketCd
            ),
            MarketNames AS (%s)
            SELECT
                I.MarketCd,
                MN.Market,
                I.Amt
            FROM InvoiceData I
            LEFT JOIN MarketNames MN ON I.MarketCd = MN.MarketCd
            ORDER BY I.MarketCd;",
                $year, $date, $expClassCondition, $commonMarketNames
            );
        }
        return Db::connect(self::$DevDb)->query($sql);
    }

    /**
     *  预处理返回数据
     * @param $previousYearData
     * @param $currentYearData
     * @return array
     */
    public static function preLists($previousYearData,$currentYearData)
    {
        // 创建以 MarketCd 为键的去年的数据映射
        $previousYearMap = [];
        foreach ($previousYearData as $item) {
            $previousYearMap[$item['MarketCd']] = $item['Amt'];
        }
        $currentYearMap = [];
        foreach ($currentYearData as $item) {
            $currentYearMap[$item['MarketCd']] = $item['Amt'];
        }

        // 合并数据
        $allMarketCodes = array_unique(array_merge(array_keys($previousYearMap), array_keys($currentYearMap)));
        $mergedData = [];

        foreach ($allMarketCodes as $marketCd) {
            $currentYearAmt = isset($currentYearMap[$marketCd]) ? $currentYearMap[$marketCd] : 0;
            $previousYearAmt = isset($previousYearMap[$marketCd]) ? $previousYearMap[$marketCd] : 0;

            // 计算增长率
            $growthRate = 0;
            if ($previousYearAmt > 0) {
                $growthRate = (($currentYearAmt - $previousYearAmt) / $previousYearAmt) * 100;
            }

            // 四舍五入并保留两位小数
            $growthRate = round($growthRate, 2);
            $growthRate = number_format($growthRate, 2)=='-100.00'?'0.00':number_format($growthRate, 2);
            $mergedData[] = [
                'MarketCd' => $marketCd,
                'Market' => isset($currentYearMap[$marketCd]) ? $currentYearData[array_search($marketCd, array_column($currentYearData, 'MarketCd'))]['Market'] : '',
                'CurrentYearAmt' => self::formatAmt($currentYearAmt / 10000),
                'PreviousYearAmt' => self::formatAmt($previousYearAmt / 10000),
                'GrowthRate' => $growthRate,
                'RateColor' => self::percentColor($growthRate)
            ];
        }
        return $mergedData;
    }

    public static function getDetail($date, $expClass, $type, $marketCd)
    {
        // 构建条件字符串
        $expClassCondition = !empty($expClass) ? "AND A.ExpClss = '$expClass'" : "";

        // 计算年份
        $year = $date;
        $lastYear = $year - 1;

        if ($type == 0) {

            // 订单数据查询
            $sql = "
        WITH OrderData AS (
            SELECT
                A.MarketCd,
                MONTH(A.OrderDate) AS Month,
                SUM(A.OrderForAmt) AS OrderForAmt,
                YEAR(A.OrderDate) AS OrderYear
            FROM TSAOrder00 A WITH (NOLOCK)
            WHERE A.OrderDate BETWEEN '{$lastYear}-01-01' AND '{$year}-12-31'
                AND A.DeleteYn = 'N'
                $expClassCondition
                AND A.CfmYn = '1'
                AND A.MarketCd = '{$marketCd}'
            GROUP BY A.MarketCd, MONTH(A.OrderDate), YEAR(A.OrderDate)
        )
        SELECT
            FORMAT(M.Month, '00') AS Month,
            ISNULL(SUM(CASE WHEN OD.OrderYear = '{$year}' THEN OD.OrderForAmt END), 0) AS CurrentYearAmt,
            ISNULL(SUM(CASE WHEN OD.OrderYear = '{$lastYear}' THEN OD.OrderForAmt END), 0) AS LastYearAmt,
            FORMAT(M.Month * 10, '000') AS Sort
        FROM (
            SELECT MONTH AS Month FROM (VALUES (1), (2), (3), (4), (5), (6), (7), (8), (9), (10), (11), (12)) AS Months(Month)
        ) AS M
        LEFT JOIN OrderData OD ON M.Month = OD.Month
        GROUP BY M.Month
        ORDER BY M.Month;
        ";
        } else {
            // 发票数据查询
            $sql = "
        WITH InvoiceData AS (
            SELECT
                D.MarketCd,
                MONTH(A.InvoiceDate) AS Month,
                ISNULL(SUM(B.ForAmt), 0) AS Amt,
                YEAR(A.InvoiceDate) AS InvoiceYear
            FROM TSAInvoice00 A WITH (NOLOCK)
            INNER JOIN TSAInvoice10 B WITH (NOLOCK) ON A.InvoiceNo = B.InvoiceNo
            INNER JOIN TSAOrder10 C WITH (NOLOCK) ON B.OrderNo = C.OrderNo AND B.OrderSerl = C.OrderSerl
            INNER JOIN TSAOrder00 D WITH (NOLOCK) ON C.OrderNo = D.OrderNo
            WHERE A.InvoiceDate BETWEEN '{$lastYear}-01-01' AND '{$year}-12-31'
                $expClassCondition
                AND A.SourceType = '1'
                AND A.CfmYn = '1'
                AND D.MarketCd = '{$marketCd}'
            GROUP BY D.MarketCd, MONTH(A.InvoiceDate), YEAR(A.InvoiceDate)
        )
        SELECT
            FORMAT(M.Month, '00') AS Month,
            ISNULL(SUM(CASE WHEN ID.InvoiceYear = '{$year}' THEN ID.Amt END), 0) AS CurrentYearAmt,
            ISNULL(SUM(CASE WHEN ID.InvoiceYear = '{$lastYear}' THEN ID.Amt END), 0) AS LastYearAmt,
            FORMAT(M.Month * 10, '000') AS Sort
        FROM (
            SELECT MONTH AS Month FROM (VALUES (1), (2), (3), (4), (5), (6), (7), (8), (9), (10), (11), (12)) AS Months(Month)
        ) AS M
        LEFT JOIN InvoiceData ID ON M.Month = ID.Month
        GROUP BY M.Month
        ORDER BY M.Month;
        ";
        }
        return Db::connect(self::$DevDb)->query($sql);
    }

    public static function preDetail($data, $langID)
    {
        // 季度数据配置和语言选择
        $quarters = [
            ['Month' => '1분기계', 'Sort' => '031', 'startMonth' => 1, 'endMonth' => 3],
            ['Month' => '2분기계', 'Sort' => '061', 'startMonth' => 4, 'endMonth' => 6],
            ['Month' => '3분기계', 'Sort' => '091', 'startMonth' => 7, 'endMonth' => 9],
            ['Month' => '4분기계', 'Sort' => '121', 'startMonth' => 10, 'endMonth' => 12],
            ['Month' => 'TOTAL', 'Sort' => '999', 'startMonth' => 1, 'endMonth' => 12] // Total
        ];

        $names = [
            'ENG' => ['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter', 'TOTAL'],
            'KOR' => ['1분기계', '2분기계', '3분기계', '4분기계', 'TOTAL'],
            'default' => ['第一季度', '第二季度', '第三季度', '第四季度', 'TOTAL'],
        ];

        $names = $names[$langID] ?? $names['default']; // 根据语言选择名称

        // 初始化季度数据
        $result = [];

        // 初始化季度和总计金额
        $quarterSums = [
            'currentYear' => [0, 0, 0, 0, 0], // 当前年金额
            'lastYear' => [0, 0, 0, 0, 0] // 去年金额
        ];

        foreach ($data as &$monthData) { // 使用引用来更新原始数据
            $month = (int)$monthData['Month'];
            $currentYearAmt = (float)$monthData['CurrentYearAmt'];
            $lastYearAmt = (float)$monthData['LastYearAmt'];

            // 计算每个月的增长率
            $growthRate = 0;
            if ($lastYearAmt != 0) {
                $growthRate = (($currentYearAmt - $lastYearAmt) / $lastYearAmt) * 100;
            }

            // 添加增长率到每个月的数据中
            $monthData['growthRate'] = number_format($growthRate, 2, '.', '');
            $monthData['CurrentYearAmt'] = self::formatAmt($monthData['CurrentYearAmt'] / 10000);
            $monthData['LastYearAmt'] = self::formatAmt($monthData['LastYearAmt'] /10000);
            $monthData['rateColor'] = self::percentColor($monthData['growthRate']);
            $monthData['growthRate'] = $monthData['growthRate']=='-100.00'?'0.00':$monthData['growthRate'];

            // 根据月份分配到相应的季度
            for ($i = 0; $i < count($quarters) - 1; $i++) {
                if ($month >= $quarters[$i]['startMonth'] && $month <= $quarters[$i]['endMonth']) {
                    $quarterSums['currentYear'][$i] += $currentYearAmt;
                    $quarterSums['lastYear'][$i] += $lastYearAmt;
                    break;
                }
            }

            // 总计
            $quarterSums['currentYear'][4] += $currentYearAmt;
            $quarterSums['lastYear'][4] += $lastYearAmt;
        }

        // 填充季度数据
        foreach ($quarters as $index => $quarter) {
            $currentYearAmt = $quarterSums['currentYear'][$index];
            $lastYearAmt = $quarterSums['lastYear'][$index];

            // 计算季度的增长率
            $growthRate = 0;
            if ($lastYearAmt != 0) {
                $growthRate = (($currentYearAmt - $lastYearAmt) / $lastYearAmt) * 100;
            }
            $growthRate=$growthRate=='-100.00'?'0.00':$growthRate;
            $result[] = [
                'Month' => $names[$index],
                'CurrentYearAmt' => self::formatAmt($currentYearAmt / 10000),
                'LastYearAmt' => self::formatAmt($lastYearAmt / 10000),
                'growthRate' => number_format($growthRate, 2, '.', ''), // 四舍五入到两位小数
                'Sort' => $quarter['Sort'],
                'rateColor' => self::percentColor($growthRate),
                'bgColor' => self::detailBgColor(1)
            ];
        }


        // 将季度数据与原数据合并
        $data = array_merge($data, $result);
        usort($data, function ($a, $b) {
            return strcmp($a['Sort'], $b['Sort']);
        });
        $data[count($data)-1]['rateColor'] = '#353535';
        $data[count($data)-1]['bgColor'] = self::detailBgColor(0);
        return $data;
    }













}