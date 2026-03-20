<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-01 11:58
 */

namespace app\controller\modules\salesManage;


use app\controller\modules\Base;
use app\model\salesManage\DailyDatanModel;
use app\model\UserModel;
use think\facade\Config;
use think\facade\Request;

class DailyData extends Base
{
    public function lists()
    {
        $post = Request::post();
        $gunbun = $post['gunbun'];
        $baseDate = $post['baseDate'];
        $baseDate = date('Ymd',strtotime($baseDate));
        $userId = $this->getUserId();
        $result = DailyDatanModel::queryLists($userId,$gunbun,$baseDate);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function detail()
    {
        $post = Request::post();
        $gunbun = $post['gunbun'];
        $index = $post['index'];
        $baseDate = $post['baseDate'];
        $baseDate = date('Ymd',strtotime($baseDate));
        $langCode = Config::get('langCode.'.$post['langID']);
        $userId = $this->getUserId();
        $result = DailyDatanModel::queryDetail($userId,$index,$gunbun,$baseDate,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result' =>$result
        ]);
    }
}