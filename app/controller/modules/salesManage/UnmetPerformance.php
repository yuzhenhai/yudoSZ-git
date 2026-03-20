<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-29 8:32
 */

namespace app\controller\modules\salesManage;


use app\controller\modules\Base;
use app\model\salesManage\UnmetPerformanceModel;
use think\facade\Config;
use think\facade\Request;

class UnmetPerformance extends Base
{
    public function lists()
    {
        $post = Request::post();
        $baseDate = date('Ymd',strtotime($post['date']));
        $langID = Config::get('langCode.'.$post['langID']);
        $result = UnmetPerformanceModel::getList($baseDate,$langID);
        $result = UnmetPerformanceModel::preResult($result);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }
}