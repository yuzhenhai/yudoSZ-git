<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-09-30 15:53
 */

namespace app\controller\modules;

use app\controller\Api;

/**
 * Class Base
 * @package app\controller\modules
 * 模块开发的底层类 继承Api
 */
class Base extends Api
{
    private static $systemClass = array(
        'ascause'        => 'AS1011',
        'ascause_c'      => 'AS1015',
        'area'           => 'AS1013',
        'markets'        => 'SA1025',
        'asclass'        => 'AS1002',
        'asclass1'       => 'AS1006',
        'asclass1_c'     => 'AS1007',
        'startpoint'     => 'AS1003',
        'asbadtype'      => 'AS1004',
        'supplyscope'    => 'SA1034',
        'supplyscope_c1' => 'SA1035',
        'supplyscope_c2' => 'SA1036',
        'supplyscope_c3' => 'SA1037',
        'supplyscope_c4' => 'SA1038',
        'supplyscope_c5' => 'SA1039',
        'actStatus'     => 'OA1003',
        'CustPattern'     => 'OA2001',
        'actGubun'        => 'OA1001',
        'actGubunClass'   => 'OA1002',
        'reptActGubunClass' => 'OA1002',
        'MoveMethod'     => 'OA1005',
        'products'     => 'SA1026',

    );
    /**
     * 返回百分百比的颜色,列表页
     * @param $percent
     * @return string
     */
    public function percentColor($percent)
    {
        if($percent<=0){
            return '#07BE00';   //绿
        }
        return '#FF6259';   //红
    }

    /**
     * 返回百分百比的颜色,详情页面
     * @param $percent
     * @return string
     */
    public function percentColorDetail($percent)
    {
        if($percent<0){
            return '#07BE00';   //绿
        }
        return '#FF6259';   //红
    }

    /**
     * 格式化金额 千分位 包留 2位小数
     * @param $amt
     * @return string
     */
    public function formatAmt($amt)
    {
       return number_format($amt, 2, '.', ',');
    }
    public function langCode($langCode)
    {
        switch ($langCode) {       //查看语言选项
                case "KOR":
                    $langCode = "SM00010001";
                    break;
                case "CHN":
                    $langCode = "SM00010003";
                    break;
                case "ENG":
                    $langCode = "SM00010002";
                    break;
                case "JPN":
                    $langCode = "SM00010004";
                    break;
                default:
                    $langCode = "SM00010003";
                    break;
            }
        return $langCode;
    }
    public function systemClass($systemClass)
    {
        return self::$systemClass[$systemClass];
    }
}