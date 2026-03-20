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
class TestationTrial extends Base
{

    /**
     * 订单区分
     * @param $param  试模报告订单区分POST传值
     * @return array
     */

    public function OrderPartition()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $res['statusCode'] = '200';
            $LangID = $config[$param['LangID']];
            $param['UserID'] = $this->getUserId();
            $UserID = $param['UserID'];
            $orderDiv = TestationTrialModel::AsTsmsyco10($LangID);

            $orderQF = array();
            foreach ($orderDiv as $key => $value) {
                $orderQF[] = array(
                    'value' => $value['RelCd1'],
                    'text'  => $value['MinorNm']
                );
            }

            $res['data'] = $orderQF;
            return json($res);
        }
    }

    public function OrderGeneralSearch()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');
            $param['langCode'] = $config[$param['LangID']];
            $param['UserID'] = $this->getUserId();
            $list = TestationTrialModel::OrderSeach($param);
            $res['statusCode'] = '200';

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

    public function orderMinuteTest()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');
            $langCode = $config[$param['LangID']];
            $list = InstallationTrialModel::orderMinute($param['OrderNo'],$langCode);
            $TstInjCnt = TestationTrialModel::getTstInjCnt($param['OrderNo']);
             $list['TstInjCnt'] = $TstInjCnt['TstInjCnt'];
            $res['statusCode'] = '200';
            $res['data'] = $list;




            return json($res);
        }
    }

    public function getCustList()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');
            $langCode = $config[$param['LangID']];
            $CustNo = $param['CustNo'];
            $CustNm = $param['CustNm'];
            $count = $param['count'];

            $list = TestationTrialModel::getCustList($CustNo,$CustNm,$count,$langCode);

            $res['statusCode'] = '200';
            $res['data'] = $list;

            $countM = count($list);
            if($countM == 50){
                $res['countM'] = true;
            }else{
                $res['countM'] = false;
            }

            return json($res);
        }
    }


    public function saveTstInj()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');
            $langCode = $config[$param['LangID']];
            $param['UserID'] = $this->getUserId();
            $UserID = $param['UserID'];
            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            // $res['users'] = $user;
            $login_id = str_replace(' ','',$users['emp_code']);

            if($param['OrderClass'] =='1'){
                $param['OrderSysRegYn'] = 'Y';
                $param['UnRegOrderNo'] = '';

            }else{
                $param['OrderSysRegYn'] = 'N';
                $param['UnRegOrderNo'] = $param['OrderNo'];
                $OrderNo = '';
            }
            $TstInjReptNo = $param['TstInjReptNo'];

            $res['statusCode'] = '200';
            $addlist = InstallationTrialModel::getAddressInfo($param['ArrivalLeaveNo']);
            if(!empty($addlist)){
                // $param['ArrivalTime'] = $addlist['ArrivalDate'];
                // $param['ArrivalLeaveNo'] = $addlist['ArrivalLeaveNo'];
                // $param['ArrivalLocationAddr'] = $addlist['LocationAddr'];
                // $param['ArrivalLat'] = $addlist['GpsLat'];
                // $param['ArrivalLng'] = $addlist['GpsLng'];
                $param['Arrivalphoto'] = $addlist['Arrivalphoto'];
            }

            $TstInjDate = strtotime(date('Y-m-d',strtotime($param['TstInjDate'])));
            $timeAs = strtotime(date('Y-m-d'));
            if($TstInjDate>$timeAs){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = "实际试模日日期不得大于当天";
                return json($res);
            }


            $add = array(
                // 'TstInjReptNo'  =>$post_mtid,           //
                'TstInjReptDate'    => $param['TstInjReptDate'],
                'TstInjDeptCd'      => $param['TstInjDeptCd'],
                'TstInjEmpID'       => $param['TstInjEmpID'],
                // 'JobNo'             => $jobno,
                'TstInjDate'        => $param['TstInjDate'],
                'AssmReptNo'        => $param['AssmReptNo'],
                'TstInjCnt'         => $param['TstInjCnt'],
                'OrderGubun'        => $param['OrderClass'],
                'OrderSysRegYn'     => $param['OrderSysRegYn'],
                'ExpClss'           => $param['ExpClss'],
                'OrderNo'           => $param['OrderNo'],
                'UnRegOrderNo'      => $param['UnRegOrderNo'],
                'GoodNm'            => $param['GoodNm'],
                'RefNo'             => $param['RefNo'],
                'SupplyScope'       => $param['SupplyScope'],
                'HRSystem'          => $param['HRSystem'],
                'ManifoldType'      => $param['ManifoldType'],
                'SystemSize'        => $param['SystemSize'],
                'SystemType'        => $param['SystemType'],
                'GateQty'           => $param['GateQty'],
                'CustCd'            => $param['CustCd'],
                'Material'          => $param['Material'],
                'TstInjPlace'       => $param['TstInjPlace'],
                'InjModel'          => $param['InjModel'],
                'SysTemp'           => $param['SysTemp'],
                'BeforTemp'         => $param['BeforTemp'],

                'AfterTemp'         => $param['AfterTemp'],
                'DryTemp'           => $param['DryTemp'],
                'IDCardYn'          => $param['IDCardYn'],
                'TstInjResult'      => $param['TstInjResult'],
                'ProblemDes'        => $param['ProblemDes'],
                'CauseAnalysis'     => $param['CauseAnalysis'],
                'ProposeSolut'      => $param['ProposeSolut'],
                'InjProcess'        => $param['InjProcess'],
                'Remark'            => $param['Remark'],
                'SysRemark'         => 'mobile-info',       //
                'RegEmpID'          => $UserID,              //
                'RegDate'           => date('Y-m-d H:i:s'),          //
                'UptEmpID'          => $UserID,
                'UptDate'           => date('Y-m-d H:i:s'),
                'ArrivalTime'       => $param['ArrivalTime'],
                'LeaveTime'         => $param['LeaveTime'],
                'ArrivalLeaveNo'    => $param['ArrivalLeaveNo'],
                'ArrivalLat'        => $param['ArrivalLat'],
                'ArrivalLng'        => $param['ArrivalLng'],
                'ArrivalLocationAddr'  => $param['ArrivalLocationAddr'],
                'LeaveLat'          => $param['LeaveLat'],
                'LeaveLng'          => $param['LeaveLng'],
                'LeaveLocationAddr' => $param['LeaveLocationAddr'],
                'Arrivalphoto'      => $param['Arrivalphoto'],
                'Leavephoto'        => $param['Leavephoto'],

            );


            if(empty($param['ArrivalLeaveNo'])){
                unset($add['ArrivalTime']);
                unset($add['ArrivalLeaveNo']);
                unset($add['ArrivalLat']);
                unset($add['ArrivalLng']);
                unset($add['ArrivalLocationAddr']);
                unset($add['Arrivalphoto']);
            }
            if(empty($param['LeaveLat'])){
                unset($add['LeaveLat']);
                unset($add['LeaveLng']);
                unset($add['LeaveTime']);
                unset($add['LeaveLocationAddr']);
                unset($add['Leavephoto']);
            }
            $jobnos = TestationTrialModel::as_jobno(trim($param['TstInjEmpID']));
            $jobno = isset($jobnos['JobNo'])?$jobnos['JobNo']:'';
            $add['JobNo'] = $jobno;
            if(empty($TstInjReptNo)){
                $post_mtid = date('Ym',intval(time()));
                $TesrOne = TestationTrialModel::getTstInjEnd($post_mtid);
                if(empty($TesrOne))
                {
                    $post_mtid = $post_mtid.'0001';
                }
                else
                {
                    $result_mtid = substr($TesrOne['TstInjReptNo'],6);
                    $post_mtid .= $result_mtid;
                    $post_mtid = $post_mtid +1;
                }
                $res['post_mtid'] = $post_mtid;

                $update = true;

                $add['facilityYn'] = 1;
                $add['TstInjReptNo'] = $post_mtid;
                $TstInjReptNo = $post_mtid;
                TestationTrialModel::addTest($add);
            }else{
                $where = array(
                    'SourceNo' => $TstInjReptNo,
                    'SourceType'    => '016'
                );
                $query = BaseModel::OAInterface($where);
                if(!empty($query['SourceNo'])){
                    if($query['OA_Status'] != 5){
                        $res['statusCode'] = 'N003';
                        $returnMsg = '正在提交OA审核，无法修改数据';

                    }
                }

                $list = TestationTrialModel::getTstInjList($TstInjReptNo);

                if($list['facilityYn'] != 1){
                    $res['statusCode'] = 'N003';
                    $returnMsg = '报告是PC端ERP输入，不可修改';
                }
                $add['UptEmpID'] = $login_id;
                $timeU = date("Y-m-d H:i:s");
                $add['UptDate'] = $timeU;
                $where = array(
                    'TstInjReptNo' => $TstInjReptNo
                );
                $update = false;
                $res['list'] = $add;
                TestationTrialModel::SaveTest($add,$where);
            }
            $res['update'] = $update;

            $res['data'] = $TstInjReptNo;
            return json($res);
        }
    }

    public function TestInfo()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();

            // $UserID = $param['UserID'];
            $list = TestationTrialModel::TestInfo($param['TstInjReptNo']);
            $TextSales = TestationTrialModel::getAssmTextSales($param['TstInjReptNo']);
            $list['TestSales'] = $TextSales;
            $w = array(
                    'TstInjReptNo'    => $param['TstInjReptNo']
                );
            // 照片列表
            $TestPhotos = TestationTrialModel::getTestPhotoNm($w);

            $Photos = array();
            $TestPhoto = array();
            foreach ($TestPhotos as $key => $value) {
                $mt_id = $value['TstInjReptNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Test']."/$dirname_year/$dirname_month/$mt_id/";
                $Photos[] = array(
                    'FileNm'    => $fileurl.$value['FileNm'],
                    'FTP_UseYn' => $value['FTP_UseYn'],
                    'TstInjReptNo' => $value['TstInjReptNo'],
                    'Photo' => $value['Photo'],
                    'Seq' => $value['Seq'],
                );

                $exname = explode('.', $value['FileNm'])[1];
                $TestPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'TstInjReptNo' => $value['TstInjReptNo'],
                    'FileNm'    => $fileurl.$value['FileNm'],
                    'FTP_UseYn' => $value['FTP_UseYn'],
                    'Photo' => $value['Photo'],
                    'Seq' => $value['Seq'],
                );
            }

            $res['Photos'] = $Photos;
            $list['TestPhoto'] = $TestPhoto;
            $list['photoCount'] = count($TestPhoto);

            $res['data'] = $list;

            return json($res);
        }
    }

    /**
     * 添加试模报告-同行人员
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public function addTastSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            // $loginID =  $param['UserID'];
            $param['UserID'] = $this->getUserId();
            $where = array(
                'TstInjReptNo'    => $param['NumberNo']
            );

            $count = TestationTrialModel::getSalesD($where);

            $w = array(
                'TstInjReptNo'    => $param['NumberNo'],
                'SaleEmpID'     => $param['EmpID']
            );
            $user = TestationTrialModel::getSalesE($w);
            if($user){
                $res['statusCode'] = '104';
                $res['statusMsg'] = '同行人员已存在';
                return json($res);
            }
            $count += 1;
             $data = array(
                'TstInjReptNo'    => $param['NumberNo'],
                'Seq'           => '0'.$count,
                'SaleEmpID'     => $param['EmpID'],
                'RegEmpID'      => $param['UserID'],
                'RegDate'       => date('Y-m-d H:i:s'),
                'UptEmpID'      => $param['UserID'],
                'UptDate'       => date('Y-m-d H:i:s'),
            );

            TestationTrialModel::addSales($data);

            $list = TestationTrialModel::getTastFellow($param['NumberNo']);
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
    public function DeleteTastSales()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $where = array(
                'TstInjReptNo'    => $param['NumberNo'],
                'Seq'           => $param['Seq']
            );
            TestationTrialModel::DeleteTestSales($where);
            $list = TestationTrialModel::getTastFellow($param['NumberNo']);
            $w = array(
                'TstInjReptNo'    => $param['NumberNo']
            );
            TestationTrialModel::DeleteTestSales($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'TstInjReptNo'    => $param['NumberNo'],
                    'Seq'           => '0'.$count,
                    'SaleEmpID'     => $value['EmpID'],
                    'RegEmpID'      => $param['UserID'],
                    'RegDate'       => date('Y-m-d H:i:s'),
                    'UptEmpID'      => $param['UserID'],
                    'UptDate'       => date('Y-m-d H:i:s'),
                );
                TestationTrialModel::addSales($data);
            }
            $list = TestationTrialModel::getTastFellow($param['NumberNo']);
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
    public function getTestPhotoNm()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $w = array(
                    'TstInjReptNo'    => $param['TstInjReptNo']
                );
            $list = TestationTrialModel::getTestPhotoNm($w);
            $config = config('web');
            $this->DownloadTestPhoto($config['Test'],$config['TestFTP'],$list);



            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['TstInjReptNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Test']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'TstInjReptNo' => $value['TstInjReptNo'],
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
    public function addTestPhotos()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $res['param'] = $param;
            $date = date('Ymdhis',time());
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $mt_id = $param['TstInjReptNo'];
            $UserID = $param['UserID'];

            // $addressL = Apis::geocoder($param['Lng'],$param['Lat'],$config['addres']);
            $address = '';//$addressL->result->formatted_address.$addressL->result->sematic_description;

            $dirname_year = substr($mt_id,0,4);
            $dirname_month = substr($mt_id,4,2);
            $dirname_defualt = substr($mt_id,0,6);
            $fun = $config['Test'];

            $dir = "$fun/$dirname_year/$dirname_month/$mt_id";
            $file = request()->file('file');
            $res['file'] = $file;
            $fileNm = 'M'.$date.rand(100,999).'.jpg';
            $savename = Filesystem::disk('public')->putFileAs($dir, $file,$fileNm);


            $remoteFile = $config['TestFTP']."/$dirname_year/$dirname_month/$mt_id/".$fileNm;
            $localFile = $config['localFile'].$dir.'/'.$fileNm;
            $res['file'] = $remoteFile;
            $res['file2'] = $localFile;

            FtpUtil::ftp_photo($mt_id,$config['TestFTP']);
            if (!FtpUtil::upload($localFile, $remoteFile)) {
                $res['statusCode'] = '105';
                $res['msg'] ="文件上传失败";
            }

            $w = array(
                'TstInjReptNo'    => $mt_id
            );

            $list = TestationTrialModel::getTestPhotoNm($w);
            $res['list'] = $list;
            $counTS = count($list);

            $res['listdate'] = $param;

            $counTS = $counTS + $param['count'] + 1;
            $seq = (int)$counTS>=10?$counTS:'0'.$counTS;

            $data = array(
                'TstInjReptNo'    => $mt_id,
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
                    'TstInjReptNo'    => $mt_id,
                    'Seq' => $data['Seq']
                );
            $Photo = TestationTrialModel::getTestPhoto($where);

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


                TestationTrialModel::addTestPhoto($data);
            }else{
                $count = '';
                for ($i = count($Photo); $i >= 1; $i--) {
                    $sq = $i>=10?$i:'0'.$i;
                    $where = array(
                        'TstInjReptNo'    => $mt_id,
                        'Seq' => $sq
                    );
                    $Photo = TestationTrialModel::getTestPhoto($where);
                    if(empty($Photo)){
                        $count = $i;
                    }
                }
                $seqD = (int)$count>=10?$count:'0'.$count;
                    $data['Seq'] = $seqD;
                    TestationTrialModel::addTestPhoto($data);

            }

            $lists = TestationTrialModel::getTestPhotoNm($w);

            $AssmPhoto = array();
            foreach ($lists as $key => $value) {
                $mt_id = $value['TstInjReptNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Test']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'TstInjReptNo' => $value['TstInjReptNo'],
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
    public function DeleteTastPhone()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $config = config('web');
            $param['UserID'] = $this->getUserId();
            $where = array(
                'TstInjReptNo'    => $param['TstInjReptNo'],
                'FileNm'           => $param['name']
            );
            TestationTrialModel::DeleteTestPhoto($where);
            $w = array(
                'TstInjReptNo'    => $param['TstInjReptNo']
            );
            $list = TestationTrialModel::getTestPhotoNm($w);

            TestationTrialModel::DeleteTestPhoto($w);
            foreach ($list as $key => $value) {
                $count = $key +1;
                $data = array(
                    'TstInjReptNo' => $value['TstInjReptNo'],
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
                TestationTrialModel::addTestPhoto($data);
            }
            $list = TestationTrialModel::getTestPhotoNm($w);
            $AssmPhoto = array();
            foreach ($list as $key => $value) {
                $mt_id = $value['TstInjReptNo'];

                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $dirname_defualt = substr($mt_id,0,6);
                $fileurl = $config['HomeUrl'].$config['Test']."/$dirname_year/$dirname_month/$mt_id/";
                $exname = explode('.', $value['FileNm'])[1];
                $AssmPhoto[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],
                    'TstInjReptNo' => $value['TstInjReptNo'],
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
    private function DownloadTestPhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = $v['TstInjReptNo'];
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


    public function TestsubAdjudication()
    {
        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $w = array(
                'TstInjReptNo'    => $param['TstInjReptNo']
            );
            $post_asid = $param['TstInjReptNo'];

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            // $res['users'] = $user;
            $login_id = str_replace(' ','',$users['emp_code']);

            if(empty($login_id)){
                $res['statusCode'] = 'I994';
                $returnMsg = '职员信息不存在';

            }


            $list = TestationTrialModel::TestInfo($post_asid);
            if(empty($list['LeaveTime'])){
                $res['statusCode'] = 'N003';
                $returnMsg = "请检查离开时间是否存在";
                $res['returnMsg'] = $returnMsg;
                return json($res);
            }
            if($list['facilityYn'] != 1){
                $res['statusCode'] = 'N003';
                $returnMsg = "报告是PC端ERP输入，不可修改";
                $res['returnMsg'] = $returnMsg;
                return json($res);
            }
            $juli1 = Apis::getDistance($list['ArrivalLat'],$list['ArrivalLng'],$list['LeaveLat'],$list['LeaveLng']);
            if($juli1 > 1000){
                $res['statusCode'] = 'N003';
                $returnMsg = "到达距离与离开距离已超出范围";
                $res['returnMsg'] = $returnMsg;
                return json($res);

            }
            if(strtotime($list['LeaveTime']) - strtotime($list['ArrivalTime']) > 86400 ){
                $res['statusCode'] = 'N003';
                $returnMsg = "到达时间与离开时间不得超过24小时";
                $res['returnMsg'] = $returnMsg;
                return json($res);


            }
            if(strtotime($list['ArrivalTime'])>strtotime($list['LeaveTime'])){
                $res['statusCode'] = 'N003';
                $returnMsg = "到达时间->离开时间请检查先后顺序";
                $res['returnMsg'] = $returnMsg;
                return json($res);
            }

            $OAwhere = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '016'
            );
            $query = BaseModel::OAInterface($OAwhere);
             if(!empty($query['SourceNo'])){
                $res['statusCode'] = 'N003';
                $returnMsg = '裁决已经存在';
            }

            if($res['statusCode']!='200'){

                // $returnMsg = mb_convert_encoding($returnMsg, 'UTF-8', 'GBK');


            }

            $add = array(
                'SourceType'  => '016',
                'SourceNo'    => $post_asid,
                'SP_Contents' => "execute dbo.P_SSATstInjReptCfm 'CA','$post_asid','$login_id'",
                'OA_Status'   => '0',
                'RegEmpID'    => $login_id,
                'RegDate'     => date('Y-m-d H:i:s'),
                'UptEmpID'    => $login_id,
                'UptDate'     => date('Y-m-d H:i:s'),
            );
            InstallationTrialModel::addOAInterface($add);
            $OAwhere = array(
                'SourceNo'  => $post_asid,
                'SourceType'    => '016'
            );
            $Interface = BaseModel::OAInterface($OAwhere);

            if(empty($Interface['SourceNo'])){
                $res['statusCode'] = 'N003';
                $returnMsg = '裁决提交失败，请稍后再试';
                $res['returnMsg'] = $returnMsg;
                return json($res);
            }
            $save = array(
              'ApprUseYn' => '1',
            );
            $where = array(
                'TstInjReptNo' => $post_asid
            );
            TestationTrialModel::SaveTest($save,$where);
            $res['data'] = $post_asid;
            return json($res);
        }
    }
    //取消OA审批
    public function unTestSubAdjudication(){

        if(Request::isPost()){
            $param = Request::param();
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $w = array(
                'TstInjReptNo'    => $param['TstInjReptNo']
            );
            $post_asid = $param['TstInjReptNo'];

            $users = UserModel::getUserDeptInfo($param['UserID'])[0];
            // $res['users'] = $user;
            $login_id = str_replace(' ','',$users['emp_code']);
            if(empty($post_asid)){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = "试模不存在";
                return json($res);
            }

            $list = TestationTrialModel::TestInfo($post_asid);
            if($list['facilityYn'] != 1){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = "报告是PC端ERP输入，不可修改";
                return json($res);
            }
            $OAwhere = array(
                'SourceType'  => '016',
                'SourceNo'    => $post_asid,
            );
            $query = BaseModel::OAInterface($OAwhere);
             if(empty($query['SourceNo'])){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = '当前试模还没有申请裁决';
                return json($res);
            }else if($query['OA_Status'] != '5'){
                $res['statusCode'] = 'N003';
                $res['returnMsg'] = '不可取消正在进行中的裁决';
                return json($res);
            }


            InstallationTrialModel::DeleteOAInterface($OAwhere);
            $save = array(
                'ApprUseYn' => '0'
            );
            $where = array(
                'TstInjReptNo' => $post_asid
            );
            TestationTrialModel::SaveTest($save,$where);
            $res['data'] = $post_asid;
            return json($res);
        }
    }
}