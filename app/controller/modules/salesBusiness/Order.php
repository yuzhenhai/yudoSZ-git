<?php
/**
 * @Author: YUZH
 * @Date: 2024-09-30 15:52
 */

namespace app\controller\modules\salesBusiness;

use app\controller\modules\Base;
use app\model\BaseModel;
use app\model\UserModel;
use app\model\salesBusiness\OrderModel;


use think\facade\Request;
use think\facade\Db;
use app\common\Util;

use think\facade\Filesystem;

use app\common\FtpUtil;

// use League\Flysystem\Filesystem;
// use League\Flysystem\Adapter\Ftp;
/**
 * Class DailyData
 * @package app\controller\modules\businessInfo
 * Ã¿ÈÕÍ³¼Æ±êÄ£¿é
 */
class Order extends Base
{
    public function Info()
    {

        if(Request::isPost()){
            $param = Request::param();
            $langCode = $this->langCode($param['LangID']);
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $list = OrderModel::getOrderProgressInfo($param['OrderNo'],$param['type'],$langCode);

            if(empty($list)){
                $res['statusCode'] = '2';
                $res['returnMsg'] = '数据为空';
                return json($res);
            }
            $auth = BaseModel::getAuth('WEI_3100',$param['UserID']);

            // if($auth == 'NO'){
            //     $res['statusCode'] = '5';
            //     $res['returnMsg'] = '没有查看权限5';
            //     return json($res);
            // }


            $list['DelvDate'] = date('Y-m-d',strtotime($list['DelvDate']));
            $list['InvoiceDay'] = date('Y-m-d',strtotime($list['InvoiceDay']));
            $list['OrderDate'] = date('Y-m-d',strtotime($list['OrderDate']));

            //品质信息
            $TQCInsRept00 = OrderModel::TQCInsRept00($list['OrderNo']);

            //Parts List
            $Bom = OrderModel::searchBomList($list['OrderNo']);

            //AS单号
            $asLists = OrderModel::asList($list['OrderNo']);
            $asList = array();
            if(!empty($asLists)){

                foreach ($asLists as $key => $value) {
                    $asList[] = array(
                        'ASRecvNo'  => $value['ASRecvNo'],
                        'ASRecvDate'=> date('Y-m-d',strtotime($value['ASRecvDate'])),
                        'ROW_NUMBER'=> $value['ROW_NUMBER']
                    );
                }
            }

            //营业图图纸
            $TDEDwReg10 = OrderModel::TDEDwReg10($list['DrawNo'],$list['DrawAmd']);



            $tmp = '';
            switch ($list['SpecType']) {
                case '1':
                    $tmp = 'Approval';
                    break;
                case '2':
                    $tmp = 'Manufacture';
                    break;
                case '3':
                    $tmp = 'App^Manu';
                    break;
            }

            if(empty(trim($list['SpecNo']))){
                $list['SpecNo'] = '--';
            }else{
                $list['SpecNo'] = $list['SpecNo'].'/'.$tmp;
            }

            if(empty(trim($list['DrawNo']))){
                $list['DrawNo'] = '--';
            }else{
                $list['DrawNo'] = $list['DrawNo'].'['.$list['DrawAmd'].']';
            }


            if($param['LangID'] == 'CHN'){
                switch ($list['Status']) {
                    case '0':
                        $list['Status'] = '订单';
                        break;
                    case '1':
                        $list['Status'] = '已出库';
                        break;
                    case '2':
                        $list['Status'] = '출고중';
                        break;
                    case '9':
                        $list['Status'] = '中断';
                        break;
                    case 'A':
                        $list['Status'] = '决裁中';
                        break;
                }
            }else if($param['LangID'] == 'KOR'){
                switch ($list['Status']) {
                    case '0':
                        $list['Status'] = '수주';
                        break;
                    case '1':
                        $list['Status'] = '출고완료';
                        break;
                    case '2':
                        $list['Status'] = '출고중';
                        break;
                    case '9':
                        $list['Status'] = '중단';
                        break;
                    case 'A':
                        $list['Status'] = '결재중';
                        break;
                }
            }else if($param['LangID'] == 'ENG'){
                switch ($list['Status']) {
                    case '0':
                        $list['Status'] = 'Order';
                        break;
                    case '1':
                        $list['Status'] = 'Deliveried';
                        break;
                    case '2':
                        $list['Status'] = 'Shipping';
                        break;
                    case '9':
                        $list['Status'] = 'Suspend';
                        break;
                    case 'A':
                        $list['Status'] = 'Waiting for Approval';
                        break;
                }
            }

                 //不良通报
            $Badlist = OrderModel::getBadlist($list['OrderNo']);

            if(!empty($Badlist)){
                $badtype = false;

            }else{
                $badtype = true;
            }


            // 组装/试模
            $Assemble = OrderModel::getAssemble($list['OrderNo']);
            $yudo_user = UserModel::getUserDeptInfo($param['UserID']);


            $data =  array(
                'OrderNo'  => $list['OrderNo'],  //订单号
                'CustNm'   => $list['CustNm'],  //客户名称
                'DeptNm'   => $list['DeptNm'] . '/' . $list['EmpNm'], //销售人员
                'OrderDate' => $list['OrderDate'],    //下单日期
                'DIdate'    =>  $list['DelvDate'] . '/' . $list['InvoiceDay'],  //交货期/出库日期
                'Status'    => $list['Status'],      //订单状态
                'OrderType' => $list['OrderType'],     //订单状态
                'RefNo'     => empty($list['RefNo']) ?'--':$list['RefNo'],       //模号
                'SpecNo'    => $list['SpecNo'],   //技术规范单号
                'DrawNo'    => $list['DrawNo'],    //图纸编号
                'orderForAmt' => $list['orderForAmt'],    //订单金额
    //图纸信息
                'DReqNoYn' => $list['DReqNoYn'],//  == 1 ?'是':'否'0.否 1.是   有无图纸依赖
                'DrawStatus' => $list['DrawStatus'],//图纸状态
                'AptDate'  => empty(trim($list['AptDate'])) ?'--':date('Y-m-d',strtotime($list['AptDate'])),//设计接受日期
                'DwPlanDate'  => empty(trim($list['DwPlanDate'])) ? '--':date('Y-m-d',strtotime($list['DwPlanDate'])),//预计出图日期
                'OutDate'      => empty(trim($list['OutDate'])) ? '--':date('Y-m-d',strtotime($list['OutDate'])),//出图日期

                // 'Bom'         => $Bom,//Parts List
                'yingyeYN'    => $TDEDwReg10['yingyeYN'], //营业图图纸是否存在判断 Y/N
                'MoldFile'    => $TDEDwReg10['fileName'], //营业图图纸

                'IDCardYN'    => $TDEDwReg10['IDCardYN'],//ID卡图纸是否存在判断 Y/N
                'IDCardfileName'    => $TDEDwReg10['IDCardfileName'],//ID卡图纸


    //生产信息
                'WReqNoYn'   => $list['WReqNoYn'],  // == 1?'是':'否'有无生产依赖

                'ProductYn'   => $list['ProductYn'],   //是否完成生产  FinishProduct--------------------------

                'PAptDate'   => empty(trim($list['PAptDate'])) ?'--' :date('Y-m-d',strtotime($list['PAptDate'])),       //生产接收日期
                'WPlanDate'  => empty(trim($list['WPlanDate'])) ?'--' : date('Y-m-d',strtotime($list['WPlanDate'])),  //作业指示日期
                'WDelvDate'  => empty(trim($list['WDelvDate'])) ?'--':date('Y-m-d',strtotime($list['WDelvDate'])),       //生产交期
                'ProductDate'   => empty(trim($list['ProductDate'])) ?'--':date('Y-m-d',strtotime($list['ProductDate'])), //生产完成日期
                'WPlanNo'    => $list['WPlanNo'],   //作业指示编号

    //品质信息
                'badtype'    => $badtype,   //有无不良通报  //不良通报
                //  --  无照片
                'InsReptNo'  => $TQCInsRept00['InsReptNo'],  //检验单号
                'QCDate'     =>  $TQCInsRept00['QCDate'],  //检验日期
                'DeptNm_EmpNm'   => $TQCInsRept00['DeptNm_EmpNm'], //检验人员
                'hasQCPhotos'  => $TQCInsRept00['hasQCPhotos'],//查看检验照片是否存在 Y/N
    //组装/试模
                'AssmReptNo'     => empty($Assemble['AssmReptNo']) ? '&nbsp;&nbsp;':$Assemble['AssmReptNo'],  //组装报告号码
                'AssmReptDate'   => empty($Assemble['AssmReptDate']) ? '&nbsp;&nbsp;':$Assemble['AssmReptDate'],//组装报告日期
                'AssmPeople'     => empty($Assemble['AssmPeople']) ? '&nbsp;&nbsp;':$Assemble['AssmPeople'],//组装人员
                'hasAssemble'    => $Assemble['hasAssemble'],//组装报告图片
                'hasTrialInjection' => $Assemble['hasTrialInjection'],//试模照片
    //AS单号
                // 'asList'   => $asList
                //ASRecvNo => ASRecvDate
            );

            $res['TQCInsRept00'] = $TQCInsRept00;
            $res['data'] = $data;
            $res['Bom'] = $Bom;
            $res['TDEDwReg10'] = $TDEDwReg10;
            $res['asList'] = $asList;
            $res['Badlist'] = $Badlist;
            $res['Assemble'] = $Assemble;


            $where = array(
                'SourceType'    => '1',
                'DeleteYn'      => 'N',
                'SourceNo'      => $data['OrderNo']

            );
            $work_reqno = OrderModel::getTPMWKReq($where);
            $res['work_reqno'] = $work_reqno;
            return json($res);
        }

    }

