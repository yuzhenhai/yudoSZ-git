<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-31 13:58
 */

namespace app\controller\modules\salesManage;


use app\controller\modules\Base;
use app\model\salesManage\YearOverYearModel;
use think\facade\Config;
use think\facade\Request;


class YearOverYear extends Base
{

    public function Lists()
    {
        $post = Request::post();
        $dateY = $post['date'];
        $deptCd = $post['dept'];
        $expClass = $post['expClass'];
        $deptDiv = $post['depClass'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = YearOverYearModel::getLists($dateY,$deptCd,'B',$expClass,$deptDiv,$langCode);
        $result = YearOverYearModel::preResult($result);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getDeptClass()
    {
        $langID = Config::get('langCode.'.Request::post('langID'));
        $result = YearOverYearModel::getDeptClass($langID);
        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>$result
        ]);
    }

    public function getDept()
    {
        $result = YearOverYearModel::getDept();
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }
}