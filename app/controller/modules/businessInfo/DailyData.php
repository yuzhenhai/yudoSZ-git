<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-09-30 15:52
 */

namespace app\controller\modules\businessInfo;

use app\controller\modules\Base;
use app\model\BaseModel;
use app\model\UserModel;
use think\facade\Request;
use think\facade\Db;
use think\facade\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
/**
 * Class DailyData
 * @package app\controller\modules\businessInfo
 * 每日统计标模块
 */
class DailyData extends Base
{

//测试数据
    public function index()
    {

        if(Request::isPost()){
            $res['statusCode'] = '200';

            $param = Request::param();

            $LangID = $this->getLangID();
            if($param['db'] == 'ALL' || $param['db'] == 'SZJT' || $param['db'] == 'GDJT' || $param['db'] == 'QDJT' || $param['db'] == 'Exter' || $param['db'] == 'Inter'){


                if($param['db'] == 'SZJT'){

                    $suzhou = array('SZ','HS','SH','LL','LLSZ');
                    $data = array();
                    $ForAmt = 0;
                    $ForAmt_Pre = 0;
                    $param['db'] = 'SZ';
                    $list = $this->getList($param,$LangID);
                    foreach ($list as $key => $val) {
                        $data[$key]['name'] = $val['name'];
                        $data[$key]['ForAmt'] = 0;
                        $data[$key]['ForAmt_Pre'] = 0;

                    }
                    foreach ($suzhou as $value) {
                        $param['db'] = $value;

                        $nows = $this->getList($param,$LangID);
                        foreach ($nows as $key => $val) {
                            $data[$key]['ForAmt'] = $data[$key]['ForAmt'] + $nows[$key]['ForAmt'];
                            $data[$key]['ForAmt_Pre'] = $data[$key]['ForAmt_Pre'] + $nows[$key]['ForAmt_Pre'];
                        }
                    }

                }else if($param['db'] == 'GDJT'){
                    // {value:'GD',text:Langs.uniGD},
                    // {value:'XR',text:Langs.uniSION},
                    $guangd = array('GD','XR');
                    $data = array();
                    $ForAmt = 0;
                    $ForAmt_Pre = 0;
                    $param['db'] = 'GD';
                    $list = $this->getList($param,$LangID);

                    foreach ($list as $key => $val) {
                        $data[$key]['name'] = $val['name'];
                        $data[$key]['ForAmt'] = 0;
                        $data[$key]['ForAmt_Pre'] = 0;

                    }
                    foreach ($guangd as $value) {
                        $param['db'] = $value;

                        $nows = $this->getList($param,$LangID);
                        foreach ($nows as $key => $val) {
                            $data[$key]['ForAmt'] = $data[$key]['ForAmt'] + $nows[$key]['ForAmt'];
                            $data[$key]['ForAmt_Pre'] = $data[$key]['ForAmt_Pre'] + $nows[$key]['ForAmt_Pre'];
                        }
                    }
                }else if($param['db'] == 'QDJT'){
                    // {value:'QD',text:Langs.uniQD},
                    // {value:'CL',text:Langs.uniCL},

                    $qingd = array('QD','CL');
                    $data = array();
                    $ForAmt = 0;
                    $ForAmt_Pre = 0;
                    $param['db'] = 'GD';
                    $list = $this->getList($param,$LangID);

                    foreach ($list as $key => $val) {
                        $data[$key]['name'] = $val['name'];
                        $data[$key]['ForAmt'] = 0;
                        $data[$key]['ForAmt_Pre'] = 0;

                    }
                    foreach ($qingd as $value) {
                        $param['db'] = $value;

                        $nows = $this->getList($param,$LangID);
                        foreach ($nows as $key => $val) {
                            $data[$key]['ForAmt'] = $data[$key]['ForAmt'] + $nows[$key]['ForAmt'];
                            $data[$key]['ForAmt_Pre'] = $data[$key]['ForAmt_Pre'] + $nows[$key]['ForAmt_Pre'];
                        }
                    }
                }else if($param['db'] == 'Exter'){

                    $Exter = array('SZ','GD','QD','XR','HS','LLSZ','CL');
                    // $Exter = array('LLSZ');
                    $data = array();
                    $ForAmt = 0;
                    $ForAmt_Pre = 0;
                    $param['db'] = 'SZ';
                    $list = $this->getList($param,$LangID);
                    foreach ($list as $key => $val) {
                        $data[$key]['name'] = $val['name'];
                        $data[$key]['ForAmt'] = 0;
                        $data[$key]['ForAmt_Pre'] = 0;
                    }
                    foreach ($Exter as $value) {
                        $param['db'] = $value;
                        //LLSZ  正式库上面所有数据都是External
                        if($param['db'] == 'LLSZ'){
                            $nows = $this->getList($param,$LangID);

                            foreach ($nows as $key => $val) {
                                switch ($val['Sort']) {
                                    case '110':
                                        $nows[$key]['Sort'] = '100';
                                        break;
                                    case '210':
                                        $nows[$key]['Sort'] = '200';
                                        break;
                                    case '310':
                                        $nows[$key]['Sort'] = '300';
                                        break;
                                    case '410':
                                        $nows[$key]['Sort'] = '400';
                                        break;
                                    case '510':
                                        $nows[$key]['Sort'] = '500';
                                        break;
                                }
                            }
                        }else{
                            $nows = $this->getList($param,$LangID,'External');
                        }


                        foreach ($nows as $key => $val) {
                            if($val['Sort'] == '100'){
                                $data[0]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[0]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '200'){
                                $data[1]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[1]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '300'){
                                $data[2]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[2]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '400'){
                                $data[3]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[3]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '500'){
                                $data[4]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[4]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }
                            if($param['db'] == "CL"){
                                $data[$key]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[$key]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }

                        }
                    }
                }else if($param['db'] == 'Inter'){
                    // LLSZ  正式库出口没有数据
                    $Inter = array('SZ','GD','QD','XR','HS','SH','LL','ABE');
                    // $Exter = array('LLSZ');
                    $data = array();
                    $ForAmt = 0;
                    $ForAmt_Pre = 0;
                    $param['db'] = 'SZ';
                    $list = $this->getList($param,$LangID);
                    foreach ($list as $key => $val) {
                        $data[$key]['name'] = $val['name'];
                        $data[$key]['ForAmt'] = 0;
                        $data[$key]['ForAmt_Pre'] = 0;
                    }
                    foreach ($Inter as $value) {
                        $param['db'] = $value;

                        $nows = $this->getList($param,$LangID,'Internal');

                        foreach ($nows as $key => $val) {
                            if($val['Sort'] == '100'){
                                $data[0]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[0]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '200'){
                                $data[1]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[1]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '300'){
                                $data[2]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[2]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '400'){
                                $data[3]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[3]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }else if($val['Sort'] == '500'){
                                $data[4]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[4]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }
                            if($param['db'] == "SH" || $param['db'] == "LL" || $param['db'] == "ABE"){
                                $data[$key]['ForAmt'] += $nows[$key]['ForAmt'];
                                $data[$key]['ForAmt_Pre'] += $nows[$key]['ForAmt_Pre'];
                            }
                        }

                    }
                }else if($param['db'] == 'ALL'){
                    // LLSZ  正式库出口没有数据
                    $ALL = array('SZ','GD','QD','HS','XR','SH','LL','CL','ABE');

                    $data = array();
                    $ForAmt = 0;
                    $ForAmt_Pre = 0;
                    $param['db'] = 'SZ';
                    $list = $this->getList($param,$LangID);
                    foreach ($list as $key => $val) {
                        $data[$key]['name'] = $val['name'];
                        $data[$key]['ForAmt'] = 0;
                        $data[$key]['ForAmt_Pre'] = 0;
                    }
                    foreach ($ALL as $value) {
                        $param['db'] = $value;

                        $nows = $this->getList($param,$LangID);
                        foreach ($nows as $key => $val) {
                            $data[$key]['ForAmt'] = $data[$key]['ForAmt'] + $nows[$key]['ForAmt'];
                            $data[$key]['ForAmt_Pre'] = $data[$key]['ForAmt_Pre'] + $nows[$key]['ForAmt_Pre'];
                        }
                    }
                }
            }else{

                $data = $this->getList($param,$LangID);
            }
            foreach ($data as $key => $value) {

                $data[$key]['ForAmt'] = number_format($value['ForAmt'],2);
                $data[$key]['ForAmt_Pre'] = number_format($value['ForAmt_Pre'],2);

                $data[$key]['percent'] = sprintf("%.2f",(((float)$value['ForAmt']-(float)$value['ForAmt_Pre'])/((float)$value['ForAmt_Pre'] == 0 ?100:(float)$value['ForAmt_Pre']))*100);
                if($data[$key]['percent']<0){
                    $data[$key]['percentColor'] = '#07be00';
                }else{
                     $data[$key]['percentColor'] = '#ff6259';
                }

            }
            $res['data'] = $data;
        }

        // $logins = array($input,$output);
        exit(json_encode($res));
    }




