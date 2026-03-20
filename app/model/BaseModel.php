<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-09-30 15:56
 */

namespace app\model;
use think\facade\Db;
use think\Model;

/**
 * Class BaseModel
 * @package app\model
 * 继承TP框架底层 Model类
 * 进行功能拓展
 */
class BaseModel extends Model
{
    const AUTH_A = 'SM00040001';
    const AUTH_D = 'SM00040002';
    const AUTH_E = 'SM00040003';
    const AUTH_J = 'SM00040004';
    const AUTH_M = 'SM00040005';

    /**
     * 查询的数据库 方便查询正式库数据,录入数据的时候就可以不用
     * @var string
     */

    public static $Db =  'sqlsrv';
    public static $DevDb =  'devSrv';



    public static function execSp($spName,$input,$output,$db=null){
        if($db){
            $result = Db::connect($db)->query("EXEC $spName $input;", $output);
        }else{
            $result = Db::query("EXEC $spName $input;", $output);
        }
        if(isset($result[0])){
            return $result[0];
        }else{
            return $result;
        }

    }

    public static function chooseDb($DB){
        switch ($DB) {
            case 'DEV':
                $db = 'sqlsrv';
                break;
            case 'SZ':
                $db = 'sqlSZsrv';
                break;
            case 'GD':
                $db = 'sqlGDsrv';
                break;
            case 'QD':
                $db = 'sqlQDsrv';
                break;
            case 'XR':
                $db = 'sqlXRsrv';
                break;
            case 'HS':
                $db = 'sqlHSsrv';
                break;
            case 'LLSZ':
                $db = 'sqlRASZsrv';
                break;
            case 'SH':
                $db = 'sqlYCHsrv';
                break;
            case 'LL':
                $db = 'sqlYCHsrv';
                break;
            case 'CL':
                $db = 'sqlYCHsrv';
                break;
            case 'ABE':
                $db = 'sqlYCHsrv';
                break;

            default:
                $db = 'sqlsrv';

                break;
        }
        return $db;

    }
    /**
     * 返回百分百比的颜色,列表页
     * @param $percent
     * @return string
     */
    public static function percentColor($percent)
    {
        if($percent<=0){
            return '#07BE00';   //绿
        }
        return '#FF6259';   //红
    }

    /**
     * 返回显示的背景色
     * @param $index
     * @return string
     */
    public static function detailBgColor($index)
    {
        if($index== 0){
            return '#DA70D6';
        }
        return '#FFFF00';
    }


    /**
     *  返回查询详情页面的季度/total的背景颜色
     * @param $sort
     * @return string|null
     */
    public static function getBackgroundColor($sort)
    {
        $highlightSorts = ['031', '061', '091', '121'];
        if (in_array($sort, $highlightSorts)) {
            return '#FFFF00';
        }
        return $sort === '999' ? '#DA70D6' : '';
    }

    /**
     * 返回百分百比的颜色,详情页
     * @param $percent
     * @return string
     */
    public static function percentColorDetail($percent)
    {
        if($percent<0){
            return '#07BE00';   //绿
        }
        return '#FF6259';   //红
    }

    /**
     * 格式化金额 千分位 包留 2位小数
     * @param $amt
     * @return string
     */
    public static function formatAmt($amt)
    {
        return number_format($amt, 2, '.', ',');
    }

    /**
     * 用户权限查询
     * @param $formId
     * @param $UserID
     * @return string
     */
    public static function getAuth($formId,$UserID){

        $auth =Db::query("select
                        A.form_auth as user_form_auth,
                        '' group_form_auth ,
                        A.form_confirm_yn,
                        A.form_save_yn,
                        A.form_delete_yn
                        from sysUserMenu A
                        left join sysMenuPool B on A.menu_id = B.menu_id
                        where B.form_id=?
                        AND A.user_id=?
                        UNION ALL
                        select '' user_form_auth,
                        M2.form_auth as group_form_auth,
                        M2.form_confirm_yn,
                        M2.form_save_yn,
                        M2.form_delete_yn
                        from sysUserGroupMapping M1
                        left join sysUserGroupMenu M2 on M1.user_group_id = M2.user_group_id
                        left join sysMenuPool M3 on M2.menu_id = M3.menu_id where M3.form_id=?  AND M1.user_id =?", [$formId,$UserID,$formId,$UserID]);

        // exit(json_encode($auth[0]['user_form_auth']));
        //如果个人/组权限都为空则检查是否是管理员
        //
        //
         $isAdmin = DB::query("select user_category from sysUserMaster where user_id=:user_id",['user_id'=>$UserID]);
            if(isset($isAdmin[0]['user_category'])){
                if($isAdmin[0]['user_category'] == 'ADMIN') {
                    $auths = 'SM00040001';
                }else if(empty($auth[0]['user_form_auth'])){
                    if(empty($auth[0]['group_form_auth'])){
                        $auths = 'NO';
                    }else{
                        $auths = isset($auth[0]['group_form_auth'])?$auth[0]['group_form_auth']:'NO';
                    }
                }else{
                    $auths = isset($auth[0]['user_form_auth'])?$auth[0]['user_form_auth']:'NO';
                }
            }else{


            if(empty($auth[0]['user_form_auth'])){
                if(empty($auth[0]['group_form_auth'])){

                     $auths = 'NO';


                }else{
                    $auths = isset($auth[0]['group_form_auth'])?$auth[0]['group_form_auth']:'NO';
                }
            }else{
                // $this->setCookie('auth',$auth['user_form_auth'],ALWAYS,false);
                $auths = isset($auth[0]['user_form_auth'])?$auth[0]['user_form_auth']:'NO';
            }
        }
        return $auths;

    }

    public static function SystemclassBigPrc($MinorCd,$langCode)
    {
        $sql = "select
                isnull(MULTIB.TransNm,MULTIA.MinorNm) AS text,
                isnull(MULTIB.DictCd,MULTIA.MinorCd) AS value,
                MULTIA.DeleteYn AS status
                from TSMSyco10 MULTIA With(Nolock)
                full join  TSMDict10 MULTIB With(Nolock) on MULTIA.MinorCd = MULTIB.DictCd and MULTIB.LangCd = '$langCode'
                where MULTIA.MajorCd = '".$MinorCd."'
                ";
        $list = Db::query($sql);
        return $list;
    }


    public static function SystemClass($RelCd1,$MULTIA,$langCode='SM00010003'){
        $sql = "select
                isnull(MULTIB.TransNm,MULTIA.MinorNm) AS text,
                isnull(MULTIB.DictCd,MULTIA.MinorCd) AS value,
                MULTIA.DeleteYn AS status
                from TSMSyco10 MULTIA With(Nolock)
                full join TSMDict10 MULTIB With(Nolock) on MULTIA.MinorCd = MULTIB.DictCd and MULTIB.LangCd = '$langCode'
                where MULTIA.RelCd1 = '".$RelCd1."' AND MULTIA.MinorCd LIKE '".$MULTIA."%'
                ";

        $list = Db::query($sql);
        return $list;
    }

    public static function OAInterface($OAwhere)
    {
        $list = DB::table('TS_OA_Interface')->field('SourceNo,OA_Status')
                ->where($OAwhere)
                ->find();
        return $list;
    }
//
}