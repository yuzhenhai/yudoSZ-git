<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-22 8:11
 */

namespace app\controller\modules\designManage;


use app\controller\modules\Base;
use app\model\designManage\DailyDataModel;
use think\facade\Config;
use think\facade\Request;

class DailyData extends Base
{
    public function index()
    {
        $post = Request::post();
        $date = date('Ymd',strtotime($post['date']));
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = DailyDataModel::getResult($date,$langCode);
        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>$result
        ]);
    }

    public function lists()
    {
        $post = Request::post();
        $date = $post['date'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = DailyDataModel::getList($date,$langCode);
        $result = DailyDataModel::preList($result);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>[
                'list'=>$result,
                'total'=>$result[count($result)-1]
            ]
        ]);
    }
}