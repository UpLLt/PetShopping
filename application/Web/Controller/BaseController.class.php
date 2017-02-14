<?php
namespace Web\Controller;

use Appapi\Controller\ApibaseController;
use Common\Model\PetTypeModel;
use Community\Model\ComScoreModel;
use Consumer\Model\WalletModel;
use Think\Controller;

/**
 * 宠物基类
 * Class BaseController
 * @package Web\Controller
 */

class BaseController extends WebbaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->judge_ptype();
        $this->lo_str();
        $this->category();
        $this->getMemberinfo();
        $this->getProduct();
        $this->banner();
        $this->user_message();
    }

    /**
     * 分页显示
     */
    protected function page($total_size = 1, $page_size = 0, $current_page = 1, $listRows = 6, $pageParam = '', $pageLink = '', $static = FALSE)
    {
        if ($page_size == 0) {
            $page_size = C("PAGE_LISTROWS");
        }

        if (empty($pageParam)) {
            $pageParam = C("VAR_PAGE");
        }

        $Page = new \Page($total_size, $page_size, $current_page, $listRows, $pageParam, $pageLink, $static);
        $Page->SetPager('Admin', '{first}{prev}&nbsp;{liststart}{list}{listend}&nbsp;{next}{last}', array("listlong" => "9", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => ""));
        return $Page;
    }

    public function is_login()
    {
        $mid = session('mid');
        $result = D('member')->where(array('id' => $mid))->field('username')->find();
        if (!$result) {
            $this->redirect('Web/Member/login', array('key' => 1));
        }
    }

    public function out()
    {
        session('mid', null);
        $this->redirect('Web/Member/login', '', 0, '');

    }

    /**
     * 首页Banner
     */
    public function banner(){
        $join   =  "LEFT JOIN ". C('DB_PREFIX') ."banner_image as b on a.id = b.banner_id";
        $banner =  D('banner')
            ->alias('a')
            ->join($join)
            ->order('b.sort_order')
            ->limit(5)
            ->where(array('a.id'=>1))
            ->select();

        foreach( $banner as $k => $v ){
            $banner[$k]['image'] = setUrl($v['image']);
                if( $v['type'] == 1 )  $url = U('Product/index',array('ptype'=>'1','pid'=>$v['link']));
                if( $v['type'] == 2 )  $url = U('Product/index',array('ptype'=>'2','pid'=>$v['link']));
                if( $v['type'] == 3 )  $url = U('Web/Community/bannerDis',array('id'=>$v['link'],'banner_id'=>$v['id'],'key'=>$v['title']));
            $banner[$k]['url'] = $url;
        }

        $this->assign('banner',$banner);
    }



    /**
     * 热门商品
     */
    public function hot(){

        $type   = session('ptype') ? session('ptype') : 2;
        $where['hot']      = 1;
        $where['status']   = 1;
        $where['pet_type'] = $type;
        $product = D('product')
            ->where( $where )
            ->limit('3')
            ->order('id desc')
            ->field('id,pro_name,smeta')
            ->select();
        foreach(  $product as $k => $v ){
            $option_price = D('product_option')->where(['product_id'=> $v['id']])->min('option_price');
            $product[$k]['picture'] = setUrl(json_decode($v['smeta'],true)['0']['url']);
            $product[$k]['price'] = $option_price;
        }

        $this->assign('product_hot',$product);
    }

    /**
     * 分类
     */
    public function category(){

        $result = D('category')
            ->field('id,parentid,name')
            ->select();

        foreach ($result as $k => $v) {
            if ($v['parentid'] == 0)
                $parent[] = $v;
        }
        unset($v);
        foreach ($parent as $k => $v) {
            $parent[$k]['number'] = $k + 2;
            foreach ($result as $key => $value) {
                if ($v['id'] == $value['parentid'])
                    $parent[$k]['child'][] = $value;
            }

        }
        $pet_type_model = new PetTypeModel();
        $pet_type = $pet_type_model->getPetLetter();

        $this->assign('category',$parent);
        $this->assign( 'pp_type',$pet_type );


    }


    /**
     * 用户信息
     */
    public function user_message(){
        $mid = session('mid');
        $com_score_model = new ComScoreModel();
        $sco_member = $com_score_model->info($mid);
        $username = D('member')->where(array('id' => $mid))->field('username,nickname,headimg')->find();

        $user_mewss['username']  = $username['nickname'];
        $user_mewss['sco_now'] = $sco_member['sco_now'];
        $user_mewss['headimg'] = setUrl( $username['headimg'] );
        $this->assign('user_message',$user_mewss);
    }

    private function lo_str()
    {

        $mid = session('mid');
        $wallet_model = new WalletModel();
        $com_score_model = new ComScoreModel();
        $balance = $wallet_model->getBalance($mid);
        $sco_member = $com_score_model->info($mid);

        if ($mid) {
            $username = D('member')->where(array('id' => $mid))->field('username,nickname,headimg')->find();

            $str = '<li><a href="'.U('Web/Member/user').'" onmouseover="show()" onmouseout="hold()"><b>'.$username['nickname'].'</b> <i class="fa fa-sort-desc" aria-hidden="true"></i></a>
						<div id="userinfo" class="userinfo">
							<img src="'.setUrl($username['headimg']).'" />
							<p>账号余额：￥'.$balance .'</p>
							<p>账号积分：'.$sco_member['sco_now'].'</p>
						</div>
					</li>|
					<li><a href="'.U('Web/Base/out').'">【退出】</a></li>|
					<li><a href="'.U('Web/Member/tidings').'"><img src="/public/Web/images/tidings.png" />我的消息</a></li>|
					<li><a href="'.U('Web/Order/cart').'"><img src="/public/Web/images/shop_cart.png" />我的购物车</a></li>|
					<li><a href="'.U('Web/App/index').'">手机版</a></li>|
					<li><a href="'.U('Web/Complaint/about').'">更多</a></li>';
        } else {
            $str = '<li><a href="'.U('Web/Member/login').'">登录</a></li>|
					<li><a href="'.U('Web/Member/register').'">免费注册</a></li>|
					<li><a href="'.U('Web/Member/tidings').'"><img src="/public/Web/images/tidings.png" />我的消息</a></li>|
					<li><a href="'.U('Web/Order/cart').'"><img src="/public/Web/images/shop_cart.png" />我的购物车</a></li>|
					<li><a href="'.U('Web/App/index').'">手机版</a></li>|
					<li><a href="'.U('Web/Complaint/about').'">更多</a></li>';
        }
        $type   = session('ptype') ? session('ptype') : 2;
        if( $type == 2 ){
            $type_url  = '/public/Webdog';
        }else{
            $type_url  ='/public/Web';
        }

        $this->assign( 'ptype_url',$type_url );
        $this->assign('login', $str);
    }

    public function judge_ptype(){

        if( session('ptype') == 2 || empty(session('ptype'))){
            $ptype = '<li class="left_one"><a href="'.U('Web/Index/changetype',array('type'=>2)).'"><img src="/public/Web/images/nav_doge.png" /></a></li>
				      <li><a href="'.U('Web/Index/changetype',array('type'=>1)).'"><img src="/public/Web/images/nav_cat.png" /></a></li>';
        }else{
            $ptype = '<li><a href="'.U('Web/Index/changetype',array('type'=>2)).'"><img src="/public/Web/images/nav_doge.png" /></a></li>
				      <li class="left_one"><a href="'.U('Web/Index/changetype',array('type'=>1)).'"><img src="/public/Web/images/nav_cat.png" /></a></li>';
        }
        $this->assign('ptype',$ptype);
    }


    /**
     * 社区获取头部信息
     */
    public function getMemberinfo() {
        $mid = session('mid');
//        $this->is_login();
        $memberinfo = D('member')
            ->alias('a')
            ->join('LEFT JOIN ego_com_score as b on a.id = b.sco_member_id')
            ->field('a.nickname, a.headimg, b.sco_now')
            ->where(array('id' => $mid))
            ->find();
        $memberinfo['headimg'] = setUrl($memberinfo['headimg']);
        $score = D('com_score')->where(array('sco_member_id' => $mid))->getField('sco_now');
        $memberinfo['score'] = $score;
        //检查是否重复签到
        $memberinfo['is_sign'] = '0';
        $starttime = strtotime(date('Y-m-d'));
        $endtime = strtotime(date('Y-m-d'.' 23:59:59'));
        $where['rec_path'] = '签到获取';
        $where['rec_time'] = array('between',array($starttime,$endtime));
        $where['rec_member_id'] = $mid;
        $is_sign = D('com_record')->where($where)->find();
//        dump($is_sign);
        if($is_sign) {
            $memberinfo['is_sign'] = '1';
        }
//        dump($memberinfo);
//        echo json_encode(array('code' == 200, 'data' => $memberinfo));
        $this->assign('memberinfo', $memberinfo);
    }

    public function getProduct() {
        //左侧推荐商品
        $products = D('product')
            ->alias('a')
            ->join('LEFT JOIN ego_product_option as b on a.id = b.product_id')
            ->field('a.id, a.pet_type, a.pro_name, a.smeta, b.option_price')
            ->limit(6)
            ->order('sales_volume desc')
            ->select();
        foreach($products as $k => $v) {
            $images = json_decode($v['smeta'], true);
            $product_info[] = array(
                'id' => $v['id'],
                'pet_type' => $v['pet_type'],
                'pro_name' => $v['pro_name'],
                'image' => $this->setUrl($images[0]['url']),
                'option_price' => $v['option_price']
            );
        }

        $this->assign('product_info', $product_info);
    }
}