<?php
/**
 * @Author: YUZH
 * @Date: 2024-09-30 15:52
 */

namespace app\controller\modules\salesBusiness;

use app\controller\modules\Base;
use app\model\BaseModel;
use app\model\UserModel;
use app\model\salesBusiness\WordPlanModel;


use think\facade\Request;
use think\facade\Db;
use app\common\Util;
use think\exception\Apis;
use think\facade\Filesystem;

use app\common\FtpUtil;


// use League\Flysystem\Filesystem;
// use League\Flysystem\Adapter\Ftp;
/**
 * Class DailyData
 * @package app\controller\modules\businessInfo
 * Ã¿ÈÕÍ³¼Æ±êÄ£¿é
 */
class WordPlan extends Base
{
    public function index()
    {

        if(Request::isPost()){
            $res['statusCode'] = '200';


        }

        // $logins = array($input,$output);
        exit(json_encode($res));
    }
    public function SearchPlan()
    {
        //
    }
    public function TPTMADept(){
        if(Request::isPost()){
            $res['statusCode'] = '200';
            $list = WordPlanModel::TPTMADept();
            $res['data'] = $list;
            return json($res);
        }
    }

    public function queryAllMsg()
    {
        if(Request::isPost()){
            $res['statusCode'] = '200';
            $param = Request::param();
            $auth = BaseModel::getAuth('WEI_2200',$param['UserID']);
            $param['auth'] = $auth;
            // $param['class'] = 'plan';
            $list = WordPlanModel::queryAllMsg($param);

            $langCode = $this->langCode($param['LangID']);
            $MinorCd = 'OA2001';
            $data = BaseModel::SystemclassBigPrc($MinorCd,$langCode);

            $MinorCd = 'OA1003';
            $datas = BaseModel::SystemclassBigPrc($MinorCd,$langCode);



            foreach ($list as $key => $value) {
                $list[$key]['ActPlanNo'] = trim($value['ActPlanNo']);
                $list[$key]['ActReptNo'] = trim($value['ActReptNo']);
                $list[$key]['ActPlanDate'] = isset($value['ActPlanDate'])?date('Y-m-d',strtotime($value['ActPlanDate'])):'';
                $list[$key]['ActReptDate'] = isset($value['ActReptDate'])?date('Y-m-d',strtotime($value['ActReptDate'])):'';

                if($value['CustPattern']){
                    foreach ($data as $k => $v) {
                        if($v['value'] == $value['CustPattern']){
                            $list[$key]['CustPatternText'] = $v['text'];
                        }
                    }
                }else{
                    $list[$key]['CustPatternText'] = $data[1]['text'];
                }
                if($param['class'] == 'plan'){
                    switch ($value['Status']){
                            case '0':
                                $planStatus='OA10030100';//this.langPl;
                                break;
                            case '1':
                                $planStatus='OA10030300';//this.langComplete;
                                break;
                            case '2':
                                $planStatus='OA10030200';//this.langDoing;
                                break;
                        }
                    foreach ($datas as $k => $v) {
                        if($v['value'] == $planStatus){
                            $list[$key]['StatusText'] = $v['text'];
                        }
                    }
                }
            }
            $res['data'] = $list;
            $res['datas'] = $datas;
            $CountM = count($list);
            if($CountM>=50){
                $res['countM'] = true;
            }else{
                $res['countM'] = false;
            }

            return json($res);
        }
    }

