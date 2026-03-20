<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-15 14:03
 */

namespace app\controller\modules\businessInfo;

use app\controller\modules\Base;
use app\model\businessInfo\SalesConditionModel;
use think\facade\Request;


class SalesCondition extends Base
{
    public function index()
    {
        $post = Request::post();
        $db = $post['db'];
        $date = $post['date'];
        $amtClass = $post['amtClass'];
        $result = SalesConditionModel::getList($db,$date,$amtClass);
        $rate = ($result[1]['amt']  - $result[0]['amt']) / $result[0]['amt'];
        $edg = 90 + 90 * $rate;
        if($edg<0)$edg=0;
        if($edg>180)$edg=180;
        $result[0]['amt'] = $this->formatAmt($result[0]['amt']);
        $result[1]['amt'] = $this->formatAmt($result[1]['amt']);
        $ratePercentage = number_format($rate * 100, 2, '.', '');
        return json([
            'statusCode' => self::getCode('SUCCESS'),
            'result' =>[
                'data' => $result,
                'color'=> $this->percentColor($ratePercentage),
                'rate' => $ratePercentage,
                'edg'=> $edg
            ]
        ]);
    }

    public function detail()
    {
        $post = request::post();
        $db = $post['db'];
        $date = $post['date'];
        $amtClass = $post['amtClass'];
        $langID = $post['langID'];
        $result = SalesConditionModel::getDetail($db,$date,$amtClass);
        $result = SalesConditionModel::formateResult($result,$langID);
        return json(['statusCode'=>self::getCode('SUCCESS'),'result'=>$result]);
    }
}