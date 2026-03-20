<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-09-30 15:38
 */

namespace app\controller;

use app\BaseController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\App;
use think\facade\Db;
use think\facade\Config;
use think\exception\HttpResponseException;

class Api extends BaseController
{
    // 保存解码后的用户信息
    protected $user;

    public function __construct(App $app)
    {
        // 调用父类构造函数
        parent::__construct($app);

        // 获取请求对象
        $request = request();

        // 获取 Authorization 头部
        $authHeader = $request->header('Authorization');

        // 检查是否提供了 Authorization 头部
        if (!$authHeader) {
            $response = json(['statusCode' => self::getCode('TOKEN_NULL'), 'message' => '未提供访问令牌'], self::getCode('TOKEN_EXPIRED'));
            throw new HttpResponseException($response);
        }

        // 提取 token，使用空格分割 Authorization 头部
        list($type, $token) = explode(' ', $authHeader);

        // 检查 token 类型是否为 Bearer，并且 token 是否存在
        if (strtolower($type) !== 'bearer' || !$token) {
            $response = json(['statusCode' => self::getCode('TOKEN_EXPIRED'), 'message' => '无效的访问令牌'], self::getCode('TOKEN_EXPIRED'));
            throw new HttpResponseException($response);
        }

        // 尝试解析和验证 JWT
        try {
            // 从配置中获取密钥
            $key = Config::get('jwt.secret');
            // 解码 token，使用 HS256 加密算法
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            // 将解码后的用户信息存储在控制器的 $user 属性中
            $this->user = $decoded;
        } catch (\Exception $e) {
            $response = json(['statusCode' => self::getCode('TOKEN_EXPIRED'), 'message' => '无效的访问令牌'], self::getCode('TOKEN_EXPIRED'));
            throw new HttpResponseException($response);
        }
    }

    public function call($DB,$spName,$input,$output){
        return Db::connect($DB)->query("EXEC $spName $input;", $output);
    }

    //选择数据路
    public function db($DB){
        switch ($DB) {
            case 'SZ':
                $db = 'sqlSZsrv';
                break;
            case 'GD':
                $db = 'sqlGDsrv';
                break;
            case 'QD':
                $db = 'sqlQDsrv';
                break;
            case 'XR':
                $db = 'sqlXRsrv';
                break;
            case 'HS':
                $db = 'sqlHSsrv';
                break;
            case 'LLSZ':
                $db = 'sqlRASZsrv';
                break;
            case 'SH':
                $db = 'sqlYCHsrv';
                break;
            case 'LL':
                $db = 'sqlYCHsrv';
                break;
            case 'CL':
                $db = 'sqlYCHsrv';
                break;
            case 'ABE':
                $db = 'sqlYCHsrv';
                break;

            default:
                $db = 'sqlsrv';

                break;
        }
        return $db;

    }

    public function Language($LangID)
    {
        // $LangID = 'CHN';
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
        return $res;
    }


}