    protected function getList($param,$LangID,$type="TOTAL")
    {
        $DB = $this->db($param['db']);
        $langs = $this->Language($LangID);
        $pBaseDt = mb_ereg_replace('-', '', $param['riqi']);
        $res = array();
        if( $param['db'] == 'SH' ||  $param['db'] == 'LL' || $param['db'] == 'CL' || $param['db'] == 'ABE') {// $LL == 'LL' ||$LLSZ == 'LLSZ' ||

            // $baseDate
            if($param['db'] == 'SH'){
                $company = 62;
            }else if($param['db'] == 'LL'){
                $company =13;
            // }else if($LLSZ == 'LLSZ'){
            //      $company = 63;
            } else if($param['db'] == 'CL'){
                 $company = 58;
            }else if($param['db'] == 'ABE'){
                 $company = 61;
            }
            if($param['date'] == 'Y'){
                $GU = 'YEAR';
            }else if($param['date'] == 'MS'){
                $GU = 'MONTHTOTAL';
            }else if($param['date'] == 'M'){
                 $GU = 'MONTH';
            } else{
                 $GU = 'DAY';
            }

            $input = ' @p_Gubun = ?,@p_BaseDate = ?,@P_Company=?,@p_LangID=?';
            $output = [$GU, $param['riqi'],$company,$LangID];
            $list = BaseModel::execSp('dbo.P_SASalAmt_Q_API',$input,$output,$DB);


            $nuws = array();
            foreach ($list as $key => $value) {
                $Opercent = $value['OrderForAmt_Pre']>0?sprintf("%.2f",(float)(($value['OrderForAmt']-$value['OrderForAmt_Pre'])/$value['OrderForAmt_Pre']*100)).'%':'0';
                if($Opercent<0){
                    $OpercentColor = '#07be00';
                }else{
                    $OpercentColor = '#ff6259';
                }
                $nuws[] = array(
                    'name'      => $langs['orderAmt'],
                    'ForAmt'      => (float)$value['OrderForAmt']/10000,
                    'ForAmt_Pre'      => (float)$value['OrderForAmt_Pre']/10000,
                    'ExternalGubnNm'      => 'TOTAL',
                    'percent'     => $Opercent,
                        'percentColor'  => $OpercentColor,
                    'Sort'      => '110',
                );
                $Ipercent = $value['InvoiceForAmt_Pre']>0?sprintf("%.2f",(float)(($value['InvoiceForAmt']-$value['InvoiceForAmt_Pre'])/$value['InvoiceForAmt_Pre']*100)).'%':'0';
                if($Ipercent<0){
                    $IpercentColor = '#07be00';
                }else{
                    $IpercentColor = '#ff6259';
                }
                $nuws[] = array(
                    'name'      => $langs['InvoiceAmt'],
                    'ForAmt'      => (float)$value['InvoiceForAmt']/10000,
                    'ForAmt_Pre'      => (float)$value['InvoiceForAmt_Pre']/10000,
                    'ExternalGubnNm'      => 'TOTAL',
                    'percent'     => $Ipercent,
                        'percentColor'  => $IpercentColor,
                    'Sort'      => '210',
                );
                $Bpercent = $value['BillForAmt_Pre']>0?sprintf("%.2f",(float)(($value['BillForAmt']-$value['BillForAmt_Pre'])/$value['BillForAmt_Pre']*100)).'%':'0';
                if($Bpercent<0){
                    $BpercentColor = '#07be00';
                }else{
                    $BpercentColor = '#ff6259';
                }
                $nuws[] = array(
                    'name'      => $langs['BillAmt'],
                    'ForAmt'      => (float)$value['BillForAmt']/10000,
                    'ForAmt_Pre'      => (float)$value['BillForAmt_Pre']/10000,
                    'ExternalGubnNm'      => 'TOTAL',
                    'percent'     => $Bpercent,
                        'percentColor'  => $BpercentColor,
                    'Sort'      => '310',
                );
                $Rpercent = $value['ReceiptForAmt_Pre']>0?sprintf("%.2f",(float)(($value['BillForAmt']-$value['ReceiptForAmt_Pre'])/$value['ReceiptForAmt_Pre']*100)).'%':'0';
                if($Rpercent<0){
                    $RpercentColor = '#07be00';
                }else{
                    $RpercentColor = '#ff6259';
                }
                $nuws[] = array(
                    'name'      => $langs['receivableAmt'],
                    'ForAmt'      => (float)$value['ReceiptForAmt']/10000,
                    'ForAmt_Pre'      => (float)$value['ReceiptForAmt_Pre']/10000,
                    'ExternalGubnNm'      => 'TOTAL',
                    'percent'     => $Rpercent,
                        'percentColor'  => $RpercentColor,
                    'Sort'      => '410',
                );
                $Ppercent = $value['ProductrForAmt_Pre']>0?sprintf("%.2f",(float)(($value['ProductrForAmt']-$value['ProductrForAmt_Pre'])/$value['ProductrForAmt_Pre']*100)).'%':'0';
                if($Ppercent<0){
                    $PpercentColor = '#07be00';
                }else{
                    $PpercentColor = '#ff6259';
                }
                $nuws[] = array(
                    'name'      => $langs['productionAmt'],
                    'ForAmt'      => sprintf("%.2f",(float)$value['ProductrForAmt']/10000),
                    'ForAmt_Pre'      => sprintf("%.2f",(float)$value['ProductrForAmt_Pre']/10000),
                    'ExternalGubnNm'      => 'TOTAL',
                    'percent'     => $Ppercent,
                        'percentColor'  => $PpercentColor,
                    'Sort'      => '510',
                );
            }

            $res = $nuws;


        }else{

            // // dump($config['connections'][$config['default']]['database']);
            $spName = 'dbo.SSADayTotal_SZ2_M2';
            $input = ' @pWorkingTag = ?,@pBaseDt = ?,@pLangCd=?';
            $output = [$param['date'], $pBaseDt,$LangID];
            $list = BaseModel::execSp($spName,$input,$output,$DB);


            foreach ($list as $key => $value) {
                if($value['ExternalGubnNm'] == $type && $type == 'TOTAL'){

                    $title = '';
                    switch ($value['Sort']) {
                        case '110':
                            $title = $langs['orderAmt'];
                            break;
                        case '210':
                            $title = $langs['InvoiceAmt'];
                            break;
                        case '310':
                            $title = $langs['BillAmt'];
                            break;
                        case '410':
                            $title = $langs['receivableAmt'];
                            break;
                        case '510':
                            $title = $langs['productionAmt'];
                            break;
                    }
                    $percent = $value['ForAmt_Pre']>0?sprintf("%.2f",(float)(($value['ForAmt']-$value['ForAmt_Pre'])/$value['ForAmt_Pre']*100)).'%':'0%';
                    if($percent<0){
                        $percentColor = '#07be00';
                    }else{
                        $percentColor = '#ff6259';
                    }
                    $res[] = array(
                        'name'      => $title,
                        'ForAmt'      => (float)$value['ForAmt'],
                        'ForAmt_Pre'      => (float)$value['ForAmt_Pre'],
                        'ExternalGubnNm'      => $value['ExternalGubnNm'],
                        'percent'     => $percent,
                        'percentColor'  => $percentColor,
                        'Sort'      => $value['Sort'],
                    );
                }else if($value['ExternalGubnNm'] == $type && $type == 'External'){

                    $title = '';

                        switch ($value['Sort']) {
                                case '100':
                                    $title = $langs['orderAmt'];
                                    break;
                                case '200':
                                    $title = $langs['InvoiceAmt'];
                                    break;
                                case '300':
                                    $title = $langs['BillAmt'];
                                    break;
                                case '400':
                                    $title = $langs['receivableAmt'];
                                    break;
                                case '500':
                                    $title = $langs['productionAmt'];
                                    break;
                            }
                        $percent = $value['ForAmt_Pre']>0?sprintf("%.2f",(float)(($value['ForAmt']-$value['ForAmt_Pre'])/$value['ForAmt_Pre']*100)).'%':'0%';
                        if($percent<0){
                            $percentColor = '#07be00';
                        }else{
                            $percentColor = '#ff6259';
                        }
                        $res[] = array(
                            'name'      => $title,
                            'ForAmt'      => (float)$value['ForAmt'],
                            'ForAmt_Pre'      => (float)$value['ForAmt_Pre'],
                            'ExternalGubnNm'      => $value['ExternalGubnNm'],
                            'percent'     => $percent,
                            'percentColor'  => $percentColor,
                            'Sort'      => $value['Sort'],
                        );

                }else if($value['ExternalGubnNm'] == $type && $type == 'Internal'){
                    $title = '';

                    switch ($value['Sort']) {
                        case '100':
                            $title = $langs['orderAmt'];
                            break;
                        case '200':
                            $title = $langs['InvoiceAmt'];
                            break;
                        case '300':
                            $title = $langs['BillAmt'];
                            break;
                        case '400':
                            $title = $langs['receivableAmt'];
                            break;
                        case '500':
                            $title = $langs['productionAmt'];
                            break;
                    }
                    $percent = $value['ForAmt_Pre']>0?sprintf("%.2f",(float)(($value['ForAmt']-$value['ForAmt_Pre'])/$value['ForAmt_Pre']*100)).'%':'0%';
                    if($percent<0){
                        $percentColor = '#07be00';
                    }else{
                        $percentColor = '#ff6259';
                    }
                    $res[] = array(
                        'name'      => $title,
                        'ForAmt'      => (float)$value['ForAmt'],
                        'ForAmt_Pre'      => (float)$value['ForAmt_Pre'],
                        'ExternalGubnNm'      => $value['ExternalGubnNm'],
                        'percent'     => $percent,
                        'percentColor'  => $percentColor,
                        'Sort'      => $value['Sort'],
                    );
                }

            }
        }
        return $res;




    }

