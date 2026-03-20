<?php
declare (strict_types = 1);

namespace app;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\App;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Config;
use think\Validate;
use think\facade\Db;
/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }
    /*
    * 返回状态码
    */
    public static function getCode($key) {
        return Config::get("statuscode.$key");
    }

    /**
     *  获取当前用户登录名
     * @return string|false
     */
    public function getUserId()
    {
        $request = request();
        $authHeader = $request->header('Authorization');
        if (!$authHeader) {
            return false;
        }
        list($type, $token) = explode(' ', $authHeader);

        if (strtolower($type) !== 'bearer' || !$token) {
            return false;
        }
        try {
            $key = Config::get('jwt.secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if($decoded->exp>time()){
                return $decoded->username;
            }else{
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     *  获取当前语言
     * @return string|false
     */
    public function getLangID()
    {
        $request = request();
        $authHeader = $request->header('Authorization');
        if (!$authHeader) {
            return false;
        }
        list($type, $token) = explode(' ', $authHeader);

        if (strtolower($type) !== 'bearer' || !$token) {
            return false;
        }
        try {
            $key = Config::get('jwt.secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if($decoded->exp>time()){
                return $decoded->langID;
            }else{
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     *获取客户端IP地址
     * @return mixed|string
     */
    public function getRequestIp() {
        // 首先检查 HTTP_X_FORWARDED_FOR 头部
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // 如果存在，取第一个 IP 地址
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // 否则检查 HTTP_CLIENT_IP
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            // 否则使用 REMOTE_ADDR
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
    public function builders(){
        $this->doBuilder('CHN');
        $this->doBuilder('KOR');
        $this->doBuilder('ENG');
    }

    /**
     * @param $langCode
     */
    public function doBuilder($langCode){

        $result = DB::connect("sqlTool")->query("select WordID,LabelCaption from brpWordInfo where LangID = ? AND (
                      ParentWordID = 'G2018020217591775079' OR
                      ParentWordID = 'G2018102616554303337' OR
                      ParentWordID = 'G2018112813040683338' OR
                      ParentWordID = 'G2018112813045447027' OR
                      ParentWordID = 'G2018112813050049799')",[$langCode]);
        $langpool = [];
        if(empty($result)){
            echo "empty";
            exit();
        }
        foreach ($result as $k => $v){
            $langpool[$v['WordID']] = $v['LabelCaption'];
        }
        $resultJson = json_encode($langpool);
        file_put_contents(app()->getRootPath().'config/lang/LANG_'.$langCode.'.json',$resultJson);
        // echo "$langCode Build Success!</br>";
    }


}
