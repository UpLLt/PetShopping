<?php
namespace Web\Controller;

use Advertisement\Model\BannerModel;
use App\Model\DocumentsModel;
use Common\Model\CommentModel;
use Common\Model\OrderModel;
use Consumer\Model\MemberModel;
use Funeral\Model\BuriedModel;
use Funeral\Model\BuriedRulesModel;
use Think\Controller;

/**
 * 宠物殡仪
 * Class IndexController
 * @package Web\Controller
 */

class BuriedController extends BaseController
{
    private $buried_model;
    private $buired_rules_model;
    private $order_model;
    private $comment_model;
    private $member_model;
    private $documents_model;
    private $banner_model;


    public function __construct()
    {
        parent::__construct();
        $this->buried_model = new BuriedModel();
        $this->buired_rules_model = new BuriedRulesModel();
        $this->order_model = new OrderModel();
        $this->comment_model = new CommentModel();
        $this->member_model = new MemberModel();
        $this->documents_model = new DocumentsModel();
        $this->banner_model = new BannerModel();

    }

    public function index()

    {
        $this->is_login();
        $this->comment();
        $this->about_company();
        $this->banner_transport();
        $this->display();
    }


    public function banner_transport(){
        $join   =  "LEFT JOIN ". C('DB_PREFIX') ."banner_image as b on a.id = b.banner_id";
        $banner =  $this->banner_model
            ->alias('a')
            ->join($join)
            ->order('b.sort_order')
            ->where(array('a.sign_key'=>'company-fun_pc'))
            ->select();

        foreach( $banner as $k => $v ){
            $banner[$k]['image'] = setUrl($v['image']);
            if( $v['type'] == 1 )  $url = U('Product/index',array('ptype'=>'1','pid'=>$v['link']));
            if( $v['type'] == 2 )  $url = U('Product/index',array('ptype'=>'2','pid'=>$v['link']));
            if( $v['type'] == 3 )  $url = U('Web/Community/bannerDis',array('id'=>$v['link'],'banner_id'=>$v['id'],'key'=>$v['title']));
            $banner[$k]['url'] = $url;
        }

        $this->assign('banner_list',$banner);
    }

