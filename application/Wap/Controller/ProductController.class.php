<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/6
 * Time: 下午2:00
 */

namespace Wap\Controller;


use Common\Model\ProductModel;
use Think\Controller;

/**
 *
 * Class ProductController
 * @package Wap\Controller
 */
class ProductController extends Controller
{
    private $product_model;

    public function __construct()
    {
        $this->product_model = new ProductModel();
        parent::__construct();
    }

    public function detail($id)
    {
        $data = $this->product_model->field('content')->find($id);
        $this->assign('data', $data);
        $this->display();
    }
}