<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-29 8:30
 */

namespace app\model\salesManage;

use app\model\BaseModel;
use think\facade\Db;

class UnmetPerformanceModel extends BaseModel
{
    public static function getList($baseDate,$langCode)
    {
        $result = Db::connect(self::$DevDb)->query("EXEC SSADayTotal_SZ2_M ?, ?, ?", ['P', $baseDate,$langCode])[0];

        return $result;
    }

    public static function preResult($data)
    {
        // 初始化结果数组
        $result = [
            'MiInvoiceForAmt' => self::initializeAmountArray(),
            'MiBillForAmt' => self::initializeAmountArray(),
            'MiReceiptForAmt' => self::initializeAmountArray(),
            'MiProductForAmt' => self::initializeAmountArray(),
            'MiWkAptForAmt' => self::initializeAmountArray(),
            'DrawMiOutForAmt' => self::initializeAmountArray(),
        ];

        // 遍历数据并计算
        foreach ($data as $item) {
            foreach (['MiInvoiceForAmt', 'MiBillForAmt', 'MiReceiptForAmt', 'MiProductForAmt', 'MiWkAptForAmt', 'DrawMiOutForAmt'] as $key) {
                $amount = (float)$item[$key] / 10000;

                // 更新总和
                $result[$key]['Total'] += $amount;
                $result[$key][$item['ExternalGubn'] == '1' ? 'ExternalAmt' : 'InternalAmt'] += $amount;

                // 分类
                $deptArray = $item['ExternalGubn'] == '1' ? 'External' : 'Internal';
                $result[$key][$deptArray][$item['DeptDiv1']] = ($result[$key][$deptArray][$item['DeptDiv1']] ?? 0) + $amount;
            }
        }

        // 对 External 和 Internal 数组进行排序
        foreach (['External', 'Internal'] as $type) {
            foreach ($result as &$keyData) {
                arsort($keyData[$type]);
            }
        }

        // 格式化金额
        foreach ($result as &$keyData) {
            self::formatAmounts($keyData);
        }
        return $result;
    }

    // 初始化金额数组
    private static function initializeAmountArray()
    {
        return [
            'Total' => 0,
            'ExternalAmt' => 0,
            'External' => [],
            'InternalAmt' => 0,
            'Internal' => [],
        ];
    }

    // 格式化金额
    private static function formatAmounts(&$keyData)
    {
        $keyData['Total'] = number_format($keyData['Total'], 2, '.', ',');
        $keyData['ExternalAmt'] = number_format($keyData['ExternalAmt'], 2, '.', ',');
        $keyData['InternalAmt'] = number_format($keyData['InternalAmt'], 2, '.', ',');

        foreach ($keyData['External'] as $dept => $amt) {
            $keyData['External'][$dept] = number_format($amt, 2, '.', ',');
        }
        foreach ($keyData['Internal'] as $dept => $amt) {
            $keyData['Internal'][$dept] = number_format($amt, 2, '.', ',');
        }
    }

}