    public function getOrderList()
    {
        if(Request::isPost()){
            $param = Request::param();

            $res['statusCode'] = '200';
            $config = config('web');

            $list = OrderModel::getOrderList($param['startDate'],$param['endDate'],$param['CustCd'],trim($param['RefNo']));
            $res['data'] = $list;
            if(empty($list)){
                $res['statusCode'] = '2';
                $res['returnMsg'] = '数据为空';
                return json($res);


            }

            return json($res);
        }
    }
    public function getBadlist()
    {
        if(Request::isPost()){
            $param = Request::param();

            $res['statusCode'] = '200';
            $config = config('web');

            $Badlist = OrderModel::getBadlist($param['OrderNo']);

            if($Badlist['hasPhotos'] == 'Y'){
                $this->DownloadOrderPhoto($config['BadReport'],$config['BadReportFTP'],$Badlist['image']);
            }

            $BadPhotos = array();
            foreach ($Badlist['image'] as $key => $value) {
                $mt_id = $value['BadReptNo'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);

                $fileurl = $config['HomeUrl'].$config['BadReport']."/$dirname_year/$dirname_month/$mt_id/";

                $exname = explode('.', $value['FileNm'])[1];
                $BadPhotos[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],

                );
            }
            $Badlist['image'] = $BadPhotos;
            $res['data'] = $Badlist;
            $res['count'] = count($Badlist['image']);

            return json($res);

        }
    }
    /**
     * 试模报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadOrderPhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = $v['BadReptNo'];
                $filename = $v['FileNm'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }


                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');

                    $urlname =  ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk";
                    $file_size = ftp_size($conn, $urlname);
                    if($file_size > 0){
                        // if ($v['FTP_UseYn'] == 'Y') {
                            if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                                return false;
                            }
                        // }
                    }
                }
            }
            ftp_close($conn);
            return true;
    }
    //检验照片
    public function phoneURL(){
        if(Request::isPost()){
            $param = Request::param();

            $res['statusCode'] = '200';
            $config = config('web');

            //品质信息
            $TQCInsRept00 = OrderModel::TQCInsRept00($param['OrderNo']);


            if(count($TQCInsRept00['QCList'])>0){

                $shuju = $this->DownloadOrderInsPhoto($config['InsReport'],$config['InsReportFTP'],$TQCInsRept00['QCList']);

            }

            $InsPhotos = array();
            foreach ($TQCInsRept00['QCList'] as $key => $value) {
                $mt_id = trim($value['InsReptNo']);
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);

                $fileurl = $config['HomeUrl'].$config['InsReport']."/$dirname_year/$dirname_month/$mt_id/";

                $exname = explode('.', $value['FileNm'])[1];
                $InsPhotos[] = array(
                    'name'    => $value['FileNm'],
                    'extname' => $exname,
                    'url' => $fileurl.$value['FileNm'],

                );
            }
            $TQCInsRept00['image'] = $InsPhotos;
            $res['data'] = $TQCInsRept00;
            $res['count'] = count($TQCInsRept00['QCList']);
            $res['shuju'] = $shuju;


            return json($res);
        }
    }

    /**
     * 试模报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadOrderInsPhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = trim($v['InsReptNo']);
                $filename = $v['FileNm'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }
                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');
                    $urlname =  ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk";
                    $file_size = ftp_size($conn, $urlname);
                    if($file_size > 0){
                        // if ($v['FTP_UseYn'] == 'Y') {
                            if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                                return false;
                            }
                        // }
                    }
                }
            }
            ftp_close($conn);
            return true;
    }

    public function getSalesPhoto()
    {
        if(Request::isPost()){
            $param = Request::param();

            $res['statusCode'] = '200';
            $config = config('web');

            // 组装/试模
            $Assemble = OrderModel::getAssemble($param['OrderNo']);

            if(count($Assemble['list'])>0){
                $this->DownloadOrderSalesPhoto($config['Sales'],$config['SalesFTP'],$Assemble['list']);

            }

            $InsPhotos = array();
            foreach ($Assemble['list'] as $key => $value) {
                $mt_id = trim($value['AssmReptNo']);
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $yeardir = "static/".$config['Sales']."/$dirname_year/$dirname_month/$mt_id";
                $fileurl = $config['HomeUrl'].$config['Sales']."/$dirname_year/$dirname_month/$mt_id/";
                $filename = $value['FileNm'];
                if(is_file($yeardir."/$filename")) {
                    $exname = explode('.', $value['FileNm'])[1];
                    $InsPhotos[] = array(
                        'name'    => $value['FileNm'],
                        'extname' => $exname,
                        'url' => $fileurl.$value['FileNm'],

                    );
                }
            }
            $Assemble['image'] = $InsPhotos;
            $res['data'] = $Assemble;
            $res['count'] = count($Assemble['image']);


            return json($res);
        }
    }
    /**
     * 试模报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadOrderSalesPhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = trim($v['AssmReptNo']);
                $filename = $v['FileNm'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }
                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');
                    $urlname =  ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk";
                    $file_size = ftp_size($conn, $urlname);
                    $res['urlname'] = $urlname;
                    $res['file_size'] = $file_size;
                    // return json($res);
                    if($file_size > 0){
                        // if ($v['FTP_UseYn'] == 'Y') {
                            if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                                return false;
                            }
                        // }
                    }
                }
            }
            ftp_close($conn);
            return true;
    }

    public function getTestPhoto()
    {
        if(Request::isPost()){
            $param = Request::param();

            $res['statusCode'] = '200';
            $config = config('web');

            // 组装/试模
            $Assemble = OrderModel::getAssemble($param['OrderNo']);




            if(count($Assemble['list_has'])>0){
                $this->DownloadOrderTestPhoto($config['Test'],$config['TestFTP'],$Assemble['list_has']);

            }

            $InsPhotos = array();
            foreach ($Assemble['list_has'] as $key => $value) {
                $mt_id = trim($value['TstInjReptNo']);
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $yeardir = "static/".$config['Test']."/$dirname_year/$dirname_month/$mt_id";
                $fileurl = $config['HomeUrl'].$config['Test']."/$dirname_year/$dirname_month/$mt_id/";
                $filename = $value['FileNm'];
                if(is_file($yeardir."/$filename")) {
                    $exname = explode('.', $value['FileNm'])[1];
                    $InsPhotos[] = array(
                        'name'    => $value['FileNm'],
                        'extname' => $exname,
                        'url' => $fileurl.$value['FileNm'],

                    );
                }
            }
            $Assemble['image'] = $InsPhotos;
            $res['data'] = $Assemble;
            $res['count'] = count($Assemble['image']);


            return json($res);
        }
    }
    /**
     * 试模报告-图片下载
     * 查询 TASRecv00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    private function DownloadOrderTestPhoto($fun,$funFTP,$list)
    {
            $config = config('web');
            $conn = ftp_connect($config['host'],$config['port']);
            ftp_login($conn,$config['username'],$config['password']);
            ftp_pasv($conn,true);
            foreach ($list as $k => $v){
                $mt_id = trim($v['TstInjReptNo']);
                $filename = $v['FileNm'];
                $dirname_year = substr($mt_id,0,4);
                $dirname_month = substr($mt_id,4,2);
                $yeardir = "static/$fun/$dirname_year/$dirname_month/$mt_id";
                if (!is_dir($yeardir)) {
                    mkdir(iconv("UTF-8", "GBK", $yeardir), 0777, true);
                }
                if(!is_file($yeardir."/$filename")) {
                    $filenameGbk =mb_convert_encoding($filename,'GBK','UTF-8');
                    $urlname =  ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk";
                    $file_size = ftp_size($conn, $urlname);
                    $res['urlname'] = $urlname;
                    $res['file_size'] = $file_size;
                    // return json($res);
                    if($file_size > 0){
                        // if ($v['FTP_UseYn'] == 'Y') {
                            if (!ftp_get($conn, "$yeardir/$filename", ftp_pwd($conn) . $funFTP."/$dirname_year/$dirname_month/$mt_id/$filenameGbk", FTP_BINARY)) {
                                return false;
                            }
                        // }
                    }
                }
            }
            ftp_close($conn);
            return true;
    }
    public function FileURL()
    {
        if(Request::isPost()){
            $param = Request::param();
            $langCode = $this->langCode($param['LangID']);
            $res['statusCode'] = '200';
            $param['UserID'] = $this->getUserId();
            $config = config('web');
            $loginId = $param['UserID'];
            $DrawNo = trim($param['DrawNo']);

            $definition = isset($param['definition'])?$param['definition']:1;


            $fileName = $param['fileName'];
            $type = $param['type'];

            if(empty($fileName)){
                $res['statusCode'] = '1';
                $res['returnMsg'] = '图纸不存在';
                return json($res);
            }
            if($param['type'] == '1'){
                $nows = explode(".pdf",$fileName);
                $pdf_Name = 'yingye';
            }else{
                $pdf_Name = pathinfo($fileName, PATHINFO_FILENAME);
            }
            $nows = explode(".pdf",$fileName);


            $year = substr($DrawNo,0,4);
            $month = substr($DrawNo,4,2);
            $dir = 'static/'.$config['Order'].'/'.$year.'/'.$month.'/'.$DrawNo;
            $imgUrl = $config['Order'].'/'.$year.'/'.$month.'/'.$DrawNo;

            if($definition == 1){

                if(is_file($dir.'/'.$pdf_Name.'.jpg')){

                    $res['data'] = $config['HomeUrl'].$imgUrl.'/'.$pdf_Name.'.jpg';
                }else{
                    $conn = ftp_connect($config['hostFTP'],$config['portFTP']);
                    ftp_login($conn,$config['usernameFTP'],$config['passwordFTP']);
                    ftp_pasv($conn,true);


                    if (!is_dir($dir)) {
                        mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
                    }

                    $year = substr($DrawNo,0,4);
                    $month = substr($DrawNo,4,2);

                    $urlname = '/'.$year.'/'.$month.'/'.$DrawNo.'/'.$fileName;
                    if($param['type'] == '1'){
                        $fileName = 'yingye.pdf';
                    }

                    $file_size = ftp_size($conn, $urlname);

                    if($file_size > 0){
                        if (!ftp_get($conn,$dir.'/'.$fileName, $urlname, FTP_BINARY)) {
                            $res['statusCode'] = '201';
                            $res['returnMsg'] = '图纸获取失败';
                            return json($res);
                        }
                    }else{
                        $res['statusCode'] = '202';
                        $res['returnMsg'] = 'FTP 图纸不存在';
                        return json($res);
                    }

                    ftp_close($conn);
                    Util::pdfToImage($dir.'/'.$fileName,$dir);
                    $res['data'] = $config['HomeUrl'].$imgUrl.'/'.$pdf_Name.'.jpg';

                    if(!is_file($dir.'/'.$pdf_Name.'.jpg')){
                        $res['statusCode'] = '203';
                        $res['returnMsg'] = '图纸损坏，请联系管理员';
                        return json($res);
                    }

                    // if(!is_file($dir.'/'.$nows[0].'.jpg')){
                    //     $res['statusCode'] = '201';
                    //     $ceshi = explode(".pdf",$fileName);
                    //     $outputPath = null;
                    //     $pdfPath = $dir.'/'.$fileName;
                    //     $pdfName = pathinfo($dir.'/'.$fileName, PATHINFO_FILENAME);
                    //     // 如果没有传入输出路径，使用 PDF 文件的目录
                    //     if ($outputPath === null) {
                    //         $outputPath = dirname($pdfPath); // 使用 PDF 文件的目录
                    //     }
                    //     // 设置输出图片的路径（包含文件名）
                    //     $outputImagePath = rtrim($outputPath, '/') . "/$pdfName.jpg"; // 确保路径结尾有斜杠
                    //     $res['outputPath'] = $outputPath;
                    //     $res['pdfName'] = $pdfName;

                    //     $res['outputImagePath'] = $outputImagePath;
                    //     $res['now'] = $ceshi;
                    //     $res['returnMsg'] = '图纸损坏，请联系管理员';
                    //     return json($res);
                    // }
                }
            }else{
                $conn = ftp_connect($config['hostFTP'],$config['portFTP']);
                ftp_login($conn,$config['usernameFTP'],$config['passwordFTP']);
                ftp_pasv($conn,true);

                if (!is_dir($dir)) {
                    mkdir(iconv("UTF-8", "GBK", $dir), 0777, true);
                }

                $year = substr($DrawNo,0,4);
                $month = substr($DrawNo,4,2);

                $urlname = '/'.$year.'/'.$month.'/'.$DrawNo.'/'.$fileName;
                if($param['type'] == '1'){
                    $fileName = 'yingye.pdf';
                }

                $file_size = ftp_size($conn, $urlname);

                if($file_size > 0){
                    if (!ftp_get($conn,$dir.'/'.$fileName, $urlname, FTP_BINARY)) {
                        $res['statusCode'] = '201';
                        $res['returnMsg'] = '图纸获取失败';
                        return json($res);
                    }
                }else{
                    $res['statusCode'] = '202';
                    $res['returnMsg'] = 'FTP 图纸不存在';
                    return json($res);
                }
                if($definition == 2){
                    $resolution = 600;
                }else{
                    $resolution = 1200;
                }
                ftp_close($conn);
                Util::pdfToImage($dir.'/'.$fileName,$dir,$resolution);
                $res['data'] = $config['HomeUrl'].$imgUrl.'/'.$pdf_Name.'.jpg';

                if(!is_file($dir.'/'.$pdf_Name.'.jpg')){
                    $res['statusCode'] = '203';
                    $res['returnMsg'] = '图纸损坏，请联系管理员';
                    return json($res);
                }
            }

            return json($res);
        }
    }
}