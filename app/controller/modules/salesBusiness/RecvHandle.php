<?php
/**
 * @Author: Yuzh
 * @Date: 2024-11-09 15:52
 */

namespace app\controller\modules\salesBusiness;

use app\controller\modules\Base;
use app\model\BaseModel;
use app\model\UserModel;

use app\model\salesBusiness\RecvHandleModel;
use app\model\salesBusiness\InstallationTrialModel;
use app\model\salesBusiness\TestationTrialModel;

use think\exception\Apis;

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
 * 每日统计标模块
 */
class RecvHandle extends Base
{

    /**
     * 订单区分
     * @param $param  试模报告订单区分POST传值
     * @return array
     */

    public function RecvSearch()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';
            $UserID = $this->getUserId();

            $users = InstallationTrialModel::Users($UserID);
            $auth = BaseModel::getAuth('WEI_2100',$UserID);
            $langCode = $this->langCode("CHN");

            $data = array(
                'EmpID'     => $users['empId'],
                'DeptCd'    => $users['DeptCd'],
                'JobNo'     => $users['JobNo'],
                'orderNo'    => $param['orderNo'],
                'asRecvNo'    => $param['asRecvNo'],
                'CustNm'    => $param['CustNm'],
                'CfmYn'    => $param['confirm'],
                'ASType' => $param['asclassID'],
                'Status'    => $param['status'],
                'startDate'    => $param['startDate'],
                'endDate'    => $param['endDate'],
                'config'      => $config,
                'count'    => $param['count'],
                'auth'      => $auth,
            );

            $list = RecvHandleModel::aslist($data);

            foreach($list as $key => $val){

                if($val['OrderSysRegYn'] == 'N'){

                    $list[$key]['OrderNo'] = $val['UnRegOrderNo'];
                }

                $typeNm = RecvHandleModel::getTSMSyco10('AS1002',$val['ASType'],$langCode);
                $list[$key]['typeNm'] = $typeNm[0]['text'];

                // $list[$key]['ASRecvDate'] = date('Y-m-d',strtotime($val['ASRecvDate']));
                if(!empty($val['ASDelvDate'])){
                    $list[$key]['ASDelvDate'] = date('Y-m-d',strtotime($val['ASDelvDate']));
                }else{
                    $list[$key]['ASDelvDate'] = '';

                }
            }

            $res['auth'] = $auth;
            $res['data'] = $list;
            if(count($list)>=50){
                $res['countM'] = true;
            }else{
                $res['countM'] = false;
            }

