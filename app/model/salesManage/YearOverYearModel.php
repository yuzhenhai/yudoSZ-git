<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-31 14:09
 */

namespace app\model\salesManage;


use app\controller\modules\salesManage\YearOverYear;
use app\model\BaseModel;
use think\facade\Db;

class YearOverYearModel extends BaseModel
{

    public static function getLists($dateY,$deptCd,$currGubn,$expClass,$deptDiv,$langCode)
    {
        $result = Db::connect(self::$DevDb)->query(" SET NOCOUNT ON; EXEC SSAPreYearCompare2_M ?, ?, ?, ?, ?, ?", [$dateY,$deptCd,$currGubn,$expClass,$deptDiv,$langCode]);
        return $result;
    }

    public static function getDept()
    {
        $sql = "select DeptNm as text,DeptCd as value from TMADept00
                where LEFT(DeptDiv2,6) = 'MA1004' GROUP BY DeptNm,DeptCd";
        return Db::connect(self::$DevDb)->query($sql);
    }

    public static function getDeptClass($langCode)
    {
        $sql = "select isnull(MULTIB.TransNm,MULTIA.MinorNm) AS text,
                        isnull(MULTIB.DictCd,MULTIA.MinorCd) AS value
                from TSMSyco10 MULTIA
                full join  TSMDict10 MULTIB on MULTIA.MinorCd = MULTIB.DictCd and MULTIB.LangCd = '{$langCode}'
                 where DeleteYn = 'N' AND MajorCd = 'MA1003'";

        return Db::connect(self::$DevDb)->query($sql);
    }

    public static function preResult($result)
    {
        // 初始化数据结构
        $separatedData = [
            'order' => [],
            'invoice' => [],
            'receipt' => [],
            'bill' => [],
            'total' => []
        ];

        // 定义数据类型和相应的金额键
        $types = ['order', 'invoice', 'bill', 'receipt'];
        $amtKeys = [
            'order' => ['DueYYOrderAmt', 'PreYYOrderAmt', 'OrderAchRate'],
            'invoice' => ['DueYYInvoiceAmt', 'PreYYInvoiceAmt', 'InvoiceAchRate'],
            'bill' => ['DueYYBillAmt', 'PreYYBillAmt', 'BillAchRate'],
            'receipt' => ['DueYYReceiptAmt', 'PreYYReceiptAmt', 'ReceiptAchRate'],

        ];

        // 格式化数据
        foreach ($result as $item) {
            foreach ($types as $type) {
                $separatedData[$type][] = self::formatData($item, $amtKeys[$type], $type);
            }
        }

        // 计算每种类型的总计并设置最后一条数据的颜色
        foreach ($types as $type) {
            $separatedData['total'][$type] = end($separatedData[$type]);
            $separatedData[$type][array_key_last($separatedData[$type])]['rateColor'] = '#353535';
        }

        return $separatedData;
    }

    // 格式化数据
    private static function formatData($item, $amtKeys, $type)
    {
        return [
            'Sort' => $item['Sort'],
            'Month' => $item['Month'],
            'amt' => self::formatAmt($item[$amtKeys[0]] / 10000), // 当前金额
            'preAmt' => self::formatAmt($item[$amtKeys[1]] / 10000), // 之前金额
            'rate' => self::formatAmt(($item[$amtKeys[2]] - 1) * 100) == '-100.00' ? '0.00' : self::formatAmt(($item[$amtKeys[2]] - 1) * 100), // 增长率
            'rateColor' => self::percentColor($item[$amtKeys[2]] - 1), // 增长率颜色
            'name'=>$type
        ];
    }


}
