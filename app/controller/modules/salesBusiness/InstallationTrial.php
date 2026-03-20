<?php
/**
 * @Author: YUZH
 * @Date: 2024-09-30 15:52
 */

namespace app\controller\modules\salesBusiness;

use app\controller\modules\Base;
use app\model\BaseModel;
use app\model\UserModel;
use app\model\salesBusiness\InstallationTrialModel;
use think\exception\Apis;
use app\model\salesBusiness\RecvHandleModel;
use think\facade\Request;
use think\facade\Db;
use app\common\Util;
use think\facade\Filesystem;

use app\common\FtpUtil;

use app\common\JlampMail;

use TCPDF;

// use League\Flysystem\Filesystem;
// use League\Flysystem\Adapter\Ftp;
/**
 * Class DailyData
 * @package app\controller\modules\businessInfo
 * Ã¿ÈÕÍ³¼Æ±êÄ£¿é
 */
class InstallationTrial extends Base
{
    public function index()
    {

        if(Request::isPost()){
            $res['statusCode'] = '200';


        }
        // $juli1 = Apis::getDistance('31.4762540000','120.9960040000','31.4762540000','120.9960040000');
        // $res['data'] = $juli1;
        // $logins = array($input,$output);
        exit(json_encode($res));
    }

    public function address()
    {
        if(Request::isPost()){
            $param = Request::param();
            $param['UserID'] = $this->getUserId();
            if(!isset($param['UserID'])){

                $res['statusCode'] = '400';
                exit(json_encode($res));
            }
            $user = UserModel::getUserDeptInfo($param['UserID']);

            $EmpId = str_replace(' ','',$user[0]['emp_code']);

            $config = config('web');

            $AddressEnd = InstallationTrialModel::getAddressEnd($EmpId);
            if(!empty($AddressEnd)){
                $list = InstallationTrialModel::getUserAddress($EmpId);
                foreach ($list as $key => $value) {
                    $mt_id = $value['ArrivalLeaveNo'];
                    $dirname_year = substr($mt_id,0,4);
                    $dirname_month = substr($mt_id,4,2);
                    $list[$key]['ArrivalDate'] = date('Y-m-d H:i:s',strtotime($value['ArrivalDate']));
                    if(!empty($value['Arrivalphoto'])){
                        $list[$key]['ArrivalphotoUrl'] = $config['HomeUrl'].$config['Arr']."/$dirname_year/$dirname_month/$mt_id/".$value['Arrivalphoto'];
                    }
                }

                $juli1 = Apis::getDistance($param['lat'],$param['lng'],$AddressEnd['GpsLat'],$AddressEnd['GpsLng']);

                $times  = time() - strtotime($AddressEnd['ArrivalDate']);
                // if($times <= 1800 && $juli1 < 200){
                //     $res['statusCode'] = 'N003';
                //     // $res['returnMsg'] = "";
                //     $res['data'] = $list;
                //     return json($res);
                // }
                if($times <= 300){
                    $res['statusCode'] = 'N003';
                    // $res['returnMsg'] = "";
                    $res['data'] = $list;
                    return json($res);
                }

            }



            $addressL = Apis::geocoder($param['lng'],$param['lat'],$config['addres']);

            // return json($addressL);
            // $param['address'] = $addressL->result->formatted_address.$addressL->result->sematic_description;
            if($addressL->status == '0'){
                $param['address'] = $addressL->result->formatted_addresses->standard_address;
            }else{
                $param['address'] = '';
            }
            $res['statusCode'] = '200';

            $asid = date('Ymd',intval(time()));


            $resultAS = InstallationTrialModel::getAddress($asid);

            // $resultAS = Db::table("TASRecv40")->where([['EmpId','=',$EmpId],['ArrivalLeaveNo','like',$asid]])->order('ArrivalLeaveNo','desc')->find();

             if(empty($resultAS))
            {
                $post_asid = $asid.'0001';
            }
            else
            {
                $result_asid = substr($resultAS['ArrivalLeaveNo'],8);
                $asid .= $result_asid;
                $post_asid = $asid +1;
            }

            $Arrivaladd = false;

            if(empty($resultAS)){
                $Arrivaladd = true;
            }else{
                if($resultAS['EmpId'] == $EmpId){
                    $enddate = strtotime($resultAS['ArrivalDate']);
                    if((time()-$enddate)>300){
                        $Arrivaladd = true;
                    }else{
                        $post_asid = $resultAS['ArrivalLeaveNo'];
                    }
                }else{
                    $Arrivaladd = true;
                }
            }


            $mt_id = $post_asid;
            $UserID = $param['UserID'];
            $date = date('Ymdhis',time());
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $fun = $config['Arr'];



            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');
            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);
            $remoteFile = $config['ArrFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;

            FtpUtil::ftp_photo($mt_id,$config['ArrFTP']);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
                return json($res);
            }


            $time = date('Y-m-d H:i:s');
            $add = array(
                'ArrivalLeaveNo'    => $post_asid,
                'EmpId'             => $EmpId,
                'ArrivalDate'       => $time,
                'ArrivalLeaveType'  => 1,
                'GpsLat'            => $param['lat'],
                'GpsLng'            => $param['lng'],
                'LocationAddr'      => $param['address'],//array($LocationAddr,'utf-8'),
                'Arrivalphoto'      => $fileNm,
            );

            try {
                if($Arrivaladd){
                    InstallationTrialModel::addAddress($add);
                }else{
                    unset($add['ArrivalLeaveNo']);
                    unset($add['ArrivalDate']);
                    unset($add['ArrivalLeaveType']);
                    unset($add['EmpId']);
                    $where = array(
                        'ArrivalLeaveNo'    => $resultAS['ArrivalLeaveNo']
                    );
                    InstallationTrialModel::saveAddress($add,$where);
                }
            } catch (Exception $e) {
                $res['statusCode'] = '503';
            }
            $list = InstallationTrialModel::getUserAddress($EmpId);

            foreach ($list as $key => $value) {
                $mt_id = $value['ArrivalLeaveNo'];
                $list[$key]['ArrivalDate'] = date('Y-m-d H:i:s',strtotime($value['ArrivalDate']));
                if(!empty($value['Arrivalphoto'])){
                    $list[$key]['ArrivalphotoUrl'] = $config['HomeUrl'].$config['Arr']."/$dirname_year/$dirname_month/$mt_id/".$value['Arrivalphoto'];
                }

            }
            $res['data'] = $list;
            return json($res);
        }

    }

    public function getAddress()
    {
        if(Request::isPost()){
            $param = Request::param();
            $param['userID'] = $this->getUserId();
            if(!isset($param['userID'])){

                $res['statusCode'] = '400';
                exit(json_encode($res));
            }
            $config = config('web');
            $user = UserModel::getUserDeptInfo($param['userID']);

            $EmpId = str_replace(' ','',$user[0]['emp_code']);
            $ArrivalNo = isset($param['ArrivalNo'])?$param['ArrivalNo']:'';
            $res['statusCode'] = '200';
            $list = InstallationTrialModel::getUserAddress($EmpId,$ArrivalNo);
            foreach ($list as $key => $value) {
                $list[$key]['ArrivalDate'] = date('Y-m-d H:i:s',strtotime($value['ArrivalDate']));

                if(!empty($value['Arrivalphoto'])){
                    $mt_id = $value['ArrivalLeaveNo'];
                    $dirname_year = substr($mt_id,0,4);
                    $dirname_month = substr($mt_id,4,2);
                    $list[$key]['ArrivalphotoUrl'] = $config['HomeUrl'].$config['Arr']."/$dirname_year/$dirname_month/$mt_id/".$value['Arrivalphoto'];

                }

            }
            $res['user'] = $user;

            $res['data'] = $list;
            return json($res);
        }


    }
    public function LeaveAddress()
    {
        if(Request::isPost()){
            $res['statusCode'] = '200';
            $param = Request::param();
            $config = config('web');

            $addressL = Apis::geocoder($param['lng'],$param['lat'],$config['addres']);
            $time = date('Y-m-d H:i:s');
            if($addressL->status == '0'){
                $address = $addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;

            }else{
                $address = '';
            }


            $mt_id = $param['ASNo'];

            $date = date('Ymdhis',time());
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            if($param['type'] == 'AS'){
                $fun = $config['Recv'];
                $funFTP = $config['RecvFTP'];
            }else if($param['type'] == 'HA'){
                $fun = $config['Handle'];
                $funFTP = $config['HandleFTP'];
            }else if($param['type'] == 'IT'){
                $fun = $config['Sales'];
                $funFTP = $config['SalesFTP'];
            }else if($param['type'] == 'TE'){
                $fun = $config['Test'];
                $funFTP = $config['TestFTP'];
            }


            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');
            $res['file'] = $file;
            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);


            $remoteFile = $funFTP."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;

            FtpUtil::ftp_photo($mt_id,$funFTP);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
                return json($res);
            }
            $data = array(
                'lat'       => $param['lat'],
                'lng'       => $param['lng'],
                'address'   => $address,
                'time'      => $time,
                'Leavephoto'=> $fileNm
            );

            $save = array(
                'LeaveLat'  => $param['lat'],
                'LeaveLng'  => $param['lng'],
                'LeaveTime' => $time,
                'LeaveLocationAddr' => $address,
                'Leavephoto'    => $fileNm,
            );

            // if($param['type'] == 'AS'){
            //     $where = array(
            //         'ASRecvNo'  => $mt_id
            //     );
            //     RecvHandleModel::SaveASRecv($save,$where);
            // }else if($param['type'] == 'HA'){
            //     $fun = $config['Handle'];
            //     $funFTP = $config['HandleFTP'];
            // }else if($param['type'] == 'IT'){
            //     $fun = $config['Sales'];
            //     $funFTP = $config['SalesFTP'];
            // }else if($param['type'] == 'TE'){
            //     $fun = $config['Test'];
            //     $funFTP = $config['TestFTP'];
            // }


            $res['data'] = $data;
            exit(json_encode($res));
        }
    }
    public function getSCode()
    {
        if(Request::isPost()){
            $param = Request::param();

            $url = 'https://api.idsys.yudoplatform.com/api/index/get_order_sn?order_sn='.$param['urlCode'];

            $results = Util::getCurl($url);
            // $results = file_get_contents($url);
            $results = json_decode($results);
             $res['statusCode'] = '200';
            if($results->msg == 'success'){
                $res['data'] = $results->data->erp_order_sn;
            }else{
                $res['statusCode'] = '300';
            }
            // $res['param'] = $param;

            exit(json_encode($res));
        }
    }

    public function installSeach()
    {
        if(Request::isPost()){
            $param = Request::param();
            $param['userID'] = $this->getUserId();
            $auth = BaseModel::getAuth('WEI_2000',$param['userID']);

            $param['auth'] = $auth;
            $list = InstallationTrialModel::getInstallSearch($param);
            $res['statusCode'] = '200';
            foreach ($list as $key => $value) {
                $list[$key]['AssmReptDate'] = date('Y-m-d',strtotime($value['AssmReptDate']));
                $list[$key]['AssmDate'] = date('Y-m-d H:i:s',strtotime($value['AssmDate']));

            }
            $res['data'] = $list;
            $count = count($list);
            $res['countM'] = false;
            $res['count'] = $count;
            if($count == 50){
                $res['count'] = $param['count']+50;
                $res['countM'] = true;
            }

            exit(json_encode($res));
        }
    }

    public function TestModelSeach()
    {
        if(Request::isPost()){
            $param = Request::param();

            $param['userID'] = $this->getUserId();
            $auth = BaseModel::getAuth('WEI_2000',$param['userID']);

            $param['auth'] = $auth;
            $list = InstallationTrialModel::TestSearch($param);
            $res['statusCode'] = '200';
            foreach ($list as $key => $value) {
                $list[$key]['TstInjDate'] = date('Y-m-d',strtotime($value['TstInjDate']));
                $list[$key]['TstInjReptDate'] = date('Y-m-d H:i:s',strtotime($value['TstInjReptDate']));

            }
            $res['data'] = $list;
            $count = count($list);
            $res['countM'] = false;
            $res['count'] = $count;
            if($count == 50){
                $res['count'] = $param['count']+50;
                $res['countM'] = true;
            }

            exit(json_encode($res));
        }
    }

    public function OrderSeach()
    {
        if(Request::isPost()){
            $param = Request::param();


            $langCode = $this->langCode("CHN");
            $param['langCode'] = $langCode;
            $list = InstallationTrialModel::OrderSeach($param);
            $res['statusCode'] = '200';
            foreach ($list as $key => $value) {
                $list[$key]['OrderDate'] = date('Y-m-d',strtotime($value['OrderDate']));
                $list[$key]['DelvDate'] = date('Y-m-d',strtotime($value['DelvDate']));

            }
            $res['data'] = $list;
            $count = count($list);
            $res['countM'] = false;
            $res['count'] = $count;
            if($count == 50){
                $res['count'] = $param['count']+50;
                $res['countM'] = true;
            }


            return json($res);
        }
    }

    public function orderMinute()
    {
        if(Request::isPost()){
            $param = Request::param();
            $langCode = $this->langCode("CHN");
            $list = InstallationTrialModel::orderMinute($param['OrderNo'],$langCode);
            $Custes = RecvHandleModel::getCustTel($list['CustCd']);//客户电话

            if(!empty($Custes)){
                $list['CustPrsn'] = isset($Custes['CustEmpNm'])?$Custes['CustEmpNm']:'';
                $list['CustTell'] = isset($Custes['C_Tel'])?$Custes['C_Tel']:'';
                $list['CustEmail'] = isset($Custes['EmailId'])?$Custes['EmailId']:'';
            }else{
                $list['CustPrsn'] = '';
                $list['CustTell'] = '';
                $list['CustEmail'] = '';
            }
            $config = config('web');
            $assmInfo = InstallationTrialModel::AssmReptMinute($param['OrderNo']);

            if(!empty($assmInfo)){
                $assmInfo['ArrivalTime'] = empty($assmInfo['ArrivalTime'])?'':date('Y-m-d H:i:s',strtotime($assmInfo['ArrivalTime']));
                $assmInfo['LeaveTime'] = empty($assmInfo['LeaveTime'])?'':date('Y-m-d H:i:s',strtotime($assmInfo['LeaveTime']));
                $assmInfo['AssmDate'] = empty($assmInfo['AssmDate'])?'':date('Y-m-d H:i:s',strtotime($assmInfo['AssmDate']));
                $assmInfo['AssmReptDate'] = empty($assmInfo['AssmReptDate'])?'':date('Y-m-d H:i:s',strtotime($assmInfo['AssmReptDate']));

                $xinxiinfos = InstallationTrialModel::getSystemClass($assmInfo['AssmReptNo']);

                // 同行人员
                $AssmSales = InstallationTrialModel::getAssmFellow($assmInfo['AssmReptNo']);
                $res['AssmSales'] = $AssmSales;
                $w = array(
                    'AssmReptNo'    => $assmInfo['AssmReptNo']
                );
                // 照片列表
                $AssmPhotos = InstallationTrialModel::getAssmPhotoNm($w);

                $this->DownloadPhoto($config['Sales'],$config['SalesFTP'],$assmInfo['AssmReptNo']);
                $Photos = array();
                $AssmPhoto = array();
                foreach ($AssmPhotos as $key => $value) {
                    $mt_id = $value['AssmReptNo'];
                    $dirname_year = substr($value['AssmReptNo'],0,4);
                    $dirname_month = substr($value['AssmReptNo'],4,2);
                    $dirname_defualt = substr($value['AssmReptNo'],0,6);
                    $fileurl = $config['HomeUrl'].$config['Sales']."/$dirname_year/$dirname_month/$mt_id/";
                    $Photos[] = array(
                        'FileNm'    => $fileurl.$value['FileNm'],
                        'FTP_UseYn' => $value['FTP_UseYn'],
                        'AssmReptNo' => $value['AssmReptNo'],
                        'Photo' => $value['Photo'],
                        'Seq' => $value['Seq'],
                    );

                    $exname = explode('.', $value['FileNm'])[1];
                    $AssmPhoto[] = array(
                        'name'    => $value['FileNm'],
                        'extname' => $exname,
                        'url' => $fileurl.$value['FileNm'],
                        'AssmReptNo' => $value['AssmReptNo'],
                        'FileNm'    => $fileurl.$value['FileNm'],
                        'FTP_UseYn' => $value['FTP_UseYn'],
                        'AssmReptNo' => $value['AssmReptNo'],
                        'Photo' => $value['Photo'],
                        'Seq' => $value['Seq'],
                    );
                }

                $res['Photos'] = $Photos;



                $res['AssmPhotos'] = $AssmPhoto;
                $res['photoCount'] = count($AssmPhoto);

                $systemC = InstallationTrialModel::getInfoxinxi($assmInfo['SystemClass']);
                $systemC = $this->infoX($systemC,$assmInfo['SystemClass']);
                $infos = array();
                if(count($xinxiinfos)<=0){
                    $res['Infoxinxis'] = $systemC;
                }else{
                    foreach ($systemC as $key => $value) {
                        $MinorNm = '';
                        $Result = '';
                        $DesContent = '';
                        $AssmReptNo = '';
                        foreach ($xinxiinfos as $k => $val) {
                            if((int)$value['Seq'] == (int)$val['Seq']){
                                $MinorNm = $value['MinorNm'];
                                $Result = $val['Result']=='Y'?true:false;
                                $DesContent = $val['DesContent'];
                                $AssmReptNo = $val['AssmReptNo'];
                            }
                        }
                        $infos[] = array(
                            'AssmReptNo'    => $AssmReptNo,
                            'Result'    => $Result,
                            'MinorNm'    => $MinorNm,
                            'DesContent'    => $DesContent,
                            'Seq'    => $value['Seq'],
                            'Project'   => $value['Project'],

                        );

                    }
                    $res['Infoxinxis'] = $infos;
                }
                $assmInfo['Infoxinxis'] = $res['Infoxinxis'];

                $res['systemC'] = $systemC;



                if($assmInfo['CustSignYn'] =="Y"){
                    $mt_id = $assmInfo['AssmReptNo'];
                    $dirname_year = substr($mt_id,0,4);
                    $dirname_month = substr($mt_id,4,2);
                    $dirname_defualt = substr($mt_id,0,6);
					$fileurl = $config['HomeUrl'].$config['Sales']."/$dirname_year/$dirname_month/$mt_id/".$config['SignC'];
                    $assmInfo['SignUrl'] = $fileurl.$config['SignName'];

                    // $fun = $config['Sales'];
                    // $dir = "$fun/$dirname_year/$dirname_month/$mt_id/";
                    // $remoteFile = $config['SalesFTP']."/$dirname_year/$dirname_month/$mt_id/".$config['SignD'].$config['SignC'].$config['SignName'];
                    // $localFile = $config['localFile'].$dir.$config['SignD'].$config['SignC'].$config['SignName'];
                    // $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
                    // if(is_file($yeardir."/".$config['SignName'])) {
                    //     if (!is_dir($yeardir)) {
                    //         mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                    //     }


                    //     FtpUtil::ftp_photo($mt_id,$config['SalesFTP'],$config['Sign']);
                    //     if (!FtpUtil::upload($localFile, $remoteFile)) {
                    //         $res['statusCode'] = '105';
                    //         $res['msg'] ="文件上传失败";
                    //     }
                    // }
                    // $assmInfo['remoteFile'] =$remoteFile;
                    // $assmInfo['localFile'] =$localFile;

                    $this->DownloadSign($config['Sales'],$config['SalesFTP'],$assmInfo['AssmReptNo']);
                }else{
                    $assmInfo['SignUrl'] ='';
                }


            }

            $res['statusCode'] = '200';
            $res['data'] = $list;
            $res['assmInfo'] = $assmInfo;


            return json($res);
        }
    }


    public function SystemclassBigPrc()
    {
        if(Request::isPost()){
            $param = Request::param();
            $langCode = $this->langCode("CHN");
            $MinorCd = $this->systemClass($param['MinorCd']);
            $list = BaseModel::SystemclassBigPrc($MinorCd,$langCode);
            $res['statusCode'] = '200';

            $data = array();
            foreach ($list as $key => $value) {
                if(trim($param['MinorCd']) == 'supplyscope' ){
                    // if($value['status'] == 'N' && !empty($value['text'])){
                        $data[] = array(
                            'text'  =>$value['text'],
                            'value'  =>$value['value'],

                        );
                    // }
                }else{

                    if($value['status'] == 'N' && !empty($value['text'])){
                        $data[] = array(
                            'text'  =>$value['text'],
                            'value'  =>$value['value'],

                        );
                    }
                }
            }
            $res['data'] = $data;
            return json($res);
        }
    }

    public function SystemMiniClass()
    {
        if(Request::isPost()){
            $param = Request::param();
            $RelCd1 = $param['RelCd1'];
            $MinorCd = $this->systemClass($param['MinorCd']);
            $langCode = $this->langCode("CHN");

            $list = BaseModel::SystemClass($RelCd1,$MinorCd,$langCode);
            $res['statusCode'] = '200';
            $data = array();
            foreach ($list as $key => $value) {
                if($value['status'] == 'N'){
                    $data[] = array(
                        'text'  =>$value['text'],
                        'value'  =>$value['value'],

                    );
                }
            }
            $res['data'] = $data;


            $res['RelCd1'] = $RelCd1;
            $res['MinorCd'] = $MinorCd;

            return json($res);
        }
    }


    public function getEmpyList(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $empId = $param['EmpNo'];
            $empNm = $param['EmpNm'];
            $deptNm = $param['DetNm'];
            $count = $param['count'];

            $result = InstallationTrialModel::getEmpyList($empId,$empNm,$deptNm,$count);
            $res['data'] = $result;

            if(count($result)>=50){
                $res['countM'] = true;
            }else{
                $res['countM'] = false;
            }

            return json($res);
        }
    }

    /**
     * 系统分类详情
     * @param $RelCd1  安装报告订单查询POST传值
     * @return array
     */

    public function getInfoxinxi(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $RelCd1 =  $param['RelCd1'];
            $result = InstallationTrialModel::getInfoxinxi($RelCd1);


            $infos = array();
            foreach ($result as $key => $val){
                if($key <=6){
                    $infos[] = array(
                        'Seq'           => $key+1,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );
                }

                if($RelCd1 == 'SA34010010'){
                    if($key == '7'){//电阻测试
                        $infos[] = array(
                            'Seq'           => '11',
                            'Result'        => true,
                            'DesContent'    => '',
                            'MinorNm'       => $val['MinorNm'],
                            'Project'       => $val['MinorCd'],
                            'color'         => '',
                        );
                    }
                } else if($RelCd1 == 'SA34010020'){
                    if($key == '7'){//气路检查
                        $infos[] = array(
                            'Seq'           => '8',
                            'Result'        => true,
                            'DesContent'    => '',
                            'MinorNm'       => $val['MinorNm'],
                            'Project'       => $val['MinorCd'],
                            'color'         => '',
                        );
                    }
                    if($key == '8'){//电阻测试
                        $infos[] = array(
                            'Seq'           => '11',
                            'Result'        => true,
                            'DesContent'    => '',
                            'MinorNm'       => $val['MinorNm'],
                            'Project'       => $val['MinorCd'],
                            'color'         => '',
                        );

                    }
                }else{
                    if($key == '7'){//气路检查
                        $infos[] = array(
                            'Seq'           => '8',
                            'Result'        => true,
                            'DesContent'    => '',
                            'MinorNm'       => $val['MinorNm'],
                            'Project'       => $val['MinorCd'],
                            'color'         => '',
                        );

                    }
                    if($key == '8'){//法兰深度
                        $infos[] = array(
                            'Seq'           => '9',
                            'Result'        => true,
                            'DesContent'    => '',
                            'MinorNm'       => $val['MinorNm'],
                            'Project'       => $val['MinorCd'],
                            'color'         => '',
                        );
                    }

                    if($key == '9'){//法兰定位
                        $infos[] = array(
                            'Seq'           => '10',
                            'Result'        => true,
                            'DesContent'    => '',
                            'MinorNm'       => $val['MinorNm'],
                            'Project'       => $val['MinorCd'],
                            'color'         => '',
                        );

                    }
                    if($key == '10'){//电阻测试
                        $infos[] = array(
                            'Seq'           => '11',
                            'Result'        => true,
                            'DesContent'    => '',
                            'MinorNm'       => $val['MinorNm'],
                            'Project'       => $val['MinorCd'],
                            'color'         => '',
                        );
                    }
                }
            }



           $res['data'] = $infos;

            return json($res);
        }
    }

    public function getArrival()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $loginID =  $param['UserID'];
            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            // $res['users'] = $user;
            $login_id = str_replace(' ','',$users['emp_code']);
            $result = InstallationTrialModel::getArrival($login_id);
            // $result = InstallationTrialModel::getArrival($loginID);
            if(!empty($result)){
                $result['ArrivalDate'] = date('Y-m-d H:i:s',strtotime($result['ArrivalDate']));

            }else{
                $result['ArrivalDate'] = '';
                $result['Arrivalphoto'] = '';
                $result['GpsLat'] = '';
                $result['GpsLng'] = '';
                $result['ArrivalLeaveNo'] = '';
                $result['ArrivalLocationAddr'] = '';
            }

            $res['data'] = $result;
            return json($res);
        }
    }
    public function InstallSave()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $addlist = InstallationTrialModel::getAddressInfo($param['ArrivalLeaveNo']);
            if(!empty($addlist)){
                // $param['ArrivalTime'] = $addlist['ArrivalDate'];
                // $param['ArrivalLeaveNo'] = $addlist['ArrivalLeaveNo'];
                // $param['ArrivalLocationAddr'] = $addlist['LocationAddr'];
                // $param['ArrivalLat'] = $addlist['GpsLat'];
                // $param['ArrivalLng'] = $addlist['GpsLng'];
                $param['Arrivalphoto'] = $addlist['Arrivalphoto'];
            }

            $AssmDate = strtotime(date('Y-m-d',strtotime($param['AssmDate'])));
            $timeAs = strtotime(date('Y-m-d'));
            if($AssmDate>$timeAs){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = "实际安装日日期不得大于当天";
                return json($res);
            }



            $phone = '/^1[3456789]\d{9}$/ims';
            $email = '/^\S+@\S+\.\S+$/';
            $tel = '/^([0-9]{3,4}-)?[0-9]{7,8}$/';

            if(!preg_match($phone,$param['CustTell'])){
                if(!preg_match($tel,$param['CustTell'])){
                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "请输入正确的电话号码";
                    // $res['data'] = $post_asid;
                    return json($res);
                }
            }
            if(!preg_match($email,$param['CustEmail'])){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = "请输入正确的邮箱地址";
                // $res['data'] = $post_asid;
                return json($res);
            }
            $add = array(
                // 'AssmReptNo'           => $post_mtid,        //
                'AssmReptDate'          => $param['AssmReptDate'].' 00:00:00.000',
                'AssmDeptCd'            => $param['DeptCd'],
                'AssmEmpID'             => $param['EmpID'],
                'AssmDate'              => date('Y-m-d H:i:s'),    //
                'ExpClss'               => $param['ExpClss'],
                'OrderNo'               => $param['OrderNo'],           //
                // 'TrialDeptCd'           => '',
                // 'TrialEmpID'            => '',
                'AssmContents'          => $param['AssmContents'],
                'Remark'                => $param['Remark'],
                'RegEmpID'              => $param['UserID'],
                'RegDate'               => date('Y-m-d H:i:s'),
                'UptEmpID'              => $param['UserID'],
                'UptDate'               => date('Y-m-d H:i:s'),
                'SysRemark'             => 'mobile-info',         //
                'SupplyScope'           => $param['SupplyScope'],
                'HRSystem'              => $param['HRSystem'],
                'ManifoldType'          => $param['ManifoldType'],
                'SystemSize'            => $param['SystemSize'],

                'SystemType'            => $param['SystemType'],
                'GateQty'               => $param['GateQty'],
                'AssmWiriMode'          => $param['AssmWiriMode'],
                'CustPrsn'              => $param['CustPrsn'],
                'CustTell'              => $param['CustTell'],
                'CustEmail'             => $param['CustEmail'],
                'AssmWiriType'          => $param['AssmWiriType'],
                'SystemClass'           => $param['SystemClass'],
                'ArrivalTime'           => $param['ArrivalTime'],
                'ArrivalLeaveNo'        => $param['ArrivalLeaveNo'],
                'ArrivalLat'            => $param['ArrivalLat'],
                'ArrivalLng'            => $param['ArrivalLng'],
                'ArrivalLocationAddr'   => $param['ArrivalLocationAddr'],
                'Arrivalphoto'          => $param['Arrivalphoto'],
                'LeaveTime'             => $param['LeaveTime'],
                'LeaveLat'              => $param['LeaveLat'],
                'LeaveLng'              => $param['LeaveLng'],
                'LeaveLocationAddr'     => $param['LeaveLocationAddr'],
                'Leavephoto'            => $param['Leavephoto'],

            );
            $Infoxinxis = $param['Infoxinxis'];
            $result_order = InstallationTrialModel::OrderAssmRept($param['OrderNo']);

            if(empty($param['AssmReptNo']) && empty($result_order)){
                $AssmReptNo = InstallationTrialModel::AssmReptNo();
                if(empty($param['ArrivalLeaveNo'])){
                    unset($add['ArrivalTime']);
                    unset($add['ArrivalLeaveNo']);
                    unset($add['ArrivalLat']);
                    unset($add['ArrivalLng']);
                    unset($add['ArrivalLocationAddr']);
                    unset($add['Arrivalphoto']);
                }

                unset($add['LeaveTime']);
                unset($add['LeaveLat']);
                unset($add['LeaveLng']);
                unset($add['LeaveLocationAddr']);
                unset($add['Leavephoto']);
                $add['facilityYn'] = 1;
                $add['AssmReptNo'] = $AssmReptNo;

                $update = false;
                $resadd = InstallationTrialModel::addInstall($add);

                foreach ($Infoxinxis as $key => $value) {
                        $data = array(
                            'AssmReptNo'    => $AssmReptNo,
                            'Seq'           => $value['Seq'],
                            'Project'       => $value['Project'],
                            'Result'        => $value['Result']?'Y':'N',
                            'DesContent'    => $value['DesContent'],
                            'RegEmpID'      => $param['UserID'],
                            'RegDate'       => date('Y-m-d H:i:s'),
                            'UptEmpID'      => $param['UserID'],
                            'UptDate'       => date('Y-m-d H:i:s'),
                        );
                        InstallationTrialModel::addSystemClass($data);
                    }

            }else{
                if($param['AssmReptNo'] == $result_order['AssmReptNo']){
                    $AssmReptNo = $param['AssmReptNo'];
                    $OAwhere = array(
                        'SourceNo'  => $param['AssmReptNo'],
                        'SourceType'    => '015'
                    );
                    $OANow = BaseModel::OAInterface($OAwhere);
                    if(!empty($OANow)){
                        $res['statusCode']  = 101;
                        $res['statusMsg']  = '正在提交OA审核，无法修改数据';

                    }
                    unset($add['facilityYn']);
                    unset($add['AssmDate']);
                    // unset($add['AssmReptNo']);
                    // unset($add['AssmEmpID']);
                    unset($add['OrderNo']);
                    unset($add['RegEmpID']);
                    unset($add['RegDate']);
                    unset($add['SysRemark']);
                    unset($add['AssmReptDate']);
                    $add['UptEmpID'] = $param["UserID"];
                    $timeU = date("Y-m-d H:i:s");
                    $add['UptDate'] = $timeU;

                    if($result_order['facilityYn'] !=1){

                        $res['statusCode']  = 102;
                        $res['statusMsg']  = '报告是PC端ERP输入，不可修改';

                    }

                    if(!empty($param['LeaveTime'])){


                        $juli1 = Apis::getDistance($param['ArrivalLat'],$param['ArrivalLng'],$param['LeaveLat'],$param['LeaveLng']);
                        if($juli1 > 1000){
                            $res['statusCode'] = 'N003';
                            $res['returnMsg'] = "到达距离与离开距离已超出范围";
                            $res['data'] = $param['AssmReptNo'];
                            return json($res);
                        }
                        if(strtotime($param['ArrivalTime'])>strtotime($param['LeaveTime'])){
                            $res['statusCode'] = 'N003';
                            $res['returnMsg'] = "到达时间->离开时间请检查先后顺序";
                            $res['data'] = $param['AssmReptNo'];
                            return json($res);
                        }

                    }else{
                        unset($add['LeaveTime']);
                        unset($add['LeaveLat']);
                        unset($add['LeaveLng']);
                        unset($add['LeaveLocationAddr']);
                        unset($add['Leavephoto']);
                    }

                    $where = array(
                        'AssmReptNo'    => $AssmReptNo
                    );
                    InstallationTrialModel::SaveInstall($add,$where);
                    InstallationTrialModel::DeleteSystemClass($AssmReptNo);
                    foreach ($Infoxinxis as $key => $value) {
                        $data = array(
                            'AssmReptNo'    => $AssmReptNo,
                            'Seq'           => $value['Seq'],
                            'Project'       => $value['Project'],
                            'Result'        => $value['Result']?'Y':'N',
                            'DesContent'    => $value['DesContent'],
                            'RegEmpID'      => $param['UserID'],
                            'RegDate'       => date('Y-m-d H:i:s'),
                            'UptEmpID'      => $param['UserID'],
                            'UptDate'       => date('Y-m-d H:i:s'),
                        );
                        InstallationTrialModel::addSystemClass($data);
                    }


                }
                $update = true;
            }


            $res['Infoxinxis'] = $param['Infoxinxis'];
            $res['update'] = $update;
            $res['data'] = $AssmReptNo;
            return json($res);
        }
    }
    private function infoX($result,$RelCd1){
        $infos = array();
        foreach ($result as $key => $val){
            if($key <=6){
                $infos[] = array(
                    'Seq'           => $key+1,
                    'Result'        => true,
                    'DesContent'    => '',
                    'MinorNm'       => $val['MinorNm'],
                    'Project'       => $val['MinorCd'],
                    'color'         => '',
                );
            }

            if($RelCd1 == 'SA34010010'){
                if($key == '7'){//电阻测试
                    $infos[] = array(
                        'Seq'           => 11,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );
                }
            } else if($RelCd1 == 'SA34010020'){
                if($key == '7'){//气路检查
                    $infos[] = array(
                        'Seq'           => 8,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );
                }
                if($key == '8'){//电阻测试
                    $infos[] = array(
                        'Seq'           => 11,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );

                }
            }else{
                if($key == '7'){//气路检查
                    $infos[] = array(
                        'Seq'           => 8,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );

                }
                if($key == '8'){//法兰深度
                    $infos[] = array(
                        'Seq'           => 9,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );
                }

                if($key == '9'){//法兰定位
                    $infos[] = array(
                        'Seq'           => 10,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );

                }
                if($key == '10'){//电阻测试
                    $infos[] = array(
                        'Seq'           => 11,
                        'Result'        => true,
                        'DesContent'    => '',
                        'MinorNm'       => $val['MinorNm'],
                        'Project'       => $val['MinorCd'],
                        'color'         => '',
                    );
                }
            }
        }
        return $infos;
    }



    /**
     * 添加安装报告-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function addSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            // $loginID =  $param['UserID'];
            $param['UserID'] = $this->getUserId();
            $where = array(
                'AssmReptNo'    => $param['AssmReptNo']
            );

            $count = InstallationTrialModel::getSalesD($where);

            $w = array(
                'AssmReptNo'    => $param['AssmReptNo'],
                'SaleEmpID'     => $param['EmpID']
            );
            $user = InstallationTrialModel::getSalesE($w);
            if($user){
                $res['statusCode'] = '104';
                $res['statusMsg'] = '同行人员已存在';
                return json($res);
            }
            $count += 1;
             $data = array(
                'AssmReptNo'    => $param['AssmReptNo'],
                'Seq'           => '0'.$count,
                'SaleEmpID'     => $param['EmpID'],
                'RegEmpID'      => $param['UserID'],
                'RegDate'       => date('Y-m-d H:i:s'),
                'UptEmpID'      => $param['UserID'],
                'UptDate'       => date('Y-m-d H:i:s'),
            );

            InstallationTrialModel::addSales($data);

            $list = InstallationTrialModel::getAssmFellow($param['AssmReptNo']);
            $res['count'] = $count;
            $res['data'] = $list;

            return json($res);
        }
    }
    /**
     * 安装报告-同行人员列表
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function SalesList()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $list = InstallationTrialModel::getAssmFellow($param['AssmReptNo']);
            $res['data'] = $list;
            return json($res);
        }
    }

    /**
     * 添加安装报告-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function DeleteSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $where = array(
                'AssmReptNo'    => $param['AssmReptNo'],
                'Seq'           => $param['Seq']
            );
            InstallationTrialModel::DeleteSales($where);
            $list = InstallationTrialModel::getAssmFellow($param['AssmReptNo']);
            $w = array(
                'AssmReptNo'    => $param['AssmReptNo']
            );
            InstallationTrialModel::DeleteSales($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'AssmReptNo'    => $param['AssmReptNo'],
                    'Seq'           => '0'.$count,
                    'SaleEmpID'     => $value['EmpID'],
                    'RegEmpID'      => $param['UserID'],
                    'RegDate'       => date('Y-m-d H:i:s'),
                    'UptEmpID'      => $param['UserID'],
                    'UptDate'       => date('Y-m-d H:i:s'),
                );
                InstallationTrialModel::addSales($data);
            }
            $list = InstallationTrialModel::getAssmFellow($param['AssmReptNo']);


            $res['data'] = $list;
            return json($res);
        }
    }

    /**
     * 安装报告-同行人员列表
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function getAssmPhotoNm()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $w = array(
                    'AssmReptNo'    => $param['AssmReptNo']
                );
            $list = InstallationTrialModel::getAssmPhotoNm($w);
            $config = config('web');
            $this->DownloadPhoto($config['Sales'],$config['SalesFTP'],$param['AssmReptNo']);



            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['AssmReptNo'];
                $dirname_year = substr($value['AssmReptNo'],0,4);
                $dirname_month = substr($value['AssmReptNo'],4,2);
                $dirname_defualt = substr($value['AssmReptNo'],0,6);
                $fileurl = $config['HomeUrl'].$config['Sales']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'AssmReptNo' => $value['AssmReptNo'],
                    'FileNm'    => $fileurl.$value['FileNm'],
                    'FTP_UseYn' => $value['FTP_UseYn'],

                    'Photo' => $value['Photo'],
                    'Seq' => $value['Seq'],
                );
            }
            $res['data'] = $AssmPhoto;
            $res['photoCount'] = count($AssmPhoto);

            return json($res);
        }
    }

     /**
     * 添加安装报告-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function addPhotos()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $res['param'] = $param;
            $date = date('Ymdhis',time());
            $config = config('web');
            $mt_id = $param['AssmReptNo'];
            $param['UserID'] = $this->getUserId();
            $UserID = $param['UserID'];

            // $addressL = Apis::geocoder($param['Lng'],$param['Lat'],$config['addres']);
            $address = '';//$addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;

            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $fun = $config['Sales'];

            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');

            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);


            $remoteFile = $config['SalesFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;


            FtpUtil::ftp_photo($mt_id,$config['SalesFTP']);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
            }

            $w = array(
                'AssmReptNo'    => $mt_id
            );

            $list = InstallationTrialModel::getAssmPhotoNm($w);

            $counTS = count($list);

            $res['listdate'] = $param;

            $counTS = $counTS + 1;
            $seq = (int)$counTS>=10?$counTS:'0'.$counTS;
            $data = array(
                'AssmReptNo'    => $mt_id,
                'Seq' => $seq,
                'FileNm'    => $fileNm,
                'RegEmpID'      => $UserID,
                'RegDate'       => date('Y-m-d H:i:s'),
                'UptEmpID'      => $UserID,
                'UptDate'       => date('Y-m-d H:i:s'),
                'AssmDate'      => date('Y-m-d H:i:s'),
                'FTP_UseYn' => 'Y',
                'Lat'           => $param['Lat'],
                'Lng'           => $param['Lng'],
                'LocationAddr'  => $address,
            );


            $where = array(
                    'AssmReptNo'    => $mt_id,
                    'Seq' => $data['Seq']
                );
            $Photo = InstallationTrialModel::getAssmPhoto($where);

            if(empty($Photo)){
                if((int)$data['Seq']<=2){
                    $addressL = Apis::geocoder($param['Lng'],$param['Lat'],$config['addres']);
                    if($addressL->status == '0'){
                        $address = $addressL->result->formatted_addresses->standard_address;
                    }else{
                        $address = '';
                    }


                    $data['LocationAddr'] = $address;
                }

                InstallationTrialModel::addPhoto($data);
            }else{
                $count = '';
                for ($i = count($Photo); $i >= 1; $i--) {
                    $sq = $i>=10?$i:'0'.$i;
                    $where = array(
                        'AssmReptNo'    => $mt_id,
                        'Seq' => $sq
                    );
                    $Photo = InstallationTrialModel::getAssmPhoto($where);
                    if(empty($Photo)){
                        $count = $i;
                    }
                }
                // $res['count'] = $count;
                // $res['data'] = $data;
                // $res['Photo'] = $Photo;
                // return json($res);
                $seqD = (int)$count>=10?$count:'0'.$count;
                $data['Seq'] = $seqD;
                if(!empty($count)){
                    InstallationTrialModel::addPhoto($data);
                }
            }






            $res['counTS'] = $counTS;

            $lists = InstallationTrialModel::getAssmPhotoNm($w);

            $AssmPhoto = array();
            foreach ($lists as $key => $value) {
                $mt_id = $value['AssmReptNo'];
                $dirname_year = substr($value['AssmReptNo'],0,4);
                $dirname_month = substr($value['AssmReptNo'],4,2);
                $dirname_defualt = substr($value['AssmReptNo'],0,6);
                $fileurl = $config['HomeUrl'].$config['Sales']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'AssmReptNo' => $value['AssmReptNo'],
                    'FileNm'    => $fileurl.$value['FileNm'],
                    'FTP_UseYn' => $value['FTP_UseYn'],
                    'AssmReptNo' => $value['AssmReptNo'],
                    'Photo' => $value['Photo'],
                    'Seq' => $value['Seq'],
                );
            }
            $res['data'] = $AssmPhoto;
            $res['photoCount'] = count($AssmPhoto);

            return json($res);
        }
    }


    /**
     * 安装报告-删除照片
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function DeletePhotos()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $where = array(
                'AssmReptNo'    => $param['AssmReptNo'],
                'Seq'           => $param['Seq']
            );
            InstallationTrialModel::DeletePhoto($where);
            $w = array(
                'AssmReptNo'    => $param['AssmReptNo']
            );
            $list = InstallationTrialModel::getAssmPhotoNm($w);

            InstallationTrialModel::DeletePhoto($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'AssmReptNo' => $value['AssmReptNo'],
                    'Seq' => '0'.$count,
                    'FileNm'    => $value['FileNm'],
                    'RegEmpID'      => $param['UserID'],
                    'RegDate'       => date('Y-m-d H:i:s'),
                    'UptEmpID'      => $param['UserID'],
                    'UptDate'       => date('Y-m-d H:i:s'),
                    'AssmDate'      => date('Y-m-d H:i:s'),
                    'FTP_UseYn' => 'Y',
                    'Lat'           => $value['Lat'],
                    'Lng'           => $value['Lng'],
                    'LocationAddr'  => $value['LocationAddr'],
                );
                InstallationTrialModel::addPhoto($data);
            }
            $list = InstallationTrialModel::getAssmPhotoNm($w);
            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['AssmReptNo'];

                $dirname_year = substr($value['AssmReptNo'],0,4);
                $dirname_month = substr($value['AssmReptNo'],4,2);
                $dirname_defualt = substr($value['AssmReptNo'],0,6);
                $fileurl = $config['HomeUrl'].$config['Sales']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'AssmReptNo' => $value['AssmReptNo'],
                    'FileNm'    => $fileurl.$value['FileNm'],
                    'FTP_UseYn' => $value['FTP_UseYn'],
                    'AssmReptNo' => $value['AssmReptNo'],
                    'Photo' => $value['Photo'],
                    'Seq' => $value['Seq'],
                );
            }
            $res['data'] = $AssmPhoto;

            $res['photoCount'] = count($AssmPhoto);

            return json($res);
        }
    }

    /**
     * 安装报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadPhoto($fun,$funFTP,$AssmReptNo)
    {

            $config = config('web');
            // $fun = 'AssembleReport';
            $w = array(
                'AssmReptNo'    => $AssmReptNo
            );
            $list = InstallationTrialModel::getAssmPhotoNm($w);
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = $v['AssmReptNo'];
                $filename = $v['FileNm'];
                $dirname_year = substr($v['AssmReptNo'],0,4);
                $dirname_month = substr($v['AssmReptNo'],4,2);
                $dirname_defualt = substr($v['AssmReptNo'],0,6);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }


                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');
                    if ($v['FTP_UseYn'] == 'Y') {
                        if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                            return false;
                        }
                    }
                }
            }
            ftp_close($conn);
            return true;
    }
     /**
     * 安装报告-签名下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadSign($fun,$funFTP,$AssmReptNo)
    {

            $config = config('web');
            // $fun = 'AssembleReport';
            $w = array(
                'AssmReptNo'    => $AssmReptNo
            );
            $list = InstallationTrialModel::getAssmPhotoNm($w);
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
                $mt_id = $AssmReptNo;
                $filename = $config['SignName'];
                $dirname_year = substr($AssmReptNo,0,4);
                $dirname_month = substr($AssmReptNo,4,2);
                $dirname_defualt = substr($AssmReptNo,0,6);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }
                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');
                    $urlname =  ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk";
                    $file_size = ftp_size($conn, $urlname);
                    if($file_size > 0){
                        if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/".$config['SignC'].$filenameGbk, FTP_BINARY)) {
                            return false;
                        }
                    }else{
                        $dir = "$fun/$dirname_year/$dirname_month/$mt_id/";
                        $remoteFile = $funFTP."/$dirname_year/$dirname_month/$mt_id/".$config['SignD'].$config['SignC'].$config['SignName'];
                        $localFile = $config['localFile'].$dir.$config['SignD'].$config['SignC'].$config['SignName'];
                        $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
                        if(is_file($yeardir."/".$config['SignName'])) {
                            if (!is_dir($yeardir)) {
                                mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                            }

                            FtpUtil::ftp_photo($mt_id,$funFTP,$config['Sign']);
                            if (!FtpUtil::upload($localFile, $remoteFile)) {
                                $res['statusCode'] = '105';
                                $res['msg'] ="文件上传失败";
                            }
                        }
                    }

                }
            ftp_close($conn);
            return true;
    }

    /**
     * 安装报告-客户签名
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function SignImage()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            // $fun = 'AssembleReport';
            $res['data'] = $param;



            $file = request()->file('sign');
            $mt_id = $param['AssmReptNo'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $fun = $config['Sales'];

            $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
            if (!is_dir($yeardir)) {
                mkdir(iconv("UTF-8", "GBK", $yeardir), 0775, true);
            }
            $dir = "$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
            $fileNm = $config['SignName'];
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);
            $res['data']['savename'] = $savename;



            $where = array(
                'AssmReptNo'    => $param['AssmReptNo']
            );

            $addressL = Apis::geocoder($param['CustGpsLng'],$param['CustGpsLat'],$config['addres']);
            if($addressL->status == '0'){
                $CustLocationAddr = $addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;
            }else{
                $CustLocationAddr = '';
            }
            $time = date('Y-m-d H:i:s');
            $data = array(
                'CustSignYn'    => 'Y',
                'CustSignDate'    => $time,
                'CustGpsLat'    => $param['CustGpsLat'],
                'CustGpsLng'    => $param['CustGpsLng'],
                'CustLocationAddr'    => $CustLocationAddr,
                'LeaveTime'    => $time,
                'LeaveLat'    => $param['CustGpsLat'],
                'LeaveLng'    => $param['CustGpsLng'],
                'LeaveLocationAddr' => $CustLocationAddr,
            );
            $save_path = $config['localFile']."$fun/$dirname_year/$dirname_month/$mt_id/".$config['SignC'].$config['SignName']; // ÄãÒª±£´æÍ¼Æ¬µÄÂ·¾¶

            if(is_file($save_path)){
                InstallationTrialModel::SaveInstall($data,$where);
                $fileNm = $config['SignName'];
                $fileurl = $config['HomeUrl']."Sales/AssembleReport/$dirname_year/$dirname_month/$mt_id/".$config['SignC'];
                $SignUrl = $fileurl.$fileNm;


                $dir = "$fun/$dirname_year/$dirname_month/$mt_id/";
                $remoteFile = $config['SalesFTP']."/$dirname_year/$dirname_month/$mt_id/".$config['SignC'].$fileNm;
                $localFile = $config['localFile'].$dir.$config['SignD'].$config['SignC'].$fileNm;
                FtpUtil::ftp_photo($mt_id,$config['SalesFTP'],$config['Sign']);
                if (!FtpUtil::upload($localFile, $remoteFile)) {
                    $res['statusCode'] = '105';
                    $res['msg'] ="文件上传失败";
                }
                $res['data'] = $SignUrl;
                $res['CustSignYn'] = 'Y';
            }else{
                $res['statusCode'] = '201';
            }

            return json($res);
        }
    }



    /**
     * 安装报告-提交OA审批
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function SubAdjudication(){

        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $post_asid = $param['AssmReptNo'];
            $param['UserID'] = $this->getUserId();
            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            // $res['users'] = $user;
            $login_id = str_replace(' ','',$users['emp_code']);
            $asUserId = str_replace(' ','',$param['UserID']);
            $OAwhere = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '015'
            );
            $query = BaseModel::OAInterface($OAwhere);

            // $query = $this->As10_model->table('TS_OA_Interface')->field('SourceNo')->where(array('SourceNo' => $post_asid,'SourceType'  => '015'))->find();
            if(!empty($query['SourceNo'])){
                $res['statusCode'] = 'N003';
                $returnMsg = '裁决已经存在';


            }
            $where = array(
                'AssmReptNo'    => $post_asid
            );
            $result = InstallationTrialModel::AssmRept($where);
            // $sql = "select top 1 * from TSAAssmRept00 With(Nolock) WHERE AssmReptNo = '$post_asid'";
            // $result = $this->jlamp_common_mdl->sqlRow($sql);

            if($result['facilityYn'] !=1){
                $res['statusCode'] = 'N003';
                $returnMsg= '报告是PC端ERP输入，不可修改';



            }
            $juli1 = Apis::getDistance($result['ArrivalLat'],$result['ArrivalLng'],$result['LeaveLat'],$result['LeaveLng']);

            $res['juli'] = $juli1;
            if($juli1 > 1000){
                $res['statusCode'] = 'N003';
                $returnMsg = '到达距离与离开距离已超出范围';

            }
            if((strtotime($result['LeaveTime']) - strtotime($result['ArrivalTime'])) > 86400 ){

                $res['statusCode'] = 'N003';
                $returnMsg = '到达时间与离开时间不得超过24小时';

            }
            if(strtotime($result['ArrivalTime'])>strtotime($result['LeaveTime'])){

                $res['statusCode'] = 'N003';
                $rreturnMsg = '到达时间->离开时间请检查先后顺序';

            }

            if($res['statusCode']!='200'){

                $returnMsg = $returnMsg;

                $res['returnMsg'] = $returnMsg;
                return json($res);
            }


            if($result['CustSignYn'] == 'Y'){
                $w = array(
                    'AssmReptNo'    => $post_asid
                );
                $results = InstallationTrialModel::AssmRept($w);
                if($results['SendEmailYn'] != 'Y'){
                    $sendYn = $this->SendEmail($param['UserID'],$result['OrderNo']);
                    if(!$sendYn){
                        $res['statusCode'] = 'I451';
                        $returnMsg = "邮件发送失败，请稍后重新提交OA审核";
                        // $returnMsg = mb_convert_encoding($returnMsg, 'UTF-8', 'GBK');
                        $res['returnMsg'] = $returnMsg;
                        return json($res);
                    }
                    $save = array(
                      'SendEmailYn' => 'Y'
                    );
                    $where = array(
                        'AssmReptNo' => $post_asid
                    );
                    InstallationTrialModel::SaveInstall($save,$where);
                }
            }

            $adds = array(
                'SourceType'  => '015',
                'SourceNo'    => $post_asid,
                'SP_Contents' => "execute dbo.P_SSAAssmReptCfm 'CA','$post_asid','$asUserId'",
                'OA_Status'   => '0',
                'RegEmpID'    => $asUserId,
                'RegDate'     => date('Y-m-d H:i:s'),
                'UptEmpID'    => $asUserId,
                'UptDate'     => date('Y-m-d H:i:s')
            );
            // $this->load->model('As20_model');
            // $ass = $this->As20_model->table('TS_OA_Interface')->add($adds);



            $OAwhere = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '015'
            );
            $Interface = BaseModel::OAInterface($OAwhere);

            // $query = $this->As10_model->table('TS_OA_Interface')->field('SourceNo')->where(array('SourceNo' => $post_asid,'SourceType'  => '015'))->find();
            if(empty($Interface['SourceNo'])){
                InstallationTrialModel::addOAInterface($adds);
                // $res['statusCode'] = 'N003';
                // $returnMsg = '裁决提交失败，请稍后再试';

                // // $returnMsg = mb_convert_encoding($returnMsg, 'UTF-8', 'GBK');

                // $res['returnMsg'] = $returnMsg;
                // return json($res);
            }



            $save = array(
              'ApprUseYn' => '1'
              // 'SendEmailYn' => 'Y'
            );
            $where = array(
                'AssmReptNo' => $post_asid
            );
            InstallationTrialModel::SaveInstall($save,$where);

            $res['data'] = $post_asid;
            return json($res);


        }

    }
    //取消OA审批
    public function unSubAdjudication()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');
            $res['statusCode'] = '200';
            $post_asid = $param['AssmReptNo'];
            $param['UserID'] = $this->getUserId();
            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            // $res['users'] = $user;
            $login_id = str_replace(' ','',$users['emp_code']);
            $asUserId = str_replace(' ','',$param['UserID']);
            $OAwhere = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '015'
            );
            $query = BaseModel::OAInterface($OAwhere);

            if(empty($query['SourceNo'])){
                $res['statusCode'] = 'N003';
                $returnMsg = '当前安装还没有申请裁决';
                $this->jlamp_comm->jsonEncEnd($this->recall_array);
            }
            elseif($query['OA_Status'] != '5'){
                $res['statusCode'] = 'N003';
                $returnMsg = '不可取消正在进行中的裁决';

            }
            if($res['statusCode']!='200'){

                // $returnMsg = mb_convert_encoding($returnMsg, 'UTF-8', 'GBK');

                $res['returnMsg'] = $returnMsg;
                return json($res);
            }
            InstallationTrialModel::DeleteOAInterface($OAwhere);
            $save = array(
                'ApprUseYn' => '0'
            );
            $where = array(
                'AssmReptNo' => $post_asid
            );
            InstallationTrialModel::SaveInstall($save,$where);

        }
    }



    private function SendEmail($UserID,$OrderNo)
    {
        // $param = Request::param();

        $config = config('web');

        $EmpId = str_replace(' ','',$UserID);

        $users = UserModel::getUserDeptInfo($UserID)[0];
            // $res['users'] = $user;
        $EmpId = str_replace(' ','',$users['emp_code']);
        $mulu = $UserID;

        if(!empty($EmpId)){
            $mulu = $EmpId;
        }else{
            $mulu = str_replace(' ','',$OrderNo);
        }
        $langCode = $config['CHN'];
        $Orders = InstallationTrialModel::orderMinute($OrderNo,$langCode);
        $data = InstallationTrialModel::AssmReptMinute($OrderNo);
        $data['Orders'] = $Orders;

        $fun = $config['Sales'];

        $mt_id = $data['AssmReptNo'];
        // $filename = $v['FileNm'];
        $dirname_year = substr($mt_id,0,4);
        $dirname_month = substr($mt_id,4,2);
        $dirname_defualt = substr($mt_id,0,6);
        $file = "static/$fun/$dirname_year/$dirname_month/$mt_id/$mulu/".$data['AssmReptNo'].'.pdf';

        if(!file_exists($file)){
            $res['statusCode'] = 'N004';
            $returnMsg = '文件不存在存在';
        }
        $EmptysH = UserModel::getEmpIDHP($EmpId);
        $data['HP'] = $EmptysH['HP'];
        $data['EmailID'] = $EmptysH['EmailID'];
        // $html = '<div style="margin-top:30px;">尊敬的'.$data['CustNm'].'</div>';
        // $html .= '<div>&nbsp;&nbsp;&nbsp;&nbsp;'.$data['CustPrsn'].' 先生 / 女士 您好！</div>';
        // $html .= '<div style="margin-bottom:40px;">&nbsp;&nbsp;&nbsp;&nbsp;非常感谢一直以来的支持与配合</div>';
        $html = '<div style="margin-top:30px;">尊敬的客户您好：</div>';
        $html .= '<div style="margin-bottom:40px;">非常感谢一直以来的支持与配合！</div>';
        $html .= '<div>'.$data['AssmDate'].'贵司安装系统事项如下</div>';
        $html .= '<div>1.贵公司模号：'.$Orders['RefNo'].'</div>';
        $html .= '<div>2.YUDO订单号：'.$data['OrderNo'].'</div>';
        $html .= '<div style="margin-bottom:40px;">3.热流道安装报告（见附件PDF）</div>';
        $html .= '<div style="margin-bottom:40px;">
                <div>以上如有任何疑问请随时联系我们</div>
                <div>谢谢支持！</div>
                </div>
                <div>此邮件为系统自动发送！(MP-安装报告)</div>
                <div><span style="font-size:25px;font-weight: bolder;color: #D11341;font-family: fantasy;transform: scale(1.5,1.5);display:inline-block;
                    -ms-transform: scale(1,1.5);
                    -webkit-transform: scale(1,1.5);
                    -moz-transform: scale(1,1.5);
                    -o-transform: scale(1,1.5);">YUDO</span>
                    <span stype="line-height:25px;height:25px;"> Leading Innovation</span></div>
                <div>
                    <p>'.$data['AssmEmpNm'].' | '.$data['AssmDeptNm'].'<p>
                    <p>M: '.$data['HP'].' T: 0512-65048882 E-mail:'.$data['EmailID'].' <p>
                    <p>W: http://www.yudo.com.cn<p>
                    <p>柳道万和（苏州）热流道系统有限公司 | YUDO(SUZHOU) HOT RUNNER SYSTEMS CO., LTD<p>
                    <p>苏州市吴中区甪直镇凌港路29号 | No.29 Ling Gang Road, Wuzhong District, Suzhou City, Jiangsu Province, China<p>
                </div>
                ';
                // $html = mb_convert_encoding($html, 'GBK', 'UTF-8');
                // $html = mb_convert_encoding($html, 'UTF-8', 'GBK');


        $list = InstallationTrialModel::getemalis($data['AssmReptNo']);

        $res['data'] = $list;


        $mail = new JlampMail();

         // $mail->setServer("smtphz.qiye.163.com", "sales@yudosuzhou.com", "*9KTH27UpaXz",25, false); //设置smtp服务器，普通连接方式
         // $mail->setServer("115.236.119.65", "sales@yudosuzhou.com", "AB9Q@U*La9jC",25, false); //设置smtp服务器，普通连接方式
         // $mail->setServer("smtp.gmail.com", "XXXXX@gmail.com", "XXXXX", 465, true); //设置smtp服务器，到服务器的SSL连接
         $mail->setServer("fastsmtphz.qiye.163.com", "sales@yudosuzhou.com", "AB9Q@U*La9jC",25, false); //设置smtp服务器，普通连接方式
         $mail->setFrom("sales@yudosuzhou.com"); //设置发件人
         $data['EmailID'] = empty($list['EmailID'])?$list['EmailID']:(string)$list['EmailID'];
         $data['CEmail'] = empty($list['CEmail'])?$list['CEmail']:(string)$list['CEmail'];
         $data['DEmail'] = empty($list['DEmail'])?$list['DEmail']:(string)$list['DEmail'];
         $data['MEmail'] = empty($list['MEmail'])?$list['MEmail']:(string)$list['MEmail'];
         $data['GMEmail'] = empty($list['GMEmail'])?$list['GMEmail']:(string)$list['GMEmail'];
         // var_dump($list['EmailID']);
         // var_dump($data);exit();

         // $mail->setReceiver($data['EmailID']); //设置收件人，多个收件人，调用多次
         $mail->setReceiver($data['CustEmail']); //设置收件人，多个收件人，调用多次
         // $mail->setReceiver($data['EmailID']); //设置收件人，多个收件人，调用多次
         $mail->setCc($data['GMEmail']); //设置抄送，多个抄送，调用多次
         //$mail->setCc($data['DEmail']); //设置抄送，多个抄送，调用多次
         // if($data['MEmail'] != $data['CEmail']){
         //     $mail->setCc($data['MEmail']); //设置抄送，多个抄送，调用多次
         //     $mail->setCc($data['CEmail']); //设置抄送，多个抄送，调用多次
         // }else{
         //     $mail->setCc($data['MEmail']); //设置抄送，多个抄送，调用多次
         // }

         if($data['DEmail'] != $data['MEmail']){
            $mail->setCc($data['DEmail']); //设置抄送，多个抄送，调用多次
            $mail->setCc($data['MEmail']); //设置抄送，多个抄送，调用多次
        }else{
            $mail->setCc($data['DEmail']); //设置抄送，多个抄送，调用多次
        }
        if($data['CEmail'] != $data['EmailID']){
            $mail->setCc($data['CEmail']); //设置抄送，多个抄送，调用多次
            $mail->setCc($data['EmailID']); //设置抄送，多个抄送，调用多次
        }else{

            // if($data['DEmail'] != $data['EmailID']){
            //     $mail->setCc($data['DEmail'])
                $mail->setCc($data['EmailID']);
            // }else{
            //     $mail->setCc($data['EmailID']);
            //  //设置抄送，多个抄送，调用多次
            // }
        }

         //$mail->setCc($data['EmailID']); //设置抄送，多个抄送，调用多次
         // $mail->setCc("erp2@yl-solution.com"); //设置抄送，多个抄送，调用多次

        // $mail->setCc("yuzhenhai2022@163.com"); //设置抄送，多个抄送，调用多次

         // $mail->setBcc("erp2@yl-solution.com"); //设置秘密抄送，多个秘密抄送，调用多次

         $mail->addAttachment($file); //添加附件，多个附件，调用多次


        $time = date('Y-m-d',time());

        $title = "YUDO - 热流道安装报告 -".$data['AssmReptNo']."-".$time."-".$data['AssmDeptNm'];
        // $title = mb_convert_encoding($title, 'UTF-8', 'GBK');

         $mail->setMail($title, $html); //设置邮件主题、内容

        $save = array(
          // 'ApprUseYn' => '1',
          'SendEmailYn' => 'Y'
        );
        $where = array(
            'AssmReptNo' => $data['AssmReptNo']
        );
        InstallationTrialModel::SaveInstall($save,$where);

         // $mail->sendMail(); //发送
        $send = $mail->sendMail(); //发送


        return json($send);


    }
     /**
     * 安装报告-获取PDF
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $param POST传值
     * @return bool
     */

    public function InstallPDF(){
        header('Content-Type: text/html; charset=UTF-8');
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $config = config('web');
            if(!isset($param['LangID'])){
                $param['LangID'] = 'CHN';
            }
            $param['UserID'] = $this->getUserId();
            $langCode = $config[$param['LangID']];
            $Orders = InstallationTrialModel::orderMinute($param['OrderNo'],$langCode);
            $data = InstallationTrialModel::AssmReptMinute($param['OrderNo']);
            $data['Orders'] = $Orders;

            // $xinxiinfos = InstallationTrialModel::getSystemClass($data['AssmReptNo']);




            $xinxiinfos = InstallationTrialModel::getSystemClass($data['AssmReptNo']);

            $systemC = InstallationTrialModel::getInfoxinxi($data['SystemClass']);
            $systemC = $this->infoX($systemC,$data['SystemClass']);
            $infos10 = array();

            foreach ($systemC as $key => $value) {
                $MinorNm = '';
                $Result = '';
                $DesContent = '';
                $AssmReptNo = '';
                foreach ($xinxiinfos as $k => $val) {
                    if((int)$value['Seq'] == (int)$val['Seq']){
                        $MinorNm = $value['MinorNm'];
                        $Result = $val['Result']=='Y'?true:false;
                        $DesContent = $val['DesContent'];
                        $AssmReptNo = $val['AssmReptNo'];
                    }
                }
                $infos10[] = array(
                    'AssmReptNo'    => $AssmReptNo,
                    'Result'    => $Result,
                    'MinorNm'    => $MinorNm,
                    'DesContent'    => $DesContent,
                    'Seq'    => $value['Seq'],
                    'Project'   => $value['Project'],

                );

            }

            $Infos = array();

            foreach($infos10 as $val){
                $val['DesContent'] = $val['DesContent'];
                $val['MinorNm'] = $val['MinorNm'];

                if(trim($val['Seq']) == '1'){
                    $Infos['fjw']['Seq'] = '1';
                    $Infos['fjw']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['fjw']['DesContent'] = $val['DesContent'];
                }

                if(trim($val['Seq']) == '2'){
                    $Infos['jk']['Seq'] = '2';
                    $Infos['jk']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['jk']['DesContent'] = $val['DesContent'];
                }
                if(trim($val['Seq']) == '3'){
                    $Infos['rldb']['Seq'] = '3';
                    $Infos['rldb']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['rldb']['DesContent'] = $val['DesContent'];
                }
                if(trim($val['Seq']) == '4'){
                    $Infos['gb']['Seq'] = '4';
                    $Infos['gb']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['gb']['DesContent'] = $val['DesContent'];
                }
                if(trim($val['Seq']) == '5'){
                    $Infos['mrsd']['Seq'] = '5';
                    $Infos['mrsd']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['mrsd']['DesContent'] = $val['DesContent'];
                }
                if(trim($val['Seq']) == '6'){
                    $Infos['zxdk']['Seq'] = '6';
                    $Infos['zxdk']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['zxdk']['DesContent'] = $val['DesContent'];
                }
                if(trim($val['Seq']) == '7'){
                    $Infos['dwq']['Seq'] = '7';
                    $Infos['dwq']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['dwq']['DesContent'] = $val['DesContent'];
                }
                if($data['SystemClass'] == 'SA34010030'){
                    if(trim($val['Seq']) == '8'){
                        $Infos['qljc']['Seq'] = '8';
                        $Infos['qljc']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                        $Infos['qljc']['DesContent'] = $val['DesContent'];
                        $Infos['qljc']['MinorNm'] = '8.'.$val['MinorNm'];
                    }
                    if(trim($val['Seq']) == '9'){
                        $Infos['flsd']['Seq'] = '9';
                        $Infos['flsd']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                        $Infos['flsd']['DesContent'] = $val['DesContent'];
                        $Infos['flsd']['MinorNm'] = '9.'.$val['MinorNm'];
                    }
                    if(trim($val['Seq']) == '10'){
                        $Infos['fldw']['Seq'] = '10';
                        $Infos['fldw']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                        $Infos['fldw']['DesContent'] = $val['DesContent'];
                        $Infos['fldw']['MinorNm'] = '10.'.$val['MinorNm'];
                    }
                } else {

                    $Infos['qljc']['Seq'] = '8';
                    $Infos['qljc']['Result'] = '';
                    $Infos['qljc']['DesContent'] = '';
                    $Infos['qljc']['MinorNm'] = '';

                     if($data['SystemClass'] == 'SA34010020'){
                        if(trim($val['Seq']) == '8'){
                            $Infos['qljc']['Seq'] = '8';
                            $Infos['qljc']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                            $Infos['qljc']['DesContent'] = $val['DesContent'];
                            $Infos['qljc']['MinorNm'] = '8.'.$val['MinorNm'];
                        }
                    }

                    $Infos['flsd']['Seq'] = '9';
                    $Infos['flsd']['Result'] = '';
                    $Infos['flsd']['DesContent'] = '';
                    $Infos['flsd']['MinorNm'] = '';

                    $Infos['fldw']['Seq'] = '10';
                    $Infos['fldw']['Result'] = '';
                    $Infos['fldw']['DesContent'] = '';
                    $Infos['fldw']['MinorNm'] = '';
                }

                if(trim($val['Seq']) == '11'){
                    $Infos['dzcs']['Seq'] = '11';
                    $Infos['dzcs']['Result'] = $val['Result'] == 'Y' ? 'OK' : 'NG';
                    $Infos['dzcs']['DesContent'] = $val['DesContent'];
                }

            }


            $data['infos'] = $Infos;

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            // $res['users'] = $user;
            $EmpId = str_replace(' ','',$users['emp_code']);
            $mulu = $param['UserID'];

            if(!empty($EmpId)){
                $mulu = $EmpId;
            }else{
                $mulu = str_replace(' ','',$param['UserID']);
            }
            if($data['CustSignYn'] != 'Y' ){
                $res['statusCode'] = 'N003';
                $returnMsg = '未签字不发邮箱';
            }
            if($data['SendEmailYn'] == 'Y' ){
                $res['statusCode'] = 'N003';
                $returnMsg = '邮件已发送';
            }
            if($data['AssmWiriType'] == 'SA34030010'){
                $AssmWiriType =  'YUDO标准';
            }else{
                $AssmWiriType =  '非标';
            }
            $fun = $config['Sales'];

            $mt_id = $data['AssmReptNo'];
            // $filename = $v['FileNm'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id/$mulu";
            $file = "static/$fun/$dirname_year/$dirname_month/$mt_id/$mulu/".$data['AssmReptNo'].'.pdf';
            if (!is_dir($yeardir)) {
                mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
            }


            if(file_exists($file)){
                $res['statusCode'] = 'N004';
                $returnMsg = '文件已存在';

            }
            if($res['statusCode']!='200'){

                // $returnMsg = mb_convert_encoding($returnMsg, 'UTF-8', 'GBK');

                $res['returnMsg'] = $returnMsg;
                return json($res);
            }

                    //邮箱
            // if(isset($data['EmpId'])){

                $EmptysH = UserModel::getEmpIDHP($EmpId);

                $Area = UserModel::getCustArea($Orders['CustCd']);


                if(empty(trim($Area['Area']))){
                     $data['Area'] = '';
                }else{
                    $Area1 = UserModel::getCustAreaNm($Area['Area']);
                    $langCode = $config['CHN'];

                    $Area2 = UserModel::getCustAreaTrNm($Area['Area'],$langCode);
                    // $Area2 = $this->As10_model->table('TSMDict10')->field('TransNm')->where(array('DictCd' => $Area['Area'],'LangCd'=>$langCode))->find();
                    if(empty(trim($Area1['MinorNm']))){
                        $data['Area'] = empty(trim($Area2['TransNm']))?'':$Area2['TransNm'];
                    }else{
                        $data['Area'] = $Area1['MinorNm'];
                    }

                }

                if($data['CustSignYn'] == 'Y'){
                    $asNo = $data['AssmReptNo'];
                    $dirYear = substr($asNo,0,4);
                    $dirMonth = substr($asNo,4,2);
                    // $dir = "./image/erpfile/Sales/AssembleReport/DEV/$dirYear/$dirMonth/$asNo/CustSign/Sign1.png";

                    $data['CustSign'] =  "static/$fun/$dirname_year/$dirname_month/$asNo/".$config['SignC'].$config['SignName'];
                }else{
                    $data['CustSign'] = '';
                }

                $data['HP'] = $EmptysH['HP'];
                $data['EmailID'] = $EmptysH['EmailID'];

                switch ($data['SystemClass']){
                case 'SA34010010':
                    $SystemClass_name = '整体式';
                    break;
                case 'SA34010020':
                    $SystemClass_name = '半整体式';
                    break;
                case 'SA34010030':
                    $SystemClass_name = '分体式';
                    break;
                }

                $data['SystemClass_name'] = mb_convert_encoding($SystemClass_name, 'UTF-8', 'GBK');

            //     // create new PDF document
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);








                // $l = Array();

                // // PAGE META DESCRIPTORS --------------------------------------

                // $l['a_meta_charset'] = 'UTF-8';
                // $l['a_meta_dir'] = 'ltr';
                // $l['a_meta_language'] = 'en';

                // // TRANSLATIONS --------------------------------------
                // $l['w_page'] = 'page';
                // // ---------------------------------------------------------

                // add a page
                $pdf->AddPage();


                // <img style="width: 150px;margin-top: 20px;" src="/image/login_logo2.png">
                $html = '<div style="color:#000;font-size:12px;">
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td colspan="4"><img style="width: 150px;" src="static/image/pdf_logo.png"></td>
                            <td style="height: 20px;line-height:20px;font-family: 宋体;font-weight:900;" colspan="8" align="left" style="text-align:left;">
                                <b style="color:#000;font-size:12px;">柳道万和（苏州）热流道系统有限公司</b><br />
                                <b style="color:#000;font-size:12px;">YUDO (SUZHOU) HOT RUNNER SYSTEMS CO., LTD</b><br />
                                <b style="color:#000;font-size:20px;">安装尺寸报告</b>
                                </td>
                        </tr>
                    </table>
                </div>

                <table border="1" cellspacing="0" cellpadding="0">

                    <tr>
                        <td align="center" colspan="2"><b>安装日期</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['AssmDate'].'</b></td>
                        <td align="center" colspan="2"><b>客户名称</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$Orders['custname'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>订单号码</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['OrderNo'].'</b></td>
                        <td align="center"><b>模号</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$Orders['RefNo'].'</b></td>
                        <td align="center"><b>区域</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['Area'].'</b></td>
                    </tr>
                    <tr>
                        <td  colspan="12" align="center" style="height: 250px;"><br /><br />&nbsp;<img style="height: 240px;" src="static/image/PDF-1.png">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>系统分类</b></td>
                        <td align="center" colspan="10"><b>'.$data['SystemClass_name'].'</b></td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>1.封胶位</b></td>
                        <td align="center" colspan="1">'.$Infos['fjw']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['fjw']['DesContent'].'</td>
                        <td align="center" colspan="2"><b>6.中心垫块</b></td>
                        <td align="center" colspan="1">'.$Infos['zxdk']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['zxdk']['DesContent'].'</td>

                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>2.浇口</b></td>
                        <td align="center" colspan="1">'.$Infos['jk']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['jk']['DesContent'].'</td>
                        <td align="center" colspan="2"><b>7.定位圈</b></td>
                        <td align="center" colspan="1">'.$Infos['dwq']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['dwq']['DesContent'].'</td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>3.热流道板</b></td>
                        <td align="center" colspan="1">'.$Infos['rldb']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['rldb']['DesContent'].'</td>
                        <td align="center" colspan="2"><b>'.$Infos['qljc']['MinorNm'].'</b></td>
                        <td align="center" colspan="1">'.$Infos['qljc']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['qljc']['DesContent'].'</td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>4.盖板</b></td>
                        <td align="center" colspan="1">'.$Infos['gb']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['gb']['DesContent'].'</td>
                        <td align="center" colspan="2"><b>'.$Infos['flsd']['MinorNm'].'</b></td>
                        <td align="center" colspan="1">'.$Infos['flsd']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['flsd']['DesContent'].'</td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>5.模仁深度</b></td>
                        <td align="center" colspan="1">'.$Infos['mrsd']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['mrsd']['DesContent'].'</td>
                        <td align="center" colspan="2"><b>'.$Infos['fldw']['MinorNm'].'</b></td>
                        <td align="center" colspan="1">'.$Infos['fldw']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['fldw']['DesContent'].'</td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>电阻测试</b></td>
                        <td align="center" colspan="1">'.$Infos['dzcs']['Result'] .'</td>
                        <td align="center" colspan="3">'.$Infos['dzcs']['DesContent'].'</td>
                        <td align="center" colspan="2"><b>接线方式</b></td>
                        <td align="center" style="font-size:8pt" colspan="1">'.$AssmWiriType.'</td>
                        <td align="center" colspan="3">'.$data['AssmWiriMode'].'</td>
                    </tr>


                    <tr>
                        <td align="center" colspan="2"><b>检查事项</b></td>
                        <td align="left" colspan="10"><b>&nbsp;&nbsp;'.$data['AssmContents'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>到达时间</b></td>
                        <td align="left" colspan="4" ><b>&nbsp;&nbsp;'.$data['ArrivalTime'].'</b></td>
                        <td align="center" colspan="2"><b>离开时间</b></td>
                        <td colspan="4" align="left"><b>&nbsp;&nbsp;'.$data['LeaveTime'].'</b></td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>安装人员</b></td>
                        <td align="left" colspan="4" ><b>&nbsp;&nbsp;'.$data['AssmEmpNm'].'</b></td>
                        <td align="center" colspan="2"><b>客户</b></td>
                        <td colspan="4" align="left">&nbsp;&nbsp;<img style="height: 50px;" src="'.$data['CustSign'].'"></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>联系方式</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['HP'].'</b><br />
                                <b>&nbsp;&nbsp;'.$data['EmailID'].'</b></td>
                        <td align="center" colspan="2"><b>联系方式</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['CustTell'].'</b><br />
                                <b>&nbsp;&nbsp;'.$data['CustEmail'].'</b></td>
                    </tr>
                </table>
                <style type="text/css">td{height: 25px;line-height:25px; left:10px;}</style>';
                ob_end_clean();


                // $html = mb_convert_encoding($html, 'GBK', 'UTF-8');
                // $html = mb_convert_encoding($html, 'UTF-8', 'GBK');


                // $html = "<h1>你好</h1>";
                $pdf->SetFont('cid0cs', '', 10);
                // output the HTML content
                $pdf->writeHTML($html, true, false, true, false, '');

                //Close and output PDF document
                // $res = $pdf->Output('example_039.pdf', 'I');
                $resA = $pdf->Output($data['AssmReptNo'].'.pdf', 'S');
                // $dir = './image/erpfile/Sales/AssembleReport/PDF/'.$data['AssmReptNo'].'/'.$mulu;

                // $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id/$mulu";
                // // $file = "static/$fun/$dirname_year/$dirname_month/$mt_id/$mulu/".$data['AssmReptNo'].'.pdf';

                // if (!is_dir($$yeardir)) {
                //     mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                // }
                $list = file_put_contents($file, $resA);

                $res['data'] = $data;

                return json($res);

        }
    }


    public function createPdf()
    {


        // create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);

            $pdf->setPrintHeader(false);
            // $pdf->setPrintFooter(false);

            // // set auto page breaks
            $pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);

            $pdf->AddPage();
            $html = "<h1>你好</h1>";
            ob_end_clean();
            $pdf->SetFont('cid0cs', '', 10);
            // output the HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
            // $pdf->Output('example_039.pdf', 'I');
            //Close and output PDF document
            $res = $pdf->Output('example_039.pdf', 'I');

    }


}