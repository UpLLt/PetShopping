<?php
namespace Web\Controller;

use Think\Controller;

/**
 * 功    能：结合ThinkSDK完成腾讯,新浪微博,人人等用户的第三方登录支付方式
 */

class OauthController extends BaseController
{

    //登录地址
    public function login($type = null){
        empty($type) && $this->error('参数错误');

        //加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = \ThinkOauth::getInstance($type);
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }


    //授权回调地址
    public function callback($type = null, $code = null){
        (empty($type) || empty($code)) && $this->error('参数错误');

        //加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns  = \ThinkOauth::getInstance($type);

        //腾讯微博需传递的额外参数
        $extend = null;
        if($type == 'tencent'){
            $extend = array('openid' => $this->_get('openid'), 'openkey' => $this->_get('openkey'));
        }

        //请妥善保管这里获取到的Token信息，方便以后API调用
        //调用方法，实例化SDK对象的时候直接作为构造函数的第二个参数传入
        //如： $qq = ThinkOauth::getInstance('qq', $token);
        $token = $sns->getAccessToken($code , $extend);

        //获取当前登录用户信息
        if(is_array($token)){
            $user_info = A('Type', 'Event')->$type($token);


            echo("<h1>恭喜！使用 {$type} 用户登录成功</h1><br>");
            echo("授权信息为：<br>");
            dump($token);
            echo("当前登录用户信息为：<br>");
            dump($user_info);


//            $session_oauth_bang=session('oauth_bang');
//            if(!empty($session_oauth_bang)){
//                $this->_bang_handle($user_info, $type, $token);
//            }else{
//                $this->_login_handle($user_info, $type, $token);
//            }

        }else{
            $this->success('登录失败！');
        }
    }


//    // 第三方账号绑定
//    public function bang($type=""){
//        if(sp_is_user_login()){
//            empty($type) && $this->error('参数错误');
//            //加载ThinkOauth类并实例化一个对象
//            import("ThinkOauth");
//            $sns  = \ThinkOauth::getInstance($type);
//            //跳转到授权页面
//            session('oauth_bang',1);
//            redirect($sns->getRequestCodeURL());
//        }else{
//            $this->error("您还没有登录！");
//        }
//    }
//
//    /**
//     * 获取登录跳转地址
//     */
//    private function _get_login_redirect(){
//        $session_login_http_referer=session('login_http_referer');
//        return empty($session_login_http_referer)?__ROOT__."/":$session_login_http_referer;
//    }
//
//    /**
//     *  处理绑定第三方账号
//     */
//    private function _bang_handle($user_info, $type, $token){
//
//        $current_uid=sp_get_current_userid();
//        $oauth_user_model = M('OauthUser');
//        $type=strtolower($type);
//        $find_oauth_user = $oauth_user_model->where(array("from"=>$type,"openid"=>$token['openid']))->find();
//        $need_bang=true;
//        if($find_oauth_user){
//
//            if($find_oauth_user['uid']==$current_uid){
//                $this->error("您之前已经绑定过此账号！",U('user/profile/bang'));exit;
//            }else{
//                $this->error("该帐号已被本站其他账号绑定！",U('user/profile/bang'));exit;
//            }
//
//        }
//
//        if($need_bang){
//
//            if($current_uid){
//                //第三方用户表中创建数据
//                $new_oauth_user_data = array(
//                    'from' => $type,
//                    'name' => $user_info['name'],
//                    'head_img' => $user_info['head'],
//                    'create_time' =>date("Y-m-d H:i:s"),
//                    'uid' => $current_uid,
//                    'last_login_time' => date("Y-m-d H:i:s"),
//                    'last_login_ip' => get_client_ip(0,true),
//                    'login_times' => 1,
//                    'status' => 1,
//                    'access_token' => $token['access_token'],
//                    'expires_date' => (int)(time()+$token['expires_in']),
//                    'openid' => $token['openid'],
//                );
//                $new_oauth_user_id=$oauth_user_model->add($new_oauth_user_data);
//                if($new_oauth_user_id){
//                    $this->success("绑定成功！",U('user/profile/bang'));
//                }else{
//                    $this->error("绑定失败！",U('user/profile/bang'));
//                }
//            }else{
//                $this->error("绑定失败！",U('user/profile/bang'));
//            }
//
//        }
//
//    }
//
//    /**
//     *  处理第三方登陆
//     */
//    private function _login_handle($user_info, $type, $token){
//        $oauth_user_model = M('OauthUser');
//        $type=strtolower($type);
//        $find_oauth_user = $oauth_user_model->where(array("from"=>$type,"openid"=>$token['openid']))->find();
//        $return = array();
//        $local_username="";
//        $need_register=true;
//        if($find_oauth_user){
//            $find_user = M('Users')->where(array("id"=>$find_oauth_user['uid']))->find();
//            if($find_user){
//                $need_register=false;
//                if($find_user['user_status'] == '0'){
//                    $this->error('您可能已经被列入黑名单，请联系网站管理员！');exit;
//                }else{
//                    session('user',$find_user);
//                    redirect($this->_get_login_redirect());
//                }
//            }else{
//                $need_register=true;
//            }
//        }
//
//        if($need_register){
//            //本地用户中创建对应一条数据
//            $new_user_data = array(
//                'user_nicename' => $user_info['name'],
//                'avatar' => $user_info['head'],
//                'last_login_time' => date("Y-m-d H:i:s"),
//                'last_login_ip' => get_client_ip(0,true),
//                'create_time' => date("Y-m-d H:i:s"),
//                'user_status' => '1',
//                "user_type"	  => '2',//会员
//            );
//            $users_model=M("Users");
//            $new_user_id = $users_model->add($new_user_data);
//
//            if($new_user_id){
//                //第三方用户表中创建数据
//                $new_oauth_user_data = array(
//                    'from' => $type,
//                    'name' => $user_info['name'],
//                    'head_img' => $user_info['head'],
//                    'create_time' =>date("Y-m-d H:i:s"),
//                    'uid' => $new_user_id,
//                    'last_login_time' => date("Y-m-d H:i:s"),
//                    'last_login_ip' => get_client_ip(0,true),
//                    'login_times' => 1,
//                    'status' => 1,
//                    'access_token' => $token['access_token'],
//                    'expires_date' => (int)(time()+$token['expires_in']),
//                    'openid' => $token['openid'],
//                );
//                $new_oauth_user_id=$oauth_user_model->add($new_oauth_user_data);
//                if($new_oauth_user_id){
//                    $new_user_data['id']=$new_user_id;
//                    session('user',$new_user_data);
//                    redirect($this->_get_login_redirect());
//                }else{
//                    $users_model->where(array("id"=>$new_user_id))->delete();
//                    $this->error("登陆失败",$this->_get_login_redirect());
//                }
//            }else{
//                $this->error("登陆失败",$this->_get_login_redirect());
//            }
//
//        }
//
//    }

}