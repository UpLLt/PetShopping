<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/13
 * Time: 下午3:23
 */

namespace Appapi\Controller;


use Advertisement\Model\BannerModel;
use Category\Model\CategoryModel;
use Common\Model\ProductModel;
use Issue\Model\ProductPetModel;

/**
 * 首页接口
 * Class HomeController
 * @package Appapi\Controller
 */
class HomeController extends ApibaseController
{
    private $banner_model;
    private $product_model;
    private $product_pet_model;
    private $category_model;


    public function __construct()
    {
        $this->banner_model = new BannerModel();
        $this->product_model = new ProductModel();
        $this->product_pet_model = new ProductPetModel();
        $this->category_model = new CategoryModel();
        parent::__construct();
    }


    public function index()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $banner = $this->banner_model->getBanner('app-home');
        foreach ($banner as $k => $v) {
            $banner[$k]['image'] = $this->setUrl($v['image']);
            if($v['type'] == 3) {
                $banner[$k]['link'] = 'https://www.mixiupet.com/Wap/Banner/artdis?id='.$v['link'];
            }
        }

        $category_food = $this->category_model->getChildString(1);
        $product_food = $this->product_model->getHotProduct($category_food, 8);

        $category_plaything = $this->category_model->getChildString(5);
        $product_plaything = $this->product_model->getHotProduct($category_plaything, 8);

        $category_medical = $this->category_model->getChildString(6);
        $product_medical = $this->product_model->getHotProduct($category_medical, 8);

        $data['banner'] = $banner;
        $data['product_pet'] = $this->product_pet_model->getHotPet();
        $data['product_food'] = $product_food;
        $data['product_plaything'] = $product_plaything;
        $data['product_medical'] = $product_medical;

        exit($this->returnApiSuccess($data));
    }
}