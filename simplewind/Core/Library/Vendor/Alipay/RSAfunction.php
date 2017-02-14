<?php

/**
 * Created by demo_rongyiyuan.
 * User: yunlongw
 * Date: 2015/10/28
 * Time: 18:59
 */

/**
 * RSA签名
 *
 * @param $data 待签名数据
 * @param $private_key_path 商户私钥文件路径
 * return 签名结果
 */
function rsaSign($data, $private_key_path)
{
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
    openssl_sign($data, $sign, $res);
    \Think\Log::record(json_encode(
        [
            'data' => $data,
            'res'  => $res,
            'sign' => $sign,
        ]
    ),\Think\Log::INFO);
    openssl_free_key($res);
    //base64编码
    $sign = base64_encode($sign);
    return $sign;
}

///**
// * RSA签名
// * @param $data 待签名数据
// * @param $private_key 商户私钥字符串
// * return 签名结果
// */
//function rsaSign($data, $private_key) {
//    //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
//    $private_key=str_replace("-----BEGIN RSA PRIVATE KEY-----","",$private_key);
//    $private_key=str_replace("-----END RSA PRIVATE KEY-----","",$private_key);
//    $private_key=str_replace("\n","",$private_key);
//
//    $private_key="-----BEGIN RSA PRIVATE KEY-----".PHP_EOL .wordwrap($private_key, 64, "\n", true). PHP_EOL."-----END RSA PRIVATE KEY-----";
//
//    $res=openssl_get_privatekey($private_key);
//
//    if($res)
//    {
//        openssl_sign($data, $sign,$res);
//    }
//    else {
//        echo "您的私钥格式不正确!"."<br/>"."The format of your private_key is incorrect!";
//        exit();
//    }
//    openssl_free_key($res);
//    //base64编码
//    $sign = base64_encode($sign);
//    return $sign;
//}

///**
// * RSA验签
// * @param $data string 待签名数据
// * @param $ali_public_key_path string 支付宝的公钥文件路径
// * @param $sign string 要校对的的签名结果
// * return 验证结果
// */
//function rsaVerify($data, $sign, $ali_public_key_path)
//{
////    logResult("RSA data ======= " . $data);
//    $pubKey = file_get_contents($ali_public_key_path);
//    $res = openssl_get_publickey($pubKey);
////    logResult("RSA Res ======= " . $res);
//    $result = openssl_verify($data, base64_decode($sign), $res);
////    logResult("RSA Result ======= " . $result);
//    openssl_free_key($res);
//    if($result == 1){
//        return true;
//    }
//    return false;
//}

/**
 * RSA验签
 *
 * @param $data 待签名数据
 * @param $alipay_public_key 支付宝的公钥字符串
 * @param $sign 要校对的的签名结果
 * return 验证结果
 */
function rsaVerify($data, $sign, $alipay_public_key)
{
    //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
    $alipay_public_key = str_replace("-----BEGIN PUBLIC KEY-----", "", $alipay_public_key);
    $alipay_public_key = str_replace("-----END PUBLIC KEY-----", "", $alipay_public_key);
    $alipay_public_key = str_replace("\n", "", $alipay_public_key);

    $alipay_public_key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL . wordwrap($alipay_public_key, 64, "\n", true) . PHP_EOL . '-----END PUBLIC KEY-----';
    $res = openssl_get_publickey($alipay_public_key);
    if ($res) {
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
    } else {
        echo "您的支付宝公钥格式不正确!" . "<br/>" . "The format of your alipay_public_key is incorrect!";
        exit();
    }
    openssl_free_key($res);
    return $result;
}

/**
 * RSA解密
 *
 * @param $content 需要解密的内容，密文
 * @param $private_key_path 商户私钥文件路径
 * return 解密后内容，明文
 */
function rsaDecrypt($content, $private_key_path)
{
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
    //用base64将内容还原成二进制
    $content = base64_decode($content);
    //把需要解密的内容，按128位拆开解密
    $result = '';
    for ($i = 0; $i < strlen($content) / 128; $i++) {
        $data = substr($content, $i * 128, 128);
        openssl_private_decrypt($data, $decrypt, $res);
        $result .= $decrypt;
    }
    openssl_free_key($res);
    return $result;
}