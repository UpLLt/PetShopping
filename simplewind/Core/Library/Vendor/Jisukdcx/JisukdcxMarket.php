<?php

/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/11
 * Time: 下午3:48
 */


/**
 * 支付宝极速快递查询
 * Class JisukdcxMarket
 */
class JisukdcxMarket
{

    private $host = "http://jisukdcx.market.alicloudapi.com";
    private $path = "/express/query";
    private $appcode = '';

    public function __construct($AppCode)
    {
        if (is_array($AppCode)) {
            $this->appcode = $AppCode['AppCode'];
        } else {
            $this->appcode = $AppCode;
        }
        if (!isset($this->appcode))
            return new Exception('参数错误');
    }


    /**
     * 查询
     * vendor('Jisukdcx.JisukdcxMarket');
     * $jisukdcx = new \JisukdcxMarket(C('ALI_JISUKDCX_KEY'));
     * dump($jisukdcx->query('3319087258865'));
     *
     * @param $number
     *
     * @return mixed
     */
    public function query($number)
    {
        $headers = [];
        array_push($headers, "Authorization:APPCODE " . $this->appcode);
        $querys = "number=$number&type=auto";
        $url = $this->host . $this->path . "?" . $querys;

        return $this->curl($url, $headers);
    }


    /**
     * GET 请求
     *
     * @param        $url
     * @param string $header
     *
     * @return mixed
     */
    private function curl($url, $header = '')
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $ret = curl_exec($curl);
        return $ret;
    }
}