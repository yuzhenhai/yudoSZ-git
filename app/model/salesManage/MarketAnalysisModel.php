<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-05 14:45
 */

namespace app\model\salesManage;

use app\model\BaseModel;
use DateTime;
use think\facade\Db;

class MarketAnalysisModel extends BaseModel
{
    public static function getLists($gubun,$date,$langCode)
    {
        $dateObj = new DateTime($date);
        $lastDayOfMonth = $dateObj->modify('last day of this month')->format('Ymd');
        $sameDayLastYear = $dateObj->modify('-1 year')->format('Ymd');
        $sql = "EXEC dbo.SSAMarket_SZ_M '$gubun', '$date', '$lastDayOfMonth', '$sameDayLastYear', '$langCode'";
        $result =  Db::connect(self::$DevDb)->query($sql);
        return $result;
    }

    public static function preLists($result)
    {
        // 计算所有 amt 和 amtPre 的总和
        $totalAmt = 0;
        $totalAmtPre = 0;
        foreach ($result as $item) {
            $totalAmt += floatval($item['OrderforAmt']);
            $totalAmtPre += floatval($item['OrderforAmt_pre']);
        }
        $data1 = [];
        $data2 = [];
        // 遍历结果并添加 rate, ratePre, growthRate，格式化并计算 amt 和 amtPre
        foreach ($result as &$item) {
            $amt = floatval($item['OrderforAmt']);
            $amtPre = floatval($item['OrderforAmt_pre']);

            // 计算 rate 和 ratePre
            $item['rate'] = $totalAmt ? $amt / $totalAmt : 0;
            $item['ratePre'] = $totalAmtPre ? $amtPre / $totalAmtPre : 0;

            // 计算 growthRate
            $item['growthRate'] = $amtPre ? (($amt - $amtPre) / $amtPre) * 100 : 0;

            // 修改字段名为 amt 和 amtPre
            $item['amt'] = $amt;
            $item['amtPre'] = $amtPre;

            $_data1 = [];
            $_data2 = [];
            $_data1['name'] = $item['MinorNm'];
            $_data1['value'] = round($item['amt'] / 10000,2);
            $_data2['name'] = $item['MinorNm'];
            $_data2['value'] = round($item['amtPre'] / 10000,2);
            $data1[] = $_data1;
            $data2[] = $_data2;

            // 格式化操作
            $item['rate'] = self::formatAmt($item['rate'] * 100);
            $item['ratePre'] = self::formatAmt($item['ratePre'] * 100);
            $item['growthRate'] = self::formatAmt($item['growthRate']);
            $item['amt'] = self::formatAmt($item['amt'] / 10000);
            $item['amtPre'] = self::formatAmt($item['amtPre'] / 10000);
            $item['rateColor'] = self::percentColor($item['growthRate']);
            // 移除原始字段名
            unset($item['OrderforAmt'], $item['OrderforAmt_pre']);
        }
        return [
            'result'=>$result,
            'chart'=>[$data1,$data2]
        ];
    }


}