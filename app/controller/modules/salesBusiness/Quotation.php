<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-12-09 8:59
 */

namespace app\controller\modules\salesBusiness;


use app\model\salesBusiness\QuotationModel;
use think\db\exception\PDOException;
use think\Exception;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

class Quotation extends \app\controller\modules\Base
{

    public function getCustInfo()
    {
        $CustCd = Request::post('CustCd');
        $result = QuotationModel::getCustInfo($CustCd);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function save()
    {
        $post = Request::post();
        $QuotNo = $post['QuotNo'];
        $QuotDate = $post['QuotDate'];
        $DelvDate = $post['DelvDate'];
        $EmpId = $post['EmpId'];
        $DeptCd = $post['DeptCd'];
        $QuotType = $post['QuotType'];
        $ValidDate = $post['ValidDate'];
        $Status = $post['Status'];
        $DelvLimit = $post['DelvLimit'];
        $DelvMethod = $post['DelvMethod'];
        $Nation = $post['Nation'];
        $CustCd = $post['CustCd'];
        $CustomerCd = $post['CustomerCd'];
        $AgentCd = $post['AgentCd'];
        $ShipToCd = $post['ShipToCd'];
        $MakerCd = $post['MakerCd'];
        $CustPrsn = $post['CustPrsn'];
        $CustPrsnHP = $post['CustPrsnHP'];
        $CustEmail = $post['CustEmail'];
        $CustFax = $post['CustFax'];
        $CustTel = $post['CustTel'];
        $CustRemark = $post['CustRemark'];
        $Resin = $post['Resin'];
        $GoodNm = $post['GoodNm'];
        $GoodClass = $post['GoodClass'];
        $RefNo = $post['RefNo'];
        $MarketCd = $post['MarketCd'];
        $PProductCd = $post['PProductCd'];
        $PPartCd = $post['PPartCd'];
        $PartDesc = $post['PartDesc'];
        $GoodSpec = $post['GoodSpec'];
        $SrvArea = $post['SrvArea'];
        $QuotDrawNo = $post['QuotDrawNo'];
        $CurrCd = $post['CurrCd'];
        $CurrRate = $post['CurrRate'];
        $ProposeAmt = $post['ProposeAmt'];
        $QuotAmt = $post['QuotAmt'];
        $QuotVat = $post['QuotVat'];
        $Payment = $post['Payment'];
        $DisCountRate = $post['DisCountRate'];
        $Remark = $post['Remark'];
        $QuotAmd = $post['QuotAmd'];
        $PrintGubun = $post['PrintGubun'];
        $SaleVatRate = $post['SaleVatRate'] / 100;
        $MiOrderRemark = $post['MiOrderRemark'];
        $ASRecvNo = $post['ASRecvNo'];
        $PrnAmtYn = $post['PrnAmtYn'];
        $CfmYn = $post['CfmYn'];
        $VatYn = $post['VatYn'];
        $OverseaYn = $post['OverseaYn'];
        $ASYn = $post['ASYn'];
        $itemForm = $post['itemForm'];
        $ExpClss = 1;
        $StdSaleVat = '0.0000';
        if($CfmYn==1){
            return json([
                'statusCode' => 406,
                'message'    => '请先取消确定',
            ]);
        }
        $userId = $this->getUserId();
        //jobNo
        $JobNo = QuotationModel::getJobNo($userId);

        $time = strtotime($QuotDate);
        $nowDate = date('Ym', $time);
        //计算标准货币
        $res = Db::table('TMACurr10')->where(array('CurrCd' => $CurrCd, 'YYMM' => $nowDate))->find();
        if(!$res){
            $date = \DateTime::createFromFormat("Ym", $nowDate);
            $date->modify("-1 month"); // 减去一个月
            $previousMonth = $date->format("Ym"); // 格式化为202412格式
            $res = Db::table('TMACurr10')->where(array('CurrCd' => $CurrCd, 'YYMM' => $previousMonth))->find();
        }
        if ($CurrCd == 'RMB') {
            $QuotForAmt = $QuotAmt;
        } else {
            $QuotForAmt = $QuotAmt * (100 / $res['BasicStdRate']);;
        }
        $QuotForVat = $QuotVat;
        $StdSaleAmt = 0;
        foreach ($itemForm as $item) {
            if (!empty($item['StdPrice']) && !empty($item['Qty']) && isset($item['DCRate'])) {
                $StdSaleAmt += $item['StdPrice'] * $item['Qty'] * $item['DCRate'] / 100;
            }
        }
        $StdSaleAmt = number_format($StdSaleAmt, 2, '.', '');
        $now = date('Y-m-d H:i:s',time());
        $add = [
            'StdSaleAmt' => $StdSaleAmt,
            'CfmEmpId' => '',
            'CfmDate' => '',
            'RegEmpID' => $userId,
            'RegDate' => $now,
            'UptEmpID' => $userId,
            'UptDate' => $now,
            'QuotNo' => $QuotNo,
            'QuotDate' => $QuotDate,
            'DelvDate' => $DelvDate,
            'EmpId' => $EmpId,
            'DeptCd' => $DeptCd,
            'QuotType' => $QuotType,
            'ValidDate' => $ValidDate,
            'Status' => $Status,
            'DelvLimit' => $DelvLimit,
            'DelvMethod' => $DelvMethod,
            'Nation' => $Nation,
            'CustCd' => $CustCd,
            'CustomerCd' => $CustomerCd,
            'AgentCd' => $AgentCd,
            'ShipToCd' => $ShipToCd,
            'MakerCd' => $MakerCd,
            'CustPrsn' => $CustPrsn,
            'CustPrsnHP' => $CustPrsnHP,
            'CustEmail' => $CustEmail,
            'CustFax' => $CustFax,
            'CustTel' => $CustTel,
            'CustRemark' => $CustRemark,
            'Resin' => $Resin,
            'GoodNm' => $GoodNm,
            'RefNo' => $RefNo,
            'MarketCd' => $MarketCd,
            'PProductCd' => $PProductCd,
            'PPartCd' => $PPartCd,
            'PartDesc' => $PartDesc,
            'GoodSpec' => $GoodSpec,
            'SrvArea' => $SrvArea,
            'QuotDrawNo' => $QuotDrawNo,
            'CurrCd' => $CurrCd,
            'CurrRate' => $CurrRate,
            'ProposeAmt' => $ProposeAmt,
            'QuotAmt' => $QuotAmt,
            'QuotVat' => $QuotVat,
            'Payment' => $Payment,
            'DisCountRate' => $DisCountRate,
            'Remark' => $Remark,
            'QuotAmd' => $QuotAmd,
            'PrintGubun' => $PrintGubun,
            'SaleVatRate' => $SaleVatRate,
            'MiOrderRemark' => $MiOrderRemark,
            'ASRecvNo' => $ASRecvNo,
            'PrnAmtYn' => $PrnAmtYn,
            'CfmYn' => 0,
            'VatYn' => $VatYn,
            'OverseaYn' => $OverseaYn,
            'ASYn' => $ASYn,
            'ExpClss' => $ExpClss,
            'JobNo' => $JobNo,
            'StdSaleVat' => $StdSaleVat,
            'GoodClass' => $GoodClass,
            'QuotForAmt' => $QuotForAmt,
            'QuotForVat' => $QuotForVat,
        ];
        if(empty($DelvDate)){
            unset($add['DelvDate']);
        }
        if(empty($ValidDate)){
            unset($add['ValidDate']);
        }
        if(empty($QuotNo)){
            $add['QuotNo'] = QuotationModel::getQuotNo();
            try {
                // 开启事务
                Db::startTrans();
                // 插入主表
                $result = Db::table('TSAQuot00')->insert($add);
                if ($result) {
                    if (!empty($itemForm)) {
                        foreach ($itemForm as $k => $v) {
                            // 准备明细数据
                            $itemAdd = [
                                'QuotNo'    => $add['QuotNo'],                         // 报价编号
                                'ExpClss'   => $ExpClss,                               // 类型
                                'Sort'      => $v['Sort'] ?? null,                     // 序号
                                'ItemCd'    => $v['ItemCd'] ?? '',                     // 产品型号
                                'UnitCd'    => $v['UnitCd'] ?? '',                     // 单位编码
                                'Qty'       => $v['Qty'] ?? 0,                         // 数量，默认为 0
                                'StdPrice'  => $v['StdPrice'] ?? 0.00,                 // 销售标准单价，默认为 0.00
                                'DCRate'    => $v['DCRate']??0.00,    // 折扣率(%)
                                'DCPrice'   => $v['DCPrice'] ?? 0.00,                  // 折扣单价
                                'DCAmt'     => $v['DCAmt'] ?? 0.00,                    // 折扣金额
                                'DCVat'     => $v['DCVat'] ?? 0.00,                    // 折扣 VAT
                                'Remark'    => $v['Remark'] ?? '',                     // 备注
                                'Nation'    => $v['Nation'] ?? '',                     // 国家
                                'NextQty'   => $v['NextQty'] ?? 0,                     // 进行数量
                                'StopQty'   => $v['StopQty'] ?? 0,                     // 暂停数量
                                'VatRate'   => $SaleVatRate
                            ];

                            // 处理货币字段
                            $DCAmt   = $v['DCAmt'] ?? 0.00;
                            $DCPrice = $v['DCPrice'] ?? 0.00;

                            if ($CurrCd === 'RMB') {
                                $DCForAmt   = $DCAmt; // 人民币下，折扣金额直接使用
                                $DCForPrice = $DCPrice; // 人民币下，折扣单价直接使用
                            } else {
                                $DCForAmt   = $DCAmt * (100 / $res['BasicStdRate']);
                                $DCForPrice  = $DCPrice * (100 / $res['BasicStdRate']);
                            }
                            // 合并货币字段
                            $itemAdd['DCForAmt']   = $DCForAmt;
                            $itemAdd['DCForPrice'] = $DCForPrice;

                            // 更新流水号
                            $itemAdd['QuotSerl'] = QuotationModel::getquotSerl($add['QuotNo']);
                            // 插入明细表
                            $insertResult = Db::table('TSAQuot10')->insert($itemAdd);
                            if (!$insertResult) {
                                // 如果插入失败，抛出异常
                                throw new Exception('插入明细表失败');
                            }
                        }
                    }
                    // 提交事务
                    Db::commit();
                    // 返回成功响应
                    return json([
                        'statusCode' => self::getCode('SUCCESS'),
                        'message'    => '数据添加成功',
                        'QuotNo'     => $add['QuotNo']
                    ]);
                }
            } catch (Exception $e) {
                // 回滚事务
                Db::rollback();
                // 返回错误响应
                return json([
                    'statusCode' => self::getCode('DB_INSTALL_FAIL'),
                    'message'    => '数据添加失败',
                    'error'      => $e->getMessage()
                ]);
            }
        }else{
            // 如果 QuotNo 不为空，则执行更新操作
            try {
                Db::startTrans();//开启事务
                unset($add['QuotNo']);
                unset($add['CfmEmpId']);
                unset($add['CfmDate']);
                unset($add['CfmYn']);
                unset($add['RegEmpID']);
                unset($add['RegDate']);
                // 执行更新操作
                $result = Db::table('TSAQuot00')
                    ->where('QuotNo', $QuotNo)
                    ->update($add);
                //删除原有的明细
                Db::table('TSAQuot10')->where('QuotNo', $QuotNo)->delete();
                if (!empty($itemForm)) {
                    foreach ($itemForm as $k => $v) {
                        // 准备明细数据
                        $itemAdd = [
                            'QuotNo'    => $QuotNo,                         // 报价编号
                            'ExpClss'   => $ExpClss,                               // 类型
                            'Sort'      => $v['Sort'] ?? null,                     // 序号
                            'ItemCd'    => $v['ItemCd'] ?? '',                     // 产品型号
                            'UnitCd'    => $v['UnitCd'] ?? '',                     // 单位编码
                            'Qty'       => $v['Qty'] ?? 0,                         // 数量，默认为 0
                            'StdPrice'  => $v['StdPrice'] ?? 0.00,                 // 销售标准单价，默认为 0.00
                            'DCRate'    => $v['DCRate'] ?? 0.00,    // 折扣率(%)
                            'DCPrice'   => $v['DCPrice'] ?? 0.00,                  // 折扣单价
                            'DCAmt'     => $v['DCAmt'] ?? 0.00,                    // 折扣金额
                            'DCVat'     => $v['DCVat'] ?? 0.00,                    // 折扣 VAT
                            'Remark'    => $v['Remark'] ?? '',                     // 备注
                            'Nation'    => $v['Nation'] ?? '',                     // 国家
                            'NextQty'   => $v['NextQty'] ?? 0,                     // 进行数量
                            'StopQty'   => $v['StopQty'] ?? 0,                     // 暂停数量
                            'VatRate'   => $SaleVatRate
                        ];

                        // 处理货币字段
                        $DCAmt   = $v['DCAmt'] ?? 0.00;
                        $DCPrice = $v['DCPrice'] ?? 0.00;

                        if ($CurrCd === 'RMB') {
                            $DCForAmt   = $DCAmt; // 人民币下，折扣金额直接使用
                            $DCForPrice = $DCPrice; // 人民币下，折扣单价直接使用
                        } else {
                            $DCForAmt   = $DCAmt * (100 / $res['BasicStdRate']);
                            $DCForPrice  = $DCPrice * (100 / $res['BasicStdRate']);
                        }
                        // 合并货币字段
                        $itemAdd['DCForAmt']   = $DCForAmt;
                        $itemAdd['DCForPrice'] = $DCForPrice;
                        // 更新流水号
                        $itemAdd['QuotSerl'] = QuotationModel::getquotSerl($QuotNo);
                        // 插入明细表
                        $insertResult = Db::table('TSAQuot10')->insert($itemAdd);
                        if (!$insertResult) {
                            // 如果插入失败，抛出异常
                            throw new Exception('插入明细表失败');
                        }
                    }
                }
                // 提交事务
                Db::commit();
                if ($result !== false) {
                    return json([
                        'statusCode' => self::getCode('SUCCESS'),
                        'message'    => '数据更新成功', // 提示成功
                        'QuotNo'=>$QuotNo
                    ]);
                } else {
                    // 未更新任何数据时提示
                    return json([
                        'statusCode' => self::getCode('SUCCESS'),
                        'message'    => '没有数据被更新',
                        'QuotNo'=>$QuotNo
                    ]);
                }
            } catch (PDOException $e) {
                Db::rollback();
                // 捕获更新异常
                return json([
                    'statusCode' => self::getCode('DB_UPDATE_FAIL'), // 数据库失败状态码
                    'message'    => '数据更新失败', // 错误提示
                    'error'      => $e->getMessage() // 捕获的错误信息
                ]);
            }
        }
    }


    public function confirm()
    {
        $post = Request::post();
        $langCode = Config::get('langCode.' . Request::post('langID'));
        $type = $post['type'];
        $QuotNo = $post['QuotNo'];
        $userId = $this->getUserId();
        $result = QuotationModel::confirm($type,$QuotNo,$userId,$langCode);
        if($result['MsgCd']=='OM00000030'){
            //确定成功
            $result['CfmYn'] = 0;
        }else{
            $result['CfmYn'] = 1;
        }
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);

    }

