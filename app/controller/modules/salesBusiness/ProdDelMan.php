<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-12-04 13:19
 */

namespace app\controller\modules\salesBusiness;

use app\controller\modules\Base;
use app\model\salesBusiness\ProdDelManModel;
use app\model\UserModel;
use think\facade\Config;
use think\facade\Request;

class ProdDelMan extends Base
{
    //asdasda
    public function lists()
    {
        $post = Request::post();
        $date = $post['date'];
        $accordingClass = $post['accordingClass'];
        $accordingNo= $post['accordingNo'];
        $custNm= $post['custNm'];
        $RefNo= $post['RefNo'];
        $userId = $this->getUserId();
        $auth = UserModel::getAuth('WEI_2400',$userId);
        $authData = ProdDelManModel::getAuthData($auth,$userId);
        $result = ProdDelManModel::getLists($authData,$date,$accordingClass,$accordingNo,$custNm,$RefNo);
//        $result = ProdDelManModel::preLists($result);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function detail()
    {
        $planNo = Request::post()['planNo'];
        $result = ProdDelManModel::getDetail($planNo);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function info()
    {
        $post = Request::post();
        $planNo = $post['planNo'];
        $wccd = $post['wccd'];
        $langCode = Config::get('langCode.' . Request::post('langID'));
        $result = ProdDelManModel::getInfo($planNo,$wccd);
        $result = ProdDelManModel::preInfo($result,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }
}