    public function info()
    {

        if(Request::isPost()){
            $res['statusCode'] = '200';

            $param = Request::param();

            $LangID = 'CHN';
            $Langs = $this->Language($LangID);
            if($param['db'] == 'ALL' || $param['db'] == 'SZJT' || $param['db'] == 'GDJT' || $param['db'] == 'QDJT' || $param['db'] == 'Exter' || $param['db'] == 'Inter'){

                // getJituan($param,$LangID){
                if($param['db'] == 'SZJT' || $param['db'] == 'GDJT' || $param['db'] == 'QDJT'){
                    $data = $this->getJituan($param,$LangID);
                    $data = $this->shuju($data);
                }else if($param['db'] == 'Exter' || $param['db'] == 'Inter'){
                    $exters = array('SZJT','GDJT','QDJT');
                    $lists = array();

                    $TforAmt = 0;
                    $TforAmt_pro = 0;
                    if($param['db'] == 'Exter'){
                        foreach ($exters as $value) {
                           $param['db'] = $value;
                           $lists = $this->getJituan($param,$LangID,'Exter');
                           $data[$value] = $lists;
                        }
                        foreach ($data as $key => $value) {
                            $TforAmt += $value['ForAmtE'];
                            $TforAmt_pro +=$value['ForAmtE_Pre'];
                            $data[$key] = $this->shuju($value);
                        }


                        $data['nameA'] = 'ABIDO Electronis';
                        $data['ForAmtA'] = number_format(0,2);
                        $data['ForAmtA_Pre'] = number_format(0,2);
                        $data['percentA'] = sprintf("%.2f",0);
                        $data['percentColorA'] = '#e02a27';

                    }else{
                        foreach ($exters as $value) {
                           $param['db'] = $value;
                           $lists = $this->getJituan($param,$LangID,'Inter');
                           $data[$value] = $lists;
                        }
                        foreach ($data as $key => $value) {

                            $TforAmt += $value['ForAmtI'];
                            $TforAmt_pro +=$value['ForAmtT_Pre'];
                            $data[$key] = $this->shuju($value);
                        }
                        $param['db'] = 'ABE';
                        $nowsA = $this->getListInfo($param,$LangID);
                        // exit(json_encode($nowsA));
                        $data['nameA'] = 'ABIDO Electronis';
                        $data['ForAmtA'] = number_format($nowsA['ForAmtT'],2);
                        $data['ForAmtA_Pre'] = number_format($nowsA['ForAmtT_Pre'],2);
                        $percentA = $nowsA['ForAmtT_Pre']>0?($nowsA['ForAmtT']-$nowsA['ForAmtT_Pre'])/$nowsA['ForAmtT_Pre']*100:0;
                        $data['percentA'] = sprintf("%.2f",$percentA);
                        $data['percentColorA'] = $percentA<0?'#07be00':'#e02a27';

                        $TforAmt += $nowsA['ForAmtT'];
                        $TforAmt_pro += $nowsA['ForAmtT_Pre'];

                    }

                    $data['nameT'] = 'Total';
                    $data['ForAmtT'] = number_format($TforAmt,2);
                    $data['ForAmtT_Pre'] = number_format($TforAmt_pro,2);
                    $percentT = $TforAmt_pro>0?($TforAmt-$TforAmt_pro)/$TforAmt_pro*100:0;
                    $data['percentT'] = sprintf("%.2f",$percentT);
                    $data['percentColorT'] = $percentT<0?'#07be00':'#e02a27';

                }else if($param['db'] == 'ALL'){




                    $Alls = array('SZ','GD','QD','HS','XR','SH','LL','LLSZ','CL','ABE');

                    $szjt = array('SZ','HS','SH','LL','LLSZ');
                    $gdjt = array('GD','XR');
                    $qdjt = array('QD','CL');
                    $ForAmtSZE = 0;
                    $ForAmt_PreSZE = 0;
                    $ForAmtGDE = 0;
                    $ForAmt_PreGDE = 0;
                    $ForAmtQDE = 0;
                    $ForAmt_PreQDE = 0;

                    $ForAmtSZI = 0;
                    $ForAmt_PreSZI = 0;
                    $ForAmtGDI = 0;
                    $ForAmt_PreGDI = 0;
                    $ForAmtQDI = 0;
                    $ForAmt_PreQDI = 0;

                    $ForAmtSZT = 0;
                    $ForAmt_PreSZT = 0;
                    $ForAmtGDT = 0;
                    $ForAmt_PreGDT = 0;
                    $ForAmtQDT = 0;
                    $ForAmt_PreQDT = 0;

                    $ForAmtA = 0;
                    $ForAmtA_Pre = 0;

                    $szjtlist = array();
                    $gdjtlist = array();
                    $qdjtlist = array();
                    foreach ($Alls as $value) {
                       $param['db'] = $value;
                       $nows = $this->getListInfo($param,$LangID,'Inter');
                       // $data[$value] = $lists;
                       if(in_array($value,$szjt)){

                            switch ($value) {
                                case 'SZ':
                                    $name = $Langs['uniSZ'];
                                    break;
                                case 'HS':
                                    $name = $Langs['uniHANS'];
                                    break;
                                case 'SH':

                                    $name = $Langs['uniSH'];
                                    $nows['ForAmtE'] = 0;
                                    $nows['ForAmtE_Pre'] = 0;
                                    $nows['ForAmtI'] = $nows['ForAmtT'];
                                    $nows['ForAmtI_Pre'] = $nows['ForAmtT_Pre'];
                                    break;
                                case 'LL':
                                    $name = $Langs['uniLL'];
                                    $nows['ForAmtE'] = 0;
                                    $nows['ForAmtE_Pre'] = 0;
                                    $nows['ForAmtI'] = $nows['ForAmtT'];
                                    $nows['ForAmtI_Pre'] = $nows['ForAmtT_Pre'];
                                    break;
                                case 'LLSZ':

                                    $name = $Langs['uniLLSZ'];

                                    $nows['ForAmtE'] = 0;
                                    $nows['ForAmtE_Pre'] = 0;
                                    $nows['ForAmtI'] = 0;
                                    $nows['ForAmtI_Pre'] = 0;
                                    $nows['ForAmtT'] = 0;
                                    $nows['ForAmtT_Pre'] = 0;
                                    break;
                            }

                            $szjtlist[] = array(
                                'name'  => $name,
                                'ForAmt'    => number_format($nows['ForAmtT'],2),
                                'ForAmt_Pre'    => number_format($nows['ForAmtT_Pre'],2),
                                'percent'   => sprintf("%.2f",$nows['percentT']),
                                'percentColor'   => $nows['percentColorT'],
                            );

                            $ForAmtSZE += $nows['ForAmtE'];
                            $ForAmt_PreSZE += $nows['ForAmtE_Pre'];
                            $ForAmtSZI += $nows['ForAmtI'];
                            $ForAmt_PreSZI += $nows['ForAmtI_Pre'];
                            $ForAmtSZT += $nows['ForAmtT'];
                            $ForAmt_PreSZT += $nows['ForAmtT_Pre'];



                       }else if(in_array($value,$gdjt)){
                            switch ($value) {
                                case 'GD':
                                    $name = $Langs['uniGD'];
                                    break;
                                case 'XR':
                                    $name = $Langs['uniSION'];
                                    break;

                            }
                            $gdjtlist[] = array(
                                'name'  => $name,
                                'ForAmt'    => number_format($nows['ForAmtT'],2),
                                'ForAmt_Pre'    => number_format($nows['ForAmtT_Pre'],2),
                                'percent'   => sprintf("%.2f",$nows['percentT']),
                                'percentColor'   => $nows['percentColorT'],
                            );
                            $ForAmtGDE += $nows['ForAmtE'];
                            $ForAmt_PreGDE += $nows['ForAmtE_Pre'];
                            $ForAmtGDI += $nows['ForAmtI'];
                            $ForAmt_PreGDI += $nows['ForAmtI_Pre'];
                            $ForAmtGDT += $nows['ForAmtT'];
                            $ForAmt_PreGDT += $nows['ForAmtT_Pre'];
                       }else if(in_array($value,$qdjt)){
                            switch ($param['db']) {
                                case 'QD':
                                    $name = $Langs['uniQD'];
                                    $nows['ForAmtE'] = 0;
                                    $nows['ForAmtE_Pre'] = 0;
                                    $nows['ForAmtI'] = $nows['ForAmtT'];
                                    $nows['ForAmtI_Pre'] = $nows['ForAmtT_Pre'];
                                    break;
                                case 'CL':
                                    $name = $Langs['uniCL'];
                                    $nows['ForAmtE'] = $nows['ForAmtT'];
                                    $nows['ForAmtE_Pre'] = $nows['ForAmtT_Pre'];
                                    $nows['ForAmtI'] = 0;
                                    $nows['ForAmtI_Pre'] = 0;
                                    break;

                            }
                            $qdjtlist[] = array(
                                'name'  => $name,
                                'ForAmt'    => number_format($nows['ForAmtT'],2),
                                'ForAmt_Pre'    => number_format($nows['ForAmtT_Pre'],2),
                                'percent'   => sprintf("%.2f",$nows['percentT']),
                                'percentColor'   => $nows['percentColorT'],
                            );
                            $ForAmtQDE += $nows['ForAmtE'];
                            $ForAmt_PreSZE += $nows['ForAmtE_Pre'];
                            $ForAmtQDI += $nows['ForAmtI'];
                            $ForAmt_PreQDI += $nows['ForAmtI_Pre'];
                            $ForAmtQDT += $nows['ForAmtT'];
                            $ForAmt_PreQDT += $nows['ForAmtT_Pre'];
                       }else if($value == 'ABE'){

                            $ForAmtA = $nows['ForAmtT'];
                            $ForAmtA_Pre = $nows['ForAmtT_Pre'];
                            $percentA = $nows['percentT'];
                            $percentColorA = $nows['percentColorT'];

                       }

                    }
                    $exters = $ForAmtSZE + $ForAmtGDE + $ForAmtQDE;
                    $exters_p = $ForAmt_PreSZE + $ForAmt_PreGDE + $ForAmt_PreQDE;


                    $inters = $ForAmtSZI + $ForAmtGDI + $ForAmtQDI + $ForAmtA;
                    $inters_p = $ForAmt_PreSZI + $ForAmt_PreGDI + $ForAmt_PreQDI + $ForAmtA_Pre;

                    $totals = $ForAmtSZT + $ForAmtGDT + $ForAmtQDT + $ForAmtA;
                    $totals_p = $ForAmt_PreSZT + $ForAmt_PreGDT + $ForAmt_PreQDT+ $ForAmtA_Pre;


                    $percentSZT = $ForAmt_PreSZT>0?($ForAmtSZT-$ForAmt_PreSZT)/$ForAmt_PreSZT*100:0;
                    $percentColorSZT = $percentSZT<0?'#07be00':'#e02a27';

                    $percentGDT = $ForAmt_PreGDT>0?($ForAmtGDT-$ForAmt_PreGDT)/$ForAmt_PreGDT*100:0;
                    $percentColorGDT = $percentGDT<0?'#07be00':'#e02a27';

                    $percentQDT = $ForAmt_PreQDT>0?($ForAmtQDT-$ForAmt_PreQDT)/$ForAmt_PreQDT*100:0;
                    $percentColorQDT = $percentQDT<0?'#07be00':'#e02a27';

                    $percentEx = $exters_p>0?($exters-$exters_p)/$exters_p*100:0;
                    $percentColorEx = $percentEx<0?'#07be00':'#e02a27';

                    $percentIn = $inters_p>0?($inters-$inters_p)/$inters_p*100:0;
                    $percentColorIn = $percentIn<0?'#07be00':'#e02a27';

                    $percentTo = $totals_p>0?($totals-$totals_p)/$totals_p*100:0;
                    $percentColorTo = $percentTo<0?'#07be00':'#e02a27';

                    $data = array(
                        'nameSZ'        => $Langs['uniSZgroup'],
                        'nameGD'        => $Langs['uniGDgroup'],
                        'nameQD'        => $Langs['uniQDgroup'],
                        'nameE'         => 'External',
                        'nameI'         => 'Internal',
                        'nameT'         => 'Total',
                        'nameA'         => 'ABIDO Electronis',

                        'exter'         => number_format($exters,2),
                        'exterp'        => number_format($exters_p,2),
                        'percentEx'     => sprintf("%.2f",$percentEx),
                        'percentColorEx'=> $percentColorEx,



                        'inter'         => number_format($inters,2),
                        'interp'        => number_format($inters_p,2),
                        'percentIn'     => sprintf("%.2f",$percentIn),
                        'percentColorIn'=> $percentColorIn,

                        'total'         => number_format($totals,2),
                        'totalp'        => number_format($totals_p,2),
                        'percentTo'     => sprintf("%.2f",$percentTo),
                        'percentColorTo'=> $percentColorTo,

                        'ForAmtA'       => number_format($ForAmtA,2),
                        'ForAmtA_Pre'   => number_format($ForAmtA_Pre,2),
                        'percentA'      => sprintf("%.2f",$percentA),
                        'percentColorA' => $percentColorA,


                        'ForAmtSZT'     => number_format($ForAmtSZT,2),
                        'ForAmt_PreSZT' => number_format($ForAmt_PreSZT,2),
                        'percentSZT'    => sprintf("%.2f",$percentSZT),
                        'percentColorSZT'=> $percentColorSZT,

                        'ForAmtGDT'     => number_format($ForAmtGDT,2),
                        'ForAmt_PreGDT' => number_format($ForAmt_PreGDT,2),
                        'percentGDT'    => sprintf("%.2f",$percentGDT),
                        'percentColorGDT'=> $percentColorGDT,

                        'ForAmtQDT'     => number_format($ForAmtQDT,2),
                        'ForAmt_PreQDT' => number_format($ForAmt_PreQDT,2),
                        'percentQDT'    => sprintf("%.2f",$percentQDT),
                        'percentColorQDT'    => $percentColorQDT,

                        'SZJT'          => $szjtlist,
                        'GDJT'          => $gdjtlist,
                        'QDJT'          => $qdjtlist,


                    );


                }
            }else{

                $data = $this->getListInfo($param,$LangID);
                $data['ForAmtE'] = number_format($data['ForAmtE'],2);
                $data['ForAmtE_Pre'] = number_format($data['ForAmtE_Pre'],2);
                $data['ForAmtI'] = number_format($data['ForAmtI'],2);
                $data['ForAmtI_Pre'] = number_format($data['ForAmtI_Pre'],2);
                $data['ForAmtT'] = number_format($data['ForAmtT'],2);
                $data['ForAmtT_Pre'] = number_format($data['ForAmtT_Pre'],2);
                foreach ($data['Exter'] as $key => $value) {
                    $data['Exter'][$key]['ForAmt'] = number_format($value['ForAmt'],2);
                    $data['Exter'][$key]['ForAmt_Pre'] = number_format($value['ForAmt_Pre'],2);
                }
                foreach ($data['Inter'] as $key => $value) {
                    $data['Inter'][$key]['ForAmt'] = number_format($value['ForAmt'],2);
                    $data['Inter'][$key]['ForAmt_Pre'] = number_format($value['ForAmt_Pre'],2);

                }
            }







            $res['data'] = $data;
        }

        // $logins = array($input,$output);
        exit(json_encode($res));
    }

