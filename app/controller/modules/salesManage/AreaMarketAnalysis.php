<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-06 9:02
 */

namespace app\controller\modules\salesManage;


use app\controller\modules\Base;
use app\model\salesManage\AreaMarketAnalysisModel;
use think\facade\Config;
use think\facade\Request;

class AreaMarketAnalysis extends Base
{
    public function lists()
    {
        $post = Request::post();
        $date = $post['date'];
        $gubun = $post['gubun'];
        $langCode = Config::get('langCode.' . Request::post('langID'));
        // 获取列表数据
        $result = AreaMarketAnalysisModel::getLists($gubun, $date, $langCode);
        // 检查 $result 是否为有效的数组，且有下标 0
        if (is_array($result) && isset($result[0])) {
            $result = AreaMarketAnalysisModel::preLists($result[0], $langCode);
        } else {
            // 处理返回为空或无数据的情况，设定默认值或空数组
            $result = AreaMarketAnalysisModel::preLists([], $langCode);
        }
        // 返回数据
        return json([
            'statusCode' => self::getCode('SUCCESS'),
            'result' => $result
        ]);
    }


    public function detail()
    {
        $post = Request::post();
        $gunbun = $post['gunbun'];
        $marketCd = $post['marketCd'];
        $baseDate = $post['baseDate'];
        $langCode = Config::get('langCode.'.$post['langID']);
        $result = AreaMarketAnalysisModel::queryDetail($marketCd,$gunbun,$baseDate,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result' =>$result
        ]);
    }

}