<?php
namespace Web\Controller;

use Common\Model\PetModel;
use Common\Model\PetTypeModel;
use Purchase\Model\SellPetModel;
use Purchase\Model\SellRulesModel;
use Think\Controller;

/**
 * 出售宠物
 * Class IndexController
 * @package Web\Controller
 */

class SellPetController extends BaseController
{
    private $sell_petmodel;
    private $pettype_model;
    private $pet_model;
    private $sell_rules_model;

    public function __construct()
    {
        parent::__construct();
        $this->sell_petmodel = new SellPetModel();
        $this->pettype_model = new PetTypeModel();
        $this->pet_model     = new PetModel();
        $this->sell_rules_model = new SellRulesModel();
    }

    public function index()
    {
        $sell_rules = $this->sell_rules_model->find();
        $this->assign('content',$sell_rules);
        $this->display();
    }




    public function selling_pets(){
        $this->getList();

    }

    /**
     * 获取品种列表
     */
    public function getPetCategory()
    {
        $pet_type   = session('ptype') ? session('ptype') : 2;

            $result = $this->pettype_model
                ->where(['pet_type' => $pet_type])
                ->field('pet_variety_id,pet_variety,pet_letter')
                ->order('pet_letter asc')
                ->select();


        $this->assign( 'PetCategory',$result );


    }

    /**
     * 卖宠物
     */
    public function selling(){
        $this->getPetCategory();
        $this->display();
    }

    /**
     * 获取自己发布的列表
     */
    public function getList() {
        $mid = session('mid');
        $this->is_login();
        $where['mid'] = $mid;

        $count = $list = $this->sell_petmodel
            ->alias('a')
            ->join('left join ego_pet_type as b on a.pet_variety = b.pet_variety_id')
            ->where($where)
            ->count();

        $page = $this->page($count,10);

        $list = $this->sell_petmodel
            ->alias('a')
            ->join('left join ego_pet_type as b on a.pet_variety = b.pet_variety_id')
            ->where($where)
            ->order('a.create_time desc')
            ->page($page->firstRow.','.$page->listRows )
            ->field('a.id, a.status,a.se_ppic ,a.se_count_male, a.se_count_female, a.se_price, a.status, b.pet_variety')
            ->select();


        foreach ($list as $k=>$v) {
            $picture = setUrl(json_decode($v['se_ppic'])[0]);
            $price =  $v['se_price'];
            $str = '<li>
					<span><a href="'.U('Web/SellPet/detail',array('id'=>$v['id'])).'"><img src="'.$picture.'" /></a><b>'.$v['pet_variety'].'</b></span>
					<span>￥'.$price.'元/每只</span>
					<span>'.$this->sell_petmodel->getStatusStr($v['status']).'</span>
				    </li>';

            if( $v['status'] == SellPetModel::SELL_PET_WAIT ){
                $data['wait'] .= $str;
            }elseif( $v['status'] == SellPetModel::SELL_PET_OK ){
                $data['ok'] .= $str;
            }elseif( $v['status'] == SellPetModel::SELL_PET_REFUSE ){
                $data['refuse'] .= $str;
            }elseif( $v['status'] == SellPetModel::SELL_PET_COMPLETE ){
                $data['complete'] .= $str;
            }
        }

        $this->assign('lists',$data);
        $this->assign('Page',$this->show());

    }


    public function publishInfo() {

        foreach( $_FILES['pictures']['name'] as $k => $v ){
            if( $v == '' ){
                exit($this->error( '请上传五张宠物图片和两张身份证照片'));
            }
        }


        $postdata = get_data(1);
        $mid = session('mid');
        $this->is_login();

        $pet_type = session('ptype');
        if( !$pet_type ) $pet_type = '2';
        $postdata['se_vaccine'] = $postdata['se_vaccine_a'].'针'.$postdata['se_vaccine_b'].'苗';
        $images = upload_img('sellpet');
        if(empty($images)) {
            exit($this->error( '图片上传失败'));
        }
        $data = array(
            'mid' => $mid,
            'status' => SellPetModel::SELL_PET_WAIT,
            'pet_type' => $pet_type,
            'pet_variety' => $postdata['pet_variety'],
            'se_age' => $postdata['se_age'],
            'se_vaccine' => $postdata['se_vaccine'],
            'se_ppic' => json_encode(array_slice($images, 2)),
            'se_insert' => $postdata['se_insert'],
            'se_count_male' => $postdata['se_count_male'],
            'se_count_female' => $postdata['se_count_female'],
            'se_price' => $postdata['se_price'],
            'se_total_price'=>$postdata['se_price'] *( $postdata['se_count_male'] + $postdata['se_count_female']),
            'se_describe' => $postdata['se_describe'],
            'se_phone' => $postdata['se_phone'],
            'se_address' => $postdata['se_address'],
            'se_card' => $postdata['se_card'],
            'se_pic' => json_encode(array_slice($images, 0, 2)),
            'create_time' => time(),
        );
        $rst = $this->sell_petmodel->add($data);
        if(!$rst) {
            exit($this->error(  '信息发布失败'));
        }
        exit($this->success('发布信息成功'));
    }


    /**
     * 获取详情
     */
    public function detail() {

        $where['id'] = I('id');
        $info = $this->sell_petmodel
            ->alias('a')
            ->join('left join ego_pet_type as b on a.pet_variety = b.pet_variety_id')
            ->where($where)
            ->field('b.pet_variety, a.status, a.pet_type, a.se_age, a.se_vaccine,a.se_insert, a.se_count_male,a.se_count_female,a.se_price, a.se_deal_price, a.se_describe,a.se_phone, a.se_address, a.se_card, a.se_pic,a.se_ppic, a.create_time')
            ->find();
        if(!$info) {
            exit($this->error('未找到'));
        }
        $info['se_ppic'] = setUrl(json_decode($info['se_ppic'], true));
        $info['se_pic'] = setUrl(json_decode($info['se_pic'], true));
        $info['create_time'] = date('Y-m-d H:i');

        $this->assign('lists',$info);
        $this->display();
    }
}