    protected function shuju($data){

        $data['ForAmtE'] = number_format($data['ForAmtE'],2);
        $data['ForAmtE_Pre'] = number_format($data['ForAmtE_Pre'],2);

        $data['ForAmtI'] = number_format($data['ForAmtI'],2);
        $data['ForAmtI_Pre'] = number_format($data['ForAmtI_Pre'],2);

        $data['ForAmtT'] = number_format($data['ForAmtT'],2);
        $data['ForAmtT_Pre'] = number_format($data['ForAmtT_Pre'],2);
        return $data;
    }

    protected function getJituan($param,$LangID,$EI=null){
        $ForAmtE = 0;
        $ForAmtE_Pre = 0;
        $ForAmtI = 0;
        $ForAmtI_Pre = 0;
        $ForAmtT = 0;
        $ForAmtT_Pre = 0;
        $data = array();
        $Langs = $this->Language($LangID);
        if($param['db'] == 'SZJT'){
            $suzhou = array('SZ','HS','SH','LL','LLSZ');
            $szjt = array();


            foreach ($suzhou as $value) {
                $param['db'] = $value;
                $nows = $this->getListInfo($param,$LangID);
                $name = '';
                switch ($param['db']) {
                    case 'SZ':
                        $name = $Langs['uniSZ'];
                        break;
                    case 'HS':
                        $name = $Langs['uniHANS'];
                        break;
                    case 'SH':

                        $name = $Langs['uniSH'];
                        $nows['ForAmtE'] = 0;
                        $nows['ForAmtE_Pre'] = 0;
                        $nows['ForAmtI'] = $nows['ForAmtT'];
                        $nows['ForAmtI_Pre'] = $nows['ForAmtT_Pre'];
                        break;
                    case 'LL':
                        $name = $Langs['uniLL'];
                        $nows['ForAmtE'] = 0;
                        $nows['ForAmtE_Pre'] = 0;
                        $nows['ForAmtI'] = $nows['ForAmtT'];
                        $nows['ForAmtI_Pre'] = $nows['ForAmtT_Pre'];
                        break;
                    case 'LLSZ':
                        $name = $Langs['uniLLSZ'];

                        $nows['ForAmtE'] = $nows['ForAmtT'];
                        $nows['ForAmtE_Pre'] = $nows['ForAmtT_Pre'];
                        $nows['ForAmtI'] = 0;
                        $nows['ForAmtI_Pre'] = 0;

                        break;
                }
                if($EI == 'Exter'){
                    $nows['ForAmtT'] = $nows['ForAmtE'];
                    $nows['ForAmtT_Pre'] = $nows['ForAmtE_Pre'];
                    $nows['percentT'] = $nows['ForAmtE_Pre']>0?($nows['ForAmtE']-$nows['ForAmtE_Pre'])/$nows['ForAmtE_Pre']*100:0;
                    $nows['percentColorT'] = $nows['percentT']<0?'#07be00':'#e02a27';


                }else if($EI == 'Inter'){
                    $nows['ForAmtT'] = $nows['ForAmtI'];
                    $nows['ForAmtT_Pre'] = $nows['ForAmtI_Pre'];
                    $nows['percentT'] = $nows['ForAmtI_Pre']>0?($nows['ForAmtI']-$nows['ForAmtI_Pre'])/$nows['ForAmtI_Pre']*100:0;
                    $nows['percentColorT'] = $nows['percentT']<0?'#07be00':'#e02a27';
                }

                $szjt[] = array(
                    'name'  => $name,
                    'ForAmt'    => number_format($nows['ForAmtT'],2),
                    'ForAmt_Pre'    => number_format($nows['ForAmtT_Pre'],2),
                    'percent'   => sprintf("%.2f",$nows['percentT']),
                    'percentColor'   => $nows['percentColorT'],
                );

                $ForAmtE += $nows['ForAmtE'];
                $ForAmtE_Pre += $nows['ForAmtE_Pre'];
                $ForAmtI += $nows['ForAmtI'];
                $ForAmtI_Pre += $nows['ForAmtI_Pre'];
                $ForAmtT += $nows['ForAmtT'];
                $ForAmtT_Pre += $nows['ForAmtT_Pre'];
            }

            $percentE = $ForAmtE_Pre>0?($ForAmtE-$ForAmtE_Pre)/$ForAmtE_Pre*100:0;
            $percentColorE = $percentE<0?'#07be00':'#e02a27';
            $percentI = $ForAmtI_Pre>0?($ForAmtI-$ForAmtI_Pre)/$ForAmtI_Pre*100:0;
            $percentColorI = $percentI<0?'#07be00':'#e02a27';
            $percentT = $ForAmtT_Pre>0?($ForAmtT-$ForAmtT_Pre)/$ForAmtT_Pre*100:0;
            $percentColorT = $percentT<0?'#07be00':'#e02a27';
            $data = array(
                'nameE'     => 'External',
                'ForAmtE'    => $ForAmtE,
                'ForAmtE_Pre'    => $ForAmtE_Pre,
                'percentE'    => sprintf("%.2f",$percentE),
                'percentColorE'    => $percentColorE,
                'nameI'     => 'Internal',
                'ForAmtI'    => $ForAmtI,
                'ForAmtI_Pre'    => $ForAmtI_Pre,
                'percentI'    => sprintf("%.2f",$percentI),
                'percentColorI'    => $percentColorI,
                'nameT'     => "Total",
                'ForAmtT'   => $ForAmtT,
                'ForAmtT_Pre' => $ForAmtT_Pre,
                'percentT'    => sprintf("%.2f",$percentT),
                'percentColorT'    => $percentColorT,
                'szjt'  => $szjt
            );


        }else if($param['db'] == 'GDJT'){
            // {value:'GD',text:Langs.uniGD},
            // {value:'XR',text:Langs.uniSION},
            $guangd = array('GD','XR');
            $gdjt = array();

            foreach ($guangd as $value) {
                $param['db'] = $value;
                $nows = $this->getListInfo($param,$LangID);
                switch ($param['db']) {
                    case 'GD':
                        $name = $Langs['uniGD'];
                        break;
                    case 'XR':
                        $name = $Langs['uniSION'];
                        break;

                }
                if($EI == 'Exter'){
                    $nows['ForAmtT'] = $nows['ForAmtE'];
                    $nows['ForAmtT_Pre'] = $nows['ForAmtE_Pre'];
                    $nows['percentT'] = $nows['ForAmtE_Pre']>0?($nows['ForAmtE']-$nows['ForAmtE_Pre'])/$nows['ForAmtE_Pre']*100:0;
                    $nows['percentColorT'] = $nows['percentT']<0?'#07be00':'#e02a27';


                }else if($EI == 'Inter'){
                    $nows['ForAmtT'] = $nows['ForAmtI'];
                    $nows['ForAmtT_Pre'] = $nows['ForAmtI_Pre'];
                    $nows['percentT'] = $nows['ForAmtI_Pre']>0?($nows['ForAmtI']-$nows['ForAmtI_Pre'])/$nows['ForAmtI_Pre']*100:0;
                    $nows['percentColorT'] = $nows['percentT']<0?'#07be00':'#e02a27';
                }

                $gdjt[] = array(
                    'name'  => $name,
                    'ForAmt'    => number_format($nows['ForAmtT'],2),
                    'ForAmt_Pre'    => number_format($nows['ForAmtT_Pre'],2),
                    'percent'   => sprintf("%.2f",$nows['percentT']),
                    'percentColor'   => $nows['percentColorT'],
                );
                $ForAmtE += $nows['ForAmtE'];
                $ForAmtE_Pre += $nows['ForAmtE_Pre'];
                $ForAmtI += $nows['ForAmtI'];
                $ForAmtI_Pre += $nows['ForAmtI_Pre'];
                $ForAmtT += $nows['ForAmtT'];
                $ForAmtT_Pre += $nows['ForAmtT_Pre'];
            }

            $percentE = $ForAmtE_Pre>0?($ForAmtE-$ForAmtE_Pre)/$ForAmtE_Pre*100:0;
            $percentColorE = $percentE<0?'#07be00':'#e02a27';
            $percentI = $ForAmtI_Pre>0?($ForAmtI-$ForAmtI_Pre)/$ForAmtI_Pre*100:0;
            $percentColorI = $percentI<0?'#07be00':'#e02a27';
            $percentT = $ForAmtT_Pre>0?($ForAmtT-$ForAmtT_Pre)/$ForAmtT_Pre*100:0;
            $percentColorT = $percentT<0?'#07be00':'#e02a27';
            $data = array(
                'nameE'     => 'External',
                'ForAmtE'    => $ForAmtE,
                'ForAmtE_Pre'    => $ForAmtE_Pre,
                'percentE'    => sprintf("%.2f",$percentE),
                'percentColorE'    => $percentColorE,
                'nameI'     => 'Internal',
                'ForAmtI'    => $ForAmtI,
                'ForAmtI_Pre'    => $ForAmtI_Pre,
                'percentI'    => sprintf("%.2f",$percentI),
                'percentColorI'    => $percentColorI,
                'nameT'     => "Total",
                'ForAmtT'   => $ForAmtT,
                'ForAmtT_Pre' => $ForAmtT_Pre,
                'percentT'    => sprintf("%.2f",$percentT),
                'percentColorT'    => $percentColorT,
                'szjt'  => $gdjt
            );
        }else if($param['db'] == 'QDJT'){

            // {value:'QD',text:Langs.uniQD},
            // {value:'CL',text:Langs.uniCL},
            $qingd = array('QD','CL');
            $qdjt = array();
            foreach ($qingd as $value) {
                $param['db'] = $value;
                $nows = $this->getListInfo($param,$LangID);
                switch ($param['db']) {
                    case 'QD':
                        $name = $Langs['uniQD'];
                        $nows['ForAmtE'] = 0;
                        $nows['ForAmtE_Pre'] = 0;
                        $nows['ForAmtI'] = $nows['ForAmtT'];
                        $nows['ForAmtI_Pre'] = $nows['ForAmtT_Pre'];
                        break;
                    case 'CL':
                        $name = $Langs['uniCL'];
                        $nows['ForAmtE'] = $nows['ForAmtT'];
                        $nows['ForAmtE_Pre'] = $nows['ForAmtT_Pre'];
                        $nows['ForAmtI'] = 0;
                        $nows['ForAmtI_Pre'] = 0;
                        break;

                }
                if($EI == 'Exter'){
                    $nows['ForAmtT'] = $nows['ForAmtE'];
                    $nows['ForAmtT_Pre'] = $nows['ForAmtE_Pre'];
                    $nows['percentT'] = $nows['ForAmtE_Pre']>0?($nows['ForAmtE']-$nows['ForAmtE_Pre'])/$nows['ForAmtE_Pre']*100:0;
                    $nows['percentColorT'] = $nows['percentT']<0?'#07be00':'#e02a27';


                }else if($EI == 'Inter'){
                    $nows['ForAmtT'] = $nows['ForAmtI'];
                    $nows['ForAmtT_Pre'] = $nows['ForAmtI_Pre'];
                    $nows['percentT'] = $nows['ForAmtI_Pre']>0?($nows['ForAmtI']-$nows['ForAmtI_Pre'])/$nows['ForAmtI_Pre']*100:0;
                    $nows['percentColorT'] = $nows['percentT']<0?'#07be00':'#e02a27';
                }
                // $ceshi[] = array($name,$nows['ForAmtI_Pre']);


                $qdjt[] = array(
                    'name'  => $name,
                    'ForAmt'    => number_format($nows['ForAmtT'],2),
                    'ForAmt_Pre'    => number_format($nows['ForAmtT_Pre'],2),
                    'percent'   => sprintf("%.2f",$nows['percentT']),
                    'percentColor'   => $nows['percentColorT'],
                );
                $ForAmtE += $nows['ForAmtE'];
                $ForAmtE_Pre += $nows['ForAmtE_Pre'];
                $ForAmtI += $nows['ForAmtI'];
                $ForAmtI_Pre += $nows['ForAmtI_Pre'];
                $ForAmtT += $nows['ForAmtT'];
                $ForAmtT_Pre += $nows['ForAmtT_Pre'];
            }

            $percentE = $ForAmtE_Pre>0?($ForAmtE-$ForAmtE_Pre)/$ForAmtE_Pre*100:0;
            $percentColorE = $percentE<0?'#07be00':'#e02a27';
            $percentI = $ForAmtI_Pre>0?($ForAmtI-$ForAmtI_Pre)/$ForAmtI_Pre*100:0;
            $percentColorI = $percentI<0?'#07be00':'#e02a27';
            $percentT = $ForAmtT_Pre>0?($ForAmtT-$ForAmtT_Pre)/$ForAmtT_Pre*100:0;
            $percentColorT = $percentT<0?'#07be00':'#e02a27';
            $data = array(
                'nameE'     => 'External',
                'ForAmtE'    => $ForAmtE,
                'ForAmtE_Pre'    => $ForAmtE_Pre,
                'percentE'    => sprintf("%.2f",$percentE),
                'percentColorE'    => $percentColorE,
                'nameI'     => 'Internal',
                'ForAmtI'    => $ForAmtI,
                'ForAmtI_Pre'    => $ForAmtI_Pre,
                'percentI'    => sprintf("%.2f",$percentI),
                'percentColorI'    => $percentColorI,
                'nameT'     => "Total",
                'ForAmtT'   => $ForAmtT,
                'ForAmtT_Pre' => $ForAmtT_Pre,
                'percentT'    => sprintf("%.2f",$percentT),
                'percentColorT'    => $percentColorT,
                'szjt'  => $qdjt
            );
        }
        return $data;
    }
    protected function getListInfo($param,$LangID)
    {

        $DB = $this->db($param['db']);
        $langs = $this->Language($LangID);
        $pBaseDt = mb_ereg_replace('-', '', $param['riqi']);
        $res = array();
        if( $param['db'] == 'SH' ||  $param['db'] == 'LL' || $param['db'] == 'CL' || $param['db'] == 'ABE') {// $LL == 'LL' ||$LLSZ == 'LLSZ' ||

            // $baseDate
            if($param['db'] == 'SH'){
                $company = 62;
            }else if($param['db'] == 'LL'){
                $company =13;
            // }else if($LLSZ == 'LLSZ'){
            //      $company = 63;
            } else if($param['db'] == 'CL'){
                 $company = 58;
            }else if($param['db'] == 'ABE'){
                 $company = 61;
            }
            if($param['date'] == 'Y'){
                $GU = 'YEAR';
            }else if($param['date'] == 'MS'){
                $GU = 'MONTHTOTAL';
            }else if($param['date'] == 'M'){
                 $GU = 'MONTH';
            } else{
                 $GU = 'DAY';
            }

            $input = ' @p_Gubun = ?,@p_BaseDate = ?,@P_Company=?,@p_LangID=?';
            $output = [$GU, $param['riqi'],$company,$LangID];
            $list = BaseModel::execSp('dbo.P_SASalAmt_Q_API',$input,$output,$DB);


            $nuws = array();
            if($list){
            foreach ($list as $key => $value) {
                switch ($param['index']) {
                    case '0':
                        $Opercent = $value['OrderForAmt']>0?sprintf("%.2f",(float)(($value['OrderForAmt']-$value['OrderForAmt_Pre'])/$value['OrderForAmt_Pre']*100)).'%':'0';
                        if($Opercent<0){
                            $OpercentColor = '#07be00';
                        }else{
                            $OpercentColor = '#ff6259';
                        }
                        $nuws = array(
                            'name'      => $langs['orderAmt'],
                            'ForAmtT'      => (float)$value['OrderForAmt']/10000,
                            'ForAmtT_Pre'      => (float)$value['OrderForAmt_Pre']/10000,
                            'percentT'     => $Opercent,
                            'percentColorT'  => $OpercentColor,
                        );
                    break;
                    case '1':
                        $Ipercent = $value['InvoiceForAmt']>0?sprintf("%.2f",(float)(($value['InvoiceForAmt']-$value['InvoiceForAmt_Pre'])/$value['InvoiceForAmt_Pre']*100)).'%':'0';
                        if($Ipercent<0){
                            $IpercentColor = '#07be00';
                        }else{
                            $IpercentColor = '#ff6259';
                        }
                        $nuws = array(
                            'name'      => $langs['InvoiceAmt'],
                            'ForAmtT'      => (float)$value['InvoiceForAmt']/10000,
                            'ForAmtT_Pre'      => (float)$value['InvoiceForAmt_Pre']/10000,
                            'percentT'     => $Ipercent,
                                'percentColorT'  => $IpercentColor,
                        );
                    break;
                    case '2':
                        $Bpercent = $value['BillForAmt']>0?sprintf("%.2f",(float)(($value['BillForAmt']-$value['BillForAmt_Pre'])/$value['BillForAmt_Pre']*100)).'%':'0';
                        if($Bpercent<0){
                            $BpercentColor = '#07be00';
                        }else{
                            $BpercentColor = '#ff6259';
                        }
                        $nuws = array(
                            'name'      => $langs['BillAmt'],
                            'ForAmtT'      => (float)$value['BillForAmt']/10000,
                            'ForAmtT_Pre'      => (float)$value['BillForAmt_Pre']/10000,
                            'percentT'     => $Bpercent,
                            'percentColorT'  => $BpercentColor,

                        );
                    break;
                    case '3':
                        $Rpercent = $value['ReceiptForAmt']>0?sprintf("%.2f",(float)(($value['BillForAmt']-$value['ReceiptForAmt_Pre'])/$value['ReceiptForAmt_Pre']*100)).'%':'0';
                        if($Rpercent<0){
                            $RpercentColor = '#07be00';
                        }else{
                            $RpercentColor = '#ff6259';
                        }
                        $nuws = array(
                            'name'      => $langs['receivableAmt'],
                            'ForAmtT'      => (float)$value['ReceiptForAmt']/10000,
                            'ForAmtT_Pre'      => (float)$value['ReceiptForAmt_Pre']/10000,
                            'percentT'     => $Rpercent,
                            'percentColorT'  => $RpercentColor,

                        );
                    break;
                    case '4':
                        $Ppercent = $value['ProductrForAmt']>0?sprintf("%.2f",(float)(($value['ProductrForAmt']-$value['ProductrForAmt_Pre'])/$value['ProductrForAmt_Pre']*100)).'%':'0';
                        if($Ppercent<0){
                            $PpercentColor = '#07be00';
                        }else{
                            $PpercentColor = '#ff6259';
                        }
                        $nuws = array(
                            'name'      => $langs['productionAmt'],
                            'ForAmtT'      => sprintf("%.2f",(float)$value['ProductrForAmt']/10000),
                            'ForAmtT_Pre'      => sprintf("%.2f",(float)$value['ProductrForAmt_Pre']/10000),
                            'percentT'     => $Ppercent,
                                'percentColorT'  => $PpercentColor,
                        );
                    break;

                }
            }
        }else{
            switch ($param['index']) {
                    case '0':
                    $name = $langs['orderAmt'];
                    break;
                    case '1':
                    $name = $langs['InvoiceAmt'];
                    break;
                    case '2':
                    $name = $langs['BillAmt'];
                    break;
                    case '3':
                    $name = $langs['receivableAmt'];
                    break;
                    case '4':
                    $name = $langs['productionAmt'];
                    break;
                }
            $nuws = array(
                'name'      => $name,
                'ForAmtT'      => sprintf("%.2f",0),
                'ForAmtT_Pre'      => sprintf("%.2f",0),
                'percentT'     => sprintf("%.2f",0),
                    'percentColorT'  => '#ff6259',
            );
        }

            $res = $nuws;


        }else{

            // // dump($config['connections'][$config['default']]['database']);
            $spName = 'dbo.SSADayTotal_SZ2_M2';
            $input = ' @pWorkingTag = ?,@pBaseDt = ?,@pLangCd=?';
            $output = [$param['date'], $pBaseDt,$LangID];
            $lists = BaseModel::execSp($spName,$input,$output,$DB);

            $Exter = array();
            $Inter = array();

            switch ($param['index']) {
                case '0':
                    $sort = '100';
                    $sortT = '110';
                    break;
                case '1':
                    $sort = '200';
                    $sortT = '210';
                    break;
                case '2':
                    $sort = '300';
                    $sortT = '310';
                    break;
                case '3':
                    $sort = '400';
                    $sortT = '410';
                    break;
                case '4':
                    $sort = '500';
                    $sortT = '510';
                    break;
            }
            $list = array();
            $TforA = 0;
            $TforA_P = 0;
            foreach ($lists as $key => $value) {
                if($sort == $value['Sort']){
                    $list[] = $value;
                }
                if ($sortT == $value['Sort']) {
                    $TforA = $value['ForAmt'];
                    $TforA_P = $value['ForAmt_Pre'];
                }
            }
            $forA = 0;
            $forA_P = 0;
            $forAI = 0;
            $forAI_P = 0;

            $DeptCdsE = array();
            $DeptCdsI = array();
            foreach ($list as $key => $value) {
                if($value['ExternalGubnNm'] != 'TOTAL' && !empty($value['ExternalGubnNm'])){
                    if($value['ExternalGubnNm'] == 'External'){
                        $forA += $value['ForAmt'];
                        $forA_P += $value['ForAmt_Pre'];

                        if(!in_array($value['DeptCd'],$DeptCdsE)){
                            $DeptCdsE[] = $value['DeptCd'];
                        }
                    }
                    if($value['ExternalGubnNm'] == 'Internal'){
                        $forAI += $value['ForAmt'];
                        $forAI_P += $value['ForAmt_Pre'];
                        if(!in_array($value['DeptCd'],$DeptCdsI)){
                            $DeptCdsI[] = $value['DeptCd'];
                        }
                    }
                }

            }


            foreach ($list as $key => $value) {
                if($value['ExternalGubnNm'] != 'TOTAL' && !empty($value['ExternalGubnNm'])){

                    if($value['ExternalGubnNm'] == 'External'){
                        foreach ($DeptCdsE as $val) {
                            if($val == $value['DeptCd']){
                                $Exter[$val][] = $value;
                            }
                        }
                    }
                    if($value['ExternalGubnNm'] == 'Internal'){
                        foreach ($DeptCdsI as $val) {
                            if($val == $value['DeptCd']){
                                $Inter[$val][] = $value;
                            }
                        }
                    }
                }
            }

            $shujuE = array();
            foreach ($Exter as $key =>$val) {
                $ForAmt = 0;
                $ForAmt_Pre = 0;
                $DeptNm = '';
                foreach ($val as $v) {
                    $ForAmt += (float)$v['ForAmt'];
                    $ForAmt_Pre += (float)$v['ForAmt_Pre'];
                    $DeptNm = $v['DeptNm'];

                }

                $percent = $ForAmt_Pre>0?($ForAmt - $ForAmt_Pre)/$ForAmt_Pre*100:0;
                if($percent<0){
                    $percentColor = '#07be00';
                }else{
                    $percentColor = '#ff6259';
                }
                $shujuE[] = array(
                    'DeptCd'    => $key,
                    'ForAmt'    => $ForAmt,
                    'ForAmt_Pre'  => $ForAmt_Pre,
                    'DeptNm'    => $DeptNm,
                    'percent'   => sprintf("%.2f",$percent),
                    'percentColor'   => $percentColor,
                );
            }
            $shujuI = array();
            foreach ($Inter as $key =>$val) {
                $ForAmt = 0;
                $ForAmt_Pre = 0;
                $DeptNm = '';
                foreach ($val as $v) {
                    $ForAmt += (float)$v['ForAmt'];
                    $ForAmt_Pre += (float)$v['ForAmt_Pre'];
                    $DeptNm = $v['DeptNm'];

                }

                $percent = $ForAmt_Pre>0?($ForAmt - $ForAmt_Pre)/$ForAmt_Pre*100:0;
                if($percent<0){
                    $percentColor = '#07be00';
                }else{
                    $percentColor = '#ff6259';
                }
                $shujuI[] = array(
                    'DeptCd'    => $key,
                    'ForAmt'    => $ForAmt,
                    'ForAmt_Pre'  => $ForAmt_Pre,
                    'DeptNm'    => $DeptNm,
                    'percent'   => sprintf("%.2f",$percent),
                    'percentColor'   => $percentColor,
                );
            }
            $percentE = $forA_P>0?($forA-$forA_P)/$forA_P*100:0;
            $percentColorE = $percentE<0?'#07be00':'#e02a27';
            $percentI = $forAI_P>0?($forAI-$forAI_P)/$forAI_P*100:0;
            $percentColorI = $percentI<0?'#07be00':'#e02a27';
            $percentT = $TforA_P>0?($TforA-$TforA_P)/$TforA_P*100:0;
            $percentColorT = $percentT<0?'#07be00':'#e02a27';
            $res = array(
                'nameE'     => 'External',
                'ForAmtE'    => (float)$forA,
                'ForAmtE_Pre'    => (float)$forA_P,
                'percentE'    => sprintf("%.2f",$percentE),
                'percentColorE'    => $percentColorE,
                'Exter'     => $shujuE,
                'nameI'     => 'Internal',
                'ForAmtI'    => (float)$forAI,
                'ForAmtI_Pre'    => (float)$forAI_P,
                'percentI'    => sprintf("%.2f",$percentI),
                'percentColorI'    => $percentColorI,
                'Inter'     => $shujuI,
                'nameT'     => "Total",
                'ForAmtT'   => (float)$TforA,
                'ForAmtT_Pre' => (float)$TforA_P,
                'percentT'    => sprintf("%.2f",$percentT),
                'percentColorT'    => $percentColorT,
            );


            $res['statusCode'] = 200;




        }
        return $res;

    }

}