    public function getQuoteItemList()
    {
        $QuotNo = Request::post()['QuotNo'];
        $result = QuotationModel::getQuoteItemList($QuotNo);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function quotInfo()
    {
        $QuotNo = Request::post()['QuotNo'];
        $result = QuotationModel::getQuoteInfo($QuotNo)[0];
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function addServiceCharge()
    {
        $result = QuotationModel::addServiceCharge();
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getStdPrice()
    {
        $post = Request::post();
        $itemCd = $post['itemCd'];
        $custCd = $post['custCd'];
        $date = $post['date'];
        $currCd = $post['currCd'];
        $result = QuotationModel::getStdPrice($itemCd,$custCd,$date,$currCd);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result[0]
        ]);
    }

    public function getItems()
    {
        $post = Request::post();
        $itemNo  = $post['itemNo'];
        $itemNm  = $post['itemNm'];
        $count  = $post['count'];
        $result = QuotationModel::getItemList($itemNo,$itemNm,$count);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function getQuoteJobPower()
    {
        $empId = Request::post('empId');
        $result = trim(QuotationModel::getQuoteJobPower($empId));

        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>(bool)$result
        ]);

    }

    public function getCurrRate()
    {
        $date = date('Ym',strtotime(Request::post('date')));
        $dateObj = \DateTime::createFromFormat('Ym', $date);
        $dateObj->modify('-1 month');
        $newDate = $dateObj->format('Ym');
        $currCd = Request::post('currCd');
        $result = QuotationModel::getCurrRate($date,$currCd);
        if(empty($result)){
            $result = QuotationModel::getCurrRate($newDate,$currCd);
        }
        return json([
           'statusCode'=>self::getCode('SUCCESS'),
           'result'=>$result
        ]);
    }

