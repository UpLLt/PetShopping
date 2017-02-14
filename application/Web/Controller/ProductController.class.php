<?php
namespace Web\Controller;

use Common\Model\CommentModel;
use Common\Model\LogisticsTempModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Community\Model\ComScoreModel;
use Consumer\Model\MemberModel;
use Issue\Model\ProductPetModel;
use Purchase\Model\SellRulesModel;
use Think\Controller;

/**
 * 商品详情
 * Class IndexController
 * @package Web\Controller
 */

class ProductController extends BaseController
{
    private $product_model;
    private $product_option_model;
    private $logistics_model;
    private $comment_model;
    private $product_pet_model;
    private $sell_rulesmodel;
    private $member_model;
    private $com_score_model;

    public function __construct()
    {
        parent::__construct();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->logistics_model = new LogisticsTempModel();
        $this->comment_model = new CommentModel();
        $this->product_pet_model = new ProductPetModel();
        $this->sell_rulesmodel = new SellRulesModel();
        $this->member_model = new MemberModel();
        $this->com_score_model = new ComScoreModel();
    }

    public function index()
    {
        $id = I('get.pid');
        $ptype = I('get.ptype');

        if( $ptype == 2 ){
            $product = $this->product_model->where(array('id'=>$id,'status'=> 1 ))->find();

            if( $product['pro_shop_type'] == 2  ){
                $this->success('该商品为外界商品，正为你跳转链接',$product['pro_thirdparty_url'],5);
//                header('Location: '.$product['pro_thirdparty_url']);

            }else{
                $this->similar($product['category_id']);
                $this->sale_hot();
                $this->comment($id);
                $this->detail($id);
                $this->display();
            }
        }
        if( $ptype == 1 ){
            $this->service_agreement();
            $this->petdetail($id);
            $this->sale_hot();
            $this->display('petdetail');
        }

    }


    public function service_agreement(){
        $info = $this->sell_rulesmodel->where("type = 3")->getField('se_service');
        $this->assign('service_agree',$info);
    }


    public function petdetail($id)
    {
        $result = $this->product_pet_model
            ->alias('a')
            ->join('LEFT JOIN ' . C('DB_PREFIX') . 'pet_type as b on a.pet_variety_id = b.pet_variety_id')
            ->field('a.*,b.pet_variety as pet_variety')
            ->where(['a.id' => $id])
            ->find();

        $result['pet_picture'] = json_decode($result['pet_picture'], true);
        foreach ($result['pet_picture'] as $k => $v) {
            $result['pet_picture'][$k]['url'] = $this->setUrl($result['pet_picture'][$k]['url']);
            unset($result['pet_picture'][$k]['alt']);
        }
        if ($result['pet_picture'])
            $result['cover'] = $result['pet_picture'][0]['url'];

        if (!is_array($result['pet_picture'])) $result['pet_picture'] = [];

        $result['pet_sex'] = $this->product_pet_model->getSextoString($result['pet_sex']);
        $result['pet_colour'] = $this->product_pet_model->getPetColorString($result['pet_colour']);
        $result['pet_age'] = $this->product_pet_model->getPetAgetoString($result['pet_age']);
        $result['pet_fur'] = $this->product_pet_model->getPetFurtoString($result['pet_fur']);

        unset($result['show']);

//        $result['detail_url'] = $this->geturl('/Wap/ProductPet/detail/id/' . $result['id']);
//        unset($result['pet_content']);


        $this->assign('pdetail',$result);

    }



    /**
     * 同类推荐
     * @param $category_id
     */
    public function similar( $category_id ){

        $product = $this->product_model
                        ->where( ['category_id' => $category_id ,'status' => 1 ] )
                        ->field('id,pro_name,smeta')
                        ->limit('6')
                        ->select();
        foreach($product as $k => $v ){
            $option_price = $this->product_option_model->where(['product_id'=> $v['id']])->min('option_price');
            $product[$k]['picture'] = setUrl(json_decode($v['smeta'],true)['0']['url']);
            $product[$k]['price'] = $option_price;
        }
        $this->assign('similar',$product);
    }

    /**
     * 热卖商品
     */
    public function sale_hot(){
        $product = $this->product_model
            ->where( [ 'status' => 1 ] )
            ->order('sales_volume')
            ->limit('6')
            ->field('id,pro_name,smeta')
            ->select();

        foreach( $product as $k => $v ){
            $option_price = $this->product_option_model->where(['product_id'=> $v['id']])->min('option_price');
            $product[$k]['picture'] = setUrl(json_decode($v['smeta'],true)['0']['url']);
            $product[$k]['price'] = $option_price;
        }
//        dump($product);exit;
        $this->assign('sale_hot',$product);
    }

    /**
     * 商品详情
     * @param $id
     */
    public function detail( $id ){

        $product = $this->product_model
                        ->where(array('id'=>$id,'status'=> 1 ))
                        ->field('pro_name,id,smeta,logistics_id,content')
                        ->find();

        $smate   =  json_decode($product['smeta'],true) ;
            foreach( $smate as $k => $v ){
                $picture[] = setUrl($v['url']);
            }

        $option = $this->product_option_model
                        ->where('product_id ='.$product['id'])
                        ->field('option_name,option_key_id')
                        ->select();
        $price  = $this->product_option_model
                        ->where('product_id ='.$product['id'])
                        ->min('option_price');
        $logistics = $this->logistics_model->where('temp_id ='.$product['logistics_id'])->field('in_price')->find();

        $list['content']    = $product['content'];
        $list['name']       = $product['pro_name'];
        $list['product_id'] = $product['id'];
        $list['picture']    = $picture;
        $list['option']     = $option;
        $list['price']      = $price;
        $list['logistics']  = $logistics['in_price'];

        $this->assign('pdetail',$list);
    }

    /**
     * 评论管理
     * @param $id
     */
    public function comment($id)
    {
        $comment = $this->comment_model->where(['relevance_id' => $id , 'status'=> 2 ])->order('id desc')->select();
        foreach( $comment as $k => $v ){
            $comment[$k]['create_time'] = date('Y-m-d',$v['create_time']);
            $comment[$k]['level'] = $this->com_score_model->getMemberScoLevel($v['mid']);
            $comment[$k]['nickname'] = $this->member_model->getNickNameByid($v['mid']);
            $comment[$k]['heading'] = $this->member_model->getNickNameByHeading($v['mid']);
            if( $v['star'] == 1 ){
                $comment[$k]['star'] = "16px";
            }
            if( $v['star'] == 2 ){
                $comment[$k]['star'] = "34px";
            }
            if( $v['star'] == 3 ){
                $comment[$k]['star'] = "52px";
            }
            if( $v['star'] == 4 ){
                $comment[$k]['star'] = "68px";
            }
            if( $v['star'] == 5 ){
                $comment[$k]['star'] = "85px";
            }
            if( $v['replay'] ) $comment[$k]['replay'] = "<p><span>回复：</span>". $v['replay'] ."</p>";
        }

        $this->assign('comment',$comment);



    }

    public function getOptionPrice(){
        $option_key_id = I('option_key_id');

        $price = $this->product_option_model->where( 'option_key_id = '.$option_key_id )->getField('option_price');
        $this->ajaxReturn($price);
    }

}