    /**
     * 查询工作日程计划，工作计划时间
     */
    public function getCalenderPlan(){

        if(Request::isPost()){
            $res['statusCode'] = '200';
            $param = Request::param();
            $auth = BaseModel::getAuth('WEI_2200',$param['UserID']);
            $param['auth'] = $auth;

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];

            $empid = str_replace(' ','',$users['emp_code']);
            $DeptCd = str_replace(' ','',$users['DeptCd']);

            if($auth == 'SM00040003'){
                $qufen = 'S';
            }else if($auth == 'SM00040002'){
                $qufen = 'D';
            }else if($auth == 'SM00040001'){
                $qufen = 'A';
            }else{
                $qufen = 'D';
            }

            $langCode = $this->langCode($param['LangID']);


            $DB = 'sqlsrv';
            if($param['class'] == 'plan'){
                $spName = 'dbo.SSAWEI_2200_M';
                $input = '@p_work_type =?,@p_BaseMounth =?,@p_SearchType=?,@p_EmpID=?,@p_langcd=?,@p_ManageYn=?,@p_DeptCd=?,@p_FinishYn=?';
                $output = ['Q', $param['startDate'],$qufen,$empid,$langCode,'Y',$DeptCd,''];
                $list = BaseModel::execSp($spName,$input,$output,$DB);

                $arr = array();

                foreach($list as $v){

                    $day = date('d',strtotime($v['ActPlanDate']));
                    if($day<10){
                        $day = substr($day,1,2);
                    }

                    if($qufen == 'A'){
                        $arr[$day][] = array('Remark'=>$v['Remark'],'DeptCd'=>$v['DeptCd'],'ActPlanDate'=>$v['ActPlanDate']);
                    }else if($qufen == 'D'){
                        $arr[$day][] = array('Remark'=>$v['Remark'],'EmpID'=>$v['EmpID'],'ActPlanDate'=>$v['ActPlanDate']);
                    }else if($qufen == 'S'){
                        $arr[$day][] = array('Remark'=>$v['Remark'],'ActPlanNo'=>$v['ActPlanNo'],'EmpID'=>$v['EmpID'],'ActPlanDate'=>$v['ActPlanDate']);
                    }

                }
            }if($param['class'] == 'rept'){
                $spName = 'dbo.SSAWEI_2200REPT_M';
                $input = '@p_work_type =?,@p_BaseMounth =?,@p_SearchType=?,@p_EmpID=?,@p_langcd=?,@p_ManageYn=?,@p_DeptCd=?';
                $output = ['Q', $param['startDate'],$qufen,$empid,$langCode,'Y',$DeptCd];
                $list = BaseModel::execSp($spName,$input,$output,$DB);
                $arr = array();

                foreach($list as $v){

                    $day = date('d',strtotime($v['ActReptDate']));
                    if($day<10){
                        $day = substr($day,1,2);
                    }

                    if($qufen == 'A'){
                        $arr[$day][] = array('Remark'=>$v['Remark'],'DeptCd'=>$v['DeptCd'],'ActReptDate'=>$v['ActReptDate']);
                    }else if($qufen == 'D'){
                        $arr[$day][] = array('Remark'=>$v['Remark'],'EmpID'=>$v['EmpID'],'ActReptDate'=>$v['ActReptDate']);
                    }else if($qufen == 'S'){
                        $arr[$day][] = array('Remark'=>$v['Remark'],'ActPlanNo'=>$v['ActPlanNo'],'EmpID'=>$v['EmpID'],'ActReptDate'=>$v['ActReptDate']);
                    }

                }
            }




            $res['qufen'] = $qufen;

            $res['data'] = $arr;
            return json($res);

        }
    }


    /**
     * 工作计划详细信息
     */
    public function planMinute(){
        if(Request::isPost()){
            $res['statusCode'] = '200';
            $param = Request::param();
            $config = config('web');

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            $empid = str_replace(' ','',$users['emp_code']);
            $DeptCd = str_replace(' ','',$users['DeptCd']);
            $auth = BaseModel::getAuth('WEI_2200',$param['UserID']);
            $param['auth'] = $auth;

            $langCode = $this->langCode($param['LangID']);
            $ActPlanNo = $param['ActPlanNo'];
            if(empty($ActPlanNo)){
                $res['statusCode'] = 'I001';
                return json($res);
            }
            $result = WordPlanModel::planMinute($ActPlanNo,$auth,$langCode);

            $dirname_year = substr($ActPlanNo,0,4);
            $dirname_month = substr($ActPlanNo,4,2);

            $fileurl = $config['HomeUrl'].$config['Plan']."/$dirname_year/$dirname_month/$ActPlanNo/";
            $PlanPhoto = array();
            if(!empty($result['LocationPhoto'])){
                $exname = explode('.', $result['LocationPhoto'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto'],
                    'extname' => $exname,
                    'url' => $fileurl.$result['LocationPhoto'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto'],
                    'phone'     => $result['LocationPhoto'],
                    'Seq' => 1,
                );
            }
            if(!empty($result['LocationPhoto2'])){
                $exname2 = explode('.', $result['LocationPhoto2'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto2'],
                    'extname' => $exname2,
                    'url' => $fileurl.$result['LocationPhoto2'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto2'],
                    'phone'     => $result['LocationPhoto2'],
                    'Seq' => 2,
                );
            }
            if(!empty($result['LocationPhoto3'])){
                $exname3 = explode('.', $result['LocationPhoto3'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto3'],
                    'extname' => $exname3,
                    'url' => $fileurl.$result['LocationPhoto3'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto3'],
                    'phone'     => $result['LocationPhoto3'],
                    'Seq' => 3,
                );
            }
            $res['data'] = $result;
            $res['PlanPhoto'] = $PlanPhoto;

            $this->DownloadPlanPhoto($config['Plan'],$config['PlanFTP'],$PlanPhoto);

            return json($res);
        }
    }

     /**
     * 添加安装报告-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function addPlanPhotos()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $date = date('Ymdhis',time());
            $config = config('web');

            $addressL = Apis::geocoder($param['lng'],$param['lat'],$config['addres']);
            $time = date('Y-m-d H:i:s');
            if($addressL->status == '0'){
                $address = $addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;
            }else{
                $address = '';
            }
            $mt_id = $param['ActPlanNo'];
            $UserID = $param['UserID'];
            $auth = BaseModel::getAuth('WEI_2200',$param['UserID']);
            $param['auth'] = $auth;

            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $fun = $config['Plan'];

            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');

            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);


            $remoteFile = $config['PlanFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;
            $res['file'] = $remoteFile;
            $res['file2'] = $localFile;

            FtpUtil::ftp_photo($mt_id,$config['PlanFTP']);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
            }

            $w = array(
                'ActPlanNo'    => $mt_id
            );

            $result = WordPlanModel::getPlan($w);

            if(empty($result['LocationPhoto'])){
                $data = array(
                    'LocationPhoto'    => $fileNm,
                );
                if(!empty($address)){
                    $data['LocationAddr'] = $address;
                }
                WordPlanModel::savePlan($data,$w);
            }else{
                if(empty($result['LocationPhoto2'])){
                    $data = array(
                        'LocationPhoto2'    => $fileNm,
                    );
                    WordPlanModel::savePlan($data,$w);
                }else{
                    if(empty($result['LocationPhoto3'])){
                        $data = array(
                            'LocationPhoto3'    => $fileNm,
                        );
                        WordPlanModel::savePlan($data,$w);
                    }
                }
            }

            $result = WordPlanModel::getPlan($w);
            $fileurl = $config['HomeUrl'].$config['Plan']."/$dirname_year/$dirname_month/$mt_id/";

            $PlanPhoto = array();
            if(!empty($result['LocationPhoto'])){
                $exname = explode('.', $result['LocationPhoto'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto'],
                    'extname' => $exname,
                    'url' => $fileurl.$result['LocationPhoto'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto'],
                    'phone'     => $result['LocationPhoto'],
                    'Seq' => 1,
                );
            }
            if(!empty($result['LocationPhoto2'])){
                $exname2 = explode('.', $result['LocationPhoto2'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto2'],
                    'extname' => $exname2,
                    'url' => $fileurl.$result['LocationPhoto2'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto2'],
                    'phone'     => $result['LocationPhoto2'],
                    'Seq' => 2,
                );
            }
            if(!empty($result['LocationPhoto3'])){
                $exname3 = explode('.', $result['LocationPhoto3'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto3'],
                    'extname' => $exname3,
                    'url' => $fileurl.$result['LocationPhoto3'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto3'],
                    'phone'     => $result['LocationPhoto3'],
                    'Seq' => 3,
                );
            }
            $res['data'] = $PlanPhoto;
            $res['photoCount'] = count($PlanPhoto);

            return json($res);
        }
    }

    /**
     * 安装报告-删除照片
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function DeletePlanPhone()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $where = array(
                'ActPlanNo'    => $param['ActPlanNo'],
            );

            $result = WordPlanModel::getPlan($where);
            if($result['LocationPhoto'] == $param['name']){

                $data = array(
                    'LocationPhoto' => Null,
                    'LocationAddr'  => Null
                );

            }else if($result['LocationPhoto2'] == $param['name']){
                $data = array(
                    'LocationPhoto2' => Null
                );

            }else if($result['LocationPhoto3'] == $param['name']){
                $data = array(
                    'LocationPhoto3' => Null
                );

            }
            WordPlanModel::savePlan($data,$where);


            $result = WordPlanModel::getPlan($where);
            $mt_id = $param['ActPlanNo'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $fileurl = $config['HomeUrl'].$config['Plan']."/$dirname_year/$dirname_month/$mt_id/";

            $PlanPhoto = array();
            if(!empty($result['LocationPhoto'])){
                $exname = explode('.', $result['LocationPhoto'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto'],
                    'extname' => $exname,
                    'url' => $fileurl.$result['LocationPhoto'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto'],
                    'phone'     => $result['LocationPhoto'],
                    'Seq' => 1,
                );
            }
            if(!empty($result['LocationPhoto2'])){
                $exname2 = explode('.', $result['LocationPhoto2'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto2'],
                    'extname' => $exname2,
                    'url' => $fileurl.$result['LocationPhoto2'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto2'],
                    'phone'     => $result['LocationPhoto2'],
                    'Seq' => 2,
                );
            }
            if(!empty($result['LocationPhoto3'])){
                $exname3 = explode('.', $result['LocationPhoto3'])[1];
                $PlanPhoto[] = array(
                    'name'    => $result['LocationPhoto3'],
                    'extname' => $exname3,
                    'url' => $fileurl.$result['LocationPhoto3'],
                    'ActPlanNo' => $result['ActPlanNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto3'],
                    'phone'     => $result['LocationPhoto3'],
                    'Seq' => 3,
                );
            }
            $res['data'] = $PlanPhoto;

            $res['photoCount'] = count($PlanPhoto);

            return json($res);
        }
    }

    /**
     * 工作计划-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadPlanPhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = $v['ActPlanNo'];
                $filename = $v['phone'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }
                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');

                        if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                            return false;
                        }
                }
            }
            ftp_close($conn);
            return true;
    }
    /**
     * 工作计划-保存
     *TOAActPlan00
     * @param array $param POST传值
     * @return bool
     */
    public function savePlan()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            if($param['CustSysRegYn'] == 'N'){
                $UnRegCustNm       = $param['CustNm'];
            }else{
                $UnRegCustNm       = null;
            }
            $ActPlanNo = $param['ActPlanNo'];
            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            $empid = str_replace(' ','',$users['emp_code']);
            $isUpdate = 0;
            if(empty($ActPlanNo)){
                $dateMonth = date('Ymd',intval(time()));
                $resultPlan = WordPlanModel::getPlanNow($dateMonth);

                //如果当月还没有编号
                if(empty($resultPlan['ActPlanNo']))
                {
                    $ActPlanNo = $dateMonth.'0001';
                }
                else
                {
                    $lastNo = substr($resultPlan['ActPlanNo'],8);
                    $planNo = $dateMonth.$lastNo;
                    $ActPlanNo = $planNo +1;
                }
            }
            else
            {
                $isUpdate = 1;
            }
            if(trim($param['ActGubun']) == 'OA10010200'){
                $JobReportYn = 'Y';
                $finishYn = 'N';
            }else{
                $JobReportYn = 'N';
                $finishYn = 'N';
            }

            $add = array(
                'ActPlanNo'     => $ActPlanNo,
                'ActPlanDate'   => $param['ActPlanDate'],
                'DeptCd'        => $param['DeptCd'],
                'EmpID'         => $param['EmpID'],
                'ActGubun'      => $param['ActGubun'],
                'RelationClass' => $param['RelationClass'],
                'ActTitle'      => $param['ActTitle'],
                'ActContents'   => $param['ActContents'],
                'CustCd'        => $param['CustCd'],
                'JobReportYn'   => $JobReportYn,
                'FinishYn'      => $finishYn,
                'DestinationNm' => $param['DestinationNm'],
                'ActSTDate'     => $param['ActSTDate'],
                'ActEDDate'     => $param['ActEDDate'],
                'Status'        => $param['Status'],  //
                'SysRemark'     =>  'mobile-info',   //
                'RegEmpID'      => $empid,  //
                'RegDate'       => date("Y-m-d H:i:s"),//
                'UptEmpID'      => $empid,
                'UptDate'       => date("Y-m-d H:i:s"),
                'CustSysRegYn'  => $param['CustSysRegYn'],
                'UnRegCustNm'    => $UnRegCustNm,
                'DistanceKm'    => $param['DistanceKm'],
                'CarNo'         => $param['CarNo'],
                'DrtDrivingYn'    => $param['DrtDrivingYn'],
                'MoveMethod'    => $param['MoveMethod'],
                'NewCustYn'    => $param['NewCustYn'],
                'CustPattern'  => $param['CustPattern'],
                'LocationAddr' => $param['LocationAddr'],
            );


            if($isUpdate == 0){
                WordPlanModel::addPlan($add);
            }else{
                $where = array(
                    'ActPlanNo'     => $ActPlanNo,
                );
                unset($add['ActPlanNo']);
                unset($add['Status']);
                unset($add['SysRemark']);
                unset($add['RegEmpID']);
                unset($add['RegDate']);
                WordPlanModel::savePlan($add,$where);
            }
            $res['data'] = $ActPlanNo;
            $res['isUpdate'] = $isUpdate;
            return json($res);
        }
    }


    public function reptMinute()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $auth = BaseModel::getAuth('WEI_2200',$param['UserID']);

            $langCode = $this->langCode($param['LangID']);

            $list = WordPlanModel::reptMinute($param['ActPlanNo'],$langCode,$param['check'],$auth);




            $ActReptNo = $param['ActPlanNo'];
            $dirname_year = substr($ActReptNo,0,4);
            $dirname_month = substr($ActReptNo,4,2);

            $fileurl = $config['HomeUrl'].$config['Rept']."/$dirname_year/$dirname_month/$ActReptNo/";
            $ReptPhoto = array();
            if(!empty($list['LocationPhoto'])){
                $exname = explode('.', $list['LocationPhoto'])[1];
                $ReptPhoto[] = array(
                    'name'    => $list['LocationPhoto'],
                    'extname' => $exname,
                    'url' => $fileurl.$list['LocationPhoto'],
                    'ActReptNo' => $list['ActReptNo'],
                    'FileNm'    => $fileurl.$list['LocationPhoto'],
                    'phone'     => $list['LocationPhoto'],
                    'Seq' => 1,
                );
            }
            if(!empty($list['LocationPhoto2'])){
                $exname2 = explode('.', $list['LocationPhoto2'])[1];
                $ReptPhoto[] = array(
                    'name'    => $list['LocationPhoto2'],
                    'extname' => $exname2,
                    'url' => $fileurl.$list['LocationPhoto2'],
                    'ActReptNo' => $list['ActReptNo'],
                    'FileNm'    => $fileurl.$list['LocationPhoto2'],
                    'phone'     => $list['LocationPhoto2'],
                    'Seq' => 2,
                );
            }
            if(!empty($list['LocationPhoto3'])){
                $exname3 = explode('.', $list['LocationPhoto3'])[1];
                $ReptPhoto[] = array(
                    'name'    => $list['LocationPhoto3'],
                    'extname' => $exname3,
                    'url' => $fileurl.$list['LocationPhoto3'],
                    'ActReptNo' => $list['ActReptNo'],
                    'FileNm'    => $fileurl.$list['LocationPhoto3'],
                    'phone'     => $list['LocationPhoto3'],
                    'Seq' => 3,
                );
            }

            if(empty($list)){
                $list['cun'] = false;
            }else{
                $list['cun'] = true;
            }
            $this->DownloadReptPhoto($config['Rept'],$config['ReptFTP'],$ReptPhoto);
            $res['ReptPhoto'] = $ReptPhoto;

            $res['data'] = $list;
            return json($res);
        }
    }


    public function planStatus(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            $empid = str_replace(' ','',$users['emp_code']);

            $ActPlanNo = $param['ActPlanNo'];
            $save = array(
                'Status'        => $param['Status'],
                'FinishYn'      => $param['FinishYn'],
                'JobReportYn'   => $param['JobReportYn'],
                'FinishDate'    => date('Y-m-d H:i:s'),
                'CfmYn'         => '1',
                'CfmEmpID'      => $empid,
                'CfmDate'       => date('Y-m-d H:i:s'),
            );
            $where = array(
                'ActPlanNo' => $ActPlanNo
            );
            WordPlanModel::savePlan($save,$where);

            $res['data'] = $ActPlanNo;

            return json($res);

        }
    }













    /**
     * 工作报告-保存
     *TOAActPlan00
     * @param array $param POST传值
     * @return bool
     */
    public function saveRept()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');


            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            $loginId = str_replace(' ','',$users['emp_code']);

            if($param['CustSysRegYn'] == 'N'){
                $UnRegCustNm       = $param['CustNm'];
            }else{
                $UnRegCustNm       = null;
            }
            $ActPlanNo = $param['ActPlanNo'];
            $ActReptNo = $param['ActReptNo'];
            //判断是否存在GPS定位信息
            $where = array(
                'ActPlanNo' => $ActPlanNo
            );
            $planLocation = WordPlanModel::getPlan($where);

            if(!empty($planLocation)){
                if(empty($planLocation['LocationAddr'])){
                    $res['statusCode'] = '201';
                    $res['statusMsg'] = '工作计划没有定位';
                    return json($res);
                }
            }
            if(empty($param['ReqConductDate'])){
                $param['ReqConductDate'] = Null;
            }
            //判断生成还是更新
            $isUpdate = 0;
            if(empty($ActReptNo)){
                $dateMonth = date('Ymd',intval(time()));
                $resultReptNo= WordPlanModel::getReptNow($dateMonth);


                // $res['dateMonth'] = $dateMonth;
                // $res['resultReptNo'] = $resultReptNo;
                // return json($res);

                //如果当月还没有编号
                if(empty($resultReptNo['ActReptNo']))
                {
                    $ActReptNo = $dateMonth.'0001';

                }
                else
                {
                    $lastNo = substr($resultReptNo['ActReptNo'],8);
                    $ActReptNo = $dateMonth.$lastNo;
                    $ActReptNo = $ActReptNo +1;

                }


            }
            else
            {
                $isUpdate = 1;
            }


            $add = array(
                'ActGubun'      => $param['ActGubun'],//
                'RelationClass' => $param['RelationClass'],
                'CustPattern'   => $param['CustPattern'],
                'ActReptNo'     => $ActReptNo,//
                'ActReptDate'   => $param['ActReptDate'],
                'ActPlanNo'     => $param['ActPlanNo'],//
                'EmpID'         => $param['EmpID'],
                'DeptCd'        => $param['DeptCd'],
                'ReptTitle'     => $param['ReptTitle'],
                'MeetingPlace'  => $param['MeetingPlace'],
                'MeetingSubject'=> $param['MeetingSubject'],
                'AttendPerson'  => $param['AttendPerson'],
                'CustRequstTxt' => $param['CustRequstTxt'],
                'SubjectDisTxt' => $param['SubjectDisTxt'],
                'ReqConductDate'=> $param['ReqConductDate'],
                'Remark'        => $param['Remark'],
                'CustCd'        => $param['CustCd'],
                'MeetingSTDate' => $param['MeetingSTDate'],
                'MeetingEDDate' => $param['MeetingEDDate'],
                'RegEmpID'      => $loginId,//
                'RegDate'       => date('Y-m-d H:i:s'),//
                'UptEmpID'      => $loginId,
                'UptDate'       => date('Y-m-d H:i:s'),
                'CustSysRegYn'  => $param['CustSysRegYn'],
                'UnRegCustNm'    => $UnRegCustNm,
                'SysRemark'     =>  'mobile-info',//
                'ActPlanYn'      => $param['ActPlanYn'],
                'DrtDrivingYn'   => $param['DrtDrivingYn'],
                'MoveMethod'     => $param['MoveMethod'],
                'CarNo'          => $param['CarNo'],
                'DestinationNm'  => $param['DestinationNm'],
                'DistanceKm'     => $param['DistanceKm']

            );
            if($isUpdate == 0){
                $where = array('ActPlanNo' => $ActPlanNo);
                $reptCheck = WordPlanModel::getRept($where);
                if(!empty($ActPlanNo) && !empty($reptCheck['ActPlanNo'])){
                    $res['statusCode'] = '201';
                    $res['statusMsg'] = '已存在工作报告';
                    return json($res);
                }

                WordPlanModel::addRept($add);
            }else{
                $w = array('ActReptNo' => $ActReptNo);
                WordPlanModel::saveRept($add,$w);
            }

            $res['isUpdate'] = $isUpdate;
            $res['data'] = $ActReptNo;
            return json($res);

        }
    }


    /**
     * 工作报告确定
     */
    public function reptConfirm(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');


            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            $loginId = str_replace(' ','',$users['emp_code']);

            $ActReptNo= $param['ActReptNo'];
            $ActPlanNo = $param['ActPlanNo'];
            $CfmYn = $param['CfmYn'];

            $langCode = $this->langCode($param['LangID']);

            if($CfmYn == 1){
                $CfmYnT = 0;
                $FinishYn = 'N';
                $FinishDate = Null;

            }else{
                $CfmYnT = 1;
                $FinishYn = 'Y';
                $FinishDate = date('Y-m-d H:i:s');
            }
            if(!empty(trim($ActPlanNo))){

                $where = array(
                    'ActPlanNo' => $ActPlanNo
                );
                $PlanList = WordPlanModel::getPlan($where);
                $save = array(
                    'Status'    => $CfmYnT,
                    'FinishYn'  => $FinishYn,
                    'FinishDate'=> $FinishDate,
                );
                WordPlanModel::savePlan($save,$where);
            }

            if(!empty($ActReptNo)){
                $where = array(
                    'ActReptNo' => $ActReptNo
                );
                // $PlanList = WordPlanModel::getRept($where);
                $save = array(
                    'CfmYn'    => $CfmYnT,
                    'CfmEmpID' => $loginId,
                    'CfmDate' => date('Y-m-d H:i:s'),
                );
                WordPlanModel::saveRept($save,$where);
            }
            // $DB = 'sqlsrv';
            // $spName = 'yudo.SOAActReptCfm';
            // $input = '@pWorkingTag =?,@pActPlanNo =?,@pActReptNo=?,@pCfmEmpId=?';
            // $output = [$CfmYnT, $ActPlanNo,$ActReptNo,$loginId];
            // $list = BaseModel::execSp($spName,$input,$output,$DB);

            $res['data'] = $ActReptNo;
            return json($res);



        }
    }


     /**
     * 添加安装报告-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function addReptPhotos()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $date = date('Ymdhis',time());
            $config = config('web');


            if(empty($param['lng']) || empty($param['lat'])){
                $res['statusCode'] = '201';
                return json($res);
            }

            $addressL = Apis::geocoder($param['lng'],$param['lat'],$config['addres']);
            $time = date('Y-m-d H:i:s');
            if($addressL->status == '0'){
                $address = $addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;
            }else{
                $address = '';
            }

            $mt_id = $param['ActReptNo'];
            $UserID = $param['UserID'];
            $auth = BaseModel::getAuth('WEI_2200',$param['UserID']);
            $param['auth'] = $auth;

            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $fun = $config['Rept'];

            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');

            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);


            $remoteFile = $config['ReptFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;
            $res['file'] = $remoteFile;
            $res['file2'] = $localFile;

            FtpUtil::ftp_photo($mt_id,$config['ReptFTP']);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
            }

            $w = array(
                'ActReptNo'    => $mt_id
            );

            $result = WordPlanModel::getRept($w);

            if(empty($result['LocationPhoto'])){
                $data = array(
                    'LocationPhoto'    => $fileNm,
                );
                if(!empty($address)){
                    $data['LocationAddr'] = $address;
                }
                WordPlanModel::saveRept($data,$w);
            }else{
                if(empty($result['LocationPhoto2'])){
                    $data = array(
                        'LocationPhoto2'    => $fileNm,
                    );
                    WordPlanModel::saveRept($data,$w);
                }else{
                    if(empty($result['LocationPhoto3'])){
                        $data = array(
                            'LocationPhoto3'    => $fileNm,
                        );
                        WordPlanModel::saveRept($data,$w);
                    }
                }
            }

            $result = WordPlanModel::getRept($w);
            $fileurl = $config['HomeUrl'].$config['Rept']."/$dirname_year/$dirname_month/$mt_id/";

            $ReptPhoto = array();
            if(!empty($result['LocationPhoto'])){
                $exname = explode('.', $result['LocationPhoto'])[1];
                $ReptPhoto[] = array(
                    'name'    => $result['LocationPhoto'],
                    'extname' => $exname,
                    'url' => $fileurl.$result['LocationPhoto'],
                    'ActReptNo' => $result['ActReptNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto'],
                    'phone'     => $result['LocationPhoto'],
                    'Seq' => 1,
                );
            }
            if(!empty($result['LocationPhoto2'])){
                $exname2 = explode('.', $result['LocationPhoto2'])[1];
                $ReptPhoto[] = array(
                    'name'    => $result['LocationPhoto2'],
                    'extname' => $exname2,
                    'url' => $fileurl.$result['LocationPhoto2'],
                    'ActReptNo' => $result['ActReptNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto2'],
                    'phone'     => $result['LocationPhoto2'],
                    'Seq' => 2,
                );
            }
            if(!empty($result['LocationPhoto3'])){
                $exname3 = explode('.', $result['LocationPhoto3'])[1];
                $ReptPhoto[] = array(
                    'name'    => $result['LocationPhoto3'],
                    'extname' => $exname3,
                    'url' => $fileurl.$result['LocationPhoto3'],
                    'ActReptNo' => $result['ActReptNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto3'],
                    'phone'     => $result['LocationPhoto3'],
                    'Seq' => 3,
                );
            }
            $res['data'] = $ReptPhoto;
            $res['photoCount'] = count($ReptPhoto);

            return json($res);
        }
    }

    /**
     * 安装报告-删除照片
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function DeleteReptPhone()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $where = array(
                'ActReptNo'    => $param['ActReptNo'],
            );

            $result = WordPlanModel::getRept($where);
            if($result['LocationPhoto'] == $param['name']){

                $data = array(
                    'LocationPhoto' => Null,
                    'LocationAddr'  => Null
                );
            }else if($result['LocationPhoto2'] == $param['name']){
                $data = array(
                    'LocationPhoto2' => Null
                );

            }else if($result['LocationPhoto3'] == $param['name']){
                $data = array(
                    'LocationPhoto3' => Null
                );

            }
            WordPlanModel::saveRept($data,$where);


            $result = WordPlanModel::getRept($where);
            $mt_id = $param['ActReptNo'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $fileurl = $config['HomeUrl'].$config['Rept']."/$dirname_year/$dirname_month/$mt_id/";

            $ReptPhoto = array();
            if(!empty($result['LocationPhoto'])){
                $exname = explode('.', $result['LocationPhoto'])[1];
                $ReptPhoto[] = array(
                    'name'    => $result['LocationPhoto'],
                    'extname' => $exname,
                    'url' => $fileurl.$result['LocationPhoto'],
                    'ActReptNo' => $result['ActReptNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto'],
                    'phone'     => $result['LocationPhoto'],
                    'Seq' => 1,
                );
            }
            if(!empty($result['LocationPhoto2'])){
                $exname2 = explode('.', $result['LocationPhoto2'])[1];
                $ReptPhoto[] = array(
                    'name'    => $result['LocationPhoto2'],
                    'extname' => $exname2,
                    'url' => $fileurl.$result['LocationPhoto2'],
                    'ActReptNo' => $result['ActReptNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto2'],
                    'phone'     => $result['LocationPhoto2'],
                    'Seq' => 2,
                );
            }
            if(!empty($result['LocationPhoto3'])){
                $exname3 = explode('.', $result['LocationPhoto3'])[1];
                $ReptPhoto[] = array(
                    'name'    => $result['LocationPhoto3'],
                    'extname' => $exname3,
                    'url' => $fileurl.$result['LocationPhoto3'],
                    'ActReptNo' => $result['ActReptNo'],
                    'FileNm'    => $fileurl.$result['LocationPhoto3'],
                    'phone'     => $result['LocationPhoto3'],
                    'Seq' => 3,
                );
            }
            $res['data'] = $ReptPhoto;

            $res['photoCount'] = count($ReptPhoto);

            return json($res);
        }
    }

    /**
     * 工作报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadReptPhoto($fun,$funFTP,$list)
    {
        $config = config('web');
        $conn = ftp_connect($config['host'],$config['port']);
        ftp_login($conn,$config['username'],$config['password']);
        ftp_pasv($conn,true);
        foreach ($list as $k => $v){
            $mt_id = $v['ActReptNo'];
            $filename = $v['phone'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
            if (!is_dir($yeardir)) {
                mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
            }
            if(!is_file($yeardir."/$filename")) {
                $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');

                    if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                        return false;
                    }
            }
        }
        ftp_close($conn);
        return true;
    }


}