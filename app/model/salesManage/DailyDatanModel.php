<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-11-01 10:21
 */

namespace app\model\salesManage;


use app\model\BaseModel;
use app\model\UserModel;
use think\facade\Db;

class DailyDatanModel extends BaseModel
{
    public static function queryLists($userId,$workingTag, $baseDate)
    {
        if(UserModel::isAdminUser($userId)){
            $result = Db::connect(self::$DevDb)->query("EXEC SSADayTotal_SZ2_M2_p ?, ?", [$workingTag, $baseDate])[0];
        }else{
            $empId = UserModel::getEmpID($userId);
            $result = Db::connect(self::$DevDb)->query("EXEC SSADayTotal_SZ2_M2_Perm ?, ?, ?", [$workingTag, $baseDate,$empId])[0];
        }
        $data = [];
        // 计算当前年份和前一年
        $currentYear = date('Y', strtotime($baseDate));
        $previousYear = $currentYear - 1;
        // 定义分类
        $categories = [
            110 => 'order',
            210 => 'invoice',
            310 => 'bill',
            410 => 'receipt',
            510 => 'invoicePro'
        ];
        // 遍历结果并分类存储数据
        foreach ($result as $item) {
            $categoryKey = $categories[$item['Sort']] ?? null;
            if ($categoryKey) {
                $forAmt = self::formatAmt($item['ForAmt']);
                $forAmtPre = self::formatAmt($item['ForAmt_Pre']);
                $growthRate = $item['ForAmt_Pre'] != 0 ? (($item['ForAmt'] - $item['ForAmt_Pre']) / $item['ForAmt_Pre']) * 100 : 0;

                $data[$categoryKey] = [
                    'growthRate' => number_format($growthRate, 2) . '%',
                    'rateColor' => self::percentColor(number_format($growthRate, 2) . '%'),
                    'forAmt' => $forAmt,
                    'forAmtPre' => $forAmtPre,
                    'year' => $currentYear,
                    'yearPre' => $previousYear,
                ];
            }
        }
        return $data;
    }

    public static function queryDetail($userId,$index,$gunbun,$baseDate,$langCode='')
    {
        $dept = UserModel::getCSDept();
//        $dept = UserModel::getLLSZDept();
        if (UserModel::isAdminUser($userId)) {
            $result = Db::connect(self::$DevDb)->query("EXEC SSADayTotal_SZ2_M2_p ?, ?", [$gunbun, $baseDate])[0];
        } else {
            $empId = UserModel::getEmpID($userId);
            $result = Db::connect(self::$DevDb)->query("EXEC SSADayTotal_SZ2_M2_Perm ?, ?, ?", [$gunbun, $baseDate, $empId])[0];
        }

        return self::PreDetail($result,$dept,$index);

    }

