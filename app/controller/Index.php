<?php
namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Request;
class Index extends BaseController
{
    public function index()
    {
        // dump(config('web'));

    }
    /**1
     * 作者: YUZH
     * 说明: 获取APP 安装包
     *
     * 开始日期: 2024.10.10
     * 结束日期: 2024.10.11
     * ---
     */
    public function Version()
    {
        if(Request::isPost()){
            $param = Request::param();
            $config = config('web');

            $data['Version'] = $config['Version'];
            if($param['osName'] == 'ios'){
                $data['downloadUrl'] = $config['downloadUrlIOS'];
            }else{
                $data['downloadUrl'] = $config['downloadUrl'];
            }

            return json($data);
        }

    }
    public function LangFile()
    {
        $this->builders();
    }
    public function Language()
    {
        $LangID = 'KOR';
        $langs = json_decode(file_get_contents(app()->getRootPath().'config/lang/LANG_'.$LangID.'.json'),true);
        $lists = $testConfig = \think\facade\Config::get('zh-cn');
        $res = array();
        foreach($lists as $k=>$v){

            if(isset($langs[$v])){
                $res[$k] = $langs[$v];
            }else{
                $res[$k] = '';
                // dump($k.'---'.$v);
            }
        }
        dump($res);
    }


}
