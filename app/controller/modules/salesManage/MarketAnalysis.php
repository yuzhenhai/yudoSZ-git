<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-05 14:44
 */

namespace app\controller\modules\salesManage;


use app\controller\modules\Base;
use app\model\salesManage\MarketAnalysisModel;
use think\facade\Config;
use think\facade\Request;

class MarketAnalysis extends Base
{
    public function lists()
    {
        $post = Request::post();
        $date = $post['date'];
        $gubun = $post['gubun'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = MarketAnalysisModel::getLists($gubun,$date,$langCode);
        $data = MarketAnalysisModel::preLists($result[0]);
        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>$data['result'],
           'chart'=>$data['chart']
        ]);
    }
}