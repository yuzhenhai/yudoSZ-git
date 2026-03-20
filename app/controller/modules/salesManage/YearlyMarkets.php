<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-04 9:33
 */

namespace app\controller\modules\salesManage;


use app\controller\modules\Base;
use app\model\salesManage\YearlyMarketsModel;
use think\facade\Config;
use think\facade\Request;
use DateTime;
class YearlyMarkets extends Base
{
    public function lists()
    {
        // 获取请求参数
        $requestData = Request::post();
        $selectedDate = $requestData['date'];
        $expClass = $requestData['expClass'];
        $dataType = $requestData['type'];
        $languageCode = Config::get('langCode.' . Request::post('langID'));
        // 获取当前年份的数据
        $currentYearData = YearlyMarketsModel::getLists($selectedDate, $expClass, $dataType, $languageCode);
        // 计算去年的同一天
        $dateTime = new DateTime($selectedDate);
        $dateTime->modify('-1 year');
        $previousYearDate = $dateTime->format('Y-m-d');
        // 获取去年的数据
        $previousYearData = YearlyMarketsModel::getLists($previousYearDate, $expClass, $dataType, $languageCode);
        $result = YearlyMarketsModel::preLists($previousYearData,$currentYearData);

        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function detail()
    {
        $post = Request::post();
        $date = $post['date'];
        $expClass = $post['expClass'];
        $type = $post['type'];
        $MarketCd = $post['MarketCd'];
        $langID = $post['langID'];
        $result = YearlyMarketsModel::getDetail($date, $expClass, $type, $MarketCd);
        $result = YearlyMarketsModel::preDetail($result,$langID);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

}