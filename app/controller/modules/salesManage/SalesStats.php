<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-23 14:58
 */

namespace app\controller\modules\salesManage;


use app\controller\modules\Base;
use app\model\salesManage\SalesStatsModel;
use think\facade\Config;
use think\facade\Request;

class SalesStats extends Base
{
    public function lists()
    {
        $post = Request::post();
        $gunbun = $post['gunbun'];
        $baseDate = $post['baseDate'];
        $baseDate = date('Ymd',strtotime($baseDate));
        $result = SalesStatsModel::queryLists($gunbun,$baseDate);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function detail()
    {
        $post = Request::post();
        $index = $post['index'];
        $date = $post['date'];
        $langCode = Config::get('langCode.'.$post['langID']);
        $result = SalesStatsModel::queryDetail($index,$date,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }
}