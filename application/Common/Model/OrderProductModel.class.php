<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/5
 * Time: 下午3:17
 */

namespace Common\Model;


/**
 * 一般商品
 * Class OrderProductModel
 * @package Common\Model
 */
class OrderProductModel extends OrderModel
{

    const RETURN_APPLY = 1; //退货
    //待审核
    //待收货(审核通过)
    //待退款(已收货)
    //`退款操作`
    //已退款(完成)
}