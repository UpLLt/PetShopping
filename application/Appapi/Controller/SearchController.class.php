<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/7/15
 * Time: 12:33
 */

namespace Appapi\Controller;
use Category\Model\CategoryModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Issue\Model\ProductPetModel;
/**
 * 搜索
 */

/**
 *
 * Class WalletController
 * @package Appapi\Controller
 */
class SearchController extends ApibaseController
{
    private $product_pet_model;
    private $product_model;
    private $product_option_model;
    private $category_model;


    public function __construct()
    {
        parent::__construct();
        $this->product_pet_model = new ProductPetModel();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->category_model = new CategoryModel();
    }


    public function search(){
        $type    = I('post.type'); //商品类型 1/商品 2/活体宠物
        $keyword = I('post.keyword'); //关键字
        $ptype   = I('post.ptype'); //宠物类型
        $category = I('category_id');

        $where['pet_type'] = $ptype;
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $this->checkparam(array($type,$ptype));

        if($type == 1 ){
            $where['pro_name'] = array('like','%'.$keyword.'%');
            if( $category ){
                $category = $this->category_model->where('parentid='.$category)->select();
            }
            $cate_str = '';
            foreach( $category as $k => $v ){
                $cate_str .= $cate_str  ? ','.$v['id'] : $v['id'];
            }

            $where['category_id'] = array('in' ,$cate_str);

            $product = $this->product_model
                ->where($where)
                ->field('id,pro_name,smeta,sales_volume,pro_shop_type,pro_thirdparty_url')
                ->select();


            foreach( $product as $key => $val ){
                $picture = json_decode( $val['smeta'],true);
                $option = $this->product_option_model->where('product_id='.$val['id'])->min('option_price');
                $product[$key]['smeta'] = setUrl( $picture[0]['url'] );
                $product[$key]['price'] = $option;

            }

        }
        if($type == 2 ){
            $where['pet_type'] = $ptype;
            $where['pet_name'] = array('like','%'.$keyword.'%');
            $where['status'] = 0;
            $product = $this->product_pet_model
                ->where( $where )
                ->field('id,pet_name,pet_price,pet_picture')
                ->select();

            foreach( $product as $k => $v ){
                $picture = json_decode( $v['pet_picture'],true);
                $product[$k]['cover'] = setUrl( $picture['0']['url'] );

            }
        }

        $data['lists'] = $product;

        exit($this->returnApiSuccess($data));

    }


}