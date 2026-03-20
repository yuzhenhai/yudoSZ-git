<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-22 8:12
 */

namespace app\model\designManage;


use app\model\BaseModel;
use think\facade\Db;

class DailyDataModel extends BaseModel
{
    public static function getResult($date, $langID)
    {
        $result = Db::connect(self::$DevDb)->query("SET NOCOUNT ON; EXEC dbo.P_DE_900_9000_M ?, ?", [$date, $langID]);

        // 初始化数据结构
        $fields = [
            'ToDayOrderForAmt', 'DwAptForAmt', 'T_DwAptForAmt', 'TT_DwAptForAmt',
            'DwOutForAmt', 'T_DwOutForAmt', 'TT_DwOutForAmt', 'DrawMiOutForAmt',
            'ToDayWkAptForAmt', 'MiWkAptForAmt'
        ];

        $Internal = $External = $total = array_fill_keys($fields, 0);

        // 数据处理
        foreach ($result as $item) {
            $target = $item['ExternalGubn'] === 'External' ? $External : $Internal;

            foreach ($fields as $field) {
                $target[$field] += $item[$field];
                $total[$field] += $item[$field];
            }

            if ($item['ExternalGubn'] === 'External') {
                $External = $target;
            } else {
                $Internal = $target;
            }
        }

        // 返回结果
        return [
            'Internal' => self::preData($Internal),
            'External' => self::preData($External),
            'Total'    => self::preData($total),
        ];
    }

    public static function preData($arr)
    {
        // 数据格式化
        foreach ($arr as $key => $value) {
            $arr[$key] = self::formatAmt($value / 10000);
        }
        return $arr;
    }

    public static function getList($date,$langCode)
    {
        return Db::connect(self::$DevDb)->query("SET NOCOUNT ON; EXEC dbo.P_DE_900_9200_M ?, ?, ?", ['Q',$date, $langCode]);
    }

    public static function preList($result)
    {
        $data = [];
        foreach ($result as $item) {
            $item['is_total'] = !ctype_digit($item['Month']);
            if (!$item['is_total']) {
                $item['Month'] = intval($item['Month']);
            }
            // 格式化数字的通用方法
            $item = self::formatAmount($item);
            // 格式化百分比率的通用方法
            $item = self::formatRate($item);
            // 设置背景色的通用方法
            $item = self::setBackground($item);
            $data[] = $item;
        }
        $index = count($data)-1;
        $data[$index]['Dw_AptRate_color'] = "#353535";
        $data[$index]['Dw_AptAmt_Rate_color'] = "#353535";
        $data[$index]['Dw_OutRate_color'] = "#353535";
        $data[$index]['Dw_OutAmt_OldRate_color'] = "#353535";
        $data[$index]['Dw_AptRate_S_color'] = "#353535";
        $data[$index]['Dw_Apt_OldRate_color'] = "#353535";
        $data[$index]['Dw_OutRate_S_color'] = "#353535";
        $data[$index]['Dw_Out_OldRate_color'] = "#353535";
        return $data;
    }

    // 格式化金额的通用方法
    private static function formatAmount($item)
    {
        $fields = [
            'Dw_Out_Pre_S', 'Dw_Out_Old_Pre', 'Dw_Out_Old_Now', 'Dw_Out_Now_S',
            'Dw_AptCnt_Pre_S', 'Dw_AptCnt_Now_S', 'Dw_AptCnt_Old_Pre', 'Dw_AptCnt_Old_Now',
            'Dw_AptCnt_Pre', 'Dw_AptCnt_Now', 'Dw_Out_Pre', 'Dw_Out_Now'
        ];

        foreach ($fields as $field) {
            $item[$field] = number_format(intval($item[$field]));
        }

        $fieldsWithDecimal = [
            'Dw_AptAmt_Pre', 'Dw_AptAmt_Now', 'Dw_OutAmt_Old_Pre', 'Dw_OutAmt_Old_Now'
        ];

        foreach ($fieldsWithDecimal as $field) {
            $item[$field] = number_format(round($item[$field] / 10000, 2), 2, '.', ',');
        }

        return $item;
    }

    // 格式化百分比的通用方法
    private static function formatRate($item)
    {
        $rateFields = [
            'Dw_AptRate', 'Dw_AptAmt_Rate', 'Dw_OutRate', 'Dw_OutAmt_OldRate',
            'Dw_AptRate_S', 'Dw_Apt_OldRate', 'Dw_OutRate_S', 'Dw_Out_OldRate'
        ];

        foreach ($rateFields as $field) {
            if (isset($item[$field])) {
                $item[$field] = round(($item[$field] * 100 - 100), 2);
                $item[$field] = number_format($item[$field], 2);
                // 设置颜色
                $colorField = $field . '_color';
                if ($item[$field] > 0) {
                    $item[$colorField] = "#FF6259"; // red
                } else {
                    $item[$colorField] = "#07BE00"; // green
                }
                // 处理特殊值
                if ($item[$field] == "-100.00") {
                    $item[$field] = "0.00";
                    $item[$colorField] = "#FF6259"; // red
                }
            }
        }

        return $item;
    }

    // 设置背景色的通用方法
    private static function setBackground($item)
    {
        if (in_array($item['Sort'], ['31', '61', '91', '121'])) {
            $item['background'] = "#FFFF00"; // yellow
        } elseif ($item['Sort'] == "999") {
            $item['background'] = "#DA70D6"; // purple
        } else {
            $item['background'] = '';
        }

        return $item;
    }


}