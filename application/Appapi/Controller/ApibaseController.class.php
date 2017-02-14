<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/6
 * Time: 15:12
 */

namespace Appapi\Controller;


use Common\Model\OrderModel;
use Think\Controller;
use Think\Model;

/**
 * 接口基类
 * Class ApibaseController
 * @package Appapi\Controller
 */
class ApibaseController extends Controller
{
    const SYSTEM_BUSY = 100; //系统繁忙，请求超时
    const REQUEST_SUCCESS = 200; //请求成功
    const FATAL_ERROR = 210; //存在逻辑错误(mark:描述问题)
    const TOKEN_ERROR = 220; //token无效
    const MISS_PARAM = 300; //缺少参数（mark:描述问题）
    const REQUEST_NO_POWER = 403; //权限不足
    const INVALID_INTERFACE = 404; //无效接口
    const SERVER_INTERNAL_ERROR = 404; //服务器内部错误


    public function __construct()
    {
        parent::__construct();
        $data = I('');
        if ( $_FILES ) {
            $data['files'] = json_encode($_FILES);
        }
        $data = json_encode($data);
        $datas = [
            'r_type'      => IS_POST ? 'post' : 'get',
            'request_m_f' => CONTROLLER_NAME . "/" . ACTION_NAME,
            'values'      => $data,
            'create_time' => time(),
            're_rq'       => 'request',
            'model'       => $this->requeryModel(),
            'ctime'       => date('Y-m-d'),
        ];
        D('appapi')->add($datas);
        $order = new OrderModel();
        $order->getPetOrderStatus();
    }


