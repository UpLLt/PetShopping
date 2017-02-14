<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/29
 * Time: 下午4:27
 */

namespace Wap\Controller;


use Issue\Model\ProductPetModel;
use Think\Controller;

/**
 * Class ProductPetController
 * @package Wap\Controller
 */
class ProductPetController extends Controller
{
    private $product_pet_model;

    public function __construct()
    {
        $this->product_pet_model = new ProductPetModel();
        parent::__construct();
    }

    public function detail($id)
    {
        if (empty($id)) $this->display();

        $result = $this->product_pet_model->field('pet_content')->find($id);
        $this->assign('data', $result);
        $this->display();
    }
}