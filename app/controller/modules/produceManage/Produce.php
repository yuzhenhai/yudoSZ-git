<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-26 13:08
 */

namespace app\controller\modules\produceManage;

use \app\controller\modules\Base;
use app\model\produceManage\ProduceModel;
use think\facade\Config;
use think\facade\Request;

class Produce extends Base
{
    public function dailyData()
    {
        $post = Request::post();
        $date = $post['date'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = ProduceModel::getDailyData($date,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function monthData()
    {
        $post = Request::post();
        $baseDate = $post['date'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = ProduceModel::getMonthData($baseDate,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function yearData()
    {
        $post = Request::post();
        $baseDate = $post['date'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = ProduceModel::getYearData($baseDate,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function lists()
    {
        $post = Request::post();
        $baseDate = $post['date'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $currId = $post['currId'];
        if($currId=='SA90010001'){
            $currId = 'C';
        }else{
            $currId = 'B';
        }
        $expClass = $post['expClass'];
        $result = ProduceModel::getLists($baseDate,$currId,$expClass,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getCurrList()
    {
        $langCode = Config::get('langCode.'.Request::post('langID'));
        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>ProduceModel::getCurrList($langCode)
        ]);
    }
}
