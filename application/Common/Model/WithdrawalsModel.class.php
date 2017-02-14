<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/29
 * Time: 下午5:14
 */

namespace Common\Model;

/**
 * 提现记录
 * Class CartModel
 * @package Common\Model
 */
class WithdrawalsModel extends CommonModel
{
    const EXAMINE_WAIT    = 1; //待审核
    const EXAMINE_ADONT   = 2; //审核通过 /正在转款
    const EXAMINE_ERROR   = 3; //审核失败
    const WITHDRA_SUCCESS = 4; //提现完成


    const WIDTH_TYPE_USER = 1; //提现类型 用户提现
    const WIDTH_TYPE_PRODUCT = 2; //提现类型 商家提现


    public function getBank(){
        $data = [
            [ 'bcode'=> '10010' , 'bname' => '中国银行'],
            [ 'bcode'=> '10020' , 'bname' => '中国农业银行'],
            [ 'bcode'=> '10030' , 'bname' => '中国建设银行'],
            [ 'bcode'=> '10040' , 'bname' => '中国工商银行'],
            [ 'bcode'=> '10050' , 'bname' => '中国民生银行'],
            [ 'bcode'=> '10060' , 'bname' => '中国交通银行'],
            [ 'bcode'=> '10070' , 'bname' => '中国邮政银行'],
        ];
        return $data;
    }

    public function getStatusValus( $status ){
        switch( $status ) {
            case self::EXAMINE_WAIT:
                return '待审核';
                break;
            case self::EXAMINE_ADONT:
                return '审核通过';
                break;
            case self::EXAMINE_ERROR:
                return '审核失败';
                break;
            case self::WITHDRA_SUCCESS:
                return '提现完成';
                break;
        }
    }

    public function getValusString( $status ){
        switch( $status ) {
            case self::EXAMINE_WAIT:
                return '请等待后台审核';
                break;
            case self::EXAMINE_ADONT:
                return '转款中，请等待';
                break;
            case self::EXAMINE_ERROR:
                return '';
                break;
            case self::WITHDRA_SUCCESS:
                return '平台已转款，请注意查收';
                break;
        }
    }


    public function getBankStr($bcode){
        switch ( $bcode ){
            case  10010:
                return "中国银行";
                break ;
            case  10020:
                return "中国农业银行";
                break ;
            case  10030:
                return "中国建设银行";
                break ;
            case  10040:
                return "中国工商银行";
                break ;
            case  10050:
                return "中国民生银行";
                break ;
            case  10060:
                return "中国交通银行";
                break ;
            case  10070:
                return "中国邮政银行";
                break ;
        }
    }

}