    public static function PreDetail($result, $dept, $index)
    {
        $categories = [
            'order' => '100',
            'invoice' => '200',
            'bill' => '300',
            'receipt' => '400',
            'invoicePro' => '500'
        ];
        $index = $categories[$index];
        $total = $total_internal = $total_External = [
            'Amt' => 0,
            'AmtPre'=> 0,
            'rate'=>0,
            'color'=>''
        ];
        // 过滤符合 index 的数据
        $filteredData = array_filter($result, function($item) use ($index) {
            return $item['Sort'] == $index;
        });
        // 去除部门信息中每个项的尾部空格
        $dept = array_map(function($item) {
            return [
                'EmpID' => rtrim($item['EmpID']),
                'DeptCd' => $item['DeptCd'],
                'DeptNm' => $item['DeptNm'],
                'EmpNm' => $item['EmpNm'],
                'Status' => $item['Status'],
                'HDeptCd' => $item['HDeptCd']
            ];
        }, $dept);
        // 为部门信息创建 DeptCd 索引，提高匹配速度
        $deptIndex = [];
        foreach ($dept as $item) {
            $deptIndex[$item['DeptCd']] = $item;
        }
        // 按 ExternalGubun 分隔数据
        $external = [];
        $internal = [];
        foreach ($filteredData as $item) {
            $item['MEmpID'] = rtrim($item['MEmpID']);
            $item['MDeptCd'] = isset($item['MDeptCd']) ? rtrim($item['MDeptCd']) : '';
            if ($item['ExternalGubun'] == 1) {
                $external[] = $item;
            } else {
                $internal[] = $item;
            }
        }

        foreach($internal as &$item){
            $item['rate'] = ($item['ForAmt_Pre'] != 0)
                ? self::formatAmt(($item['ForAmt'] - $item['ForAmt_Pre']) / $item['ForAmt_Pre'] * 100)
                : '0.00';

            $item['AmtPre'] = self::formatAmt($item['ForAmt_Pre']);
            $item['Amt'] = self::formatAmt($item['ForAmt']);
            $item['color'] = self::percentColor($item['rate']);
            $total_internal['Amt'] += $item['ForAmt'];
            $total_internal['AmtPre'] += $item['ForAmt_Pre'];
        }

        $total['Amt'] +=  $total_internal['Amt'];
        $total['AmtPre'] +=  $total_internal['AmtPre'];
        $total_internal['rate'] = ($total_internal['AmtPre'] != 0)
            ? self::formatAmt(($total_internal['Amt'] - $total_internal['AmtPre']) / $total_internal['AmtPre'] * 100)
            : '0.00';
        $total_internal['rate'] = $total_internal['rate']==0?'0.00':$total_internal['rate'];
        $total_internal['rate'] = $total_internal['rate']=='-100.00'?'0.00':$total_internal['rate'];
        $total_internal['color'] = self::percentColor($total_internal['rate']);
        $total_internal['Amt'] = self::formatAmt($total_internal['Amt']);
        $total_internal['AmtPre'] = self::formatAmt($total_internal['AmtPre']);
        $total_internal['DeptNm'] = 'Internal';

        foreach($external as $value){
            $total_External['Amt'] += $value['ForAmt'];
            $total_External['AmtPre'] += $value['ForAmt_Pre'];
        }

        $total['Amt'] +=  $total_External['Amt'];
        $total['AmtPre'] +=  $total_External['AmtPre'];
        $total['rate'] = ($total['AmtPre'] != 0)
            ? self::formatAmt(($total['Amt'] - $total['AmtPre']) / $total['AmtPre'] * 100)
            : '0.00';

        $total['Amt'] = self::formatAmt($total['Amt']);
        $total['AmtPre'] = self::formatAmt($total['AmtPre']);
        $total['color'] = self::percentColor($total['rate']);
        $total_External['rate'] = ($total_External['AmtPre'] != 0)
            ? self::formatAmt(($total_External['Amt'] - $total_External['AmtPre']) / $total_External['AmtPre'] * 100)
            : '0.00';
        $total_External['rate'] = $total_External['rate']==0?'0.00':$total_External['rate'];
        $total_External['rate'] = $total_External['rate']=='-100.00'?'0.00':$total_External['rate'];
        $total_External['color'] = self::percentColor($total_External['rate']);
        $total_External['Amt'] = self::formatAmt($total_External['Amt']);
        $total_External['AmtPre'] = self::formatAmt($total_External['AmtPre']);
        // 合并 external 数据的部门信息

        foreach ($external as &$value) {
            $deptItem = $deptIndex[$value['DeptCd']] ?? null;
            if ($deptItem) {
                $value = array_merge($value, [
                    'DeptNm' => $deptItem['DeptNm'],
                    'EmpNm' => $deptItem['EmpNm'],
                    'Status' => $deptItem['Status'],
                    'MEmpID' => $deptItem['EmpID'],
                    'MDeptCd' => $deptItem['HDeptCd'],
                ]);
            }
        }


        unset($value); // 清除引用
        // 按 `EmpNm` 分组 external 数据
        $_external = [];
        foreach ($external as $itt) {
            if(isset($itt['EmpNm'])){
                $_external[$itt['EmpNm']][] = $itt;
            }

        }
        // 更新数据的金额
        foreach ($dept as &$item) {
            foreach ($external as $value) {
                if ($item['DeptCd'] == $value['DeptCd']) {
                    $item['AmtPre'] = $value['ForAmt_Pre'];
                    $item['Amt'] = $value['ForAmt'];
                }
            }
        }

        unset($item);
        // 构建层级结构
        function buildHierarchy($data, $parentCd = ['07000', '16000'])
        {
            $result = [];
            foreach ($data as $item) {
                if (in_array($item['HDeptCd'], $parentCd)) {
                    $children = buildHierarchy($data, [$item['DeptCd']]);
                    $item['children'] = array_filter($children, function($child) {
                        return $child['Status'] !== 'N' || isset($child['AmtPre']);
                    });
                    if (($item['Status'] !== 'N' || isset($item['AmtPre'])) && (!empty($item['children']) || isset($item['AmtPre']))) {
                        $result[] = $item;
                    }
                }
            }
            return $result;
        }
        $data = buildHierarchy($dept);
        // 将部门数据按 EmpNm 分组
        $_data = [];
        foreach ($data as $item) {
            if (!isset($_data[$item['EmpNm']])) {
                $_data[$item['EmpNm']] = [
                    'EmpNm' => $item['EmpNm'],
                    'EmpID' => $item['EmpID'],
                    'children' => []
                ];
            }
            $_data[$item['EmpNm']]['children'][] = $item;
        }
        // 递归处理层级结构，计算 Amt 和 AmtPre 和
        function processData(&$data)
        {
            foreach ($data as &$item) {
                $amtSum = 0;
                $amtPreSum = 0;
                if (isset($item['children']) && is_array($item['children']) && count($item['children']) > 0) {
                    processData($item['children']);

                    foreach ($item['children'] as $child) {
                        $amtSum += $child['Amt'] ?? 0;
                        $amtPreSum += $child['AmtPre'] ?? 0;
                    }
                }
                $amtSum += $item['Amt'] ?? 0;
                $amtPreSum += $item['AmtPre'] ?? 0;
                $item['Amt'] = $amtSum;
                $item['AmtPre'] = $amtPreSum;
            }
        }
        processData($_data);
        $external = $_data;
        self::processData($external);
        $external =  array_values($external);
        foreach ($external as &$item) {  // 引用外层数组
            foreach ($item['children'] as &$value) {  // 引用中间数组
                foreach ($value['children'] as &$child) {  // 引用最内层数组
                    // 确保 EmpID 存在且与父级 EmpID 不相同
                    if (isset($child['EmpID']) && $child['EmpID'] != $value['EmpID']) {
                        $value['parent'] = $child['EmpNm'];
                    }

                }
            }
        }

        return [
          'external'=>$external,
          'internal'=>$internal,
          'total_internal'=>$total_internal,
          'total_external'=>$total_External,
          'total'=>$total,
        ];
    }


    public static function processData(&$data)
    {
        foreach ($data as &$item) {
            // 处理当前项的 ForAmt 和 Rate
            if (isset($item['AmtPre']) && isset($item['Amt']) && $item['AmtPre'] != 0) {
                $item['rate'] = ($item['AmtPre'] != 0) ? self::formatAmt(($item['Amt'] - $item['AmtPre']) / $item['AmtPre'] * 100) : '0.00';
            } else {
                $item['rate'] = '0.00'; // 防止除零错误
            }
            $item['color'] = self::percentColor($item['rate']);
            // 格式化金额
            $item['AmtPre'] = self::formatAmt($item['AmtPre']);
            $item['Amt'] = self::formatAmt($item['Amt']);
            // 递归处理 children 子项
            if (isset($item['children']) && is_array($item['children'])) {
                self::processData($item['children']);
            }
        }
    }





}
