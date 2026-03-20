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
class WordPlanModel extends BaseModel
{
     //获取部门列表
    public static function TPTMADept(){
        $list = Db::table('TMADept00')->field("DeptCd as value,DeptNm as text ")->where('Status','=','Y')->select();

        return $list;
    }


    //计划报告查询
    public static function queryAllMsg($data)
    {
        $res = self::Users($data['UserID']);
        switch ($data['auth']){
            case 'SM00040001':   //全部
                $authwhere = '';
                break;
            case 'SM00040003':   //个人加部门
                $authwhere = " AND MA.EmpId = '".$res['empId']."'";
                break;
            case 'SM00040004':
                $authwhere = " AND GA.DeptCd = '".$res['DeptCd']."'";
                break;
            case 'SM00040002':
                $authwhere = " AND GA.DeptCd = '".$res['DeptCd']."'";
                break;
            case 'SM00040005':   //管理
                $authwhere = "AND GA.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','".$res['empId']."') )";
//                $authwhere = " AND UB.MDeptCd = '$MDeptCd'";
                break;
            default:  //默认为个人
                $authwhere = " AND GA.DeptCd = '".$res['DeptCd']."'";
                break;
        }
        $where = '';

        $where .= empty($data['DeptCdCx']) ? '' :" AND MA.DeptCd = '".$data['DeptCdCx']."'";

        if($data['class'] == 'plan'){
            $where .= empty($data['number']) ? '' : " AND MA.ActPlanNo like '".$data['number']."%'";
            $where .= empty($data['startDate']) ? '' : " AND MA.ActPlanDate Between '".$data['startDate']."' AND '".$data['endDate']."'";
            $sql = "select top 50 * from
                (select
                Row_Number()over(order
                by MA.ActPlanNo desc)AS id,
                MA.ActPlanNo,
                MA.ActPlanDate,
                CASE MA.CustSysRegYn WHEN 'Y' THEN MC.CustNm WHEN 'N' THEN MA.UnRegCustNm ELSE '' END AS CustNm,
                ISNULL(UA.EmpNm,'') AS CustUserNm,
                ISNULL(Art.ActReptNo,'') AS ActReptNo,
                UB.EmpNm,
                GA.DeptNm,
                MA.Status,
                MA.CustPattern,
                MA.LocationAddr,
                MA.ActTitle,
                MA.CustSysRegYn,
                CASE MA.NewCustYn WHEN 'Y' THEN '是' WHEN 'N' THEN '否' ELSE '否' END AS NewCustYn
                from TOAActPlan00 MA With(Nolock)
                left join TOAActRept00 Art on Art.ActPlanNo = MA.ActPlanNo
                left join TMACust00 MC on MC.CustCd = MA.CustCd
                left join TMAEmpy00 UA on MA.EmpId = UA.EmpID
                left join TMAEmpy00 UB on MA.EmpID = UB.EmpID
                left join  TMADept00 GA on MA.DeptCd = GA.DeptCd
                where 1=1 $where
                $authwhere
                )T where id > '".$data['count']."' order by id asc";
        }else{
            $where .= empty($data['number']) ? '' : " AND MA.ActReptNo like '".$data['number']."%'";
            $where .= empty($data['startDate']) ? '' : " AND MA.ActReptDate Between '".$data['startDate']."' AND '".$data['endDate']."'";
            $sql = "select top 50 * from
                (select
                Row_Number()over(order by MA.ActReptNo desc)AS id,
                MA.ActReptNo,
                MA.ActReptDate,
                MA.ActPlanNo,
                MA.MeetingSubject,
                MA.MeetingPlace,
                MA.LocationAddr,
                CASE MA.CustSysRegYn WHEN 'Y' THEN MC.CustNm WHEN 'N' THEN MA.UnRegCustNm ELSE '' END AS CustNm,
                ISNULL(UA.EmpNm,'') AS CustUserNm,
                MA.ReptTitle,
                UB.EmpNm,
                GA.DeptNm,
                MA.CustPattern,
                MA.CfmYn,
                MA.CustSysRegYn
                from TOAActRept00 MA With(Nolock)
                left join TMACust00 MC on MC.CustCd = MA.CustCd
                left join TMAEmpy00 UA on MA.EmpId = UA.EmpID
                left join TMAEmpy00 UB on MA.EmpID = UB.EmpID
                left join  TMADept00 GA on MA.DeptCd = GA.DeptCd
                where 1=1 $where
                $authwhere)T where id > '".$data['count']."' order by id asc";

        }
        $list = Db::query($sql);
        return $list;
    }
    public static function Users($userID)
    {
        $sql = "select UB.DeptCd,UB.MDeptCd,UC.emp_code from TMAEmpy00 UA  With(Nolock)
                left join TMADept00 UB With(Nolock) on UB.DeptCd = UA.DeptCd
                left join sysUserMaster UC With(Nolock) on UA.EmpId = UC.emp_code and isnull(UC.emp_code,'')!=''
                 WHERE UC.user_id = '$userID'";

        $list = Db::query($sql);
        $sql = "select UD.JobNo from sysUserMaster UC With(Nolock)
                left join TMAJobc10 UD With(Nolock) on UD.EmpId = UC.emp_code
                INNER join TMAJobc00 UE With(Nolock) on UE.JobNo = UD.JobNo and UE.SaleYn = 'Y'
                WHERE UC.user_id = '$userID' and UD.LastYN = 'Y'
                and getdate() between UD.STDate and UD.EDDate";
        $JobNos = Db::query($sql);

        $res = array(
            'DeptCd'    => $list[0]['DeptCd'],
            'MDeptCd'    => $list[0]['MDeptCd'],
            'empId'    => $list[0]['emp_code']
        );
        if($JobNos){
            $res['JobNo'] = $JobNos[0]['JobNo'];
        }else{
            $res['JobNo'] = '';
        }
        return $res;
    }