    public function lists()
    {
        $post = Request::post();
        $langCode = Config::get('langCode.' . Request::post('langID'));
        $custNm = $post['custNm'];
        $startDate = $post['startDate'];
        $endDate = $post['endDate'];
        $quoteNo = $post['quoteNo'];
        $count = $post['count'] ?? 0;
        $userId = $this->getUserId();
//        $userId = 'S2012028';
        $auth =QuotationModel::getAuth('WEI_2500',$userId);
        $authInfo = QuotationModel::getAuthInfo($auth,$userId);
        $result = QuotationModel::getQuotList($quoteNo,$custNm ,$startDate, $endDate, $count,$authInfo);
        $result = QuotationModel::preQuotList($result,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }


    public function selectList()
    {
        $langCode = Config::get('langCode.' . Request::post('langID'));
        $QuotTypeS = QuotationModel::getQuotTypeS($langCode);
        $DelvLimitS = QuotationModel::getDelvLimitS($langCode);
        $DelvMethodS = QuotationModel::getDelvMethodS($langCode);
        $NationS = QuotationModel::getNationS($langCode);
        $GoodClassS = QuotationModel::getGoodClassS($langCode);
        $MarketCdS = QuotationModel::getMarketCdS($langCode);
        $PrintGubunS = QuotationModel::getPrintGubunS($langCode);
        $StatusListS = QuotationModel::getQuotStatusList($langCode);
        $SrvAreaS = QuotationModel::getSrvArea($langCode);
        $UnitCdS = QuotationModel::getUnitCdS();
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>[
                'QuotTypeS'=>$QuotTypeS,
                'DelvLimitS'=>$DelvLimitS,
                'DelvMethodS'=>$DelvMethodS,
                'NationS'=>$NationS,
                'GoodClassS'=>$GoodClassS,
                'MarketCdS'=>$MarketCdS,
                'PrintGubunS'=>$PrintGubunS,
                'StatusListS'=>$StatusListS,
                'SrvAreaS'=>$SrvAreaS,
                'UnitCdS'=>$UnitCdS
            ]
        ]);
    }

    public function selectListSync()
    {
        $langCode = Config::get('langCode.' . Request::post('langID'));
        $parentCd = Request::post('parentCd');
        $result = QuotationModel::getMinDict($parentCd,$langCode);
        return json([
            'statusCode'=>self::getCode('SUCCESS'),
            'result'=>$result
        ]);

    }
}