            return json($res);

        }
    }
     /**
     * 注塑厂和最终客户
     */
    public function getCustZZList(){
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';

            $param['langCode'] = $config[$param['LangID']];

            $list = RecvHandleModel::getCustZZList($param);

            $res['data'] = $list;

            $count = count($list);
            if($count >= 50){
                $countM = true;
            }else{
                $countM = false;
            }


            $res['countM'] = $countM;
            return json($res);
        }
    }
    /**
     * 注塑厂和最终客户
     */
    public function getCustYJList(){
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';

            $param['langCode'] = $config[$param['LangID']];

            $list = RecvHandleModel::getCustYJList($param);

            $res['data'] = $list;

            $count = count($list);
            if($count >= 50){
                $countM = true;
            }else{
                $countM = false;
            }
            $res['countM'] = $countM;
            return json($res);
        }
    }
    /**
     * 移模部门
     */
    public function getDeptsList(){
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';

            // $param['langCode'] = $config[$param['LangID']];

            $list = RecvHandleModel::getDeptsList($param);

            $res['data'] = $list;

            $count = count($list);
            if($count >= 50){
                $countM = true;
            }else{
                $countM = false;
            }
            $res['countM'] = $countM;
            return json($res);
        }
    }


    public function getASMinute(){
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';
            $UserID = $this->getUserId();
            $langCode = $config[$param['LangID']];
            $ASRecvNo = $param['ASRecvNo'];
            $list = RecvHandleModel::getASMinute($ASRecvNo,$langCode);

            //注塑厂名称
            if(!empty(trim($list['CustomerCd']))){
                $CustomerCdNm = RecvHandleModel::getCustCd($list['CustomerCd']);
                $list['CustomerNm'] = $CustomerCdNm['text'];
            }else{
                $list['CustomerNm'] = '';
            }
            //最终客户名称
            if(!empty(trim($list['MakerCd']))){
                $MakerCdNm = RecvHandleModel::getCustCd($list['MakerCd']);
                $list['MakerNm'] = $MakerCdNm['text'];
            }else{
                $list['MakerNm'] = '';
            }
            if($list['OrderGubun'] != '1'){
                $list['OrderNo'] = $list['UnRegOrderNo'];
            }
            //一级供应商
            if(!empty(trim($list['AgentCd']))){
                $AgentCdNm = RecvHandleModel::getCustCd($list['AgentCd']);
                $list['AgentNm'] = $AgentCdNm['text'];
            }else{
                $list['AgentNm'] = '';
            }

            $where = array(
                'ASRecvNo'    => $ASRecvNo
            );

            $RecvSales = RecvHandleModel::as_sales($ASRecvNo);


            $RecvPhoto = RecvHandleModel::getASPhoto($where);
            $this->DownloadRecvPhoto($config['Recv'],$config['RecvFTP'],$RecvPhoto);


            $RecvPhotos = array();
            foreach ($RecvPhoto as $key => $value) {
                $mt_id = $value['ASRecvNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Recv']."/$dirname_year/$dirname_month/$mt_id/";

                $exname = explode('.', $value['FileNm'])[1];
                $RecvPhotos[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASRecvNo' => $value['ASRecvNo'],
                    'FileNm'    => $fileurl.$value['FileNm'],
                    'FTP_UseYn' => $value['FTP_UseYn'],
                    'Photo' => $value['Photo'],
                    'Seq' => $value['Seq'],
                );
            }
            $TableList = RecvHandleModel::ASMinuteTable($ASRecvNo);
            $list['TableList'] = $TableList;
            $list['RecvSales'] = $RecvSales;
            $list['RecvPhotos'] = $RecvPhotos;


            $list['photoCount'] = count($RecvPhotos);

            $list['ArrivalTime'] = isset($list['ArrivalTime'])?date('Y-m-d H:i:s',strtotime($list['ArrivalTime'])):Null;
            $list['LeaveTime'] = isset($list['LeaveTime'])?date('Y-m-d H:i:s',strtotime($list['LeaveTime'])):Null;
            $list['ASRecvDate'] = isset($list['ASRecvDate'])?date('Y-m-d',strtotime($list['ASRecvDate'])):Null;
            $list['ASDelvDate'] = isset($list['ASDelvDate'])?date('Y-m-d',strtotime($list['ASDelvDate'])):Null;


            if($list['CustSignYn'] =="Y"){
                    $mt_id = $list['ASRecvNo'];
                    $dirname_year = substr($mt_id,0,4);
                    $dirname_month = substr($mt_id,4,2);
                    $dirname_defualt = substr($mt_id,0,6);
                    $fileurl =$config['HomeUrl'].$config['Recv']."/$dirname_year/$dirname_month/$mt_id/".$config['SignC'];
                    $list['SignUrl'] = $fileurl.$config['SignName'];





                    $this->DownloadSign($config['Recv'],$config['RecvFTP'],$list['ASRecvNo']);
                }else{
                    $list['SignUrl'] ='';
                }
                        $auth = BaseModel::getAuth('WEI_2100',$UserID);

            $res['auth'] = $auth;
            $res['data'] = $list;

            $where = array(
                'ASRecvNo' => $ASRecvNo
            );
            $ASNOList = RecvHandleModel::getAsHandleProc($where);

            $res['ASNOList'] = $ASNOList;
            return json($res);
        }
    }
     /**
     * 安装报告-签名下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadSign($fun,$funFTP,$ASRecvNo)
    {

            $config = config('web');
            // $fun = 'AssembleReport';
            // $w = array(
            //     'ASRecvNo'    => $ASRecvNo
            // );
            // $list = InstallationTrialModel::getAssmPhotoNm($w);
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
                $mt_id = $ASRecvNo;
                $filename = $config['SignName'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }
                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');

                    $urlname =  ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id".$config['Sign']."/".$config['SignName'];
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

    public function getASSave()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';

            $login_id = $this->getUserId();
            $users = UserModel::getUserDeptInfo($login_id)[0];
            $login_id = str_replace(' ','',$users['emp_code']);
            $jobno = TestationTrialModel::as_jobno($login_id);
            if(empty($jobno)){
                $jobnos = '';
            }else{
                $jobnos = $jobno['JobNo'];
            }
            if($param['OrderGubun'] != 1){


                $OrderSysRegYn = 'N';
                $UnRegOrderNo = $param['OrderNo'];
                $param['OrderNo'] = '';
                $OldDrawNo = '';
                $OldDrawAmd = '';
            }else{
                $OrderSysRegYn = 'Y';
                $UnRegOrderNo = '';

                $orders = RecvHandleModel::updataASOrder($param['OrderNo']);
                $OldDrawNo = $orders['DrawNo'];
                $OldDrawAmd = $orders['DrawAmd'];
            }


            $isUpdate = 0;
            $post_asid = $param['ASRecvNo'];
            if(empty($param['ASRecvNo'])){
                $asid = date('Ymd',intval(time()));
                $result_asid = RecvHandleModel::getLikeTASRecv($asid);

                //如果当月还没有组装号
                if(empty($result_asid))
                {
                    $post_asid = $asid.'0001';
                }
                else
                {
                    $result_asid = substr($result_asid['ASRecvNo'],8);
                    $asid .= $result_asid;
                    $post_asid = $asid +1;
                }
            }
            else
            {
                $isUpdate = 1;
            }

            if($isUpdate == 0){

                if($param['OrderGubun'] != 1){
                   $OrderCnt = '0';
                }else{
                    $OrderCnt = $param['OrderCnt']+1;


                }
            }else{
                    $OrderCnt = $param['OrderCnt'];
            }
            $res['isUpdate'] = $isUpdate;
            $res['OrderCnt'] = $OrderCnt;
            $res['post_asid'] = $post_asid;

            $ASRecvDate = strtotime(date('Y-m-d',strtotime($param['ASRecvDate'])));
            $timeAs = strtotime(date('Y-m-d'));
            if($ASRecvDate>$timeAs){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = "AS日期不得大于当天";

                return json($res);
            }


            $phone = '/^1[3456789]\d{9}$/ims';
            $email = '/^\S+@\S+\.\S+$/';
            $tel = '/^([0-9]{3,4}-)?[0-9]{7,8}$/';

            if($param['ASType'] == 'AS10020030'){
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
            }else{
                if(empty(trim($param['ASDelvDate']))){
                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "交货日期不能为空";
                    // $res['data'] = $post_asid;
                    return json($res);
                }
            }

            if($param['OrderGubun'] == 1){
                if(!isset($param['PProductCd'])){
                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "Product Name不能为空";
                    // $res['data'] = $post_asid;
                    return json($res);
                }
                if(empty($param['PProductCd'])){
                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "Product Name不能为空";
                    // $res['data'] = $post_asid;
                    return json($res);
                }
            }
            if($param['ASType'] != 'AS10020030'){
                if(!isset($param['PProductCd'])){
                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "Product Name不能为空";
                    // $res['data'] = $post_asid;
                    return json($res);
                }
                if(empty($param['PProductCd'])){
                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "Product Name不能为空";
                    // $res['data'] = $post_asid;
                    return json($res);
                }
            }

            $addlist = InstallationTrialModel::getAddressInfo($param['ArrivalLeaveNo']);
            if(!empty($addlist)){
                // $param['ArrivalTime'] = $addlist['ArrivalDate'];
                // $param['ArrivalLeaveNo'] = $addlist['ArrivalLeaveNo'];
                // $param['ArrivalLocationAddr'] = $addlist['LocationAddr'];
                // $param['ArrivalLat'] = $addlist['GpsLat'];
                // $param['ArrivalLng'] = $addlist['GpsLng'];
                $param['Arrivalphoto'] = $addlist['Arrivalphoto'];
            }

            $add = array(
                'OrderNo'           => $param['OrderNo'],
                'SpecNo'            => $param['SpecNo'],
                'SpecType'          => $param['SpecType'],
                'ASRecvNo'          => $param['ASRecvNo'],
                'OrderGubun'        => $param['OrderGubun'], //新旧区分
                'MarketCd'          => $param['MarketCd'],
                'PProductCd'        => $param['PProductCd'],
                'ASType'            => $param['ASType'],
                'ASRecvDate'        => date('Y-m-d',strtotime($param['ASRecvDate'])),
                'ASDelvDate'        => !empty($param['ASDelvDate'])?$param['ASDelvDate']:Null,
                'EmpId'             => $param['EmpId'],
                'DeptCd'            => $param['DeptCd'],
                'CustCd'            => $param['CustCd'],
                'Resin'             => $param['Resin'],
                'OCCPoint'          => $param['OCCpoint'],
                'ASBadType'         => $param['ASBadType'],
                'ASCauseDonor'      => $param['ASCauseDonor'],
                'DutyGubun'         => $param['DutyGubun'],
                'ASClass1'          => $param['ASClass1'],
                'ASClass2'          => $param['ASClass2'],
                'ASAreaGubun'       => $param['ASAreaGubun'],
                'ASArea'            => $param['ASArea'],

                'CustPrsn'          => $param['CustPrsn'],
                'CustTell'          => $param['CustTell'],
                'CustEmail'         => $param['CustEmail'],
                'OldDrawNo'         => $OldDrawNo,
                'OldDrawAmd'        => $OldDrawAmd,
                'ExpClss'           => $param['ExpClss'],
                'RefNo'             => $param['RefNo'],
                'GoodNm'            => $param['GoodNm'],     // 客户产品名称
                'JobNo'             => $jobnos,

                'SupplyScope'       => $param['SupplyScope'],
                'HRSystem'          => $param['HRSystem'],
                'ManifoldType'      => $param['ManifoldType'],
                'SystemSize'        => $param['SystemSize'],
                'SystemType'        => $param['SystemType'],
                'GateType'          => $param['GateType'],

                'TransYn'           => $param['TransYn'],
                'TransDeptCd'       => $param['TransDeptCd'],
    //            'CfmYn'             => $post_confirm,
    //            'AptYn'             => $post_apt,
                'AptEmpId'          => $login_id,
                'AptDate'           => date('Y-m-d H:i:s'),
                'ChargeYn'          => $param['ChargeYn'],
                'ItemReturnYn'      => $param['ItemReturnYn'],
    //            'ProductYn'         => $post_product,

                'ASStateRemark'     => $param['ASStateRemark'],
                'ASCauseRemark'     => $param['ASCauseRemark'],
                'ASSolve'           => $param['ASSolve'],
                'Remark'            => $param['Remark'],

                'RegEmpID'          => $login_id,
                'RegDate'           => date('Y-m-d H:i:s'),
                'UptEmpID'          => $login_id,
                'UptDate'           => date('Y-m-d H:i:s'),

                'OrderCnt'          => $OrderCnt,
                'SysRemark'         => 'mobile-info',
                'CustomerCd'        => $param['CustomerCd'],
                'MakerCd'           => $param['MakerCd'],
                'AgentCd'           => $param['AgentCd'],
                'OrderSysRegYn'     => $OrderSysRegYn,
                'UnRegOrderNo'      => $UnRegOrderNo,
                'GateQty'           => $param['GateQty'],

                'ResTest'           => $param['ResTest'],
                'ResTestDesc'       => $param['ResTestDesc'],
                'TempRiseTest'      => $param['TempRiseTest'],
                'TempRiseTestDesc'  => $param['TempRiseTestDesc'],
                'AccUseYn'          => $param['AccUseYn'],
                'AccUseDesc'        => $param['AccUseDesc'],
                'ArrivalTime'       => $param['ArrivalTime'],
                'LeaveTime'         => $param['LeaveTime'],
                'ArrivalLeaveNo'    => $param['ArrivalLeaveNo'],


                'ArrivalLocationAddr'      => $param['ArrivalLocationAddr'],
                'ArrivalLat'     => $param['ArrivalLat'],
                'ArrivalLng'     => $param['ArrivalLng'],
                'Arrivalphoto'     => $param['Arrivalphoto'],

                'LeaveLat'     => $param['LeaveLat'],
                'LeaveLng'     => $param['LeaveLng'],
                'LeaveLocationAddr' => $param['LeaveLocationAddr'],
                'Leavephoto'     => $param['Leavephoto'],
            );
            // $w = array(
            //     'ASRecvNo'  => '',
            //     'EmpId'     => 'app_admin'
            // );
            // Db::table("TASRecv00")->where($w)->delete();

            if($param['ASType'] != 'AS10020030'){
                unset($add['ArrivalTime']);
                unset($add['LeaveTime']);
            }

            if($isUpdate == 0){

                if(empty($param['ArrivalLeaveNo'])){
                    unset($add['ArrivalTime']);
                    unset($add['ArrivalLeaveNo']);
                    unset($add['ArrivalLat']);
                    unset($add['ArrivalLng']);
                    unset($add['ArrivalLocationAddr']);
                    unset($add['Arrivalphoto']);
                }else{

                    // $whereArr = array(

                    //     'ArrivalLeaveNo' => $param['ArrivalLeaveNo'],

                    //     'EmpId'     => $param['EmpId'],
                    // );

                    // $RecvList = RecvHandleModel::getTASRecv00New($whereArr);
                    // if(!empty($RecvList)){
                    //     if($RecvList['CustCd'] != $param['CustCd']){
                    //         $res['statusCode'] = 'N003';
                    //         $res['returnMsg'] = "同一个到达时间只能同一个客户\r\n请检查!!!";
                    //         return json($res);

                    //     }
                    // }

                }


                unset($add['LeaveTime']);
                unset($add['LeaveLat']);
                unset($add['LeaveLng']);
                unset($add['LeaveLocationAddr']);
                unset($add['Leavephoto']);
                $add['ASRecvNo'] = $post_asid;
                $add['facilityYn'] = 1;
                $ceshi = RecvHandleModel::AddASRecv($add);

                $res['ceshi'] = $add;
                $res['returnCode'] = 'Y001';
                $res['data'] = $post_asid;

            }
            else
            {
                $where = array(
                    'ASRecvNo' => $post_asid
                );
                unset($add['ASRecvNo']);

                if(empty($param['ArrivalLeaveNo'])){
                    unset($add['ArrivalTime']);
                    unset($add['ArrivalLeaveNo']);
                    unset($add['ArrivelLat']);
                    unset($add['ArrivalLng']);
                    unset($add['ArrivalLocationAddr']);
                    unset($add['Arrivalphoto']);
                }
                // unset($add['OldDrawNo']);
                // unset($add['OldDrawAmd']);
                $this->recall_array['data'] = $post_asid;

                $resASAA = RecvHandleModel::getTASRecv00($where);



                if($resASAA['ASType'] == "AS10020030" && $resASAA['facilityYn'] == 0){

                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "不能修改PC端录入客户维修报告为信息";
                    $res['data'] = $post_asid;
                    return json($res);
                }

                if(!empty($param['LeaveTime'])){


                    $juli1 = Apis::getDistance($param['ArrivalLat'],$param['ArrivalLng'],$param['LeaveLat'],$param['LeaveLng']);
                    if($juli1 > 1000){
                        $res['statusCode'] = 'N003';
                        $res['returnMsg'] = "到达距离与离开距离已超出范围";
                        $res['data'] = $post_asid;
                        return json($res);
                    }
                    if(strtotime($param['ArrivalTime'])>strtotime($param['LeaveTime'])){
                        $res['statusCode'] = 'N003';
                        $res['returnMsg'] = "到达时间->离开时间请检查先后顺序";
                        $res['data'] = $post_asid;
                        return json($res);
                    }

                }else{
                    unset($add['LeaveTime']);
                    unset($add['LeaveLat']);
                    unset($add['LeaveLng']);
                    unset($add['LeaveLocationAddr']);
                    unset($add['Leavephoto']);
                }

                $res['returnCode'] = 'Y003';

                RecvHandleModel::SaveASRecv($add,$where);
            }

            $res['isUpdate'] = $isUpdate;
            $res['data'] = $post_asid;
            return json($res);
        }
    }


    public function getOrderMinute()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';

            $langCode = $config[$param['LangID']];
            $param['UserID'] = $this->getUserId();
            $list = RecvHandleModel::getOrderMinute($param['OrderNo'],$langCode);


            $Order_count = RecvHandleModel::as_count($param['OrderNo']);

            $user = UserModel::getUserDeptInfo($param['UserID']);

            $Custes = RecvHandleModel::getCustTel($list['CustCd']);//客户电话

            $list['EmpId'] = $user[0]['emp_code'];
            $list['EmpNm'] = $user[0]['EmpNm'];
            $list['DeptCd'] = $user[0]['DeptCd'];
            $list['DeptNm'] = $user[0]['DeptNm'];
            $list['CustomerNm'] = isset($list['CustomerNm'])?$list['CustomerNm']:'';
            $list['CustomerNo'] = isset($list['CustomerNo'])?$list['CustomerNo']:'';
            $list['GateType'] = isset($list['GateType'])?$list['GateType']:'';
            $list['GateTypeNm'] = isset($list['GateTypeNm'])?$list['GateTypeNm']:'';
            $list['HRSystem'] = isset($list['HRSystem'])?$list['HRSystem']:'';
            $list['HRSystemNm'] = isset($list['HRSystemNm'])?$list['HRSystemNm']:'';

            $list['MakerCd'] = isset($list['MakerCd'])?$list['MakerCd']:'';
            $list['MakerNm'] = isset($list['MakerNm'])?$list['MakerNm']:'';
            $list['MakerNo'] = isset($list['MakerNo'])?$list['MakerNo']:'';
            $list['ManifoldType'] = isset($list['ManifoldType'])?$list['ManifoldType']:'';
            $list['ManifoldTypeNm'] = isset($list['ManifoldTypeNm'])?$list['ManifoldTypeNm']:'';
            $list['SupplyScope'] = isset($list['SupplyScope'])?$list['SupplyScope']:'';
            $list['SupplyScopeNm'] = isset($list['SupplyScopeNm'])?$list['SupplyScopeNm']:'';
            $list['SystemSize'] = isset($list['SystemSize'])?$list['SystemSize']:'';
            $list['SystemSizeNm'] = isset($list['SystemSizeNm'])?$list['SystemSizeNm']:'';

            $list['SystemType'] = isset($list['SystemType'])?$list['SystemType']:'';
            $list['SystemTypeNm'] = isset($list['SystemTypeNm'])?$list['SystemTypeNm']:'';
            $list['systemtype'] = isset($list['systemtype'])?$list['systemtype']:'';
            $list['systype'] = isset($list['systype'])?$list['systype']:'';
            $list['MarketCd'] = isset($list['MarketCd'])?$list['MarketCd']:'';



            if(!empty($Custes)){
                $list['CustPrsn'] = isset($Custes['CustEmpNm'])?$Custes['CustEmpNm']:'';
                $list['CustTell'] = isset($Custes['C_Tel'])?$Custes['C_Tel']:'';
                $list['CustEmail'] = isset($Custes['EmailId'])?$Custes['EmailId']:'';



            }else{
                $list['CustPrsn'] = '';
                $list['CustTell'] = '';
                $list['CustEmail'] = '';
            }


            $list['OrderCnt'] = $Order_count;

            $res['data'] = $list;

            return json($res);
        }
    }

    public function getUserInfo()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();

            $user = userUserModel::getUserDeptInfo($param['UserID']);
            $list = array();
            $list['EmpId'] = $user['emp_code'];
            $list['EmpNm'] = $user['EmpNm'];
            $list['DeptCd'] = $user['DeptCd'];
            $list['DeptNm'] = $user['DeptNm'];

            $res['data'] = $list;

            return json($res);
        }
    }
     /**
     * 添加AS申请-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function addRecvSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            // $loginID =  $param['UserID'];
            $param['UserID'] = $this->getUserId();
            $ASRecvNo = $param['NumberNo'];
            $where = array(
                'ASRecvNo'    => $param['NumberNo']
            );

            $nows = RecvHandleModel::as_sales($ASRecvNo);
            $count = count($nows);
            $w = array(
                'ASRecvNo'    => $param['NumberNo'],
                'SaleEmpID'     => $param['EmpID']
            );
            $user = RecvHandleModel::getSalesE($w);
            if($user){
                $res['statusCode'] = '104';
                $res['statusMsg'] = '同行人员已存在';
                return json($res);
            }
            $count += 1;
             $data = array(
                'ASRecvNo'    => $ASRecvNo,
                'Seq'           => '0'.$count,
                'SaleEmpID'     => $param['EmpID'],
                'RegEmpID'      => $param['UserID'],
                'RegDate'       => date('Y-m-d H:i:s'),
                'UptEmpID'      => $param['UserID'],
                'UptDate'       => date('Y-m-d H:i:s'),
            );

            RecvHandleModel::addRecvSales($data);

            $list = RecvHandleModel::as_sales($ASRecvNo);
            $res['count'] = $count;
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
    public function DeleteRecvSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $where = array(
                'ASRecvNo'    => $param['NumberNo'],
                'Seq'           => $param['Seq']
            );
            $dele = RecvHandleModel::DeleteRecvSales($where);


            $list = RecvHandleModel::as_sales($param['NumberNo']);
            $w = array(
                'ASRecvNo'    => $param['NumberNo']
            );
            RecvHandleModel::DeleteRecvSales($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'ASRecvNo'    => $param['NumberNo'],
                    'Seq'           => '0'.$count,
                    'SaleEmpID'     => $value['SaleEmpID'],
                    'RegEmpID'      => $param['UserID'],
                    'RegDate'       => date('Y-m-d H:i:s'),
                    'UptEmpID'      => $param['UserID'],
                    'UptDate'       => date('Y-m-d H:i:s'),
                );
                RecvHandleModel::addRecvSales($data);
            }
            $list = RecvHandleModel::as_sales($param['NumberNo']);


            $photoCount = count($list);
            $res['photoCount'] = $photoCount;
            $res['data'] = $list;
            return json($res);
        }
    }


















    /**
     * 试模报告报告-图片列表
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function getRecvPhotoNm()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $w = array(
                    'ASRecvNo'    => $param['ASRecvNo']
                );
            $list = RecvHandleModel::getASPhoto($w);
            $config = config('web');
            $this->DownloadRecvPhoto($config['Recv'],$config['RecvFTP'],$list);



            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['ASRecvNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Recv']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASRecvNo' => $value['ASRecvNo'],
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
    public function addRecvPhotos()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $date = date('Ymdhis',time());
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $mt_id = $param['ASRecvNo'];
            $UserID = $param['UserID'];

            // $addressL = Apis::geocoder($param['Lng'],$param['Lat'],$config['addres']);
            $address = '';//$addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;

            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $fun = $config['Recv'];

            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');

            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);


            $remoteFile = $config['RecvFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;
            $res['file'] = $remoteFile;
            $res['file2'] = $localFile;

            FtpUtil::ftp_photo($mt_id,$config['RecvFTP']);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
            }

            $w = array(
                'ASRecvNo'    => $mt_id
            );

            $list = RecvHandleModel::getASPhoto($w);

            $counTS = count($list);

            $res['listdate'] = $param;

            $counTS = $counTS + $param['count'] + 1;
            $seq = (int)$counTS>=10?$counTS:'0'.$counTS;
            $data = array(
                'ASRecvNo'    => $mt_id,
                'Seq' => $seq,
                'FileNm'    => $fileNm,
                'RegEmpID'      => $UserID,
                'RegDate'       => date('Y-m-d H:i:s'),
                'UptEmpID'      => $UserID,
                'UptDate'       => date('Y-m-d H:i:s'),
                'FTP_UseYn' => 'Y',
                'Lat'       => $param['Lat'],
                'Lng'       => $param['Lng'],
                'LocationAddr'       => $address,

            );

            $where = array(
                    'ASRecvNo'    => $mt_id,
                    'Seq' => $data['Seq']
                );

            $PhotoL = RecvHandleModel::getASPhotoF($where);
            $res['PhotoL'] = $PhotoL;
            if(empty($PhotoL)){

                if((int)$data['Seq']<=2){
                    $addressL = Apis::geocoder($param['Lng'],$param['Lat'],$config['addres']);
                    if($addressL->status == '0'){
                        $address = $addressL->result->formatted_addresses->standard_address;
                    }else{
                        $address = '';
                    }


                    $data['LocationAddr'] = $address;
                }
                RecvHandleModel::addRecvPhoto($data);
            }else{

                $count = '';
                for ($i = count($PhotoL); $i >= 1; $i--) {
                    $sq = $i>=10?$i:'0'.$i;
                    $where = array(
                        'ASRecvNo'    => $mt_id,
                        'Seq' => $sq
                    );
                    $Photo = RecvHandleModel::getASPhotoF($where);
                    if(empty($Photo)){
                        $count = $i;
                    }
                }
                $seqD = (int)$count>=10?$count:'0'.$count;
                $data['Seq'] = $seqD;
                RecvHandleModel::addRecvPhoto($data);
            }



            $lists = RecvHandleModel::getASPhoto($w);

            $AssmPhoto = array();
            foreach ($lists as $key => $value) {
                $mt_id = $value['ASRecvNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Recv']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASRecvNo' => $value['ASRecvNo'],
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
     * 安装报告-删除照片
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function DeleteRecvPhone()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $where = array(
                'ASRecvNo'    => $param['ASRecvNo'],
                'FileNm'           => $param['name']
            );
            RecvHandleModel::DeleteRecvPhoto($where);
            $w = array(
                'ASRecvNo'    => $param['ASRecvNo']
            );
            $list = RecvHandleModel::getASPhoto($w);

            RecvHandleModel::DeleteRecvPhoto($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'ASRecvNo' => $value['ASRecvNo'],
                    'Seq' => '0'.$count,
                    'FileNm'    => $value['FileNm'],
                    'RegEmpID'      => $param['UserID'],
                    'RegDate'       => date('Y-m-d H:i:s'),
                    'UptEmpID'      => $param['UserID'],
                    'UptDate'       => date('Y-m-d H:i:s'),
                    'FTP_UseYn' => 'Y',
                    'Lat'       => $value['Lat'],
                    'Lng'       => $value['Lng'],
                    'LocationAddr'       => $value['LocationAddr'],
                );
                RecvHandleModel::addRecvPhoto($data);
            }
            $list = RecvHandleModel::getASPhoto($w);
            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['ASRecvNo'];

                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Recv']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASRecvNo' => $value['ASRecvNo'],
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
     * 试模报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadRecvPhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = $v['ASRecvNo'];
                $filename = $v['FileNm'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }


                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');
                    if ($v['FTP_UseYn'] == 'Y') {

                        $urlname =  ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk";
                        $file_size = ftp_size($conn, $urlname);
                        if($file_size > 0){
                            if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                                return false;
                            }
                        }
                    }
                }
            }
            ftp_close($conn);
            return true;
    }


        /**
     * AS申请品目
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function ASMinuteTable()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $ASRecvNo = $param['ASRecvNo'];
            $TableList = RecvHandleModel::ASMinuteTable($ASRecvNo);

            $res['data'] = $TableList;

            return json($res);
        }
    }
    /**
     * AS申请品目
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function ASUnit()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $ASUnit = RecvHandleModel::ASUnit();

            $res['data'] = $ASUnit;

            return json($res);
        }
    }

     /**
     * AS申请品目列表
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function getItemList()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $ASUnit = RecvHandleModel::getItemList($param['ItemNo'],$param['ItemNm'],$param['count']);

            $res['data'] = $ASUnit;

            return json($res);
        }
    }

    /**
     * AS申请品目 保存添加
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function saveAsItem()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $ASRecvSerl = $param['ASRecvSerl'];
            if(empty($param['ASRecvSerl'])){
                $where = array(
                    'ASRecvNo' => $param['ASRecvNo'],
                    'Sort' => $param['Sort']
                );
                $checkSort = RecvHandleModel::getASItem($where);

                if(!empty($checkSort['Sort'])){
                    return json($res);
                }
                $w = array(
                    'ASRecvNo' => $param['ASRecvNo'],

                );
                $ASRecv = RecvHandleModel::getASItem($w);
                if(empty($ASRecv))
                {
                    $ASRecvSerl = '0001';
                }
                else
                {
                    if($ASRecv['ASRecvSerl'] >= 9)
                    {
                        $ASRecvSerl = '00'.($ASRecv['ASRecvSerl'] + 1);
                    }
                    else
                    {
                        $ASRecvSerl = '000'.($ASRecv['ASRecvSerl'] + 1);
                    }
                }
                $add = array(
                    'ASRecvNo'    => $param['ASRecvNo'],
                    'ASRecvSerl'  => $ASRecvSerl,
                    'Sort'        => $param['Sort'],
                    'SpareYn'     => $param['SpareYn'],
                    'ItemCd'      => $param['ItemCd'],
                    'UnitCd'      => $param['UnitCd'],
                    'Qty'         => $param['Qty'],
                    'NextQty'     => $param['NextQty'],
                    'StopQty'     => $param['StopQty'],
                    'ChargeYn'    => $param['ChargeYn'],
                    'Remark'      => $param['Remark'],
                );
                RecvHandleModel::addRecvItem($add);
                $update = false;
            }else{
                $save = array(
                    'ASRecvNo'    => $param['ASRecvNo'],
                    'Sort'        => $param['Sort'],
                    'SpareYn'     => $param['SpareYn'],
                    'ItemCd'      => $param['ItemCd'],
                    'UnitCd'      => $param['UnitCd'],
                    'Qty'         => $param['Qty'],
                    'NextQty'     => $param['NextQty'],
                    'StopQty'     => $param['StopQty'],
                    'ChargeYn'    => $param['ChargeYn'],
                    'Remark'      => $param['Remark'],
                );
                $where = array(
                    'ASRecvNo' => $param['ASRecvNo'],
                    'ASRecvSerl' => $param['ASRecvSerl'],
                );
                RecvHandleModel::SaveASItem($save,$where);
                $update = true;
            }

            $list = RecvHandleModel::getASItemS($param['ASRecvNo'],$ASRecvSerl);
            $res['update'] = $update;
            $res['data'] = $list;
            return json($res);
        }
    }
    /**
     * AS申请品目列表
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function DeleteRecvItem()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $where = array(
                'ASRecvNo'  => $param['ASRecvNo'],
                'ASRecvSerl'    => $param['ASRecvSerl']
            );

            RecvHandleModel::DeleteRecvItem($where);

            $list = RecvHandleModel::ASMinuteTable($param['ASRecvNo']);
            $w = array(
                'ASRecvNo'  => $param['ASRecvNo'],
            );
            RecvHandleModel::DeleteRecvItem($w);
            foreach ($list as $key => $v) {
                $Sort = ($key+1)*10;
                if($key >= 9)
                {
                    $ASRecvSerl = '00'.($key + 1);

                }
                else
                {
                    $ASRecvSerl = '000'.($key + 1);
                }

                $data = array(
                    'ASRecvNo'      => $v['ASRecvNo'],
                    'ASRecvSerl' => $ASRecvSerl,
                    'Sort'      => '0'.$Sort,
                    'SpareYn'      => $v['SpareYn'],
                    'ItemCd'      => $v['ItemCd'],
                    'UnitCd'      => $v['UnitCd'],
                    'Qty'         => $v['Qty'],
                    'NextQty'     => $v['NextQty'],
                    'StopQty'     => $v['StopQty'],
                    'ChargeYn'    => $v['ChargeYn'],
                    'Remark'      => $v['Remark']

                );
                RecvHandleModel::addRecvItem($data);
            }
            $lists = RecvHandleModel::ASMinuteTable($param['ASRecvNo']);

            $res['data'] = $lists;

            return json($res);
        }
    }

     /**
     * 查看AS申请品目
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function getItem()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $where = array(
                'ASRecvNo' => $param['ASRecvNo'],
                'ASRecvSerl'    => $param['ASRecvSerl']

            );

            $list = RecvHandleModel::getASItemS($param['ASRecvNo'],$param['ASRecvSerl']);

            $res['data'] = $list;

            return json($res);
        }
    }
    /**
     * 查看技术规范
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function getSpecList()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';



            $list = RecvHandleModel::getSpecList($param['SpecNo'],$param['CustNm'],$param['count']);

            foreach ($list as $key => $value) {
                $SpecTypeNm = '';
                switch ($value['SpecType']){
                    case '1':
                        $SpecTypeNm = 'Apprpval';
                        break;
                    case '2':
                        $SpecTypeNm = 'Manufacture';
                        break;
                    case '3':
                        $SpecTypeNm = 'App^Manu';
                        break;
                }

                $list[$key]['SpecTypeNm'] = $SpecTypeNm;
                $ExpClssNm = '';
                switch ($value['ExpClss']){
                    case '4':
                        $ExpClssNm = '国外';
                        break;
                    case '1':
                        $ExpClssNm = '国内';
                        break;
                }
                $list[$key]['ExpClssNm'] = $ExpClssNm;
            }

            $res['data'] = $list;

            return json($res);
        }
    }

    /**
     * AS接受 客户签名
     * ²éÑ¯ TASRecv00 ±íÖÐÊÇ·ñÓÐ·ûºÏÌõ¼þµÄ¼ÇÂ¼
     * @param array $data ÐÞ¸ÄÊý¾Ý
     * @return bool
     */
    public function RecvSignImage()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');




            $file = request()->file('sign');
            $mt_id = $param['ASRecvNo'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $fun = $config['Recv'];

            $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
            if (!is_dir($yeardir)) {
                mkdir(iconv("UTF-8", "GBK", $yeardir), 0775, true);
            }
            $dir = "$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];

            $fileNm = $config['SignName'];
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);
            $res['data']['savename'] = $savename;

            $where = array(
                'ASRecvNo'    => $param['ASRecvNo']
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

            $save_path = $config['localFile']."$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign']."/".$config['SignName']; // ÄãÒª±£´æÍ¼Æ¬µÄÂ·¾¶

            if(is_file($save_path)){
                RecvHandleModel::SaveASRecv($data,$where);

                $fileNm = $config['SignName'];
                $fileurl = $config['HomeUrl']."$fun/$dirname_year/$dirname_month/$mt_id/";
                $SignUrl = $fileurl.$config['SignC'].$fileNm;

                $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
                $remoteFile = $config['RecvFTP']."/$dirname_year/$dirname_month/$mt_id".$config['Sign']."/".$fileNm;
                $localFile = $config['localFile'].$dir.$config['Sign'].'/'.$fileNm;
                FtpUtil::ftp_photo($mt_id,$config['RecvFTP'],$config['Sign']);
                if (!FtpUtil::upload($localFile, $remoteFile)) {
                    $res['statusCode'] = '105';
                    $res['msg'] ="文件上传失败";
                }
                $res['fileurl'] = $fileurl.$config['Sign'];
                $res['data'] = $SignUrl;
                $res['CustSignYn'] = 'Y';
                //  $res['data'] = $data;
                // $res['save_path'] = $save_path;
                // return json($res);
            }else{
                $res['statusCode'] = '201';
            }



            return json($res);
        }
    }
      /**
     * AS接受 PDF生成
     * ²éÑ¯ TASRecv00 ±íÖÐÊÇ·ñÓÐ·ûºÏÌõ¼þµÄ¼ÇÂ¼
     * @param array $data ÐÞ¸ÄÊý¾Ý
     * @return bool
     */
    public function RecvPDF(){
        // header('Content-Type: text/html; charset=UTF-8');
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $langCode = $config[$param['LangID']];
            $ASRecvNo = $param['ASRecvNo'];
            $param['UserID'] = $this->getUserId();
            $data = RecvHandleModel::getASMinute($ASRecvNo,$langCode);

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];

            $EmpId = str_replace(' ','',$users['emp_code']);

            if(!empty($EmpId)){
                $mulu = $EmpId;
            }else{
                $mulu = $param['UserID'];
            }

            if($data['CustSignYn'] != 'Y' ){
                $res['statusCode'] = '201';
                $res['returnMsg'] = '未签字不发邮箱';
                return json($res);
            }

            if($data['SendEmailYn'] == 'Y' ){
                $res['statusCode'] = '202';
                $res['returnMsg'] = '邮件已发送';
                return json($res);
            }
            $fun = $config['Recv'];
            $dirname_year = substr($ASRecvNo,0,4);
            $dirname_month = substr($ASRecvNo,4,2);
            $dirname_defualt = substr($ASRecvNo,0,6);

            $yeardir = "static/$fun/$dirname_year/$dirname_month/$ASRecvNo/$mulu";
            if (!is_dir($yeardir)) {
                mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
            }
            $file = "static/$fun/$dirname_year/$dirname_month/$ASRecvNo/$mulu/".$ASRecvNo.'.pdf';
            if(file_exists($file)){
                $res['statusCode'] = '203';
                $res['returnMsg'] = '文件已存在';
                return json($res);
            }
            $EmptysH = UserModel::getEmpIDHP($EmpId);
            $data['HP'] = $EmptysH['HP'];
            $data['EmailID'] = $EmptysH['EmailID'];
            $Area = UserModel::getCustArea($data['CustCd']);
            if(empty(trim($Area['Area']))){
                 $data['Area'] = '';
            }else{
                $Area1 = UserModel::getCustAreaNm($Area['Area']);
                $langCode = $config['CHN'];
                $Area2 = UserModel::getCustAreaTrNm($Area['Area'],$langCode);
                if(empty(trim($Area1['MinorNm']))){
                    $data['Area'] = empty(trim($Area2['TransNm']))?'':$Area2['TransNm'];
                }else{
                    $data['Area'] = $Area1['MinorNm'];
                }

            }

            if($data['CustSignYn'] == 'Y'){
                $asNo = $data['ASRecvNo'];

                $data['CustSign'] =  "static/$fun/$dirname_year/$dirname_month/$ASRecvNo".$config['Sign']."/".$config['SignName'];
            }else{
                $data['CustSign'] = '';
            }


            if($data['ChargeYn'] == 'Y'){
                $ChargeYn = '收费';
            }else{
                $ChargeYn = '免费';
            }

            if($data['ResTest'] == 'Y'){
                $ResTest = 'OK';
            }else{
                $ResTest = 'NG';
            }
            if($data['TempRiseTest'] == 'Y'){
                 $TempRiseTest = 'OK';
            }else{
                $TempRiseTest = 'NG';
            }
            if($data['AccUseYn'] == 'Y'){
                 $AccUseYn = '有';
            }else{
                $AccUseYn = '无';
            }


            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);

            $pdf->AddPage();

                $data['ASRecvDate'] = date("Y-m-d",strtotime($data['ASRecvDate']));
                $data['ArrivalTime'] = date("Y-m-d H:i",strtotime($data['ArrivalTime']));
                $data['LeaveTime'] = date("Y-m-d H:i",strtotime($data['LeaveTime']));
                // <img style="width: 150px;margin-top: 20px;" src="/image/login_logo2.png">
                $html = '<div style="color:#000;font-size:12px;">
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td colspan="4"><img style="width: 150px;" src="static/image/pdf_logo.png"></td>
                            <td style="height: 20px;line-height:20px;font-family: 宋体;font-weight:900;" colspan="8" align="left" style="text-align:left;">
                                <b style="color:#000;font-size:12px;">柳道万和（苏州）热流道系统有限公司</b><br />
                                <b style="color:#000;font-size:12px;">YUDO(SUZHOU)HOT RUNNER SYSTEMS CO.,LTD</b><br />
                                <b style="color:#000;font-size:20px;">客户维修报告</b>
                                </td>
                        </tr>
                    </table>
                </div>
                <style type="text/css">td{height: 25px;line-height:25px; left:10px;}</style>
                <table border="1" cellspacing="0" cellpadding="0">

                    <tr>
                        <td align="center" colspan="2"><b>维修日期</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ASRecvDate'].'</b></td>
                        <td align="center" colspan="2"><b>客户名称</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['CustNm'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>订单号码</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['OrderNo'].'</b></td>
                        <td align="center"><b>模号</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['RefNo'].'</b></td>
                        <td align="center"><b>区域</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['Area'].'</b></td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>塑胶</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['Resin'].'</b></td>
                        <td align="center" colspan="2"><b>系统类型</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['GoodNm'].'</b></td>
                    </tr>

                    <tr>
                        <td  colspan="12" align="center" style="height: 200px;"><br /><br />&nbsp;<img style="height: 180px;" src="static/image/PDF-AS.png">&nbsp;</td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>HRS</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['HRSystemNm'].'</b></td>
                        <td align="center" colspan="2"><b>系统大小</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['SystemSizeNm'].'</b></td>
                        <td align="center" colspan="2"><b>SystemType</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['SystemTypeNm'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>Gate类型</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['GateTypeNm'].'</b></td>
                        <td align="center" colspan="2"><b>Gate数量</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['GateQty'].'</b></td>
                        <td align="center" colspan="2"><b>是否收费</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$ChargeYn.'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>电阻测试</b></td>
                        <td align="center" colspan="1"><b>'.$ResTest.'</b></td>
                        <td align="center" colspan="3"><b>'.$data['ResTestDesc'].'</b></td>
                        <td align="center" colspan="2"><b>升温测试</b></td>
                        <td align="center" colspan="1"><b>'.$TempRiseTest.'</b></td>
                        <td align="center" colspan="3"><b>'.$data['TempRiseTestDesc'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>有无配件</b></td>
                        <td align="center" colspan="2"><b>'.$AccUseYn.'</b></td>
                        <td align="center" colspan="8"><b>'.$data['AccUseDesc'].'</b></td>
                    </tr>
                     <tr>
                        <td align="center" colspan="2"><b>AS现象</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ASClass1Nm'].'</b></td>
                        <td align="center" colspan="2"><b>AS原因-种类</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ASClass2Nm'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>不良现象描述</b></td>
                        <td align="left" colspan="10" ><b>&nbsp;&nbsp;'.$data['ASStateRemark'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>原因分析</b></td>
                        <td align="left" colspan="10"><b>&nbsp;&nbsp;'.$data['ASCauseRemark'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>建议/解决方案</b></td>
                        <td align="left" colspan="10"><b>&nbsp;&nbsp;'.$data['ASSolve'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>服务地点</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ASArea'].'</b></td>
                        <td align="center" colspan="2"><b>联系人</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['CustPrsn'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>到达时间</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ArrivalTime'].'</b></td>
                        <td align="center" colspan="2"><b>离开时间</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['LeaveTime'].'</b></td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>维修人员</b></td>
                        <td align="left" colspan="4" ><b>&nbsp;&nbsp;'.$data['EmpNm'].'</b></td>
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
                </table>';

                ob_end_clean();

                $html = mb_convert_encoding($html, 'GBK', 'UTF-8');
                $html = mb_convert_encoding($html, 'UTF-8', 'GBK');

                $pdf->SetFont('cid0cs', '', 10);
                // output the HTML content
                $pdf->writeHTML($html, true, false, true, false, '');


                // $res = $pdf->Output('example_039.pdf', 'I');
                $resA = $pdf->Output($data['ASRecvNo'].'.pdf', 'S');

                $list = file_put_contents($file, $resA);

                $res['data'] = $data;

                return json($res);


        }
    }

    /**
     * 有无接受权限
     * @param array $data ÐÞ¸ÄÊý¾Ý
     * @return bool
     */
    public function UpdateDw(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $auth = BaseModel::getAuth('AS_100_1100',$param['UserID']);

            $res['data'] = $auth;


            if($auth == $config['AUTH_A']){
                $res['data'] = true;
            }else{
                $res['data'] = false;
            }
            return json($res);
        }
    }

    public function ASTDEDwReq(){

        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $result = RecvHandleModel::ASTDEDwReq($param['ASRecvNo']);

            $res['data'] = $result;




            $TDEDwReg00 = RecvHandleModel::TDEDwReg00($param['OldDrawNo']);

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];

            $EmpId = str_replace(' ','',$users['emp_code']);
            // $TSASpec00 = $this->As10_model->find()->as_TSASpec00($SpecNo);
            $Time = date('Ymd',intval(time()));

            if($Time >= 20230103){
                $asid = date('Ymd',intval(time()));
            }else{
                $asid = date('Ym',intval(time()));
            }

            // $sql = "select top 1 ReqNo from TDEDwReq00 With ( Nolock ) where ReqNo LIKE '$asid%%' order by ReqNo desc";
            $result_asid = RecvHandleModel::ASTDEDwReg00($asid);
            //如果当月还没有组装号
            if(empty($result_asid))
            {
                $post_asid = $asid.'0001';
            }
            else
            {
                $result_asid = substr($result_asid['ReqNo'],8);
                $asid .= $result_asid;
                $post_asid = $asid +1;
            }

            $lists = RecvHandleModel::TDEDwReq00($param['ASRecvNo']);

            if(!$lists){
                $lists = array(
                    'ReqNo'             => '',
                    'ReqDate'           => $result['ReqDate'],
                    'ReqDeptCd'         => $result['ReqDeptCd'],
                    'ReqDeptNm'         => $result['ReqDeptNm'],
                    'ReqEmpID'          => $result['ReqEmpID'],
                    'ReqEmpNm'          => $result['ReqEmpNm'],
                    'SourceType'        => $result['SourceType'],
                    'SourceTypeNm'      => '售后服务',
                    'SourceNo'          => $result['SourceNo'],
                    'ExpClss'           => $result['ExpClss'],
                    'SpecType'          => '',
                    'OrderNo'           => '',
                    'OldDrawNo'         => $result['OldDrawNo'],
                    'CustCd'            => $result['CustCd'],
                    'CustNm'            => $result['CustNm'],
                    'CustNo'            => $result['CustNo'],
                    'DwReqDate'         => $result['DwReqDate'],//要求出图日期
                    'RevYn'             => 'N',
                    'RevCnt'            => $result['RevCnt'],
                    'RevNo'             =>  '',
                    // 'AptYn'             => '0',
                    // 'AptEmpID'          => $AptEmpID,
                    // 'AptEmpNm'          => $AptEmpNm,
                    // 'AptDate'           => date('Y-m-d H:i:s',time()),
                    'Status'            => '0',//0:委托 1：未出图 2：出图
                    // 'DwPlanDate'        => '',
                    'DCCd'              => $TDEDwReg00['DCCd'],
                    'DCNm'              => $TDEDwReg00['DCNm'],
                    'DwEmpID'           => $TDEDwReg00['DwEmpID'],
                    'DwEmpNm'           => $TDEDwReg00['DwEmpNm'],
                    'DwMEmpID'          => '',
                    'DwMEmpNm'          => '',
                    'DrawNo'            => '',//图纸编码
                    'DrawAmd'           => '',

                    'NozzleType'        => $result['NozzleType'],
                    'NozzleType2'       => '',
                    'SysClass1'         => $result['SysClass1'],
                    // 'BackYn'            => $TSASpec00['BackYn'],
                    'BackEmpID'         => '',
                    'BackDate'          => '',
                    'BackReason'        => '',
                    'BackRedo'          => '',
                    'StopYn'            => 'N',
                    // 'StopEmpID'         => $TDEDwReg00->StopEmpID,
                    // 'StopDate'          => $TDEDwReg00->StopDate,
                    // 'StopCancelDate'    => $TDEDwReg00->StopCancelDate,
                    'StopReason'        => '',
                    'SupplyScope'       => $result['SupplyScope'],
                    'Remark'            => '',//----------------------
                    'RegEmpID'          => '',
                    'RegDate'           => '',
                    'UptEmpID'          => '',
                    'UptEmpNm'          => '',
                    'UptDate'           => '',
                    'Ref_SendDate'      => '',//----------------------
                    'Ref_Title'         => '',//----------------------
                    'DrawDate'          => '',
                    // 'Design_Remark'     => '',
                    // 'Design_Update'     => '',
                    // 'Design_UptEmpID'   => '',
                    // 'DeleteYn'          => 'N',
                    // 'DeleteRemark'      => '',
                );
            }

            if($lists['Status'] == '0'){
                    $lists['StatusYn'] = '委托';
                }else if($lists['Status'] == '1'){
                    $lists['StatusYn'] = '未出图';
                }else if($lists['Status'] == '2'){
                    $lists['StatusYn'] = '出图';
                }
                if($lists['SourceType'] == 'A'){
                    $lists['SourceTypeNm'] = '售后服务';
                }else if($lists['SourceType'] == 'O'){
                    $lists['SourceTypeNm'] = '订单';
                }else if($lists['SourceType'] == 'S'){
                    $lists['SourceTypeNm'] = '技术规范';
                }
            $lists['ReqDate'] = date("Y-m-d",strtotime($lists['ReqDate']));

            $res['data'] = $lists;
            $res['TDEDwReg00'] = $TDEDwReg00;
            $res['result'] = $result;

            return json($res);
        }
    }



    public function SaveDw(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $isUpdate = 0;

            //判断生成asid还是更新asid
            if($param['ReqNo'] == 'noid'){

                $asid = date('Ymd',intval(time()));


                $result_asid = RecvHandleModel::ASTDEDwReg00($asid);

                if(empty($result_asid))
                {
                    $post_ReqNo = $asid.'0001';
                }
                else
                {
                    $result_asid = substr($result_asid['ReqNo'],8);
                    $asid .= $result_asid;
                    $post_ReqNo = $asid +1;
                }
            }
            else
            {
                $post_ReqNo = $param['ReqNo'];
                $isUpdate = 1;
            }

            $add = array(
                'ReqNo'             => $post_ReqNo,
                'ReqDate'           => $param['ReqDate'],
                'ReqDeptCd'         => $param['ReqDeptCd'],
                'ReqEmpID'          => $param['ReqEmpID'],
                'SourceType'        => $param['SourceType'], //新旧区分
                'SourceNo'          => $param['SourceNo'],
                'ExpClss'           => $param['ExpClss'],
                'SpecType'          => $param['SpecType'],
                'OldDrawNo'         => $param['OldDrawNo'],
                'CustCd'            => $param['CustCd'],
                'DwReqDate'         => $param['DwReqDate'],
                'RevYn'             => $param['RevYn'],
                'RevCnt'            => $param['RevCnt'],
                'RevNo'             => $param['RevNo'],

                'Status'            => $param['Status'],
                'DCCd'              => $param['DCCd'],
                'DwEmpID'           => $param['DwEmpID'],
                'DwMEmpID'          => $param['DwMEmpID'],
                'NozzleType'        => $param['NozzleType'],
                'NozzleType2'       => $param['NozzleType2'],
                'SysClass1'         => $param['SysClass1'],

                'SupplyScope'       => $param['SupplyScope'],
                'Remark'            => $param['Remark'],


            );


            $res['isUpdate'] = $isUpdate;


            // $this->load->model('As20_model');

            if($isUpdate == 0){

                    $add['RegEmpID']          = $param['UserID'];
                    $add['RegDate']           = date('Y-m-d H:i:s');
                    $add['UptEmpID']          = $param['UserID'];
                    $add['UptDate']           = date('Y-m-d H:i:s');


                RecvHandleModel::addRecvDwReq($add);
                // $this->jlamp_comm->jsonEncEnd($res);
                $result_asid = RecvHandleModel::ASTDEDwReg($post_ReqNo);
                $res['data'] = $post_ReqNo;

                if(!$result_asid){
                    $res['statusCode'] = '201';
                    $res['msg'] = "数据保存错误，请查看是否存在特殊字符以及字段未添加";
                    return json($res);
                }
            }
            else
            {

                if($param['UserID']){
                    $add['UptEmpID']          = $param['UserID'];
                    $add['UptDate']           = date('Y-m-d H:i:s');
                }
                $where = array(
                    'ReqNo' => $post_ReqNo
                );
                unset($add['ReqNo']);
                $addres = RecvHandleModel::SaveASDwReq($add,$where);

                $res['data'] = $post_ReqNo;
            }
            return json($res);
        }
    }


    /**
     * AS接受 有无AS接受
     * @param array $data ÐÞ¸ÄÊý¾Ý
     * @return bool
     */
    public function UpdateAS(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            $login_id = str_replace(' ','',$users['emp_code']);

            $get_asid = $param['ASRecvNo'];


            $DB = 'sqlsrv';
            $spName = 'dbo.P_SASRecvCfm_MM';
            $input = '@p_WorkingTag =?,@p_ASRecvNo =?,@p_CfmEmpId=?';
            $output = ['AA', $get_asid,$login_id];

            $list = BaseModel::execSp($spName,$input,$output,$DB);

            if($list[0]['Status'] == 'P_SASRecvCfm_006'){
                $res['statusCode'] = '201';

                $res['msg'] = '已做接收处理的数据，请确认！';
            }
            if($list[0]['Status'] == 'P_SASRecvCfm_007'){
                $res['statusCode'] = '202';

                $res['msg'] = '接收时发生错误，请确认！';
            }


            $res['data'] = $get_asid;

            return json($res);
        }
    }

    /**
     * AS接受 提交OA审核
     * @param array $data ÐÞ¸ÄÊý¾Ý
     * @return bool
     */

    public function subAdjudication(){

        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $langCode = $config[$param['LangID']];
            $post_asid = $param['ASRecvNo'];
            $data = RecvHandleModel::getASMinute($post_asid,$langCode);

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            $asUserId = str_replace(' ','',$users['emp_code']);

            $login_id = $param['UserID'];
            if(empty($post_asid) || empty($login_id)){
                $res['statusCode'] = '201';
                $res['returnMsg'] = 'as号/职员信息不存在';
                return json($res);

            }

            if($data['ASType'] == 'AS10020030'){

                $juli1 = Apis::getDistance($data['ArrivalLat'],$data['ArrivalLng'],$data['LeaveLat'],$data['LeaveLng']);
                if($juli1 > 1000){
                    $res['statusCode'] = '202';
                    $res['returnMsg'] = '到达距离与离开距离已超出范围';
                    $res['ArrivalLat'] = $data['ArrivalLat'];
                    $res['ArrivalLng'] = $data['ArrivalLng'];
                    $res['LeaveLat'] = $data['LeaveLat'];
                    $res['LeaveLng'] = $data['LeaveLng'];
                    $res['juli1'] = $juli1;

                    return json($res);

                }
                $juli2 = Apis::getDistance($data['CustGpsLat'],$data['CustGpsLng'],$data['LeaveLat'],$data['LeaveLng']);
                $juli3 = Apis::getDistance($data['ArrivalLat'],$data['ArrivalLng'],$data['CustGpsLat'],$data['CustGpsLng']);

                // if($juli2 > 1000){
                //     $res['statusCode'] = '203';
                //     $res['returnMsg'] = '到达距离与客户签字距离已超出范围';
                //     return json($res);

                // }
                // if($juli3 > 1000){
                //     $res['statusCode'] = '204';
                //     $res['returnMsg'] = '离开距离与客户签字距离已超出范围';
                //     return json($res);
                // }

                if(strtotime($data['LeaveTime']) - strtotime($data['ArrivalTime']) > 86400 ){
                    $res['statusCode'] = '204';
                    $res['returnMsg'] = '到达时间与离开时间不得超过24小时';
                    return json($res);
                }

                if(strtotime($data['ArrivalTime'])>strtotime($data['LeaveTime'])){
                    $res['statusCode'] = '204';
                    $res['returnMsg'] = '到达时间->客户签字时间->离开时间请检查先后顺序';
                    return json($res);
                }

            }else{
                $TableList = RecvHandleModel::ASMinuteTable($data['ASRecvNo']);

                if(count($TableList)<=0){
                    $res['statusCode'] = '205';
                    $res['returnMsg'] = '请录入品目信息';
                    return json($res);
                }
            }

            $OAwhere = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '001'
            );
            $query = BaseModel::OAInterface($OAwhere);


            if(!empty($query['SourceNo'])){
                $res['statusCode'] = '206';
                $res['returnMsg'] = '裁决已经存在';
                return json($res);
            }


            $add = array(
                'SourceType'  => '001',
                'SourceNo'    => $post_asid,
                'SP_Contents' => "execute yudo.SASRecvCfm 'CA' , '$post_asid','$asUserId' ",
                'OA_Status'   => '0',
                'RegEmpID'    => $asUserId,
                'RegDate'     => date("Y-m-d H:i:s"),
                'UptEmpID'    => $asUserId,
                'UptDate'     => date("Y-m-d H:i:s")
            );

            if($data['CustSignYn'] == 'Y'){
                $datas = RecvHandleModel::getASMinute($post_asid,$langCode);

                if($datas['SendEmailYn'] != 'Y'){
                    $sendYn = $this->SendEmail($post_asid,$param['UserID'],$langCode);
                    if(!$sendYn){
                        $res['statusCode'] = '207';
                        $returnMsg = '邮件发送失败，请稍后重新提交OA审核';
                        return json($res);
                    }
                    $save = array(
                      'SendEmailYn' => 'Y',
                    );
                    $w = array(
                        'ASRecvNo'  => $post_asid,
                    );
                    RecvHandleModel::SaveASRecv($save,$w);

                }
            }


            $OAwhere = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '001'
            );
            $Interface = BaseModel::OAInterface($OAwhere);
            if(empty($Interface['SourceNo'])){
                InstallationTrialModel::addOAInterface($add);
                // $res['statusCode'] = 'N003';
                // $returnMsg = '裁决提交失败，请稍后再试';

                // // $returnMsg = mb_convert_encoding($returnMsg, 'UTF-8', 'GBK');

                // $res['returnMsg'] = $returnMsg;
                // return json($res);
            }
            $where = array(
                'ASRecvNo'  => $post_asid,
                'OrderGubun'=> '1'
            );
            $resultAs = RecvHandleModel::getTASRecv00($where);

            if(!empty($resultAs['ASRecvNo'])){
                $Cnt = RecvHandleModel::as_countOA($data['OrderNo']);
                $OrderCnt = $Cnt + 1;
            }else{
                $OrderCnt = 0;
            }
            $save = array(
              'Status' => 'A',
              'SendEmailYn' => 'Y',
              'ApprUseYn' => '1',
              'OrderCnt'  => $OrderCnt
            );
            if($data['ASType'] != 'AS10020030'){
                unset($save['SendEmailYn']);
            }
            $w = array(
                'ASRecvNo'  => $post_asid,
            );
            RecvHandleModel::SaveASRecv($save,$w);

            $res['data'] = $post_asid;
            return json($res);
        }
    }
    /**
     * AS接受 取消裁决
     * @param array $data ÐÞ¸ÄÊý¾Ý
     * @return bool
     */
    public function unSubAdjudication(){

        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            $post_asid = $param['ASRecvNo'];
            if(empty($post_asid)){
                $res['statusCode'] = '201';
                $res['returnMsg'] = 'as号不存在';
                return json($res);
            }

            $where = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '001'
            );
            $query = BaseModel::OAInterface($where);

            if(empty($query['SourceNo'])){
                $res['statusCode'] = '202';
                $res['returnMsg'] = '当前AS还没有申请裁决';
                return json($res);
            }
            elseif($query['OA_Status'] != '5'){
                $res['statusCode'] = '203';
                $res['returnMsg'] = '不可取消正在进行中的裁决';
                return json($res);

            }
            InstallationTrialModel::DeleteOAInterface($where);

            $save = array(
                'Status' => '0',
                'ApprUseYn' => '0',
                'CfmYn' => '0'
            );
            $w = array(
                'ASRecvNo'  => $post_asid,
            );
            RecvHandleModel::SaveASRecv($save,$w);
            $res['data'] = $post_asid;
            return json($res);
        }
    }

    private function SendEmail($ASNO,$UserID,$langCode){//smtphz.qiye.163.com
        $config = config('web');
        $data = RecvHandleModel::getASMinute($ASNO,$langCode);
        $users = UserModel::getUserDeptInfo($UserID)[0];
        $data['EmpId'] = str_replace(' ','',$data['EmpId']);
        $EmpId = str_replace(' ','',$users['emp_code']);
        if(!empty($EmpId)){
            $mulu = $EmpId;
        }else{
            $mulu = $UserID;
        }

        if($data['OrderGubun'] != 1 && $data['ASType'] =='AS10020030'){
            $data['OrderNo']             = $data['UnRegOrderNo'];
        }

        $fun = $config['Recv'];

        $mt_id = $data['ASRecvNo'];
        // $filename = $v['FileNm'];
        $dirname_year = substr($mt_id,0,4);
        $dirname_month = substr($mt_id,4,2);
        $dirname_defualt = substr($mt_id,0,6);
        $file = "static/$fun/$dirname_year/$dirname_month/$mt_id/$mulu/".$mt_id.'.pdf';

        if(!file_exists($file)){
            $res['statusCode'] = '208';
            $returnMsg = 'PDF文件不存在';
            return json($res);
        }
        $data['ASRecvDate'] = date("Y-m-d",strtotime($data['ASRecvDate']));
        $EmptysH = UserModel::getEmpIDHP($data['EmpId']);
        $data['HP'] = $EmptysH['HP'];
        $data['EmailID'] = $EmptysH['EmailID'];
        $html = '<div style="margin-top:30px;">尊敬的客户您好：</div>';
        $html .= '<div style="margin-bottom:40px;">非常感谢一直以来的支持与配合！</div>';
        $html .= '<div>'.$data['ASRecvDate'].'贵司维修系统事项如下</div>';
        $html .= '<div>1.贵公司模号：'.$data['RefNo'].'</div>';
        $html .= '<div>2.YUDO订单号：'.$data['OrderNo'].'</div>';
        $html .= '<div style="margin-bottom:40px;">3.客户维修报告（见附件PDF）</div>';
        $html .= '<style>
                    .youx a{
                        color: red;font-weight:900;text-decoration: auto !important;
                    }
                </style>
                <div style="margin-bottom:40px;">
                <div>以上如有任何疑问请随时联系我们</div>
                <div>谢谢支持！</div>
                </div>
                <div>此邮件由系统自动发出，请勿直接回复。(MP-客户维修)</div>
                <div>如需回复，请发邮件至：<span class="youx"  style=" color: red;font-weight:900;text-decoration: auto !important;">'.$data['EmailID'].'</span>，谢谢支持！</div>
                <div><span style="font-size:25px;font-weight: bolder;color: #D11341;font-family: fantasy;transform: scale(1.5,1.5);display:inline-block;
                    -ms-transform: scale(1,1.5);
                    -webkit-transform: scale(1,1.5);
                    -moz-transform: scale(1,1.5);
                    -o-transform: scale(1,1.5);">YUDO</span>
                    <span stype="line-height:25px;height:25px;"> Leading Innovation</span></div>
                <div>
                    <p>'.$data['EmpNm'].' | '.$data['DeptNm'].'<p>
                    <p>M: '.$data['HP'].' T: 0512-65048882 E-mail:'.$data['EmailID'].' <p>
                    <p>W: http://www.yudo.com.cn<p>
                    <p>柳道万和（苏州）热流道系统有限公司 | YUDO(SUZHOU) HOT RUNNER SYSTEMS CO., LTD<p>
                    <p>苏州市吴中区甪直镇凌港路29号 | No.29 Ling Gang Road, Wuzhong District, Suzhou City, Jiangsu Province, China<p>
                </div>

                ';
        $list = RecvHandleModel::getemalis($data['ASRecvNo']);
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

        $mail->setReceiver($data['CustEmail']); //设置收件人，多个收件人，调用多次
        $mail->setCc($data['GMEmail']); //设置抄送，多个抄送，调用多次


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
            $mail->setCc($data['EmailID']); //设置抄送，多个抄送，调用多次
        }

        $mail->addAttachment($file); //添加附件，多个附件，调用多次

        $time = date('Y-m-d',time());

        $title = "YUDO - 客户维修报告 -".$data['ASRecvNo']."-".$time."-".$data['DeptNm'];

        $mail->setMail($title, $html); //设置邮件主题、内容

        $save = array(
          'SendEmailYn' => 'Y',
        );
        $w = array(
            'ASRecvNo'  => $data['ASRecvNo'],
        );
        RecvHandleModel::SaveASRecv($save,$w);


        $send = $mail->sendMail(); //发送

        return json($send);
    }


    //AS处理
    /**
     * 查询AS处理列表
     * @param array $param POST传值
     * @return bool
     */
    public function getAsHandle(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $param['langCode'] = $config[$param['LangID']];
            $users = InstallationTrialModel::Users($param['UserID']);
            $auth = BaseModel::getAuth('WEI_2100',$param['UserID']);

            $param['users'] = $users;
            $param['auth'] = $auth;
            $result = RecvHandleModel::getAsHandle($param);

            foreach ($result as $key => $value) {
                if($value['OrderSysRegYn'] == 'Y'){
                    $result[$key]['OrderNo'] = $value['OrderNo'];
                }else{
                    $result[$key]['OrderNo'] = $value['UnRegOrderNo'];
                }
                $result[$key]['ASDate'] = date('Y-m-d',strtotime($value['ASDate']));
            }

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
     * 查询AS处理查询AS接受
     * @param array $param POST传值
     * @return bool
     */
    public function ASHandlePrc(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            if(!empty($param['OrderNo']) || !empty($param['ASRecvNo'])){
                $param['startDate'] = '1900-00-00';
                $param['endDate'] = date('Y-m-d',intval(time()));
            }
            $result = RecvHandleModel::ASHandlePrc($param);

            // $this->As10_model->auth($this->loginUser,$this->auth);
            $langCode = $config['CHN'];
            foreach($result as $key => $val){

                if($val['OrderSysRegYn'] == 'N'){

                    $result[$key]['OrderNo'] = $val['UnRegOrderNo'];
                }
                $typeNm = RecvHandleModel::getTSMSyco10('AS1002',$val['ASType'],$langCode);
                if(!empty($typeNm)){
                    $result[$key]['typeNm'] = $typeNm[0]['text'];
                }else{
                    $result[$key]['typeNm'] ='';
                }

                $result[$key]['ASRecvDate'] = date('Y-m-d',strtotime($val['ASRecvDate']));
                $result[$key]['ASDelvDate'] = date('Y-m-d',strtotime($val['ASDelvDate']));

            }

            $lists = array();
            foreach($result as $key => $val){
                $w = array(
                    'ASRecvNo'  => $val['ASRecvNo']
                );
                $list = RecvHandleModel::getAsHandleByAsRecvNo($w);
                if(empty($list)){
                    $lists[] = $val;
                }
            }
            $res['data'] = $lists;

            return json($res);
        }
    }

    /**
     * 查询AS类型
     * @param array $param POST传值
     * @return bool
     */
    public function getASKindList(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            $langCode = $config[$param['LangID']];
            $lists = RecvHandleModel::getASKindList($langCode);

            $nows = array();
            foreach ($lists as $key => $value) {
                $nows[] = array(
                    'value' => $value['DictCd'],
                    'text'  => $value['TransNm'],
                );
            }

            $res['data'] = $nows;

            return json($res);
        }
    }
    /**
     * AS类型列表
     * @param array $param POST传值
     * @return bool
     */
    public function getASProcKindList(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            $langCode = $config[$param['LangID']];
            $lists = RecvHandleModel::getASProcKindList($langCode);

            $nows = array();
            foreach ($lists as $key => $value) {
                $nows[] = array(
                    'value' => $value['DictCd'],
                    'text'  => $value['TransNm'],
                );
            }

            $res['data'] = $nows;

            return json($res);
        }
    }
    /**
     * AS处理结果列表
     * @param array $param POST传值
     * @return bool
     */
    public function getASProcResultList(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            $langCode = $config[$param['LangID']];
            $lists = RecvHandleModel::getASProcResultList($langCode);

            $nows = array();
            foreach ($lists as $key => $value) {
                $nows[] = array(
                    'value' => $value['DictCd'],
                    'text'  => $value['TransNm'],
                );
            }

            $res['data'] = $nows;

            return json($res);
        }
    }

    /**
     * 部品返还区分列表
     * @param array $param POST传值
     * @return bool
     */
    public function getItemReturnList(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            $langCode = $config[$param['LangID']];
            $lists = RecvHandleModel::getItemReturnList($langCode);



            $res['data'] = $lists;

            return json($res);
        }
    }

    /**
     * 部品返还区分列表
     * @param array $param POST传值
     * @return bool
     */
    public function LoginUser(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $UserID = $param['UserID'];
            $users = UserModel::getUserDeptInfo($UserID)[0];

            $list = array(

                'EmpNm' => $users['EmpNm'],
                'EmpId' => $users['emp_code'],
                'DeptNm' => $users['DeptNm'],
                'DeptCd' => $users['DeptCd'],
            );

            $res['data'] = $list;

            return json($res);
        }
    }

    /**
     * 保存AS处理
     * @param array $param POST传值
     * @return bool
     */
    public function SaveHandle(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $UserID = $param['UserID'];
            $users = UserModel::getUserDeptInfo($UserID)[0];
            $JobNo = InstallationTrialModel::Users($param['UserID']);
            $langCode = $config[$param['LangID']];

            $addlist = InstallationTrialModel::getAddressInfo($param['ArrivalLeaveNo']);
            if(!empty($addlist)){
                // $param['ArrivalTime'] = $addlist['ArrivalDate'];
                // $param['ArrivalLeaveNo'] = $addlist['ArrivalLeaveNo'];
                // $param['ArrivalLocationAddr'] = $addlist['LocationAddr'];
                // $param['ArrivalLat'] = $addlist['GpsLat'];
                // $param['ArrivalLng'] = $addlist['GpsLng'];
                $param['Arrivalphoto'] = $addlist['Arrivalphoto'];
            }
            $ASDate = strtotime(date('Y-m-d',strtotime($param['ASDate'])));
            $timeAs = strtotime(date('Y-m-d'));
            if($ASDate>$timeAs){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = "AS处理日期不得大于当天";
                return json($res);
            }


            $add = [
                'ASNo' => $param['ASNo'],
                'ASDate' => $param['ASDate'],
                'ASRecvNo' => $param['ASRecvNo'],
                'EmpId' => $param['EmpID'],
                'JobNo' => $JobNo['JobNo'],
                'DeptCd' => $param['DeptCd'],
                'CustCd' => $param['CustCd'],
                'ASKind' => $param['ASKind'],//AS类型
                'ASProcKind' => $param['ASProcKind'],//AS处理区分
                'ProcResult' => $param['ProcResult'],//AS处理结果
                'ASAmt' => $param['ASAmt'],//所需配件费用
                'ASRepairAmt' => $param['ASRepairAmt'],//修理费
                'ASAreaGubun' => $param['ASAreaGubun'],//服务地区区分
                'ASArea' => $param['ASArea'],//服务地点
                'ItemReturnYn' => $param['ItemReturnYn'],//部品反回与否
                'ItemReturnGubun' => $param['ItemReturnGubun'],//部品返还区分
                'ChargeYn' => $param['ChargeYn'],//收费与否
                'ASNote' => $param['ASNote'],//AS处理详细
                'ProcResultReason' => $param['ProcResultReason'],//AS处理结果原因
                'CustOpinion' => $param['CustOpinion'],//客户意见
                'Remark' => $param['Remark'],//备注
                'ProcPerson' => $param['ProcPerson'],//经办人
                'TransLine' => $param['TransLine'],//行驶里程

                'RegEmpID' => $users['emp_code'],
                'RegDate' => date('Y-m-d H:i:s'),
                'UptEmpID' => $users['emp_code'],

                'CustPrsn' => $param['CustPrsn'],
                'CustTell' => $param['CustTell'],
                'CustEmail' => $param['CustEmail'],
                'ResTest' => $param['ResTest'],
                'ResTestDesc' => $param['ResTestDesc'],
                'TempRiseTest' => $param['TempRiseTest'],
                'TempRiseTestDesc' => $param['TempRiseTestDesc'],
                'UptDate'  => date('Y-m-d H:i:s'),
                'facilityYn'    => 1,
                'ArrivalTime' => $param['ArrivalTime'],//抵达时间
                'StartTime' => $param['StartTime'],//离开时间

                'ArrivalLeaveNo'    => $param['ArrivalLeaveNo'],
                'ArrivalLat'        => $param['ArrivalLat'],
                'ArrivalLng'        => $param['ArrivalLng'],
                'ArrivalLocationAddr'        => $param['ArrivalLocationAddr'],
                'LeaveLat'      => $param['LeaveLat'],
                'LeaveLng'      => $param['LeaveLng'],
                'LeaveLocationAddr'      => $param['LeaveLocationAddr'],
                'Arrivalphoto'      => $param['Arrivalphoto'],
                'Leavephoto'      => $param['Leavephoto'],


            ];
            $date = date('Ym',time());
            if($param['ASNo'] == ''){

                    $lastAsHandle = RecvHandleModel::getLastAsHandle();
                    if(empty($lastAsHandle)){
                        $add['ASNo'] = $date.'0001';
                    }else{
                        $add['ASNo'] = $lastAsHandle['ASNo']+1;
                    }
                    $asHandleNo = $add['ASNo'];

                    if(empty($param['ArrivalLeaveNo'])){
                        unset($add['ArrivalLeaveNo']);
                        unset($add['ArrivalLat']);
                        unset($add['ArrivalLng']);
                        unset($add['ArrivalLocationAddr']);
                        unset($add['Arrivalphoto']);

                    }
                    if(empty($param['StartTime'])){
                        unset($add['StartTime']);
                        unset($add['LeaveLat']);
                        unset($add['LeaveLng']);
                        unset($add['LeaveLocationAddr']);
                        unset($add['Leavephoto']);
                    }

                    $updata = true;

                    RecvHandleModel::addProc($add);

            }else{
                $asHandleNo = $param['ASNo'];
                $w = array(
                    'ASNo'  => $param['ASNo']
                );
                $resAS = RecvHandleModel::getAsHandleProc($w);

                if($resAS['facilityYn'] != 1){
                    $res['statusCode'] = 'N003';
                    $res['returnMsg'] = "AS处理是PC端ERP录入，不可修改";
                    return json($res);
                }
                unset($add['ASNo']);
                unset($add['RegEmpID']);
                unset($add['RegDate']);
                if(empty($param['StartTime'])){
                    unset($add['StartTime']);
                    unset($add['LeaveLat']);
                    unset($add['LeaveLng']);
                    unset($add['LeaveLocationAddr']);
                    unset($add['Leavephoto']);
                }else{
                    $juli1 = Apis::getDistance($param['ArrivalLat'],$param['ArrivalLng'],$param['LeaveLat'],$param['LeaveLng']);
                    if($juli1 > 1000){
                        $res['statusCode'] = 'N003';
                        $res['returnMsg'] = "到达距离与离开距离已超出范围";
                        return json($res);

                    }
                    $juli2 = Apis::getDistance($resAS['CustGpsLat'],$resAS['CustGpsLng'],$param['LeaveLat'],$param['LeaveLng']);
                    $juli3 = Apis::getDistance($param['ArrivalLat'],$param['ArrivalLng'],$resAS['CustGpsLat'],$resAS['CustGpsLng']);

                    if(strtotime($param['ArrivalTime'])>strtotime($param['StartTime'])){
                        $res['statusCode'] = 'N003';
                        $res['returnMsg'] = "到达时间->离开时间请检查先后顺序";
                        return json($res);
                    }
                }
                $updata = false;
                $where = array(
                    'ASNo'  => $asHandleNo
                );
                RecvHandleModel::SaveProc($add,$where);


            }


            if(!empty($param['TableList'])){

                foreach ($param['TableList'] as $k => $v){

                    $itemAdd = [
                        'ASNo'   => $asHandleNo,
                        'ASSerl' => $v['ASRecvSerl'],
                        'Sort'       => $v['Sort'],
                        'ItemCd' => $v['ItemCd'],
                        'UnitCd' => $v['UnitCd'],
                        'Qty'    => $v['Qty'],
                        'ASRepairAmt' => $v['ASRepairAmt'],
                        'Amt'         => $v['Amt'],
                        'ChargeYn'    => $v['ChargeYn'],
                        'ReUseYn'     => $v['ReUseYn'],
                        'ASRecvNo'    => $param['ASRecvNo'],
                        'ASRecvSerl'  => $v['ASRecvSerl'],
                        'Remark'      => $v['Remark'],
                    ];

                    if(isset($v['isAsHandle'])){
                        if($v['isAsHandle'] == 1){
                            unset($itemAdd['ASRecvNo']);
                            unset($itemAdd['ASRecvSerl']);
                        }
                    }

                    $item = RecvHandleModel::getAsHandleItem($asHandleNo,$v['ASSerl']);
                    if(empty($item[0])){
                        RecvHandleModel::addAsHandleItem($itemAdd);
                    }else{
                        $where = array(
                            'ASNo'      => $asHandleNo,
                            'ASSerl'    => $v['ASSerl']
                        );
                        RecvHandleModel::setAsHandleItem($where,$itemAdd);
                    }

                }
            }
            $res['updata'] = $updata;
            $res['data'] = $asHandleNo;
            return json($res);
        }
    }
    /**
     * AS处理详情页查询
     * @param array $param POST传值
     * @return bool
     */
    public function getAsHandleInfo(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $langCode = $config[$param['LangID']];
            $info = RecvHandleModel::getAsHandleInfo($param['ASNo'],$langCode);


            $HandleSales = RecvHandleModel::ASHandleSales($info['ASNo']);
            $info['HandleSales'] = $HandleSales;

            $where = array(
                'ASNo'  => $info['ASNo']
            );
            $list = RecvHandleModel::ASHandlephoto($where);

            $this->DownloadHandlePhoto($config['Handle'],$config['HandleFTP'],$list);
            $HandlePhotos = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['ASNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Handle']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $HandlePhotos[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASNo' => $value['ASNo'],
                    'FileNm'    => $fileurl.$value['FileNm'],
                    'FTP_UseYn' => $value['FTP_UseYn'],
                    'Photo' => $value['Photo'],
                    'Seq' => $value['Seq'],
                );
            }

            $info['HandlePhotos'] = $HandlePhotos;
            $TableList = RecvHandleModel::getAsHandleItem($info['ASNo']);

            if(empty($TableList)){
                $TableList = RecvHandleModel::ASMinuteTable($info['ASRecvNo']);
                foreach ($TableList as $key => $value) {
                    $TableList[$key]['isAsHandle'] = 0;
                    $TableList[$key]['Amt'] = 0;
                    $TableList[$key]['ASRepairAmt'] = 0;
                    $TableList[$key]['ReUseYn'] = 'N';
                    $TableList[$key]['ASSerl'] = $value['ASRecvSerl'];
                }
                $list['TableList'] = $TableList;
            }
            $ASRecvs = RecvHandleModel::getASMinute($info['ASRecvNo'],$langCode);
            $info['ASType'] = $ASRecvs['ASType'];
            $info['TableList'] = $TableList;


            $mt_id = $info['ASNo'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);


            $fileNm = $config['SignName'];
            $fileurl = $config['HomeUrl'].$config['Handle']."/$dirname_year/$dirname_month/$mt_id/";
            $SignUrl = $fileurl.$config['SignC'].$fileNm;
            if($info['CustSignYn'] == 'Y'){

                $mt_id = $info['ASNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl =$config['HomeUrl'].$config['Handle']."/$dirname_year/$dirname_month/$mt_id/";
                $info['SignUrl'] = $fileurl.$config['SignC'].$config['SignName'];
                $this->DownloadSign($config['Handle'],$config['HandleFTP'],$info['ASNo']);
            }else{
                $info['SignUrl'] ='';
            }

            $info['ArrivalTime'] = date('Y-m-d H:i:s',strtotime($info['ArrivalTime']));

            if($info['CustSignYn'] == 'Y'){
                $info['StartTime'] = date('Y-m-d H:i:s',strtotime($info['StartTime']));
            }

            $info['ASDate'] = date('Y-m-d',strtotime($info['ASDate']));

            $res['data'] = $info;
            return json($res);
        }
    }


     /**
     * AS申请品目
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function ASHandleTable()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $ASNo = $param['ASNo'];
            $TableList = RecvHandleModel::getAsHandleItem($ASNo);

            $res['data'] = $TableList;

            return json($res);
        }
    }

    public function saveHandleItem()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $ASNo = $param['ASNo'];
            $itemAdd = [
                'ASNo'   => $param['ASNo'],
                'ItemCd' => $param['ItemCd'],
                'UnitCd' => $param['UnitCd'],
                'Qty'    => $param['Qty'],
                'Price'  => $param['Price'],
                'ASRepairAmt' => $param['ASRepairAmt'],
                'Amt'         => $param['Amt'],
                'ChargeYn'    => $param['ChargeYn'],
                'ReUseYn'     => $param['ReUseYn'],
                'Remark'      => $param['Remark'],
            ];
            $where = array(
                'ASNo'      => $ASNo,
                'ASRecvSerl'    => $param['ASRecvSerl']
            );
            RecvHandleModel::setAsHandleItem($where,$itemAdd);

            return json($res);
        }
    }

    /**
     * 查看AS处理品目
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function getHandleItem()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';

            $where = array(
                'ASNo' => $param['ASNo'],
                'ASSerl'    => $param['ASSerl']
            );

            $list = RecvHandleModel::getAsHItem($where);

            $res['data'] = $list;

            return json($res);
        }
    }
    /**
     * 添加AS处理-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function addHandleSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            // $loginID =  $param['UserID'];
            $param['UserID'] = $this->getUserId();
            $ASNo = $param['NumberNo'];
            $where = array(
                'ASNo'    => $param['NumberNo']
            );

            $nows = RecvHandleModel::ASHandleSales($ASNo);
            $count = count($nows);
            $w = array(
                'ASNo'    => $param['NumberNo'],
                'SaleEmpID'     => $param['EmpID']
            );
            $user = RecvHandleModel::getHandleSalesE($w);
            if($user){
                $res['statusCode'] = '104';
                $res['statusMsg'] = '同行人员已存在';
                return json($res);
            }
            $count += 1;
             $data = array(
                'ASNo'    => $ASNo,
                'Seq'           => '0'.$count,
                'SaleEmpID'     => $param['EmpID'],
                'RegEmpID'      => $param['UserID'],
                'RegDate'       => date('Y-m-d H:i:s'),
                'UptEmpID'      => $param['UserID'],
                'UptDate'       => date('Y-m-d H:i:s'),
            );

            RecvHandleModel::addHandleSales($data);

            $list = RecvHandleModel::ASHandleSales($ASNo);
            $res['count'] = $count;
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
    public function DeleteHandleSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $where = array(
                'ASNo'    => $param['NumberNo'],
                'Seq'           => $param['Seq']
            );
            $dele = RecvHandleModel::DeleteHandleSales($where);


            $list = RecvHandleModel::ASHandleSales($param['NumberNo']);
            $w = array(
                'ASNo'    => $param['NumberNo']
            );
            RecvHandleModel::DeleteHandleSales($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'ASNo'    => $param['NumberNo'],
                    'Seq'           => '0'.$count,
                    'SaleEmpID'     => $value['SaleEmpID'],
                    'RegEmpID'      => $param['UserID'],
                    'RegDate'       => date('Y-m-d H:i:s'),
                    'UptEmpID'      => $param['UserID'],
                    'UptDate'       => date('Y-m-d H:i:s'),
                );
                RecvHandleModel::addHandleSales($data);
            }
            $list = RecvHandleModel::ASHandleSales($param['NumberNo']);


            $photoCount = count($list);
            $res['photoCount'] = $photoCount;
            $res['data'] = $list;
            return json($res);
        }
    }


    /**
     * AS处理-图片列表
     * @param array $param POST传值
     * @return bool
     */
    public function ASclPhoto(){

        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            $w = array(
                    'ASNo'    => $param['ASNo']
                );
            $list = RecvHandleModel::ASHandlephoto($w);


            $this->DownloadHandlePhoto($config['Handle'],$config['HandleFTP'],$list);



            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['ASNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Handle']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASNo' => $value['ASNo'],
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
    public function addHandlePhotos()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $date = date('Ymdhis',time());
            $config = config('web');
            $mt_id = $param['ASNo'];
            $UserID = $param['UserID'];

            // $addressL = Apis::geocoder($param['Lng'],$param['Lat'],$config['addres']);
            $address = '';//$addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;

            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $fun = $config['Handle'];

            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');

            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);


            $remoteFile = $config['HandleFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;
            $res['file'] = $remoteFile;
            $res['file2'] = $localFile;

            FtpUtil::ftp_photo($mt_id,$config['HandleFTP']);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
            }

            $w = array(
                'ASNo'    => $mt_id
            );

            $list = RecvHandleModel::ASHandlephoto($w);

            $counTS = count($list);

            $res['listdate'] = $param;

            $counTS = $counTS + $param['count'] + 1;
            $seq = (int)$counTS>=10?$counTS:'0'.$counTS;
            $data = array(
                'ASNo'    => $mt_id,
                'Seq' => $seq,
                'FileNm'    => $fileNm,
                'RegEmpID'      => $UserID,
                'RegDate'       => date('Y-m-d H:i:s'),
                'UptEmpID'      => $UserID,
                'UptDate'       => date('Y-m-d H:i:s'),
                'FTP_UseYn' => 'Y',
                'Lat'           => $param['Lat'],
                'Lng'           => $param['Lng'],
                'LocationAddr'  => $address,

            );

            $where = array(
                'ASNo'    => $mt_id,
                'Seq' => $data['Seq']
            );

            $PhotoL = RecvHandleModel::getASHandlePhotoF($where);
            $res['PhotoL'] = $PhotoL;
            if(empty($PhotoL)){
                if((int)$data['Seq']<=2){
                    $addressL = Apis::geocoder($param['Lng'],$param['Lat'],$config['addres']);
                    if($addressL->status == '0'){
                        $address = $addressL->result->formatted_addresses->standard_address;
                    }else{
                        $address = '';
                    }


                    $data['LocationAddr'] = $address;
                }

                RecvHandleModel::addHandlePhoto($data);
            }else{
                $count = '';
                for ($i = count($PhotoL); $i >= 1; $i--) {
                    $sq = $i>=10?$i:'0'.$i;
                    $where = array(
                        'ASNo'    => $mt_id,
                        'Seq' => $sq
                    );
                    $Photo = RecvHandleModel::getASHandlePhotoF($where);
                    if(empty($Photo)){
                        $count = $i;
                    }
                }
                $seqD = (int)$count>=10?$count:'0'.$count;
                $data['Seq'] = $seqD;
                RecvHandleModel::addHandlePhoto($data);
            }
            $w = array(
                'ASNo'    => $mt_id
            );

            $lists = RecvHandleModel::ASHandlephoto($w);

            $AssmPhoto = array();
            foreach ($lists as $key => $value) {
                $mt_id = $value['ASNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Handle']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASNo' => $value['ASNo'],
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
     * 安装报告-删除照片
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function DeleteHandlePhone()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $where = array(
                'ASNo'    => $param['ASNo'],
                'FileNm'           => $param['name']
            );
            RecvHandleModel::DeleteHandlePhoto($where);
            $w = array(
                'ASNo'    => $param['ASNo']
            );
            $list = RecvHandleModel::ASHandlephoto($w);

            RecvHandleModel::DeleteHandlePhoto($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'ASNo' => $value['ASNo'],
                    'Seq' => '0'.$count,
                    'FileNm'    => $value['FileNm'],
                    'RegEmpID'      => $param['UserID'],
                    'RegDate'       => date('Y-m-d H:i:s'),
                    'UptEmpID'      => $param['UserID'],
                    'UptDate'       => date('Y-m-d H:i:s'),
                    'FTP_UseYn' => 'Y',
                    'Lat'           => $value['Lat'],
                    'Lng'           => $value['Lng'],
                    'LocationAddr'  => $value['LocationAddr'],
                );
                RecvHandleModel::addHandlePhoto($data);
            }
            $list = RecvHandleModel::ASHandlephoto($w);
            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['ASNo'];

                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Handle']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'ASNo' => $value['ASNo'],
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
     * 试模报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadHandlePhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = $v['ASNo'];
                $filename = $v['FileNm'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
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
     * AS接受 客户签名
     * ²éÑ¯ TASRecv00 ±íÖÐÊÇ·ñÓÐ·ûºÏÌõ¼þµÄ¼ÇÂ¼
     * @param array $data ÐÞ¸ÄÊý¾Ý
     * @return bool
     */
    public function HandleSignImage()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');

            $file = request()->file('sign');
            $mt_id = $param['ASNo'];
            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $fun = $config['Handle'];

            $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];
            if (!is_dir($yeardir)) {
                mkdir(iconv("UTF-8", "GBK", $yeardir), 0775, true);
            }
            $dir = "$fun/$dirname_year/$dirname_month/$mt_id".$config['Sign'];

            $fileNm = $config['SignName'];
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);
            $res['data']['savename'] = $savename;

            $where = array(
                'ASNo'    => $param['ASNo']
            );

            $addressL = Apis::geocoder($param['CustGpsLng'],$param['CustGpsLat'],$config['addres']);

            $CustLocationAddr = $addressL->result->formatted_addresses->standard_address;//$addressL->result->formatted_address.$addressL->result->sematic_description;

            $time = date('Y-m-d H:i:s');
            $data = array(
                'CustSignYn'    => 'Y',
                'CustSignDate'    => $time,
                'CustGpsLat'    => $param['CustGpsLat'],
                'CustGpsLng'    => $param['CustGpsLng'],
                'CustLocationAddr'    => $CustLocationAddr,
                'StartTime'    => $time,
                'LeaveLat'    => $param['CustGpsLat'],
                'LeaveLng'    => $param['CustGpsLng'],
                'LeaveLocationAddr' => $CustLocationAddr,
            );

            $save_path = $config['localFile']."$fun/$dirname_year/$dirname_month/$mt_id/".$config['SignC'].$config['SignName']; // ÄãÒª±£´æÍ¼Æ¬µÄÂ·¾¶

            if(is_file($save_path)){
                RecvHandleModel::SaveProc($data,$where);

                $fileNm = $config['SignName'];
                $fileurl = $config['HomeUrl']."$fun/$dirname_year/$dirname_month/$mt_id/";
                $SignUrl = $fileurl.'CustSign/'.$fileNm;


                $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
                $remoteFile = $config['HandleFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
                $localFile = $config['localFile'].$dir.'/'.$config['SignC'].$fileNm;
                FtpUtil::ftp_photo($mt_id,$config['HandleFTP'],$config['Sign']);
                if (!FtpUtil::upload($localFile, $remoteFile)) {
                    $res['statusCode'] = '105';
                    $res['msg'] ="文件上传失败";
                }
                $res['data'] = $SignUrl;
                $res['CustSignYn'] = 'Y';
                //  $res['data'] = $data;
                // $res['save_path'] = $save_path;
                // return json($res);
            }else{
                $res['statusCode'] = '201';
            }



            return json($res);
        }
    }


    public function DowASPDF(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $langCode = $config[$param['LangID']];
            $data = RecvHandleModel::getAsHandleInfo($param['ASNo'],$langCode);
            $TableList = RecvHandleModel::getAsHandleItem($data['ASNo']);
            $ASNo = $param['ASNo'];

            $tab = '';
            foreach($TableList as $k=>$v){

                    $shu = $k+1;
                    $ItemNm = $v['ItemNm'];
                    $Spec = $v['Spec'];
                    $Qty = (int)$v['Qty'];
                    if(trim($ItemNm) != ''){
                        $tab .='<tr>';
                        $tab .='<td align="center" colspan="2"><b>'.$shu.'</b></td>';
                        $tab .='<td align="center" colspan="5"><b>'.$ItemNm.'</b></td>';
                        $tab .='<td align="center" colspan="3"><b>'.$Spec.'</b></td>';
                        $tab .='<td align="center" colspan="2"><b>'.$Qty.'</b></td>';
                        $tab .='</tr>';
                   }
                }
            $user = UserModel::getUserDeptInfo($param['UserID']);

            $EmpId = str_replace(' ','',$user[0]['emp_code']);
            if(!empty($EmpId)){
                $mulu = $EmpId;
            }else{
                $mulu = str_replace(' ','',$param['UserID']);
            }

            if($data['CustSignYn'] != 'Y' ){
                $res['statusCode'] = '202';
                $res['returnMsg'] = '未签字不发邮箱';
                return json($res);
            }

            if($data['SendEmailYn'] == 'Y' ){
                $res['statusCode'] = '202';
                $res['returnMsg'] = '邮件已发送';
                return json($res);

            }


            $fun = $config['Handle'];
            $dirname_year = substr($ASNo,0,4);
            $dirname_month = substr($ASNo,4,2);
            $dirname_defualt = substr($ASNo,0,6);

            $yeardir = "static/$fun/$dirname_year/$dirname_month/$ASNo/$mulu";
            if (!is_dir($yeardir)) {
                mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
            }
            $file = "static/$fun/$dirname_year/$dirname_month/$ASNo/$mulu/".$ASNo.'.pdf';
            if(file_exists($file)){
                $res['statusCode'] = '203';
                $res['returnMsg'] = '文件已存在';
                return json($res);
            }


                    //邮箱
            if(isset($data['EmpId'])){

                $EmptysH = UserModel::getEmpIDHP($EmpId);
                $data['HP'] = $EmptysH['HP'];
                $data['EmailID'] = $EmptysH['EmailID'];
                $Area = UserModel::getCustArea($data['CustCd']);
                if(empty(trim($Area['Area']))){
                     $data['Area'] = '';
                }else{
                    $Area1 = UserModel::getCustAreaNm($Area['Area']);
                    $langCode = $config['CHN'];
                    $Area2 = UserModel::getCustAreaTrNm($Area['Area'],$langCode);
                    if(empty(trim($Area1['MinorNm']))){
                        $data['Area'] = empty(trim($Area2['TransNm']))?'':$Area2['TransNm'];
                    }else{
                        $data['Area'] = $Area1['MinorNm'];
                    }

                }

                if($data['CustSignYn'] == 'Y'){
                    $asNo = $data['ASNo'];

                    $data['CustSign'] =  "static/$fun/$dirname_year/$dirname_month/$ASNo/".$config['SignC'].$config['SignName'];
                }else{
                    $data['CustSign'] = '';
                }


                if($data['ChargeYn'] == 'Y'){
                    $ChargeYn = '收费';
                }else{
                    $ChargeYn = '免费';
                }

                if($data['ResTest'] == 'Y'){
                    $ResTest = 'OK';
                }else{
                    $ResTest = 'NG';
                }
                if($data['TempRiseTest'] == 'Y'){
                     $TempRiseTest = 'OK';
                }else{
                    $TempRiseTest = 'NG';
                }

                $where = array(
                    'DictCd'    => $data['ASKind'],
                    'LangCd'    => $langCode,
                );
                $ASKind = RecvHandleModel::getASKindsLists($where);
                $where = array(
                    'DictCd'    => $data['ASProcKind'],
                    'LangCd'    => $langCode,
                );
                $ASProcKind = RecvHandleModel::getASKindsLists($where);
                $where = array(
                    'DictCd'    => $data['ProcResult'],
                    'LangCd'    => $langCode,
                );
                $ProcResult = RecvHandleModel::getASKindsLists($where);



                // create new PDF document
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);

                $pdf->setPrintHeader(false);
                // $pdf->setPrintFooter(false);

                // // set auto page breaks
                $pdf->SetAutoPageBreak(FALSE, PDF_MARGIN_BOTTOM);

                $pdf->AddPage();
                $data['ASDate'] = date("Y-m-d",strtotime($data['ASDate']));
                $data['ArrivalTime'] = date("Y-m-d H:i",strtotime($data['ArrivalTime']));
                $data['StartTime'] = date("Y-m-d H:i",strtotime($data['StartTime']));
                // <img style="width: 150px;margin-top: 20px;" src="/image/login_logo2.png">

                $html = '<div style="color:#000;font-size:12px;">
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td colspan="4" align="center"><img style="width: 130px;height:50px" src="/image/pdf_logo.png"></td>
                            <td style="height: 18px;line-height:18px;font-family: 宋体;font-weight:900;text-align:left; padding:2px" colspan="8" align="left">
                                <b style="color:#000;font-size:12px;">柳道万和（苏州）热流道系统有限公司</b><br />
                                <b style="color:#000;font-size:12px;">YUDO(SUZHOU)HOT RUNNER SYSTEMS CO.,LTD</b><br />
                                <b style="color:#000;font-size:20px;">客户维修报告</b>
                                </td>
                        </tr>
                    </table>
                </div>
                <style type="text/css">td{height: 16px;line-height:16px; left:10px;}</style>
                <table border="1" cellspacing="0" cellpadding="0">

                    <tr>
                        <td align="center" colspan="2"><b>维修日期</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ASDate'].'</b></td>
                        <td align="center" colspan="2"><b>客户名称</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['CustNm'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>订单号码</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['OrderNo'].'</b></td>
                        <td align="center"><b>模号</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['RefNo'].'</b></td>
                        <td align="center"><b>区域</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['Area'].'</b></td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>塑胶</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['Resin'].'</b></td>
                        <td align="center" colspan="2"><b>系统类型</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['GoodNm'].'</b></td>
                    </tr>

                    <tr>
                        <td  colspan="12" align="center" style="height: 200px;"><br /><br />&nbsp;<img style="height: 180px;" src="static/image/PDF-AS.png">&nbsp;</td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>HRS</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['HRSystemNm'].'</b></td>
                        <td align="center" colspan="2"><b>系统大小</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['SystemSizeNm'].'</b></td>
                        <td align="center" colspan="2"><b>SystemType</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['SystemTypeNm'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>Gate类型</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['GateTypeNm'].'</b></td>
                        <td align="center" colspan="2"><b>Gate数量</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$data['GateQty'].'</b></td>
                        <td align="center" colspan="2"><b>是否收费</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$ChargeYn.'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>电阻测试</b></td>
                        <td align="center" colspan="1"><b>'.$ResTest.'</b></td>
                        <td align="center" colspan="3"><b>'.$data['ResTestDesc'].'</b></td>
                        <td align="center" colspan="2"><b>升温测试</b></td>
                        <td align="center" colspan="1"><b>'.$TempRiseTest.'</b></td>
                        <td align="center" colspan="3"><b>'.$data['TempRiseTestDesc'].'</b></td>
                    </tr>

                     <tr>
                        <td align="center" colspan="2"><b>AS类型</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$ASKind['text'].'</b></td>
                        <td align="center" colspan="2"><b>AS处理分类</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$ASProcKind['text'].'</b></td>
                        <td align="center" colspan="2"><b>AS处理结果</b></td>
                        <td align="left" colspan="2"><b>&nbsp;&nbsp;'.$ProcResult['text'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>AS处理详情</b></td>
                        <td align="left" colspan="10" ><b>&nbsp;&nbsp;'.$data['ASNote'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>AS处理结果_原因</b></td>
                        <td align="left" colspan="10"><b>&nbsp;&nbsp;'.$data['ProcResultReason'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>客户意见</b></td>
                        <td align="left" colspan="10"><b>&nbsp;&nbsp;'.$data['CustOpinion'].'</b></td>
                    </tr>



                    <tr>
                        <td align="center" colspan="2"><b>服务地点</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ASArea'].'</b></td>
                        <td align="center" colspan="2"><b>联系人</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['CustPrsn'].'</b></td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2"><b>到达时间</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['ArrivalTime'].'</b></td>
                        <td align="center" colspan="2"><b>离开时间</b></td>
                        <td align="left" colspan="4"><b>&nbsp;&nbsp;'.$data['StartTime'].'</b></td>
                    </tr>

                    <tr>
                        <td align="center" colspan="2"><b>维修人员</b></td>
                        <td align="left" colspan="4" ><b>&nbsp;&nbsp;'.$data['EmpNm'].'</b></td>
                        <td align="center" colspan="2"><b>客户</b></td>
                        <td colspan="4" align="left">&nbsp;&nbsp;<img style="height: 45px;" src="'.$data['CustSign'].'"></td>
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
                 <table border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td align="center"border="1" colspan="2"><b>配件清单</b></td>
                        <td align="center"  colspan="10"><b>&nbsp;</b></td>
                    </tr>
                     </table>
                    <table border="1" cellspacing="0" cellpadding="0">
                        <tr>
                            <td align="center" colspan="2"><b>序号</b></td>
                            <td align="center" colspan="5"><b>品目名称</b></td>
                            <td align="center" colspan="3"><b>规格</b></td>
                            <td align="center" colspan="2"><b>数量</b></td>
                        </tr>
                        '.$tab.'
                    </table>';
                    ob_end_clean();



                $pdf->SetFont('cid0cs', '', 10);
                // output the HTML content
                $pdf->writeHTML($html, true, false, true, false, '');


                // $res = $pdf->Output('example_039.pdf', 'I');
                $resA = $pdf->Output($data['ASRecvNo'].'.pdf', 'S');

                $list = file_put_contents($file, $resA);

                $res['data'] = $data;

                return json($res);


            }

        }
    }

        //提交裁决
    public function HandleAdjudication(){
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $langCode = $config[$param['LangID']];
            $data = RecvHandleModel::getAsHandleInfo($param['ASNo'],$langCode);

            $user = UserModel::getUserDeptInfo($param['UserID']);

            $EmpId = str_replace(' ','',$user[0]['emp_code']);

            $ASNo = $param['ASNo'];

            if(empty($ASNo) || empty($EmpId)){
                $res['statusCode'] = 'I994';
                $res['returnMsg'] = 'as号/职员信息不存在';
                return json($res);
            }


            $juli1 = Apis::getDistance($data['ArrivalLat'],$data['ArrivalLng'],$data['LeaveLat'],$data['LeaveLng']);
            if($juli1 > 1000){
                $res['statusCode'] = 'I452';
                $res['returnMsg'] = "到达距离与离开距离已超出范围";

                return json($res);
            }
            $juli2 = Apis::getDistance($data['CustGpsLat'],$data['CustGpsLng'],$data['LeaveLat'],$data['LeaveLng']);
            $juli3 = Apis::getDistance($data['ArrivalLat'],$data['ArrivalLng'],$data['CustGpsLat'],$data['CustGpsLng']);

            if($juli2 > 1000){
                $res['statusCode'] = 'I452';
                $res['returnMsg'] = "到达距离与客户签字距离已超出范围";
                return json($res);
            }
            if($juli3 > 1000){
                $res['statusCode'] = 'I452';
                $res['returnMsg'] = "离开距离与客户签字距离已超出范围";
                return json($res);
            }

            if(strtotime($data['StartTime']) - strtotime($data['ArrivalTime']) > 86400 ){
                $res['statusCode'] = 'I452';
                $res['returnMsg'] = "到达时间与离开时间不得超过24小时";
                return json($res);
            }

            if(strtotime($data['ArrivalTime'])>strtotime($data['StartTime'])){
                $res['statusCode'] = 'I452';
                $res['returnMsg'] = "到达时间->客户签字时间->离开时间请检查先后顺序";
                return json($res);
            }
            $OAwhere = array(
                'SourceNo'  => $ASNo,
                'SourceType'    => '017'
            );
            $query = BaseModel::OAInterface($OAwhere);

            if(!empty($query['SourceNo'])){
                $res['statusCode'] = '206';
                $res['returnMsg'] = '裁决已经存在';
                return json($res);
            }
            if($data['CustSignYn'] == 'Y'){
                $datas = RecvHandleModel::getAsHandleInfo($ASNo,$langCode);

                if($datas['SendEmailYn'] != 'Y'){
                    $sendYn = $this->SendASemail($ASNo,$param['UserID'],$langCode);

                    if(!$sendYn){
                        $res['statusCode'] = 'I452';
                        $res['returnMsg'] = "邮件发送失败，请稍后重新提交OA审核";
                        return json($res);
                    }
                }
            }
            $add = array(
                'SourceType'  => '017',
                'SourceNo'    => $ASNo,
                'SP_Contents' => "execute yudo.SASProcCfm 'CA','$ASNo','$EmpId'",
                'OA_Status'   => '0',
                'RegEmpID'    => $EmpId,
                'RegDate'     => date('Y-m-d H:i:s'),
                'UptEmpID'    => $EmpId,
                'UptDate'     => date('Y-m-d H:i:s')
            );
            InstallationTrialModel::addOAInterface($add);

            $OAwhere = array(
                'SourceNo'  => $ASNo,
                'SourceType'    => '017'
            );
            $Interface = BaseModel::OAInterface($OAwhere);
            if(empty($Interface['SourceNo'])){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = '裁决提交失败，请稍后再试';
                return json($res);
            }

            $save = array(
              // 'Status' => 'A',
                'SendEmailYn'   => 'Y',
                'ApprUseYn'     => '1'
            );
            $where = array(
                'ASNo' => $ASNo
            );
            RecvHandleModel::SaveProc($save,$where);

            $res['data'] = $ASNo;
            return json($res);
        }
    }
    public function UnHandleAdjudication()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $langCode = $config[$param['LangID']];

            $ASNo = $param['ASNo'];
            if(empty($ASNo)){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = 'as处理编号不存在';
                return json($res);
            }
            $where = array(
                'SourceType'  => '017',
                'SourceNo'    => $ASNo,
            );
            $query = BaseModel::OAInterface($where);
            if(empty($query['SourceNo'])){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = '当前AS处理还没有申请裁决';
                return json($res);
            }
            elseif($query['OA_Status'] != '5'){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = '不可取消正在进行中的裁决';
                return json($res);

            }
            InstallationTrialModel::DeleteOAInterface($where);

            $save = array(
                // 'Status' => '0',
                'ApprUseYn' => '0',
                'CfmYn' => '0'
            );
            $where = array(
                'ASNo' => $ASNo
            );
            RecvHandleModel::SaveProc($save,$where);

            $res['data'] = $ASNo;
            return json($res);
        }
    }
    private function SendASemail($ASNo,$UserID,$langCode){
        // $ASNo = "2022040003";
        $config = config('web');

        $data = RecvHandleModel::getAsHandleInfo($ASNo,$langCode);

        $user = UserModel::getUserDeptInfo($UserID);

        $EmpId = str_replace(' ','',$user[0]['emp_code']);

        if(!empty($EmpId)){
            $mulu = $EmpId;
        }else{
            $mulu = str_replace(' ','',$UserID);
        }


        $fun = $config['Handle'];

        $mt_id = $data['ASNo'];
        // $filename = $v['FileNm'];
        $dirname_year = substr($mt_id,0,4);
        $dirname_month = substr($mt_id,4,2);
        $dirname_defualt = substr($mt_id,0,6);
        $file = "static/$fun/$dirname_year/$dirname_month/$mt_id/$mulu/".$mt_id.'.pdf';

        if(!file_exists($file)){
            $res['statusCode'] = 'I452';
            $res['returnMsg'] = "文件不存在存在";
            return json($res);
        }
        $data['ASDate'] = date("Y-m-d",strtotime($data['ASDate']));
        $EmptysH = UserModel::getEmpIDHP($data['EmpId']);
        $data['HP'] = $EmptysH['HP'];
        $data['EmailID'] = $EmptysH['EmailID'];


        $html = '<div style="margin-top:30px;">尊敬的客户您好：</div>';
        $html .= '<div style="margin-bottom:40px;">非常感谢一直以来的支持与配合！</div>';
        $html .= '<div>'.$data['ASDate'].'贵司维修系统事项如下</div>';
        $html .= '<div>1.贵公司模号：'.$data['RefNo'].'</div>';
        $html .= '<div>2.YUDO订单号：'.$data['OrderNo'].'</div>';
        $html .= '<div style="margin-bottom:40px;">3.客户维修报告（见附件PDF）</div>';
        $html .= '<div style="margin-bottom:40px;">
                <div>以上如有任何疑问请随时联系我们</div>
                <div>谢谢支持！</div>
                </div>
                <div>此邮件为系统自动发送！(MP-AS处理)</div>
                <div><span style="font-size:25px;font-weight: bolder;color: #D11341;font-family: fantasy;transform: scale(1.5,1.5);display:inline-block;
                    -ms-transform: scale(1,1.5);
                    -webkit-transform: scale(1,1.5);
                    -moz-transform: scale(1,1.5);
                    -o-transform: scale(1,1.5);">YUDO</span>
                    <span stype="line-height:25px;height:25px;"> Leading Innovation</span></div>
                <div>
                    <p>'.$data['EmpNm'].' | '.$data['DeptNm'].'<p>
                    <p>M: '.$data['HP'].' T: 0512-65048882 E-mail:'.$data['EmailID'].' <p>
                    <p>W: http://www.yudo.com.cn<p>
                    <p>柳道万和（苏州）热流道系统有限公司 | YUDO(SUZHOU) HOT RUNNER SYSTEMS CO., LTD<p>
                    <p>苏州市吴中区甪直镇凌港路29号 | No.29 Ling Gang Road, Wuzhong District, Suzhou City, Jiangsu Province, China<p>
                </div>

                ';

        $list = RecvHandleModel::getASEmalis($data['ASNo']);

        $mail = new JlampMail();

        $mail->setServer("fastsmtphz.qiye.163.com", "sales@yudosuzhou.com", "AB9Q@U*La9jC",25, false); //设置smtp服务器，普通连接方式
        $mail->setFrom("sales@yudosuzhou.com"); //设置发件人
        $data['EmailID'] = empty($list['EmailID'])?$list['EmailID']:(string)$list['EmailID'];
        $data['CEmail'] = empty($list['CEmail'])?$list['CEmail']:(string)$list['CEmail'];
        $data['DEmail'] = empty($list['DEmail'])?$list['DEmail']:(string)$list['DEmail'];
        $data['MEmail'] = empty($list['MEmail'])?$list['MEmail']:(string)$list['MEmail'];
        $data['GMEmail'] = empty($list['GMEmail'])?$list['GMEmail']:(string)$list['GMEmail'];

        $mail->setReceiver($data['CustEmail']); //设置收件人，多个收件人，调用多次
        if($data['DEmail'] != $data['MEmail']){
            $mail->setCc($data['DEmail']); //设置抄送，多个抄送，调用多次
        }
        if($data['MEmail'] != $data['CEmail']){
            $mail->setCc($data['MEmail']); //设置抄送，多个抄送，调用多次
            $mail->setCc($data['CEmail']); //设置抄送，多个抄送，调用多次
        }else{
            $mail->setCc($data['MEmail']); //设置抄送，多个抄送，调用多次
        }
        if(!empty($data['EmailID'])){
            if($data['DEmail'] != $data['EmailID']){
                $mail->setCc($data['EmailID']); //设置抄送，多个抄送，调用多次
            }
        }
        $mail->addAttachment($file); //添加附件，多个附件，调用多次

        $time = date('Y-m-d',time());

        $title = "YUDO - 客户维修报告 -".$data['ASNo']."-".$time."-".$data['DeptNm'];

        $mail->setMail($title, $html); //设置邮件主题、内容

        $send = $mail->sendMail(); //发送

        return json($send);

    }


    public function updataASREcv()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            // $list = RecvHandleModel::updataASREcv();

            // $data = array();
            // foreach ($list as $key => $value) {
            //     // if(empty($value['OldDrawNo'])){
            //     // $orders = RecvHandleModel::updataASOrder($value['OrderNo']);
            //     if(empty($value['ArrivalLeaveNo'])){
            //         $addlist = InstallationTrialModel::getAddressInfo($value['ArrivalLeaveNo']);

            //         $data[] = array(
            //             'ASRecvNo'  => $value['ASRecvNo'],
            //             'ArrivalLeaveNo' => $value['ArrivalLeaveNo'],
            //             'Arrivalphoto' => $value['Arrivalphoto'],
            //             'Arrivalphoto2' => $addlist['Arrivalphoto'],

            //         );
            //         $save = array(
            //             'Arrivalphoto' => $addlist['Arrivalphoto'],
            //         );

            //         $where = arraY(
            //             'ASRecvNo'  => $value['ASRecvNo']
            //         );
            //         // RecvHandleModel::SaveASRecv($save,$where);
            //     }
            //     // }
            // }
            ////安装报告
            // $list = RecvHandleModel::updataANzhucv();
            //  $data = array();
            // foreach ($list as $key => $value) {

            //     if(!empty($value['ArrivalLeaveNo'])){
            //         $addlist = InstallationTrialModel::getAddressInfo($value['ArrivalLeaveNo']);

            //         $data[] = array(
            //             'AssmReptNo'  => $value['AssmReptNo'],
            //             'ArrivalLeaveNo' => $value['ArrivalLeaveNo'],
            //             'Arrivalphoto' => $value['Arrivalphoto'],
            //             'Arrivalphoto2' => $addlist['Arrivalphoto'],

            //         );
            //         $save = array(
            //             'Arrivalphoto' => $addlist['Arrivalphoto'],
            //         );

            //         $where = arraY(
            //             'AssmReptNo'  => $value['AssmReptNo']
            //         );
            //         // InstallationTrialModel::SaveInstall($save,$where);
            //     }

            // }

            ////试模报告
            $list = RecvHandleModel::updataTestucv();
             $data = array();
            foreach ($list as $key => $value) {

                if(!empty($value['ArrivalLeaveNo'])){
                    $addlist = InstallationTrialModel::getAddressInfo($value['ArrivalLeaveNo']);

                    $data[] = array(
                        'TstInjReptNo'  => $value['TstInjReptNo'],
                        'ArrivalLeaveNo' => $value['ArrivalLeaveNo'],
                        'Arrivalphoto' => $value['Arrivalphoto'],
                        'Arrivalphoto2' => $addlist['Arrivalphoto'],

                    );
                    $save = array(
                        'Arrivalphoto' => $addlist['Arrivalphoto'],
                    );

                    $where = arraY(
                        'TstInjReptNo'  => $value['TstInjReptNo']
                    );
                     // TestationTrialModel::SaveTest($save,$where);
                }

            }
            $res['data'] = $data;
            return json($res);

        }

    }


}