    private function requeryModel()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            return 'IOS';
        } else if ($_SERVER['HTTP_USER_AGENT'] == 'okhttp/3.3.1') {
            return 'Android';
        } else {
            return 'Other';
        }
    }

    /**
     * 检查token
     *
     * @param $token
     *
     * @return bool
     */
    public function checktoken($mid, $token)
    {
        $result = D('member')
            ->field('id,token,token_end_time')
            ->find($mid);
        if (!$result) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR, '登录过期，请重新登录'));
        }

        $m_token = $result['token'];
        if ($m_token != $token) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR, '登录过期，请重新登录'));
        }
        if (time() > $result['token_end_time']) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR, '登录过期，请重新登录'));
        }
        return true;
    }


    /**
     * 返回token
     */
    public function createtoken()
    {
        return md5(md5(time() . "ebuy" . rand(10, 99)));
    }


    /**
     * 返回服务器接口信息
     *
     * @param array  $data
     * @param string $token
     * @param string $code
     * $code说明
     *
     * @return json
     */
    public function returnApiSuccess($data = [], $token = "")
    {
        if (!count($data)) {
            $result['code'] = self::REQUEST_SUCCESS;
            $result['datas'] = [];
            return json_encode($result, true);
        }

        $data = self::recursionArrayChangeNullToNullString($data);
        $result['code'] = self::REQUEST_SUCCESS;
        if ($token) $result['token'] = $token;

        $result['datas'] = $data;
        return json_encode($result, true);
    }


    /**
     * 返回错误的信息
     *
     * @param $code 错误代码
     * $code说明
     *      100 - 系统繁忙，请求超时
     *      200 - 请求成功
     *      210 - 存在逻辑错误(mark:描述问题)
     *      220 - token无效
     *      300 - 缺少参数（mark:描述问题）
     *      403 - 权限不足
     *      404 - 无效接口
     *      500 - 服务器内部错误
     *
     * @return json
     */
    public function returnApiError($code, $errormsg = "", $urlencode = false)
    {
        $result['code'] = $code;
        if (!empty($errormsg)) {
            $result['mark'] = $urlencode ? urlencode($errormsg) : $errormsg;
        }
        return json_encode($result, true);
    }

    /**
     * 拼接url
     *
     * @param $param
     * 注意格式
     * array('/Home/doc',$id)
     *
     * @return string
     */
    public function geturl($param, $root = true)
    {
        $http_host = "http://" . $_SERVER['HTTP_HOST'];
        if ($root) $http_host .= __ROOT__;
        if (is_string($param))
            return $http_host . $param;
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (!empty($v)) {
                    if ($k != (count($param) - 1))
                        $url .= $v . '/';
                    else
                        $url .= $v;
                }
            }
            return $http_host . $url;
        }
    }


    /**
     * 拼接data/upload/目录
     * /20160707/577dbdd561ca7.png
     *
     * @param $img
     *
     * @return string
     */
    public function setuploadpath($img)
    {
        return '/' . C('UPLOADPATH') . $img;
    }


    /**
     * 检查参数
     *
     * @param $param
     */
    public function checkparam(array $param, $backname = self::MISS_PARAM)
    {
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (empty($v)) exit($this->returnApiError($backname, '请对照数组的健:' . $k));
            }
        } else if (is_string($param)) {
            if (empty($param)) exit($this->returnApiError($backname));
        } else {
            exit($this->returnApiError($backname));
        }
    }


    /**
     * 检查参数类型必须为数字
     *
     * @param array $param
     */
    public function checkisNumber(array $param)
    {
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (!is_numeric($v)) exit($this->returnApiError(self::FATAL_ERROR, '参数类型必须为数字'));
            }
        }
    }

    /**
     * 递归数组，将value=null改变为value=""
     *
     * @param $array
     *
     * @return array|string
     */
    function recursionArrayChangeNullToNullString($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                if (is_array($array[$k])) {
                    $array[$k] = self::recursionArrayChangeNullToNullString($array[$k]);
                } else {
                    if ($array[$k] === null) $array[$k] = "";
                }
            }
        } else {
            if ($array === null) $array = "";
        }
        return $array;
    }


    /**
     * 返回模型操作错误
     *
     * @param        $object_db
     * @param bool   $result
     * @param string $mark
     *
     * @return array
     */
    public function getDbErrorInfo($object_db, $result = false, $mark = '失败')
    {
        return [
            'result'     => $result,
            'error'      => $mark,
            'getError'   => $object_db->getError(),
            'sql'        => $object_db->getLastSql(),
            'getDbError' => M()->getDbError(),
        ];

    }


    /**
     * 获取验证码
     *
     * @param int $length
     * @param int $mode
     *
     * @return string
     */
    public function get_code($length = 32, $mode = 0)//获取随机验证码函数
    {
        switch ($mode) {
            case '1':
                $str = '123456789';
                break;
            case '2':
                $str = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case '3':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case '4':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            case '5':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                break;
            case '6':
                $str = 'abcdefghijklmnopqrstuvwxyz1234567890';
                break;
            default:
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
                break;
        }
        $checkstr = '';
        $len = strlen($str) - 1;
        for ($i = 0; $i < $length; $i++) {
            //$num=rand(0,$len);//产生一个0到$len之间的随机数
            $num = mt_rand(0, $len);//产生一个0到$len之间的随机数
            $checkstr .= $str[$num];
        }
        return $checkstr;
    }

    public function isIdCard($number)
    {
        $sigma = '';
        //加权因子
        $wi = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        //校验码串
        $ai = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        //按顺序循环处理前17位
        for ($i = 0; $i < 17; $i++) {
            //提取前17位的其中一位，并将变量类型转为实数
            $b = (int)$number{$i};
            //提取相应的加权因子
            $w = $wi[$i];
            //把从身份证号码中提取的一位数字和加权因子相乘，并累加 得到身份证前17位的乘机的和
            $sigma += $b * $w;
        }
        //echo $sigma;die;
        //计算序号  用得到的乘机模11 取余数
        $snumber = $sigma % 11;
        //按照序号从校验码串中提取相应的余数来验证最后一位。
        $check_number = $ai[$snumber];
        if ($number{17} == $check_number) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 拼接图片url
     *
     * @param $param
     *
     * @return string
     */
    public function setUrl($param)
    {
        $options_obj = M("Options");
        $option = $options_obj->where("option_name='cmf_settings'")->find();
        $settings = json_decode($option['option_value'], true);
        $http_host = 'http://' . $settings['storage']['Qiniu']['domain'] . '/';
        if (is_string($param))
            return $http_host . $param;
    }

    /**
     *检测是否登录
     */
    public function check_login()
    {
        $mid = session('mid');
        if (!$mid) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
    }


}