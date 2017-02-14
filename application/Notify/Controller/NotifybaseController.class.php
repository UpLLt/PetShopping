<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/25
 * Time: 14:04
 */

namespace Notify\Controller;


use Common\Model\OrderModel;
use Common\Model\OrderProductModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Consumer\Model\MemberModel;
use Think\Controller;

/**
 * 回调订单处理业务逻辑
 * Class NotifybaseController
 * @package Notify\Controller
 */
class NotifybaseController extends Controller
{
    public $order_model;
    public $order_product_model;
    public $product_model;
    public $member_model;
    private $product_option_model;

    public function __construct()
    {
        parent::__construct();
        $this->order_model = new OrderModel();
        $this->order_product_model = new OrderProductModel();
        $this->product_model = new ProductModel();
        $this->member_model = new MemberModel();
        $this->product_option_model = new ProductOptionModel();
    }


    /**
     * 商品
     * 库存变更
     * 销量变更
     */
    public function product_change($order_id)
    {
        if (!$order_id) return false;
        $order_data = $this->order_model->find($order_id);

        $order_product_data = $this->order_product_model->where(['order_id' => $order_data['id']])->select();

        foreach ($order_product_data as $k => $v) {
            $v['snapshot'] = json_decode($v['snapshot'], true);
            $this->product_model
                ->where(['id' => $v['product_id']])
                ->save(['sales_volume' => ['exp', 'sales_volume+' . $v['quantity']]]);
            $this->product_option_model
                ->where(['option_key_id' => $v['snapshot']['option_key_id']])
                ->save(['inventory' => ['exp', 'inventory-' . $v['quantity']]]);
        }
        return true;
    }

}