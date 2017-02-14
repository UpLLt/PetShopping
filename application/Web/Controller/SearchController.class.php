<?php
namespace Web\Controller;

use Category\Model\CategoryModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;
use Issue\Model\ProductPetModel;
use Think\Controller;

/**
 * 搜索商品
 * Class IndexController
 * @package Web\Controller
 */

class SearchController extends BaseController
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

    public function index()
    {
        $type = I('type');
        $pet_variety_id = I('pet_variety_id');
        $id = I('id');
        $this->sale_hot();
        if( $type == 1 ){
            $this->productpet($pet_variety_id);
        }
        if( $type == 2 ){
            $this->GetCategoryValue($id);
            $this->GetCategory($id);
            $this->product($id);
            $this->display();
        }

    }

    /**
     * @param $id
     * 获取类别的值
     */
    public function GetCategoryValue($id){
        $category = $this->category_model->where('id='.$id)->find();
        $change_category = $category['parentid'] == 0 ? $id : $category['parentid'] ;
        $type   = session('ptype') ? session('ptype') : 2;

        if( $change_category ==  1 ){
                if( $type == 1 ){
                        $select2 = '		<dd name="4" ><a href="javascript:void(0);">通用</a></dd>
		                                    <dd name="3" ><a href="javascript:void(0);">大型猫</a></dd>
								            <dd name="2" ><a href="javascript:void(0);">中型猫</a></dd>
								            <dd name="1" ><a href="javascript:void(0);">小型猫</a></dd>';
                        $select3= '         <dd name="4"><a href="javascript:void(0);">通用</a></dd>
                                            <dd name="3"><a href="javascript:void(0);">老年猫</a></dd>
								            <dd name="2"><a href="javascript:void(0);">中年猫</a></dd>
								            <dd name="1"><a href="javascript:void(0);">幼年猫</a></dd>';
                }else{
                        $select2 = '		<dd name="4" ><a href="javascript:void(0);">通用</a></dd>
		                                    <dd name="3" ><a href="javascript:void(0);">大型犬</a></dd>
								            <dd name="2" ><a href="javascript:void(0);">中型犬</a></dd>
								            <dd name="1" ><a href="javascript:void(0);">小型犬</a></dd>';
                        $select3= '         <dd name="4"><a href="javascript:void(0);">通用</a></dd>
                                            <dd name="3"><a href="javascript:void(0);">老年犬</a></dd>
								            <dd name="2"><a href="javascript:void(0);">中年犬</a></dd>
								            <dd name="1"><a href="javascript:void(0);">幼年犬</a></dd>';
                }
        }else{
            if( $type == 1 ){
                $select2 = '
		                                    <dd name="3" ><a href="javascript:void(0);">大型猫</a></dd>
								            <dd name="2" ><a href="javascript:void(0);">中型猫</a></dd>
								            <dd name="1" ><a href="javascript:void(0);">小型猫</a></dd>';
                $select3= '
                                            <dd name="3"><a href="javascript:void(0);">老年猫</a></dd>
								            <dd name="2"><a href="javascript:void(0);">中年猫</a></dd>
								            <dd name="1"><a href="javascript:void(0);">幼年猫</a></dd>';
            }else{
                $select2 = '
		                                    <dd name="3" ><a href="javascript:void(0);">大型犬</a></dd>
								            <dd name="2" ><a href="javascript:void(0);">中型犬</a></dd>
								            <dd name="1" ><a href="javascript:void(0);">小型犬</a></dd>';
                $select3= '
                                            <dd name="3"><a href="javascript:void(0);">老年犬</a></dd>
								            <dd name="2"><a href="javascript:void(0);">中年犬</a></dd>
								            <dd name="1"><a href="javascript:void(0);">幼年犬</a></dd>';
            }

        }
        $this->assign('select2',$select2);
        $this->assign('select3',$select3);
    }

    public function product($id){
        $category = $this->category_model->where('id='.$id)->find();

        $type   = session('ptype') ? session('ptype') : 2;
        $where['pet_type'] = $type;

        if( $category['parentid'] == 0 ){

            $son_id = $this->category_model->where('parentid='.$id)->field('name,id')->select();
            $whe_id = '';

            foreach( $son_id as $k => $v ){
                $whe_id .= $whe_id ? ','.$v['id'] : $v['id'];
            }

            $where['category_id'] = array('in',$whe_id);
            $category_id = $id;
        }else{
            $where['category_id'] = $id;

            $cate_small_id  = $this->category_model->where('id='.$id)->find();
            $this->assign('cate_small_id',$cate_small_id);
            $category_id = $category['parentid'];
        }

        $count  = $this->product_model
            ->where($where)
            ->field('id,pro_name,smeta,sales_volume')
            ->count();

        $page = $this->page($count,12);
        $product = $this->product_model
            ->where($where)
            ->limit($page->firstRow .','.$page->listRows)
            ->field('id,pro_name,smeta,sales_volume')
            ->select();

        foreach( $product as $key => $val ){
            $picture = json_decode( $val['smeta'],true);
            $option = $this->product_option_model->where('product_id='.$val['id'])->min('option_price');
            $product[$key]['smeta'] = setUrl( $picture[0]['url'] );
            $product[$key]['price'] = $option;
        }

        $this->assign('cate_name',$category['name']);
        $this->assign('pet_type',$type);
        $this->assign( 'category_id',$category_id );
        $this->assign('count',$count);
        $this->assign('Page',$page->show('Admin'));
        $this->assign('lists',$product);
    }




    public function GetCategory($id){

        $type   = session('ptype') ? session('ptype') : 2;
        $where['pet_type'] = $type;

        $category = $this->category_model->where('id='.$id)->find();
        if( $category['parentid'] == 0 ){
            $son_id = $this->category_model->where('parentid='.$id)->field('name,id')->select();
        }else{
            $son_id = $this->category_model->where('parentid='.$category['parentid'])->field('name,id')->select();
        }
        $this->assign('category',$son_id);
    }


    public function  ajaxGetProduct(){

        $category     = I('category_id');
        $zzz_category = I('zzz_category');
        $pet_body_id  = I('zzz_pet_body_id');
        $pet_age_id   = I('zzz_pet_age_id');
        $price        = I('zzz_price'); //价格筛选区间
        $page         = I('page');
        $sales_number = I('sales_number'); //销量
        $price_zzzzzzz= I('price_zzzzzzz'); //点击价格
        if( $sales_number == 1 )  $order = 'sales_volume desc';
        if( $sales_number == 2 )  $order = 'sales_volume asc';
        if( $price_zzzzzzz == 1 ) $order = 'reference_price desc';
        if( $price_zzzzzzz == 2 ) $order = 'reference_price asc';


        $type   = session('ptype') ? session('ptype') : 2;
        $where['pet_type'] = $type;

        if( !$zzz_category ){
            $son_id = $this->category_model->where('parentid='.$category)->field('name,id')->select();
            $whe_id = '';

            foreach( $son_id as $k => $v ){
                $whe_id .= $whe_id ? ','.$v['id'] : $v['id'];
            }
            $where['category_id'] = array('in',$whe_id);

        }else{
            $where['category_id'] = $zzz_category;
        }

        if( $pet_body_id )  $where['pet_body_id'] = $pet_body_id;
        if( $pet_age_id )   $where['pet_age_id']  = $pet_age_id;

        if( $price == 1 ){
            $where['reference_price'] = array('EGT',0);
            $where['reference_price'] = array('ELT',200);
        }
        if( $price == 2 ){
            $where['reference_price'] = array('EGT',200);
            $where['reference_price'] = array('ELT',500);
        }
        if( $price == 3 ){
            $where['reference_price'] = array('EGT',500);
            $where['reference_price'] = array('ELT',1000);
        }
        if( $price == 4 ){
            $where['reference_price'] = array('EGT',1000);
        }

        $page = isset($page) && intval($page) > 1 ? $page : '1';

        $type   = session('ptype') ? session('ptype') : 2;
        $where['pet_type'] = $type;
        $count  = $this->product_model
            ->where($where)
            ->field('id,pro_name,smeta,sales_volume')
            ->count();


        $star = ( $page - 1 ) * 12;
        $Allpage = ceil( $count /12 );

        $list = $this->product_model
            ->where($where)
            ->limit($star,12)
            ->order($order)
            ->field('id,pro_name,smeta,sales_volume')
            ->select();


        $pro = $page - 1;
        if ($pro == 0) $pro = 1;
        $next = $page + 1;
        if ($next > $Allpage) $next = $page;

        $str1 = '<a href="javascript:void(0)"  onclick="page(this)" name="1" >首页</a><a href="javascript:void(0)"  onclick="page(this)" name="' . $pro . '">上一页</a>';

        //大于页数省略
        $str2 = '';
        if( $Allpage > 15 ) {
            $str2 .= '<a  href="javascript:void(0);"   >...</a>';
            $page_end =  $page + 5;
            $page_start   =  $page - 5;
        }
        //页数判定
        for( $i=1 ; $i<=$Allpage ;$i++ ){
            if( $Allpage > 15 ){
                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else if( $i >= $page_start && $i <= $page_end){
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }
            }else{
                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else{
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }
            }
        }
        if( $Allpage > 20 ) {
            $str2 .= '<a  href="javascript:void(0);"   >...</a>';
        }
        $str3 = '<a href="javascript:void(0)"  onclick="page(this)" name="' . $next . '">下一页</a>';
        $page = $str1.$str2.$str3;

        $str = '';
        foreach($list as $k => $v) {

            $picture = json_decode( $v['smeta'],true);
            $option = $this->product_option_model->where('product_id='.$v['id'])->min('option_price');
            $product[$k]['smeta'] = setUrl( $picture[0]['url'] );
            $product[$k]['price'] = $option;

            $pet_picture = setUrl(json_decode($v['smeta'],true)[0]['url']);


            $str .= '<li>
						<a href="'.U('Product/index',array('pid'=>$v['id'],'ptype'=>2)).'"><img src="'.$pet_picture.'" /></a>
						<h1>'.$v['pro_name'].'</h1>
						<p><span>￥<b>'. $option .'</b></span> <strong>销售：'.$v['sales_volume'].'</strong></p>
					</li>';

        }


        if( $Allpage == 1 || $Allpage == 0 ) $page = '';
        $data['str'] = $str;
        $data['count'] = $count;
        $data['Page']  = $page;
//        $data['order_type'] = $postdata['price']== 1 ? 2 : 1;

        $this->ajaxReturn($data);
    }



    public function productpet($pet_variety_id){
        $type   = session('ptype') ? session('ptype') : 2;

        if( $pet_variety_id ) $where['pet_variety_id'] = $pet_variety_id;


        $where['pet_type'] = $type;
        $where['status']   = 0;
        $where['show']     = 1;

        $count = $this->product_pet_model
            ->where( $where )
            ->field('id,pet_name,pet_price,pet_picture')
            ->count();
        $page = $this->page($count,12);

        $product = $this->product_pet_model
            ->where( $where )
            ->field('id,pet_name,pet_price,pet_picture')
            ->limit($page->firstRow .','.$page->listRows)
            ->select();

        foreach( $product as $k => $v ){
            $picture = json_decode( $v['pet_picture'],true);
            $product[$k]['picture'] = setUrl( $picture['0']['url'] );
        }
        $this->assign('Page',$page->show('Admin'));
        $this->assign('lists',$product);
        $this->assign('count',$count);
        $this->assign('pet_variety_id',$pet_variety_id);
        $this->display('productpet');
    }

    public function ajaxReturnPet(){
        $type   = session('ptype') ? session('ptype') : 2;
        $pet_variety_id = I('pet_variety_id');
        $page  = I('page');
        $price = I('price');
        if( $pet_variety_id ) $where['pet_variety_id'] = $pet_variety_id;
        $where['pet_type']       = $type;
        $where['status']         = 0;
        $where['show']           = 1;


        //页码
        $page = isset($page) && intval($page) > 1 ? $page : '1';
        //每页显示数量
        if( $price == 1 )   $order = 'pet_price desc';
        $count = $this->product_pet_model
            ->where($where)
            ->count();
        $star = ($page - 1) * 12;
        $Allpage = ceil($count /12);
        $list = $this->product_pet_model
            ->where($where)
            ->limit($star,12)
            ->order($order)
            ->field('id,pet_name,pet_price,pet_picture')
            ->select();
        $pro = $page - 1;
        if ($pro == 0) $pro = 1;
        $next = $page + 1;
        if ($next > $Allpage) $next = $page;

        $str1 = '<a href="javascript:void(0)"  onclick="page(this)" name="1" >首页</a><a href="javascript:void(0)"  onclick="page(this)" name="' . $pro . '">上一页</a>';

        //大于页数省略
        $str2 = '';
        if( $Allpage > 15 ) {
            $str2 .= '<a  href="javascript:void(0);"   >...</a>';
            $page_end =  $page + 5;
            $page_start   =  $page - 5;
        }
        //页数判定
        for( $i=1 ; $i<=$Allpage ;$i++ ){
            if( $Allpage > 15 ){
                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else if( $i >= $page_start && $i <= $page_end){
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }
            }else{
                if( $page == $i ){
                    $str2 .= '<span class="current" href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</span>';
                }else{
                    $str2 .= '<a  href="javascript:void(0);" onclick="page(this)"  name="' . $i . '" >' . $i . '</a>';
                }
            }
        }
        if( $Allpage > 20 ) {
            $str2 .= '<a  href="javascript:void(0);"   >...</a>';
        }
        $str3 = '<a href="javascript:void(0)"  onclick="page(this)" name="' . $next . '">下一页</a>';
        $page = $str1.$str2.$str3;

        $str = '';
        foreach($list as $k => $v) {
            $pet_picture = setUrl(json_decode($v['pet_picture'],true)[0]['url']);
            $str .= '<li>

						<a href="'.U('Product/index',array('pid'=>$v['id'],'ptype'=>1)).'"><img  src="'.$pet_picture.'" /></a>
						<h1>'.$v['pet_name'].'</h1>
						<p><span>￥<b>'.$v['pet_price'].'</b></span> </p>
					</li>';

        }

        if( $Allpage == 1 || $Allpage == 0 ) $page = '';
        $data['str'] = $str;
        $data['count'] = $count;
        $data['Page']  = $page;

        $this->ajaxReturn($data);
    }

    /**
     * 热卖商品
     */
    public function sale_hot(){
        $type   = session('ptype') ? session('ptype') : 2;
        $product = $this->product_model
            ->where( [ 'status' => 1 , 'pet_type' => $type ] )
            ->order('sales_volume')
            ->limit('3')
            ->field('id,pro_name,smeta')
            ->select();

        foreach( $product as $k => $v ){
            $option_price = $this->product_option_model->where(['product_id'=> $v['id']])->min('option_price');
            $product[$k]['picture'] = setUrl(json_decode($v['smeta'],true)['0']['url']);
            $product[$k]['price'] = $option_price;
        }

        $this->assign('sale_hot',$product);
    }


    public function search(){
        $this->sale_hot();

        $type = I('type');
        $keyword = I('keyword');
        $ptype   = session('ptype') ? session('ptype') : 2;
        $where['pet_type'] = $ptype;
        $where['status']   = 1;

        if( !$keyword ){
            $this->error('请输入关键字！');
        }
        $_GET['type'] = $type;
        $_GET['keyword'] = $keyword;
        $_GET['ptype'] = $ptype;
        if($type == 1 ){
            $where['pro_name'] = array('like','%'.$keyword.'%');
            $count  = $this->product_model
                ->where($where)
                ->field('id,pro_name,smeta,sales_volume')
                ->count();

            $page = $this->page($count,12);
            $product = $this->product_model
                ->where($where)
                ->limit($page->firstRow .','.$page->listRows)
                ->field('id,pro_name,smeta,sales_volume')
                ->select();
            $str = '';

            foreach( $product as $key => $val ){
                $picture = json_decode( $val['smeta'],true);
                $option = $this->product_option_model->where('product_id='.$val['id'])->min('option_price');
                $product[$key]['smeta'] = setUrl( $picture[0]['url'] );
                $product[$key]['price'] = $option;

                $str .='	<li>
								<a href="'.U('Product/index',array('ptype'=>2,'pid'=>$val['id'])).'"><img   src="'.$product[$key]['smeta'].'" /></a>
								<h1>'.$val['pro_name'].'</h1>
								<p><span>￥<b>'.$option.'</b></span> <strong>销售：'.$val['sales_volume'].'</strong></p>
							</li>';

            }

        }
        if($type == 2 ){
            $where['pet_name'] = array('like','%'.$keyword.'%');
            $where['status']   = 0;
            $count = $this->product_pet_model
                ->where( $where )
                ->field('id,pet_name,pet_price,pet_picture')
                ->count();
            $page = $this->page($count,15);

            $product = $this->product_pet_model
                ->where( $where )
                ->field('id,pet_name,pet_price,pet_picture')
                ->limit($page->firstRow .','.$page->listRows)
                ->select();

            foreach( $product as $k => $v ){
                $picture = json_decode( $v['pet_picture'],true);
                $product[$k]['picture'] = setUrl( $picture['0']['url'] );
                $str .='	<li>
								<a href="'.U('Product/index',array('ptype'=>1,'pid'=>$v['id'])).'"><img   src="'.$product[$k]['picture'].'" /></a>
								<h1>'.$v['pet_name'].'</h1>
								<p><span>￥<b>'.$v['pet_price'].'</b></span></p>
							</li>';
            }
        }

        $this->assign('formget',I('post.'));
        $this->assign('count',$count);
        $this->assign('product',$str);
        $this->assign('Page',$page->show());
        $this->display();
    }

}