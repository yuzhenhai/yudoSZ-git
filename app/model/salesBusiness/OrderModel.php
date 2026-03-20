<?php
/**
 * @Author: Yuzh
 * @Date: 2024-09-30 15:55
 */

namespace app\model\salesBusiness;

use app\model\BaseModel;
use think\facade\Db;
/**
 * Class DailyDataModel
 * @package app\model\businessInfo
 * 每日统计表的模型(数据库)操作
 */
//
class OrderModel extends BaseModel
{

    /**
     * 查询指定订单进展现况
     *
     * @param string $order_code
     * @param string $type O:Order,D:Draw
     *
     * @return object
     */
     public static function getOrderProgressInfo($OrderNo, $type = 'O', $lang)
    {


        $sqls = "SELECT
            TSAOrder00.OrderNo,
            TSAOrder00.CfmYn,
            C.CustNm,
            C.CustCd,
            TSAOrder00.AgentCd,
            TSAOrder00.MakerCd,
            TSAOrder00.OrderDate,
            TSAOrder00.DelvDate,
            TSAOrder00.Status,
            TSAOrder00.SpecType,
            TSAOrder00.HRSystem,
            IsNull(TSAOrder00.SpecNo, '') As SpecNo,
            TSAOrder00.RefNo,
            R.AptYn as DesignYn,
            R.AptDate,
            R.DwPlanDate,
            W.OutDate,
            W.OutYn,
            IsNull(TSAOrder00.DrawNo, '') As DrawNo,
            TSAOrder00.DrawAmd,
            P.AptYn As ProductYn,
            P.AptDate As PAptDate,
            M.WPlanDate,
            M.WDelvDate,
            TSAOrder00.ProductDate,
            IsNull(M.WPlanNo, '') As WPlanNo,
            D.DeptNm,
            E.EmpNm,
            TSAOrder00.Deptcd,
            TSAOrder00.EmpID,
            isNull(CONVERT(DECIMAL(18, 2), TSAOrder00.OrderForAmt), '0.00') as orderForAmt,
            isNull((SELECT D.TransNm FROM TSMSyco10 S LEFT JOIN TSMDict10 D ON S.MinorCd = D.DictCd AND D.LangCd = '$lang' WHERE S.MajorCd = 'SA3001' AND D.DictCd = TSAOrder00.OrderType), (SELECT S.MinorTransNm FROM TSMSyco10 S WHERE S.MajorCd = 'SA3001' AND S.MinorCd = TSAOrder00.OrderType)) AS OrderType,
            isNull((SELECT D.TransNm FROM TSMSyco10 S LEFT JOIN TSMDict10 D ON S.MinorCd = D.DictCd AND D.LangCd = '$lang' WHERE S.MajorCd = 'DE0002' AND S.RelCd1 = R.Status), (SELECT S.MinorTransNm FROM TSMSyco10 S WHERE S.MajorCd = 'DE0002' AND S.RelCd1 = R.Status)) AS StatusNm
            from TSAOrder00 WITH(NOLOCK)

            left join TMADept00 AS D WITH(NOLOCK) on(TSAOrder00.Deptcd = D.DeptCd)
            left join TMAEmpy00 AS E WITH(NOLOCK) on(TSAOrder00.EmpID = E.EmpID)
            left join TMACust00 AS C WITH(NOLOCK) on(TSAOrder00.CustCd = C.CustCd)
            left join TDEDwReg00 As W WITH(NOLOCK) on(TSAOrder00.DrawNo = W.DrawNo AND TSAOrder00.DrawAmd = W.DrawAmd)
            left join TDEDwReq00 AS R WITH(NOLOCK) on(W.ReqNo = R.ReqNo)
            left join (SELECT SourceNo, SourceType, Min(ReqNo) As ReqNo From TPMWKReq00 WITH(NOLOCK) Group By SourceNo, SourceType) As P1 on(TSAOrder00.OrderNo = P1.SourceNo AND P1.SourceType = '1')
            left join TPMWKReq00 As P WITH(NOLOCK) on(P1.ReqNo = P.ReqNo AND P.DeleteYn='N')
            left join TPMWKPlan00 As M WITH(NOLOCK) on(P.ReqNo = M.ReqNo)
        ";
        if ($type == 'O') {
            $sqls .= " where TSAOrder00.OrderNo = '$OrderNo'";
        } else {
            if ($type == 'D') {
                $sqls .= " where TSAOrder00.DrawNo = '$OrderNo'";
            } else {
            $sqls .= " where TSAOrder00.OrderNo = '$OrderNo'";
            }
        }

        $list = DB::query($sqls);
        if(!empty($list)){
            $data = $list[0];
        }else{
            $data = array();
        }

        if (!empty($data)) {
             // 根据订单号查询是否有图纸依赖
            $draw_reqno = self::getDwReq($data,$lang);
            if (empty($draw_reqno)) {
                $data['DReqNoYn'] = '0';
                $data['DrawStatus'] = '--';
            } else {
                $data['DReqNoYn'] = '1';
                $data['DrawStatus'] = $draw_reqno['StatusNm'];
            }
            // 根据订单号查询是否有生产依赖
            $where = array(
                'SourceType'    => '1',
                'DeleteYn'      => 'N',
                'SourceNo'      => $data['OrderNo']

            );
            $work_reqno = self::getTPMWKReq($where);
            if (empty($work_reqno)) {
                $data['WReqNoYn'] = '0';
            } else {
                $data['WReqNoYn'] = '1';
            }
            // 查询送货单日期(max)
            $invoice_day = self::getTSAInvoice($data);

            $data['InvoiceDay'] = $invoice_day['InvoiceDate'];
        }
        return $data;
    }
    // 根据订单号查询是否有图纸依赖
    public static function getDwReq($data,$lang)
    {
        $list = DB::query("SELECT
                        TDEDwReq00.ReqNo,
                        isNull((SELECT D.TransNm FROM TSMSyco10 S LEFT JOIN TSMDict10 D ON S.MinorCd = D.DictCd AND D.LangCd = '$lang' WHERE S.MajorCd = 'DE0002' AND S.RelCd1 = TDEDwReq00.Status),
                        (SELECT S.MinorTransNm FROM TSMSyco10 S WITH(NOLOCK) WHERE S.MajorCd = 'DE0002' AND S.RelCd1 = TDEDwReq00.Status)) AS StatusNm
                        FROM TDEDwReq00 WITH(NOLOCK)
                         where (TDEDwReq00.SourceType = 'O' AND TDEDwReq00.SourceNo = '" . $data['OrderNo'] . "') or (TDEDwReq00.SourceType = 'S' AND TDEDwReq00.SourceNo = '" . $data['SpecNo'] . "' AND TDEDwReq00.SpecType = '" . $data['SpecType'] . "') order by TDEDwReq00.ReqNo desc");
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;
    }
    // 根据订单号查询是否有生产依赖
    public static function getTPMWKReq($where)
    {

        $list = DB::table('TPMWKReq00')->where($where)->find();

        return $list;
    }
    // 根据订单号查询是否有图纸依赖
    public static function getTSAInvoice($data)
    {
        $list = DB::query("SELECT MAX(A.InvoiceDate) AS InvoiceDate
            FROM TSAInvoice10 B WITH(NOLOCK) left join TSAInvoice00 AS A WITH(NOLOCK)
            ON B.InvoiceNo = A.InvoiceNo AND B.ExpClss = A.ExpClss WHERE B.SourceType = '1' AND  B.SourceNo = '" . $data['OrderNo']."'");
        if(!empty($list)){
            $res = $list[0];
        }else{
            $res = array();
        }
        return $res;
    }

    /**
     * 品质信息
     *
     * @param $order string
     *
     * @return object
     */
    public static function TQCInsRept00($order){

         $TQCInsRept00 = DB::query("SELECT LEFT(TQCInsRept00.InsReptNo,255) as InsReptNo,
          TQCInsRept00.QCDate,
          D.DeptNm,
          E.EmpNm from TQCInsRept00 WITH(NOLOCK)
          left join TMADept00 As D WITH(NOLOCK) on(TQCInsRept00.QCDeptCd = D.DeptCd)
          left join TMAEmpy00 As E WITH(NOLOCK) on(TQCInsRept00.QCEmpID = E.EmpID)
          where TQCInsRept00.CfmYn = '1' AND TQCInsRept00.SourceType = '1' AND TQCInsRept00.SourceNo = '".$order."'");

        if(!empty($TQCInsRept00)){
            $res = $TQCInsRept00[0];
        }else{
            $res = array();
        }

         if(empty($res)){
            $res['InsReptNo'] = '--';
            $res['QCDate'] = '--';
            $res['DeptNm'] = '--';
            $res['EmpNm'] = '--';
            $res['DeptNm_EmpNm'] = '--';
            $res['hasQCPhotos'] = 'N';
         }else{
            $res['QCDate'] = date('Y-m-d',strtotime($res['QCDate']));
            $res['DeptNm_EmpNm'] = $res['DeptNm'] . '/' . $res['EmpNm'];

            $QCList = DB::query("select * from TQCInsRept10 WITH(NOLOCK) where InsReptNo = '" . $res['InsReptNo'] . "'");

            if(empty($QCList[0])){
                $res['hasQCPhotos'] = 'N';
                $res['QCList'] = array();
            }else{
                $res['hasQCPhotos'] = 'Y';
                $res['QCList'] = $QCList;
            }
         }
         return $res;
    }
    /**
     * Parts List
     *
     * @param $order string
     *
     * @return object
     */
    public static function searchBomList($order)
    {
        $data = [];
        // bom00

        $where = array(
            'SourceType'    => '1',
            'DeleteYn'      => 'N',
            'SourceNo'      => $order
        );
        $bom00 = Db::table('TPMBOM00')->field('BomNo As ItemCode, BomNm As ItemName, SetQty As Qty, Remark')->where($where)->find();
        // return $bom00;
        if (!empty($bom00)) {
            // $bom00 = $bom00->toArray();
            $bom00['Spec'] = '';
            $bom00['Level'] = '0------';
            if ($bom00['Remark'] == null) {
                $bom00['Remark'] = '';
            }
            $bomno = $bom00['ItemCode'];
            $bom00['ItemCode'] = $order;
            array_push($data, $bom00);

            // bom10
            $bom10 = DB::query("select BomNo, BomSerl,TPMBOM10.Remark,item.ItemNo As ItemCode,item.ItemNm As ItemName,isNull(item.Spec, '') As Spec, Qty from TPMBOM10 WITH(NOLOCK) left join TMAItem00 as item WITH(NOLOCK) on(item.ItemCd = TPMBOM10.ItemCd) where BomNo = '$bomno'");

            if (!empty($bom10)) {
                foreach ($bom10 as $value) {

                    if ($value['Remark'] == null) {
                        $value['Remark'] = '';
                    }
                    $value['Level'] = '--1----';
                    array_push($data, $value);

                    // bom20
                    $bom20 = DB::query("select item.ItemNo As ItemCode,item.ItemNm As ItemName,item.Spec,Qty,item.BomYn,TPMBOM20.Remark,TPMBOM20.ItemCd from TPMBOM20 WITH(NOLOCK) left join TMAItem00 as item WITH(NOLOCK) on(item.ItemCd = TPMBOM20.ItemCd) where BomNo = '" . $value['BomNo'] . "' AND BomSerl = '" . $value['BomSerl'] . "'");

                    if (!empty($bom20)) {
                        foreach ($bom20 as $val) {
                            if ($val['Remark'] == null) {
                                $val['Remark'] = '';
                            }
                            $val['Level'] = '----2--';
                            array_push($data, $val);

                            if ($val['BomYn'] == 'Y') {
                                $bom30 = DB::query("select item.ItemNo As ItemCode, item.ItemNm As ItemName, item.Spec, TPMBOMMaster.Qty, TPMBOMMaster.Remark from TPMBOMMaster WITH(NOLOCK) left join TMAItem00 as item WITH(NOLOCK) on(item.ItemCd = TPMBOMMaster.ItemCd) where GoodCd = '" . $val['ItemCd'] . "'");


                                if (!empty($bom30)) {
                                    foreach ($bom30 as $tmp) {
                                        $tmp['Level'] = '------3';
                                        $tmp['Qty'] = $tmp['Qty'] * $val['Qty'];
                                        if ($tmp['Remark'] == null) {
                                            $tmp['Remark'] = '';
                                        }
                                        array_push($data, $tmp);

                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }


     /**
     * 获取AS单号
     *
     * @param $order string
     *
     * @return object
     */
    public static function asList($order){

        $lists = DB::table('TASRecv00')->field('ASRecvNo, ASRecvDate')->where('OrderNo','=',$order)->order('ASRecvDate','desc')->select();


        return $lists;
    }


    /**
     * 营业图纸   、 ID卡图纸
     *
     * @param $order string
     *
     * @return object
     */
    public static function TDEDwReg10($DrawNo,$DrawAmd){

        $Reg10 = Db::table('TDEDwReg00')->where('DrawNo','=',$DrawNo)->order('DrawAmd','desc')->find();

        if(empty($Reg10)){
          $list['yingyeYN'] = 'N';
              $list['fileName'] = '';
              $list['IDCardYN'] = 'N';
            $list['IDCardfileName'] = '';
            $list['IDDrawNo'] = $DrawNo;

          return $list;
        }else{
          $where = array(
              'DrawNo'    => $Reg10['DrawNo'],
              'DrawAmd'   => $Reg10['DrawAmd'],
              'ItemType'  => 'DE00040013'
          );
        }
        $TDEDwReg10 = Db::table('TDEDwReg10')->field('DwgFileNm as fileName,DrawSerl')->where($where)->order('DrawSerl','asc')->find();

        $list = array();


        $TDEDwReg00 =  Db::table('TDEDwReg00')->where('DrawNo','=',$Reg10['DrawNo'])->find();

        if($Reg10['OutYn'] == '1'){
          if(empty($TDEDwReg10)){
              $list['yingyeYN'] = 'N';
              $list['fileName'] = '';
          }else{
              $list['yingyeYN'] = 'Y';
              $list['fileName'] = $TDEDwReg10['fileName'];
          }
        }else{
          $list['yingyeYN'] = 'N';
          $list['fileName'] = '';
        }
        $list['DrawNo'] = $DrawNo;

        $where = array(
            'DrawNo'    => $DrawNo,
            'DrawAmd'   => $DrawAmd,
            'ItemType'  => 'DE00040014'
        );
        $IDCardFiles = Db::table('TDEDwReg10')->field('DwgFileNm as fileName,DrawSerl')->where($where)->order('DrawSerl','asc')->find();

        if(empty($IDCardFiles)){
            $list['IDCardYN'] = 'N';
            $list['IDCardfileName'] = '';
        }else{
            $list['IDCardYN'] = 'Y';
            $list['IDCardfileName'] = $IDCardFiles['fileName'];
        }
        $list['IDDrawNo'] = $DrawNo;

        return $list;
    }

     /**
     * 不良通报
     *
     * @param $order string
     *
     * @return object
     */

    public static function getBadlist($OrderNo){

        $list = Db::table('TQCBadRept00')->where('SourceNo','=',$OrderNo)->find();

        // $list = $BadReportlist;
        if(!empty($list)){
            $where = array(
              'BadReptNo' => $list['BadReptNo'],
              // 'FTP_UseYn' => 'Y'
            );

            $hasPhotos = Db::table('TQCBadRept10')->where($where)->select();


            if (empty($hasPhotos)) {
                $list['hasPhotos'] = 'N';
                $list['image'] = '';
            } else {
                $list['hasPhotos'] = 'Y';
                $list['image'] = $hasPhotos;
            }

        }

       return $list;
    }

    /**
     * 组装/试模
     *
     * @param $order string
     *
     * @return object
     */
    public static function getAssemble($order){

        $list = DB::query("select A.AssmReptNo,
              A.AssmReptDate,
              D.DeptNm,
              E.EmpNm  as AssmPeople
              from TSAAssmRept00 as A WITH(NOLOCK)
              left join TMADept00 As D WITH(NOLOCK) on(A.AssmDeptCd = D.DeptCd)
              left join TMAEmpy00 As E WITH(NOLOCK) on(A.AssmEmpID = E.EmpID)
              where A.CfmYn = '1' AND A.OrderNo = '$order'");
        // 查询组装试模信息
        if(empty($list)){
            $assembleInfo['AssmReptNo'] = '--';
            $assembleInfo['AssmReptDate'] = '--';
            $assembleInfo['AssmPeople'] = '--';
            $assembleInfo['hasAssemble'] = 'N';
            $assembleInfo['hasTrialInjection'] = 'N';
        }else{
            $assembleInfo = $list[0];
            $assembleList = DB::query("select * from TSAAssmRept10  WITH(NOLOCK)
              where AssmReptNo = '". $assembleInfo['AssmReptNo'] ."'");
            if (empty($assembleList)) {
                $assembleInfo['hasAssemble'] = 'N';
                $assembleInfo['list'] = array();
            } else {
                $assembleInfo['hasAssemble'] = 'Y';
                $assembleInfo['list'] = $assembleList;
            }

            $assembleInfo['AssmReptDate'] = date('Y-m-d',strtotime($assembleInfo['AssmReptDate']));
        }

        $TSATstInjRept00 = DB::query("select TstInjReptNo,
              TstInjReptDate,
              CASE OrderSysRegYn WHEN 'Y' THEN OrderNo WHEN 'N' THEN UnRegOrderNo ELSE '' END AS OrderNo
              from TSATstInjRept00  WITH(NOLOCK)
              where CfmYn = '1' AND OrderNo = '$order'");

        if(empty($TSATstInjRept00)){
          $assembleInfo['hasTrialInjection'] = 'N';
           $assembleInfo['list_has'] = '';
           $assembleInfo['TstInjReptNo'] = '--';
        }else{

            // 查询试模照片
            $trialInjectionList = DB::query("select * from TSATstInjRept10  WITH(NOLOCK)
              where TstInjReptNo = ".$TSATstInjRept00[0]['TstInjReptNo']);
          $assembleInfo['TstInjReptNo'] = $TSATstInjRept00[0]['TstInjReptNo'];

          if (empty($trialInjectionList)) {
              $assembleInfo['hasTrialInjection'] = 'N';

              $assembleInfo['list_has'] = '';
          } else {
              $assembleInfo['hasTrialInjection'] = 'Y';
              $assembleInfo['list_has'] = $trialInjectionList;
          }

        }
        return $assembleInfo;

    }

    /**
     * 查询订单列表
     *
     * @param string $start
     * @param string $end
     * @param string $custCd
     * @param string $cRMAuth
     * @param string $refNo
     *
     * @return object
     */
    public static function getOrderList($start, $end, $custCd, $refNo)
    {
        $sql = "SELECT
        CONVERT(VARCHAR(10), TSAOrder00.OrderDate, 120) as orderDate,
        TSAOrder00.OrderNo as orderNo,
        isNull(TMACust00.CustNo, '') as custNo,
        isNull(TMACust00.CustNm, '') as custNm,
        isNull(TMAEmpy00.EmpNm, '') as empNm,
        isNull(TMADept00.DeptNm, '') as deptNm,
        isNull(TSAOrder00.RefNo, '') as refNo
        from TSAOrder00 WITH(NOLOCK)
        left join TMAEmpy00 WITH(NOLOCK) ON TSAOrder00.EmpID = TMAEmpy00.EmpID
        left join TMADept00 WITH(NOLOCK) ON TSAOrder00.DeptCd = TMADept00.DeptCd
        left join TMACust00 WITH(NOLOCK) ON TSAOrder00.CustCd = TMACust00.CustCd
        left join TSASpec00 WITH(NOLOCK) ON TSAOrder00.SpecType = TSASpec00.SpecType AND TSAOrder00.SpecNo = TSASpec00.SpecNo AND TSASpec00.DeleteYn = 'N'
        where (TSAOrder00.orderDate between '$start' and '$end') AND TSAOrder00.CustCd = '$custCd' AND TSAOrder00.RefNo like '%$refNo%%'
        order by TSAOrder00.orderNo desc
        ";
        $return = DB::query($sql);

        return $return;
    }


    public static function text()
    {
        $list = Db::query("EXEC dbo.SSAMarket_SZ_M 'Y', '2024-12-26', '20241231', '20231231', 'SM00010003'");
        dump($list);
    }

}