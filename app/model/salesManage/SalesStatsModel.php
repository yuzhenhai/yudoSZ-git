<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-24 10:21
 */

namespace app\model\salesManage;


use app\model\BaseModel;
use think\facade\Db;

class SalesStatsModel extends BaseModel
{
    public static function queryLists($workingTag, $baseDate)
    {
        // 查询结果
        $result = Db::connect(self::$DevDb)->query("EXEC SSADayTotal_SZ2_M2_p ?, ?", [$workingTag, $baseDate])[0];
        $data = [];
        // 计算当前年份和前一年
        $currentYear = date('Y', strtotime($baseDate));
        $previousYear = $currentYear - 1;
        // 定义分类
        $categories = [
            110 => 'order',
            210 => 'invoice',
            310 => 'bill',
            410 => 'receipt',
            510 => 'invoicePro'
        ];
        // 遍历结果并分类存储数据
        foreach ($result as $item) {
            $categoryKey = $categories[$item['Sort']] ?? null;
            if ($categoryKey) {
                $forAmt = self::formatAmt($item['ForAmt']);
                $forAmtPre = self::formatAmt($item['ForAmt_Pre']);
                $growthRate = ($item['ForAmt_Pre'] != 0) ? (($item['ForAmt'] - $item['ForAmt_Pre']) / $item['ForAmt_Pre']) * 100 : 0;

                $data[$categoryKey] = [
                    'growthRate' => number_format($growthRate, 2) . '%',
                    'rateColor' => self::percentColor(number_format($growthRate, 2) . '%'),
                    'forAmt' => $forAmt,
                    'forAmtPre' => $forAmtPre,
                    'year' => $currentYear,
                    'yearPre' => $previousYear,
                ];
            }
        }
        return $data;
    }

    public static function queryDetail($type, $date, $langCode)
    {
        // 根据类型设置 SQL 查询
        if ($type === 'invoicePro') {
            $result = Db::connect(self::$DevDb)->query("SET NOCOUNT ON; EXEC P_PM_900_1200_M 'Q', '{$date}', '', '', '', '', 'SM00010003'");
        } else {
            $result = Db::connect(self::$DevDb)->query("SET NOCOUNT ON; EXEC SSAPreYearCompare2_M ?, ?, ?, ?, ?, ?", [$date, '', '', '', '', $langCode]);
        }
        if (!$result) {
            return []; // 查询失败时返回空数组
        }
        $data = [];
        $fieldMap = [
            'order' => ['preYear' => 'PreYYOrderAmt', 'thisYear' => 'DueYYOrderAmt', 'rate' => 'OrderAchRate'],
            'invoice' => ['preYear' => 'PreYYInvoiceAmt', 'thisYear' => 'DueYYInvoiceAmt', 'rate' => 'InvoiceAchRate'],
            'bill' => ['preYear' => 'PreYYBillAmt', 'thisYear' => 'DueYYBillAmt', 'rate' => 'BillAchRate'],
            'receipt' => ['preYear' => 'PreYYReceiptAmt', 'thisYear' => 'DueYYReceiptAmt', 'rate' => 'ReceiptAchRate'],
            'invoicePro' => ['preYear' => 'PreYYOrderAmt', 'thisYear' => 'DueYYOrderAmt', 'rate' => 'OrderAchRate']
        ];
        // 检查字段映射是否存在
        if (!isset($fieldMap[$type])) {
            return [];
        }
        // 获取字段映射
        $fields = $fieldMap[$type];
        foreach ($result as $item) {
            $growRate = number_format(($item[$fields['rate']] - 1) * 100, 2);
            $row = [
                'Month' => $item['Month'],
                'Sort' => $item['Sort'],
                'preYear' => self::formatAmt($item[$fields['preYear']] / 10000),
                'thisYear' => self::formatAmt($item[$fields['thisYear']] / 10000),
                'growRate' => ($growRate === "-100.00" ? "0.00" : $growRate) . "%",
                'growColor' => self::percentColor($item[$fields['rate']] - 1),
                'backgroundColor' => self::getBackgroundColor($item['Sort']),
            ];
            if($row['Sort']=='999') $row['growColor'] = '#353535';
            $data[] = $row;
        }
        return $data;
    }
}
