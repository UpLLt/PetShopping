<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/29
 * Time: 下午5:14
 */

namespace Common\Model;

/**
 * 购物车
 * Class CartModel
 * @package Common\Model
 */
class CartModel extends OrderModel
{
    const SHOPPING_TYPE_PET = 1;     //购物车 商品类型为活体宠物；
    const SHOPPING_TYPE_PRODUCT = 2; //购物车 商品类型为商品；
}