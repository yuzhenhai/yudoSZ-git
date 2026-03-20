<?php
/**
 * Class User
 * @package app\controller
 * 用户操作接口,如登录验证、获取用户数据、菜单权限等
 */

namespace app\controller;

use app\BaseController;
use app\common\CurlHelper;
use think\App;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Request;
use think\facade\Config;
use app\model\BaseModel;
class KAPPAPI extends BaseController
{

    public function __construct(App $app)
    {
        parent::__construct($app);

    }


    /**
     *登录接口
     * @return \think\response\Json
     */
    public function index()
    {
        if(Request::isPost()){
            $param = Request::param();
            if(!empty($param['SiteCode']) && !empty($param['work_type'])){

                $work_type = $param['work_type'];
                $FrDate = $param['FrDate'];
                $ToDate = $param['ToDate'];
                // $tier1 = $_POST['tier1'];
                $SiteCode = '';
                if($param['SiteCode'] == 'SZ'){
                    $SiteCode = 'sqlSZsrv';
                }else if($param['SiteCode'] == 'TD'){
                    $SiteCode = 'sqlGDsrv';
                }else if($param['SiteCode'] == 'QD'){
                    $SiteCode = 'sqlQDsrv';
                }
                if(empty($SiteCode)){
                    return json('Error in value transmission, please check');
                }

                $input = ' @p_work_type = ?,@p_FrDate = ?,@p_ToDate=?';
                $output = [$work_type, $FrDate,$ToDate];
                try{

                    $list = BaseModel::execSp('dbo.P_SKAPP_API_OrderInfo_Q',$input,$output,$SiteCode);

                    return json($list);
                } catch (Exception $e) {
                   return json('Error in value transmission, please check');
                }


            }else{
                exit('Error in value transmission, please check');
            }
        }else{
            return json('Please contact the administrator');
        }

    }


}