    // 工作计划详情
    public static function planMinute($actPlanNo,$auth,$langCode){
        switch ($auth){
            case 'SM00040001':   //全部
                $where = '';
                break;
            case 'SM00040003':   //个人
                $where = " AND MA.EmpId = '$empId'";
                break;
            case 'SM00040002':   //部门
                $where = " AND GA.DeptCd = '$DeptCd'";
                break;
            case 'SM00040005':   //管理
                $where =  "AND GA.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','$empId') )";
                break;
            default:  //默认为个人
                $where = " AND GA.DeptCd = '$DeptCd'";
                break;
        }
        $sql = "select
                MA.ActPlanNo,
                MA.ActPlanDate,
                MA.LocationAddr,
                MA.CustCd,
                MA.EmpID,
                MA.DeptCd,
                MA.ActGubun,
                MA.RelationClass,
                ISNULL(LB.TransNm,LA.MinorNm) AS ActGubunNm,
                ISNULL(LD.TransNm,LC.MinorNm) AS RelationClassNm,
                MA.Status,
                MA.DestinationNm,
                MA.ActTitle,
                MA.ActSTDate,
                MA.ActEDDate,
                MA.ActContents,
                MA.JobReportYn,
                MA.FinishYn,
                 CASE MA.CustSysRegYn WHEN 'Y' THEN MC.CustNm WHEN 'N' THEN MA.UnRegCustNm ELSE '' END AS CustNm,
                --ISNULL(MC.CustNm,'') AS CustNm,
                ISNULL(UB.EmpNm,'') AS CustUserNm,
                UB.EmpNm,
                GA.DeptNm,
                MA.CustSysRegYn,
                MA.DistanceKm,
                MA.LocationPhoto,
                MA.CarNo,
                MA.DrtDrivingYn,
                MA.MoveMethod,
                MA.NewCustYn,
                MA.CustPattern,
                MA.LocationPhoto2,
                MA.LocationPhoto3


                from TOAActPlan00 MA With(Nolock)
                left join TMACust00 MC on MC.CustCd = MA.CustCd
                --left join TMAEmpy00 UA on MC.EmpId = UA.EmpID
                left join TMAEmpy00 UB on MA.EmpID = UB.EmpID
                left join TMADept00 GA on MA.DeptCd = GA.DeptCd
                left join TSMSyco10 LA on MA.ActGubun = LA.MinorCd
                left join TSMDict10 LB on MA.ActGubun = LB.DictCd and LB.LangCd = '$langCode'
                left join TSMSyco10 LC on MA.RelationClass = LC.MinorCd
                left join TSMDict10 LD on MA.RelationClass = LD.DictCd and LD.LangCd = '$langCode'
                where MA.ActPlanNo = '$actPlanNo' $where";
        $list = Db::query($sql);
        return $list[0];
    }
    /**
     *  工作计划详情
     * 查询 TOAActPlan00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getPlan($where)
    {
        $list = Db::table('TOAActPlan00')->where($where)->find();
        return $list;
    }
    /**
     *  工作计划详情
     * 查询 TOAActPlan00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getPlanNow($ActPlanNo)
    {
        $list = Db::table('TOAActPlan00')->where('ActPlanNo','like',$ActPlanNo.'%')->order('ActPlanNo','desc')->find();
        return $list;
    }
    /**
     *  工作计划-添加照片
     * 查询 TOAActPlan00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function savePlan($data,$where){
        $res = Db::table("TOAActPlan00")->where($where)->save($data);
        return $res;
    }
    /**
     *  工作计划-添加照片
     * 查询 TOAActPlan00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addPlan($data){
        $res = Db::table("TOAActPlan00")->insert($data);

        return $res;
    }




    // 工作报告详情
    public static function reptMinute($actReptNo,$langCode,$check,$auth){

        if($check == 'plan'){
            $where = "MA.ActPlanNo = '$actReptNo'";
        }else{
            $where = "MA.ActReptNo = '$actReptNo'";
        }

        switch ($auth){
            case 'SM00040001':   //全部
                $where .= '';
                break;
            case 'SM00040003':   //个人
                $where .= " AND MA.EmpId = '$empId'";
                break;
            case 'SM00040002':   //部门
                $where .= " AND GA.DeptCd = '$DeptCd'";
                break;
            case 'SM00040005':   //管理
                $where .=  "AND GA.DeptCd in (select DeptCd from dbo.fnMDeptCd('y','$empId') )";
                break;
            default:  //默认为个人
                $where .= " AND GA.DeptCd = '$DeptCd'";
                break;
        }
        $sql = "SELECT
                MA.ActReptNo,
                MA.ActReptDate,
                MA.ActPlanNo,
                MA.CustCd,
                MA.EmpID,
                MA.DeptCd,
                MA.ActGubun,
                MA.RelationClass,
                MA.CustPattern,
                ISNULL(LB.TransNm,LA.MinorNm) AS ActGubunNm,
                ISNULL(LD.TransNm,LC.MinorNm) AS RelationClassNm,
                ISNULL(LF.TransNm,LE.MinorNm) AS CustPatternNm,
                MA.ReptTitle,
                MA.MeetingPlace,
                MA.AttendPerson,
                MA.MeetingSubject,
                MA.CustRequstTxt,
                MA.SubjectDisTxt,
                MA.ReqConductDate,
                MA.Remark,
                MA.MeetingSTDate,
                MA.MeetingEDDate,
                MA.CfmYn,
                CASE MA.CustSysRegYn WHEN 'Y' THEN MC.CustNm WHEN 'N' THEN MA.UnRegCustNm ELSE '' END AS CustNm,
                UB.EmpNm,
                GA.DeptNm,
                MA.CustSysRegYn,

                MA.ActPlanYn,
                MA.DrtDrivingYn,
                MA.MoveMethod,
                MA.CarNo,
                MA.DestinationNm,
                MA.DistanceKm,
                MA.LocationInYn,
                MA.LocationUseYn,
                MA.LocationAddr,
                MA.LocationPhoto,
                MA.LocationPhoto2,
                MA.LocationPhoto3

                from TOAActRept00 MA With(Nolock)
                left join TMACust00 MC on MC.CustCd = MA.CustCd
                left join TMAEmpy00 UB on MA.EmpID = UB.EmpID
                left join TMADept00 GA on MA.DeptCd = GA.DeptCd
                left join TSMSyco10 LA  on MA.ActGubun = LA.MinorCd
                left join TSMDict10 LB on MA.ActGubun = LB.DictCd and LB.LangCd = '$langCode'
                left join TSMSyco10 LC  on MA.RelationClass = LC.MinorCd
                left join TSMDict10 LD on MA.RelationClass = LD.DictCd and LD.LangCd = '$langCode'
                left join TSMSyco10 LE  on MA.CustPattern = LE.MinorCd
                left join TSMDict10 LF on MA.CustPattern = LF.DictCd and LF.LangCd = '$langCode'
                where $where";
        $list = Db::query($sql);
        if(empty($list[0])){
            $res = array();
        }else{
            $res = $list[0];
        }

        return $res;
    }


    /**
     *  工作报告详情
     * 查询 TOAActRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getRept($where)
    {
        $list = Db::table('TOAActRept00')->where($where)->find();
        return $list;
    }
    /**
     *  工作报告最后一条数据
     * 查询 TOAActRept00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getReptNow($ActPlanNo)
    {
        $list = Db::table('TOAActRept00')->where('ActReptNo','like',$ActPlanNo.'%')->order('ActReptNo','desc')->find();
        return $list;
    }

    /**
     *  工作报告-保存
     * 查询 TOAActPlan00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function saveRept($data,$where){
        $res = Db::table("TOAActRept00")->where($where)->save($data);
        return $res;
    }
    /**
     *  工作报告-添加
     * 查询 TOAActPlan00 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function addRept($data){
        $res = Db::table("TOAActRept00")->insert($data);

        return $res;
    }


    /**
     *
     * 报错信息
     * 查询 TSMMsge10 表中是否有符合条件的记录
     * @param array $data 修改数据
     * @return bool
     */
    public static function getMsge($where)
    {
        $list = Db::table('TSMMsge10')->where($where)->find();
        return $list;
    }
}