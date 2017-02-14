<?php
namespace Web\Controller;

use Advertisement\Model\BannerImageModel;
use Advertisement\Model\BannerModel;

use Category\Model\CategoryModel;
use Common\Model\PetTypeModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Issue\Model\ProductPetModel;
use Think\Controller;

/**
 * 宠物首页
 * Class IndexController
 * @package Web\Controller
 */

class IndexController extends BaseController
{
    private $banner_model;
    private $banner_image_model;
    private $pet_type_model;
    private $product_pet_model;
    private $product_model;
    private $category_model;
    private $product_option_model;

    public function __construct()
    {
        parent::__construct();
        $this->banner_model = new BannerModel();
        $this->banner_image_model = new BannerImageModel();
        $this->product_pet_model = new ProductPetModel();
        $this->pet_type_model = new PetTypeModel();
        $this->product_model = new ProductModel();
        $this->category_model = new CategoryModel();
        $this->product_option_model = new ProductOptionModel();

    }

    public function index()
    {
        $this->pet();
        $this->product();
        $this->hot();
        $this->display();
    }




    public function changetype(){
        $type = I('type');
        if( $type == 1 ){
            session( 'ptype',1 );
        }
        if( $type == 2 ){
            session( 'ptype',2 );
        }

        $this->redirect('Web/Index/index');
    }

    /**
     * 获取活体宠物 类型

     */
    public function pet(  ){

        $type   = session('ptype') ? session('ptype') : '';

        $join   =  "LEFT JOIN ". C('DB_PREFIX') ."banner_image as b on a.id = b.banner_id";
        $banner =  $this->banner_model
            ->alias('a')
            ->join($join)
            ->order('b.sort_order')
            ->where(array('a.type'=>BannerModel::TYPE_SIDE))
            ->select();

        $where['status']   = 0;
        $where['show']     = 1;
        if($type) $where['pet_type'] = $type;

        $product = $this->product_pet_model
                        ->where( $where )
                        ->limit(8)
                        ->field('id,pet_name,pet_price,pet_picture')
                        ->select();

       foreach( $product as $k => $v ){
           $picture = json_decode( $v['pet_picture'],true);
           $product[$k]['picture'] = setUrl( $picture['0']['url'] );
       }


        if($type) $where_ca['pet_type'] = $type;
       $category_pet = $this->pet_type_model->where( $where_ca )->limit(5)->field('pet_variety_id,pet_variety')->select();
       $this->assign('ptt_banner',setUrl($banner['0']['image']));
       $this->assign('category_pet',$category_pet);
       $this->assign('pet_lists',$product );

    }

    /**
     * 获取宠物信息
     *
     */
    public function product(){

        $type   = session('ptype') ? session('ptype') : '';

        $category  = $this->category_model->where('parentid = 0')->field('id,name')->select();

        $join   =  "LEFT JOIN ". C('DB_PREFIX') ."banner_image as b on a.id = b.banner_id";
        $banner =  $this->banner_model
            ->alias('a')
            ->join($join)
            ->order('b.sort_order')
            ->where(array('a.type'=>BannerModel::TYPE_SIDE))
            ->select();

        foreach( $category as $k => $v ){
           $cate_id = $this->category_model->where('parentid='.$v['id'] )->field('id,name')->select();
           $sonid = '';

           foreach( $cate_id as $kkk => $vvv ){
               $sonid .= $sonid['id'] ? ','.$vvv['id'] : $vvv['id'];
           }

            $where['category_id'] = array( 'in',$sonid );
            if( $type )   $where['pet_type'] = $type;
            $where['status']   = 1;
            $where['hot']      = 1;

            $product = $this->product_model
                ->where($where)
                ->limit(8)
                ->order('id desc')
                ->field('id,pro_name,smeta,sales_volume')
                ->select();

                foreach( $product as $key => $val ){
                    $picture = json_decode( $val['smeta'],true);
                    $option = $this->product_option_model->where('product_id='.$val['id'])->min('option_price');

                    $product[$key]['smeta'] = setUrl( $picture[0]['url'] );
                    $product[$key]['price'] = $option;
                }
            $category[$k]['category_list'] = $cate_id;
            $category[$k]['banner'] = setUrl( $banner[$k + 1]['image'] );
            $category[$k]['number'] = $k + 2;
            $category[$k]['product'] = $product;
        }

        $this->assign('product_lists',$category);
    }








}