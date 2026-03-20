<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-15 14:40
 */

namespace app\model\businessInfo;

use think\facade\Db;
use app\model\BaseModel;

class SalesConditionModel extends BaseModel
{
    public static function getList($db,$date,$amtClass)
    {
        $lastYear = $date - 1;
        $sql ="SELECT CAST(ROUND(SUM({$amtClass})/10000, 2) AS DECIMAL(10, 2)) AS amt,
                       '{$lastYear}' AS dateY
                FROM TSATotYM00
                WHERE LEFT(SumYM, 4) = '{$lastYear}'
                  AND SumType = '1'
                
                UNION
                
                SELECT CAST(ROUND(SUM({$amtClass})/10000, 2) AS DECIMAL(10, 2)) AS amt,
                       '{$date}' AS dateY
                FROM TSATotYM00
                WHERE LEFT(SumYM, 4) = '{$date}'
                  AND SumType = '1'";
        return  Db::connect(self::chooseDb($db))->query($sql);

    }

    public static function getDetail($db,$date,$amtClass)
    {
        $thisYear = $date;
        $preYear = $thisYear-1;
        switch ($amtClass){
            case 'OrderForAmt':
                $field = 'SUM(OrderForAmt)/10000';
                break;
            case 'InvoiceForAmt':
                $field = 'SUM(InvoiceForAmt)/10000';
                break;
            case 'BillForAmt':
                $field = 'SUM(BillForAmt)/10000';
                break;
            case 'ReceiptForAmt':
                $field = 'SUM(ReceiptForAmt)/10000';
                break;
        }
        $sql = "SELECT 
                Month, 
                Month + '0' AS Sort, 
                MAX(CASE WHEN Year = '{$preYear}' THEN YearTotal ELSE 0 END) AS preYear, 
                MAX(CASE WHEN Year = '{$thisYear}' THEN YearTotal ELSE 0 END) AS thisYear, 
                ISNULL(
                    CASE 
                        WHEN (MAX(CASE WHEN Year = '{$thisYear}' THEN YearTotal ELSE 0 END) - MAX(CASE WHEN Year = '{$preYear}' THEN YearTotal ELSE 0 END)) / NULLIF(MAX(CASE WHEN Year = '2023' THEN YearTotal ELSE 0 END), 0) = -1.0 
                        THEN 0.00 
                        ELSE (MAX(CASE WHEN Year = '{$thisYear}' THEN YearTotal ELSE 0 END) - MAX(CASE WHEN Year = '{$preYear}' THEN YearTotal ELSE 0 END)) / NULLIF(MAX(CASE WHEN Year = '2023' THEN YearTotal ELSE 0 END), 0) 
                    END, 0
                ) AS growRate 
            FROM 
                ( 
                    SELECT 
                        SUBSTRING(SumYM, 5, 2) AS Month, 
                        LEFT(SumYM, 4) AS Year, 
                        {$field} AS YearTotal 
                    FROM 
                        TSATotYM00 
                    WHERE 
                        SumType = '1' AND LEFT(SumYM, 4) IN ('{$preYear}', '{$thisYear}') 
                    GROUP BY 
                        LEFT(SumYM, 4), SUBSTRING(SumYM, 5, 2) 
                ) AS YearlyData 
            GROUP BY 
                Month 
            ORDER BY 
                Month;";
        return   Db::connect(self::chooseDb($db))->query($sql);
    }

    /**
     * 数据格式化处理
     * @param $result
     * @param $langID
     * @return array|array[]
     */
    public static function formateResult($result, $langID)
    {
        // 季度数据配置和语言选择
        $quarters = [
            ['Month' => '1분기계', 'Sort' => '031', 'startMonth' => 1, 'endMonth' => 3],
            ['Month' => '2분기계', 'Sort' => '061', 'startMonth' => 4, 'endMonth' => 6],
            ['Month' => '3분기계', 'Sort' => '091', 'startMonth' => 7, 'endMonth' => 9],
            ['Month' => '4분기계', 'Sort' => '121', 'startMonth' => 10, 'endMonth' => 12]
        ];
        $names = [
            'ENG' => ['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'],
            'KOR' => ['1분기계', '2분기계', '3분기계', '4분기계'],
            'default' => ['第一季度', '第二季度', '第三季度', '第四季度'],
        ];
        $names = $names[$langID] ?? $names['default']; // 根据语言选择名称

        // 季度数据和总数据计算的通用方法
        function calculateData($result, $startMonth = null, $endMonth = null)
        {
            $totalPreYear = $totalThisYear = 0;
            foreach ($result as $row) {
                $month = intval(substr($row['Sort'], 0, 2)); // 获取月份
                if (!$startMonth || ($month >= $startMonth && $month <= $endMonth)) {
                    $totalPreYear += floatval($row['preYear']);
                    $totalThisYear += floatval($row['thisYear']);
                }
            }
            return [$totalPreYear, $totalThisYear];
        }

        // 计算每个季度数据
        $quartersData = array_map(function ($quarter, $i) use ($result, $names) {
            list($preYear, $thisYear) = calculateData($result, $quarter['startMonth'], $quarter['endMonth']);
            return [
                'Month' => $names[$i],
                'Sort' => $quarter['Sort'],
                'preYear' => self::formatAmt($preYear), // 调用格式化函数
                'thisYear' => self::formatAmt($thisYear), // 调用格式化函数
                'growRate' => number_format((($thisYear - $preYear) / ($preYear ?: 1)) * 100, 2) . '%',
                'growColor' => self::percentColor(($thisYear - $preYear) / ($preYear ?: 1)),
                'backgroundColor' => self::getBackgroundColor($quarter['Sort'])
            ];
        }, $quarters, array_keys($quarters));

        // 计算 TOTAL 行
        list($totalPreYear, $totalThisYear) = calculateData($result);
        $totalRow = [
            'Month' => 'TOTAL',
            'Sort' => '999',
            'preYear' => self::formatAmt($totalPreYear), // 调用格式化函数
            'thisYear' => self::formatAmt($totalThisYear), // 调用格式化函数
            'growRate' => number_format((($totalThisYear - $totalPreYear) / ($totalPreYear ?: 1)) * 100, 2) . '%',
            'growColor' => '#353535', // 这里固定为灰色
            'backgroundColor' => self::getBackgroundColor('999')
        ];

        // 遍历并格式化每行数据
        foreach ($result as &$row) {
            $row['preYear'] = self::formatAmt(floatval($row['preYear']));
            $row['thisYear'] = self::formatAmt(floatval($row['thisYear']));
            // 格式化 growRate 保留两位小数，并添加百分号
            $row['growRate'] = number_format($row['growRate'] * 100, 2) . '%';
            $row['growColor'] = self::percentColor(floatval($row['growRate']) / 100);
        }

        // 合并季度数据和 TOTAL 行到结果集中，并排序
        $result = array_merge($result, $quartersData);
        $result[] = $totalRow;

        // 排序
        usort($result, function ($a, $b) {
            return $a['Sort'] <=> $b['Sort'];
        });

        return $result;
    }












}