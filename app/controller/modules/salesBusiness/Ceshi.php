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
class Ceshi extends Base
{

    public function shuju()
    {
        if(Request::isPost()){
            $param = Request::param();
        $list = Db::query("SELECT  A.DrawNo,
                    A.DrawAmd,
                    A.DrawDate,
                    A.DrawDeptCd,
                    A.DrawEmpID,
                    D.EmpNm As DrawEmpNm,
                    A.DwMEmpID,
                    D1.EmpNm As DwMEmpNm,
                    A.SourceType, A.SourceNo,
                    CASE A.SourceType
                        WHEN 'O' THEN IsNull(A.SourceNo,'')
                        WHEN 'S' THEN IsNull(O1.OrderNo,'')
                        WHEN 'A' THEN IsNull(V.OrderNo,'')
                        ELSE '' END AS OrderNo,
                    CASE WHEN A.SourceType = 'O' THEN IsNull(BOM1.CfmYn,'0')
                        WHEN A.SourceType = 'S' And A.SpecType <> '1' THEN IsNull(BOM2.CfmYn,'0')
                        WHEN A.SourceType = 'A' THEN IsNull(BOM3.CfmYn,'0') ELSE '0'
                        END AS BomCfmYn,
                    A.ExpClss,
                    A.SpecType,
                    A.ReqNo,
                    A.CustCd,
                    B.CustNo As CustNo,
                    B.CustNm As CustNm,
                    A.DCCd, DC.DCClass,
                    A.RevYn,
                    A.RevCnt,
                    A.FileYn,
                    A.DrawSTDate,
                    A.DrawEDDate,
                    A.S_Cnt,
                    A.M_Cnt,
                    A.B_Cnt,
                    A.PL_Cnt,
                    A.CfmYn,
                    A.CfmEmpID,
                    A.CfmDate,
                    A.StopYn,
                    A.OutYn,
                    A.OutEmpID,
                    E.EmpNm As OutEmpNm,
                    A.OutDate,
                    A.Remark,
                    A.RegEmpID,
                    A.RegDate,
                    A.UptEmpID,
                    A.UptDate,
                    F.DwPlanDate As DwPlanDate,
                    F.DwReqDate As DwReqDate,
                    CASE A.SourceType
                        WHEN 'O' THEN IsNull(O.CustPONo,'')
                        WHEN 'S' THEN IsNull(O1.CustPONo,'')
                        ELSE ''
                        END AS CustPONo,
                    CASE A.SourceType WHEN 'O' THEN IsNull(O.RefNo,'')
                        WHEN 'S' THEN IsNull(S.RefNo,'')
                        WHEN 'A' THEN IsNull(V.RefNo,'')
                        ELSE ''
                    END AS RefNo,
                    A.ProcGubun,
                    E1.EmpNm,
                    E1.EmailID AS SEmail,
                    D.EmailID AS DEmail,
                    (SELECT TOP 1 EmailID FROM TMAEmpy00 With (Nolock) WHERE EmpID IN (SELECT MEmpID FROM TMADept00 With (Nolock) WHERE DeptCd = '10000')) AS MEmail,
                    S.Ref_From,
                    M.ChRemark
                FROM TDEDwReg00 As A With (Nolock)
                Left Outer Join TMACust00 As B With (Nolock) On A.CustCd = B.CustCd
                Left Outer Join TMAEmpy00 As D With (Nolock) On A.DrawEmpID = D.EmpID
                Left Outer Join TMAEmpy00 As D1 With (Nolock) On A.DwMEmpID = D1.EmpID
                Left Outer Join TMAEmpy00 As E With (Nolock) On A.OutEmpID = E.EmpID
                Left Outer Join TDEDwReq00 As F With (Nolock) On A.ReqNo = F.ReqNo AND F.DeleteYn='N'
                Left Outer Join TSASpec00 As S With(Nolock) On A.SourceNo = S.SpecNo And A.SpecType = S.SpecType And A.SourceType = 'S' AND S.DeleteYn = 'N'
                Left Outer Join TSAOrder00 As O1 With(Nolock) On A.SourceNo = O1.SpecNo And A.SpecType = O1.SpecType And A.SourceType = 'S' AND O1.DeleteYn = 'N'
                Left Outer Join TSAOrder00 As O With(Nolock) On A.SourceNo = O.OrderNo And A.SourceType = 'O' AND O.DeleteYn = 'N'
                Left Outer Join TASRecv00 As V With(Nolock) On A.SourceNo = V.ASRecvNo And a.SourceType = 'A'
                Left Outer Join TPMBom00 As BOM1 With(Nolock) On A.SourceNo = BOM1.SourceNo And A.SourceType = 'O' And BOM1.SourceType = '1' AND BOM1.DeleteYn = 'N'
                Left Outer Join TPMBom00 As BOM2 With(Nolock) On O1.OrderNo = BOM2.SourceNo And A.SourceType = 'S' And BOM2.SourceType = '1' AND BOM2.DeleteYn = 'N'
                Left Outer Join TPMBom00 As BOM3 With(Nolock) On V.ASRecvNo = BOM3.SourceNo And A.SourceType = 'A' And BOM3.SourceType = '2' AND BOM3.DeleteYn = 'N'
                Left Outer Join TDEDCenter00 As DC With(Nolock) On A.DCCd = DC.DCCd
                Left Outer Join TMAEmpy00 As E1 With (Nolock) On
                CASE A.SourceType
                     WHEN 'O' THEN IsNull(O.EmpId,'')
                     WHEN 'S' THEN IsNull(S.EmpId,'')
                     WHEN 'A' THEN IsNull(V.EmpId,'')
                     END = E1.EmpID
                Left Outer Join TSAChSpecM M On F.RevNo = M.ChSpecNo
                WHERE A.OutDate Between '".$param['start']."' And '".$param['end']."'
                    AND A.CfmYn = '1' AND A.StopYn = 'N' AND
                    ((A.SourceNo != '' AND A.SourceType = 'O') OR (A.SourceType = 'S' AND O1.OrderNo != '') OR (A.SourceType = 'A' AND V.OrderNo != ''))
                    AND CustNm NOT like N'朗力%' AND CustNm NOT like 'RUNIPSYS%'
                    order by A.OutDate      ");
                   // AND ((A.SourceNo != '' AND A.SourceType = 'O') OR (A.SourceType = 'S' AND O1.OrderNo != '') OR (A.SourceType = 'A' AND V.OrderNo != ''))

            $shuju = array();
            $shujuY = array();
            foreach ($list as $key => $value) {
                 $TDEDwReg10 = OrderModel::TDEDwReg10($value['DrawNo'],$value['DrawAmd']);
                 if($TDEDwReg10['yingyeYN'] == 'Y'){
                    $yesfou = $this->FileURL($value['DrawNo'],$TDEDwReg10['fileName']);

                    if(!$yesfou){
                        $shuju[] = array(
                            $value['DrawNo'],$TDEDwReg10['fileName'],$TDEDwReg10['IDCardfileName']);
                    }
                    // else{
                    //     $shujuY[] = $value['DrawNo'];
                    // }

                     // $shuju[] = array(
                     //    'DrawNo'    => $value['DrawNo'],
                     //    'DrawAmd'   => $value['DrawAmd'],
                     //    'OrderNo'   => $value['OrderNo'],
                     //    'fileName'  => $TDEDwReg10
                     // );
                 }
            }
            $res = array();
            $res['shuju'] = $shuju;
            // $res['shujuY'] = $shujuY;

            return json($res);
                    // $TDEDwReg10 = OrderModel::TDEDwReg10($list['DrawNo'],$list['DrawAmd']);
            }
    }
    public function FileURL($DrawNo,$fileName)
    {
            $res['statusCode'] = '200';
            $config = config('web');
            $year = substr($DrawNo,0,4);
            $month = substr($DrawNo,4,2);
            $conn = ftp_connect($config['hostFTP'],$config['portFTP']);
            ftp_login($conn,$config['usernameFTP'],$config['passwordFTP']);
            ftp_pasv($conn,true);

            $year = substr($DrawNo,0,4);
            $month = substr($DrawNo,4,2);

            $urlname = '/'.$year.'/'.$month.'/'.$DrawNo.'/'.$fileName;
            $file_size = ftp_size($conn, $urlname);
            if($file_size > 0){
                return true;
            }else{
                return false;
            }

            ftp_close($conn);
    }

}