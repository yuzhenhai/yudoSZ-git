<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-11 9:25
 */

namespace app\controller;


use app\model\MenuModel;
use think\facade\Config;
use think\facade\Request;

class Menu extends Api
{
    /**
     *  获取首页菜单
     * @return \think\response\Json
     */
    public function getMenu()
    {
        $userId = $this->getUserId();
        $menuList = MenuModel::getMenuList($userId,Request::get()['LangID']);
        // 移除前3个菜单 YudoChina_Erp | J.Mobile | Menu
        array_splice($menuList, 0, 3);

        //获取菜单配置数据
        $config = Config::get('appmenu');
        $menuMap = [];

        foreach ($menuList as $key => $item) {
            $item['title'] = $item['MenuName'];
            // 删除不需要的字段
            $filteredItem = array_diff_key($item, array_flip(['MenuName','seq', 'AssemblyFile', 'ParameterInfo', 'FileFolder', 'ReleaseStatus', 'Category', 'OrderSeq']));

            // 检查 FormID 是否存在于 config 中并添加 image 和 page
            $formID = $filteredItem['FormID'];
            if (isset($config[$formID])) {
                $filteredItem['image'] = $config[$formID]['image'];
                $filteredItem['page'] = $config[$formID]['page'];
                $filteredItem['is_show'] = $config[$formID]['is_show'];
            }


            // 判断是否为一级菜单，只有一级菜单才初始化 items 数组
            if (!isset($filteredItem['ParentMenuID']) || empty($filteredItem['ParentMenuID'])) {
                $filteredItem['items'] = [];
            }

            // 将菜单项加入 menuMap
            $menuMap[$filteredItem['MenuID']] = $filteredItem;
        }
        // 将子菜单项添加到父菜单的 items 数组中
        foreach ($menuMap as $item) {
            if (isset($menuMap[$item['ParentMenuID']])) {
                if($item['is_show']){   //is_show控制显示
                    $menuMap[$item['ParentMenuID']]['items'][] = $item;
                }

                unset($menuMap[$item['MenuID']]); // 从顶层菜单中删除子菜单项
            }
        }
        $web = config('web');

        $version['Version'] = $web['Version'];

        $version['downloadUrl'] = $web['downloadUrlIOS'];


        // 返回最终的菜单结构
        $result = array_values($menuMap);
        return json(['statusCode' => self::getCode('SUCCESS'), 'data' => $result,'version'=>$version]);
    }
    /**
     *  获取首页菜单
     * @return \think\response\Json
     */
    public function syslogHistory()
    {
        if(Request::isPost()){
            $res['statusCode'] = '200';

            $param = Request::param();
            try {
                MenuModel::fromMenuID($this->getUserId(),$param['FormID'],$param['title'],$param['osName']);
            } catch (\Exception $e) {

            }
            return json($res);
        }
    }
    public function updatelogHistory()
    {
        if(Request::isPost()){
            $res['statusCode'] = '200';

            $param = Request::param();
            try {
                $where = array(
                    'user_id' => $this->getUserId(),
                    'log_type' => 'MPAGE',
                    'form_id' => $param['FormID'],
                );
                $list = MenuModel::getHistory($where);
                $res['list'] = $list;
                $w = array(
                    'user_id' => $this->getUserId(),
                    'log_type' => 'MPAGE',
                    'login_key' => $list['login_key'],
                );
                $data = array(
                    'logout_time' => date('Y-m-d H:i:s').'.'.substr(time(),-3),
                );
                MenuModel::SaveHistory($w,$data);
            } catch (\Exception $e) {

            }
            return json($res);
        }
    }

}