    /**
     * 用户评论
     */
    public function comment(){
        $comment = $this->comment_model->where(array('order_type'=>4))->order('id desc')->select();

        foreach( $comment as $k => $v ){
            $comment[$k]['create_time'] = date('Y-m-d',$v['create_time']);
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

    /**
     * 公司简介
     */
    public function about_company(){
        $this->is_login();
        $company = $this->documents_model->where('doc_class = "company_buried"')->field('content')->find();
        $this->assign('company',$company['content']);
    }



    public function buried_pet(){

        $join = "LEFT JOIN ".C('DB_PREFIX')."region as b on a.bu_province = b.code";
        $buired_rules = $this->buired_rules_model
            ->alias('a')
            ->join($join)
            ->field('b.name,b.code')
            ->group('b.name,b.code')
            ->select();
        if(!$buired_rules) $this->error('该模块尚未开放');
        $province = '';
        foreach( $buired_rules as $k => $v ){
            $province .= " <option  value='".$v['code']."'>".$v['name']."</option>";
        }
        $area = $this->getBuriedArea($buired_rules['0']['code']);
        $area['province'] = $province;
        $area['country'] = $area['send']['country'];

        $this->assign('area',$area);
        $this->display();
    }

    /**
     * 获取殡仪的地址（点击省）
     * @param null $bu_province
     */
    public function getBuriedArea($bu_province = null){
        $bu_province = $bu_province ? $bu_province : I('bu_province');

        $join = "LEFT JOIN ".C('DB_PREFIX')."region as b on a.bu_city=b.code";
        $buired_rules = $this->buired_rules_model
            ->alias('a')
            ->join($join)
            ->where(['bu_province='.$bu_province])
            ->field('b.name,b.code')
            ->group('b.name,b.code')
            ->select();
        $city = '';
        foreach( $buired_rules as $k => $v ){
            $city .= " <option  value='".$v['code']."'>".$v['name']."</option>";
        }

        $country = $this->getBuiredCountry($buired_rules[0]['code']);
        $data['city'] = $city;
        $data['send'] = $country;
        if( I('bu_province') ){
            $this->ajaxReturn($data);
        }else{
            return $data;
        }
    }

    /**
     * 获取运输的地址（点击县）
     * @param null $bu_city
     * @return string
     */
    public function getBuiredCountry($bu_city = null){

        $bu_city = $bu_city ? $bu_city : I('bu_city');
        $join = "LEFT JOIN ".C('DB_PREFIX')."region as b on a.bu_country = b.code";
        $buired_rule = $this->buired_rules_model
            ->alias('a')
            ->join($join)
            ->where(['bu_city='.$bu_city])
            ->field('b.name,b.code')
            ->group('b.name,b.code')
            ->select();

        $country = '';
        foreach( $buired_rule as $k => $v ){
            $country .= " <option  value='".$v['code']."'>".$v['name']."</option>";
        }
        $service = $this->getPrice($buired_rule[0]['code']);
        $data['country'] = $country;
        $data['bu_service'] = $service;

        if( I('bu_city') ){
            $this->ajaxReturn($data);
        }else{
            return $data;
        }
    }



    /**
     * 获取区域价格
     * @param $country
     * @return mixed
     */
    public function getPrice($country)
    {

        $country = $country ? $country : I('country');
        $rst = $this->buired_rules_model->getDetail($country);

        $buired_str = '	<dl>
							<label><input type="radio" value="1" name="bu_bury" class="checkbox mag"  checked/>普通埋葬</label>  <span>'.$rst['bu_normal'].'元</span>
							<p>埋葬在金菊花宠物墓园</p>
						</dl>

						<dl>
							<label><input type="radio"  value="2" name="bu_bury" class="checkbox mag" />深埋树葬</label>  <span>'.$rst['bu_tree'].'元</span>
							<p>埋葬在金菊花公墓树下，赠送心形纪念碑 （赠送整个过程照片至你微信）</p>
						</dl>

						<dl>
							<label><input type="radio"  value="3" name="bu_bury" class="checkbox mag" />豪华树葬</label>  <span>'.$rst['bu_luxury'].'元</span>
							<p>埋葬在金菊花公墓树下，赠送心形纪念碑，仿真草坪，白色围栏。 （赠送整个过程照片至你微信）</p>
						</dl>

						<dl>
							<label><input type="radio"  value="4" name="bu_bury" class="checkbox mag" />普通西葬</label>  <span>'.$rst['bu_normal_west'].'元</span>
							<p>水泥青石镶边，黑色大理石墓碑 （赠送整个过程照片至你微信）</p>
						</dl>

						<dl>
							<label><input type="radio"  value="5" name="bu_bury" class="checkbox mag" />豪华埋葬</label>  <span>'.$rst['bu_luxury_west'].'元</span>
							<p>水泥青石镶边，汉白玉墓碑，仿真草坪，白色围栏。 （赠送整个过程照片至你微信）</p>
						</dl>';

        $data['buired'] = $buired_str;
        $data['send_addre'] = $rst['bu_send_addre'];
        if( I('country') ){
            $this->ajaxReturn($data);
        }else{
            return $data;
        }


    }

    public function protocol_pet_pic(){
        $country = I('country');
        $trasport_rules = $this->buired_rules_model->where('bu_country='.$country)->field('bu_service')->find();

        $this->assign('bu_service',$trasport_rules['bu_service']);
        $this->display();
    }


    /**
     * 提交殡仪订单
     */
    public function funeral()
    {

        $this->is_login();
        $postdata = get_data(1);
        $mid = session('mid');
        if(strlen($postdata['bu_contacts_phone']) != 11 ) {
            exit($this->returnApiError(BaseController::FATAL_ERROR, '手机号格式错误'));
        }
        $price = $this->buired_rules_model->getDetail($postdata['bu_country']);
        $bu_bury = $this->buried_model->getStr($postdata['bu_bury']);

        $total = $price[$bu_bury];


        if($postdata['bu_method'] == 1) {//上门取货
            $total = $price['bu_price'];
        }
        $fire_pri = 0;
        if($postdata['bu_buried'] == 1) {//要火化
            if($postdata['bu_weight'] <= $price['bu_cremation']) {
                $fire_pri = $price['bu_cremation_price'];
                $total += $fire_pri;
            } else {
                $fire_pri = $price['bu_cremation_price']+ ($postdata['bu_weight']- $price['bu_cremation'])*$price['bu_overstep_price'];
                $total += $fire_pri;
            }
        }
        $this->buried_model->startTrans();
        $is_commit = true;
        $order = array(
            'order_sn' => $this->order_model->getOrderNumber(),
            'order_type' => OrderModel::ORDER_TYPE_FUNERAL,
            'status' => OrderModel::STATUS_WAIT_FOR_PAY,
            'cover'=> C('DEFAULT_BINYI_URL'),
            'comment_status'=> '0',
            'order_price' => $total,
            'create_time' => time(),
            'mid' => $mid
        );
        $rst = $this->order_model->add($order);

        if(!$rst) {
            $is_commit = false;
        }
        $postdata['bu_address'] = $postdata['bu_address'] ? $postdata['bu_address'] : $price['bu_send_addre'];
        $buried = array(
            'order_id' => $rst,
            'bu_buried' => $postdata['bu_buried'],
            'bu_weight' => $postdata['bu_weight'],
            'bu_bury' => $this->buried_model->getMethod($postdata['bu_bury']),
            'bu_contacts' => $postdata['bu_contacts'],
            'bu_contacts_phone' => $postdata['bu_contacts_phone'],
            'bu_address' => $postdata['bu_address'],
            'bu_funeral_price' => $price[$bu_bury],
            'bu_price' => $total,
            'bu_pick_up_price' => $postdata['bu_method'] == 1 ? $price['bu_price'] : 0,
            'bu_method' => $postdata['bu_method'],
            'bu_area'=>$postdata['bu_country']
        );
        $res = $this->buried_model->add($buried);
        if(!$res) {
            $is_commit = false;
        }
        if($is_commit){
            $this->buried_model->commit();
        }else{
            $this->buried_model->rollback();
            exit($this->returnApiError(BaseController::FATAL_ERROR, '订单生成失败'));
        }

        $return = array(
            'order_id' => $rst,
            'pickup_price' => sprintf("%.2f",($postdata['bu_method'] == 1 ? $price['bu_price'] : '0')),
            'funeral_price' => sprintf("%.2f", $buried['bu_funeral_price']),
            'fire_price' => sprintf("%.2f", $fire_pri),
            'total_price' => sprintf("%.2f",$total),
        );

        $this->success('生成订单成功',U('Web/Order/oneOrderBefore',array('order_id'=>$rst)));

    }

}