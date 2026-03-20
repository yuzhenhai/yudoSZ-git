<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-26 13:09
 */

namespace app\controller\modules\purchaseManage;


use app\controller\modules\Base;
use app\model\purchaseManage\PurchaseModel;
use think\facade\Config;
use think\facade\Request;

class Purchase extends Base
{
    public function stats()
    {
        $post = Request::post();
        $baseDate = $post['date'];
        $imp = $post['imp'];
        $basic = $post['basic'];
        if($imp=='PO00010001'){
            $imp = 1;
        }else if($imp=='PO00010002'){
            $imp = 4;
        }
        if($basic=='PO90020010'){
            $basic = 'O';
        }elseif($basic=="PO90020020"){
            $basic = 'D';
        }elseif($basic=="PO90020030"){
            $basic = 'I';
        }elseif($basic=="PO90020040"){
            $basic = 'A';
        }
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = PurchaseModel::getStats($baseDate,$imp,$basic,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function statsDetail()
    {
        $post = Request::post();
        $baseDate = $post['date'];
        $name = $post['name'];
        $imp = $post['imp'];
        $basic = $post['basic'];
        if($imp=='PO00010001'){
            $imp = 1;
        }else if($imp=='PO00010002'){
            $imp = 4;
        }
        if($basic=='PO90020010'){
            $basic = 'O';
        }elseif($basic=="PO90020020"){
            $basic = 'D';
        }elseif($basic=="PO90020030"){
            $basic = 'I';
        }elseif($basic=="PO90020040"){
            $basic = 'A';
        }
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = PurchaseModel::getStatsDetail($name,$baseDate,$imp,$basic,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function lists()
    {
        $post = Request::post();
        $baseDate = $post['date'];
        $imp = $post['imp'];
        $type = $post['type'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        if($imp=='PO00010001'){
            $imp = 1;
        }else if($imp=='PO00010002'){
            $imp = 4;
        }
        if($type=='PO90010020'){
            $type = "O";
        }else if($type=='PO90010010'){
            $type = "P";
        }
        $result = PurchaseModel::getLists($baseDate,$imp,$type,$langCode);
        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>$result
        ]);
    }

    public function detail()
    {
        $post = Request::post();
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $baseDate = $post['date'];
        $imp = $post['imp'];
        $type = $post['type'];
        $basic = $post['basic'];
        if($imp=='PO00010001'){
            $imp = 1;
        }else if($imp=='PO00010002'){
            $imp = 4;
        }
        if($type=='PO90010020'){
            $type = "O";
        }else if($type=='PO90010010'){
            $type = "P";
        }
        $result = PurchaseModel::getDetail($baseDate,$imp,$type,$basic,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getSelectList()
    {
        $post = Request::post();
        $type = $post['type'];
        $langCode = Config::get('langCode.'.Request::post('langID'));
        $result = PurchaseModel::getSelectList($type,$langCode);
        if($type=='PO9001'){
            $default = [];
            $default['text']  = $this->getDefaultOption($langCode);
            $default['value'] = '';
            array_unshift($result, $default);
        }
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getDefaultOption($langCode)
    {
        return PurchaseModel::getDefaultOption($langCode);
    }

}