<?php
/**
 * Class User
 * @package app\controller
 * 用户操作接口,如登录验证、获取用户数据、菜单权限等
 */

namespace app\controller;

use app\BaseController;
use app\common\CurlHelper;
use think\App;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\Config;
use Firebase\JWT\JWT; // 需要安装 firebase/php-jwt
use Firebase\JWT\Key;
use app\model\UserModel;
class User extends BaseController
{

    public function __construct(App $app)
    {
        parent::__construct($app);

    }
    public function getLanguage()
    {
        $data = Request::get();
        $langID = empty($data['langID'])?'CHN':$data['langID'];
        return json($this->Language($langID));
    }

    /**
     *登录接口
     * @return \think\response\Json
     */
    public function login()
    {
        try{
            // 获取请求参数
            $data = Request::post();
            $user_id = $data['username'];
            $password = $data['password'];
            $device_id = $data['deviceCode'];
            $langID = isset($data['langID'])?$data['langID']:'CHN';
            //验证权限
            if(!UserModel::valid($user_id)){
                return json([
                    'statusCode' => self::getCode('AUTH_FAILURE'),
                    'message' => '没有使用权限',
                ]);
            }

            //验证账户密码
            if(!UserModel::verify($user_id,$password)){
                return json([
                    'statusCode' => self::getCode('AUTH_FAILURE'),
                    'message' => '账户密码验证失败',
                ]);
            }
            //验证设备码及账户
            if(!UserModel::verifyDevice($user_id,$device_id)){
                return json([
                    'statusCode' => self::getCode('DEVICE_FAILURE'),
                    'message' => '设备未注册验证',
                ]);
            }
            $userInfo = UserModel::getUserDeptInfo($user_id);
            // 登录成功，生成 JWT token
            $key = Config::get('jwt.secret'); // 从配置中获取密钥
            $exptime = time()+43200; //12小时过期
            $payload = [
                'iat' => time(), // 签发时间
                'exp' => $exptime, // 过期时间
                'username' => $data['username'], // 用户名
                'langID' => $langID,//多语言
            ];
            $accessToken = JWT::encode($payload, $key,'HS256'); // 生成 token
            // 返回成功响应
            return json([
                'statusCode' => self::getCode('SUCCESS'),
                'message' => '登录成功',
                'data' => [
                    'access_token' => $accessToken, // 返回 token
                    'username' => $data['username'],
                    'userInfo'=> $userInfo,
                    'exptime'=>$exptime,
                    'langs' => $this->Language($langID)
                ]
            ]);
        }catch (\Exception $e){

        }

    }

    public function login2()
    {
        // 获取请求参数
        $data = Request::post();
        $user_id = $data['username'];
        $password = $data['password'];
        $device_id = $data['deviceCode'];
        $langID = isset($data['langID'])?$data['langID']:'CHN';

        //验证权限
        if(!UserModel::valid2($user_id)){
            return json([
                'statusCode' => self::getCode('AUTH_FAILURE'),
                'message' => '没有使用权限',
            ]);
        }

        //验证账户密码
        if(!UserModel::verify($user_id,$password)){
            return json([
                'statusCode' => self::getCode('AUTH_FAILURE'),
                'message' => '账户密码验证失败',
            ]);
        }
        //验证设备码及账户
        if(!UserModel::verifyDevice($user_id,$device_id)){
            return json([
                'statusCode' => self::getCode('DEVICE_FAILURE'),
                'message' => '设备未注册验证',
            ]);
        }
        $userInfo = UserModel::getUserDeptInfo($user_id);
        // 登录成功，生成 JWT token
        $key = Config::get('jwt.secret'); // 从配置中获取密钥
        $exptime = time()+28000; //8小时过期
        $payload = [
            'iat' => time(), // 签发时间
            'exp' => $exptime, // 过期时间
            'username' => $data['username'], // 用户名
        ];
        $accessToken = JWT::encode($payload, $key,'HS256'); // 生成 token
        // 返回成功响应
        return json([
            'statusCode' => self::getCode('SUCCESS'),
            'message' => '登录成功',
            'data' => [
                'access_token' => $accessToken, // 返回 token
                'username' => $data['username'],
                'userInfo'=> $userInfo,
                'exptime'=>$exptime,
                'langs' => $this->Language($langID)
            ]
        ]);
    }

    /**
     *设备验证接口
     * @return \think\response\Json
     */
    public function device()
    {
        $data = Request::post();
        $device_id = $data['deviceCode'];
        $user_id = $data['registerId'];
        if(!UserModel::getUser($user_id)){
            return json(['statusCode'=>self::getCode('PARAM_ERROR'),'message'=>'用户不存在']);
        }
        $device_token = '';
        $device_type = 'A'; // I/A IOS 安卓
        $use_yn = 'N';
        $insert_userid = $user_id;
        $insert_time = date('Y-m-d H:i:s.') . substr((microtime(true) - floor(microtime(true))) * 1000, 0, 3);
        $insert_pc = $this->getRequestIp();

        // 将插入数据放入数组
        $insert_data = [
            'user_id'=>$user_id,
            'device_id' => $device_id,
            'insert_userid' => $insert_userid,
            'device_token' => $device_token,
            'device_type' => $device_type,
            'use_yn' => $use_yn,
            'insert_time' => $insert_time,
            'insert_pc' => $insert_pc,
        ];
        try {
            // 插入数据到数据库
            $result = Db::table('sysUserMobileDevice')->insert($insert_data);
            // 检查结果
            if ($result) {
                return json(['statusCode' => self::getCode('SUCCESS'), 'message' => '数据插入成功']);
            } else {
                return json(['statusCode' => self::getCode('DB_OPERATION_FAIL'), 'message' => '数据插入失败']);
            }
        } catch (\Exception $e) {
            return json(['statusCode' => self::getCode('DB_OPERATION_FAIL'), 'message' => $e->getMessage()]);
        }

    }

    /**
     * 修改密码接口
     * 因为User有登录和注册设别 所以这个类没有检查access_token
     * 修改密码需要检查 所以单独检查access_token
     * @return \think\response\Json
     */
    public function changePassword()
    {
        $user_id = $this->getUserId();
        $data = Request::post();
        $password = $data['password'];
        $newPassword = $data['newPassword'];
        // 验证当前密码
        if(UserModel::verify($user_id,$password)){
            if(UserModel::changePassword($user_id,$newPassword)){
                return json(['statusCode'=>self::getCode('SUCCESS'),'message'=>'密码修改成功']);
            }else{
                return json(['statusCode'=>self::getCode('DB_OPERATION_FAIL'),'message'=>'密码修改失败,请重试']);
            }
        }
        return json(['statusCode'=>self::getCode('AUTH_FAILURE'),'message'=>'当前密码输入错误']);

    }


    public function getUsers()
    {
        $post = Request::post();
        $result = UserModel::getUsers($post['userId'],$post['userNm'],$post['deptNm']);
        return json([
            'statusCode' => self::getCode('SUCCESS'),
            'result'=>$result
        ]);
    }

    public function Language($LangID)
    {
        // $LangID = "CHN";
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
