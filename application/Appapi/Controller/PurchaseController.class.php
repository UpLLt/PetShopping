<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/11
 * Time: 15:35
 */

namespace Appapi\Controller;


use Purchase\Model\SellPetModel;
use Purchase\Model\SellRulesModel;

class PurchaseController extends ApibaseController
{
    private $sell_petmodel, $sell_rulesmodel;

    public function __construct()
    {
        parent::__construct();
        $this->sell_petmodel = new SellPetModel();
        $this->sell_rulesmodel = new SellRulesModel();
    }

    public function publishInfo() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam(array($postdata['mid'],$postdata['token'], $postdata['pet_type'], $postdata['pet_variety'], $postdata['se_age'], $postdata['se_vaccine'], $postdata['se_price'], $postdata['se_phone'], $postdata['se_address']));

        $images = upload_img('sellpet');
        if(empty($images)) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '图片上传失败'));
        }
        $data = array(
            'mid' => $mid,
            'status' => SellPetModel::SELL_PET_WAIT,
            'pet_type' => $postdata['pet_type'],
            'pet_variety' => $postdata['pet_variety'],
            'se_age' => $postdata['se_age'],
            'se_vaccine' => $postdata['se_vaccine'],
            'se_ppic' => json_encode(array_slice($images, 2)),
            'se_insert' => $postdata['se_insert'],
            'se_count_male' => $postdata['se_count_male'],
            'se_count_female' => $postdata['se_count_female'],
            'se_price' => $postdata['se_price'],
            'se_total_price' => $postdata['se_price'] * ($postdata['se_count_male'] + $postdata['se_count_female']),
            'se_describe' => $postdata['se_describe'],
            'se_phone' => $postdata['se_phone'],
            'se_address' => $postdata['se_address'],
            'se_card' => $postdata['se_card'],
            'se_pic' => json_encode(array_slice($images, 0, 2)),
            'create_time' => time(),
        );
        $rst = $this->sell_petmodel->add($data);
        if(!$rst) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '信息发布失败'));
        }
        exit($this->returnApiSuccess());
    }

    /**
     * 获取自己发布的列表
     */
    public function getList() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

        $where['status'] = $postdata['status'];
        $where['mid'] = $mid;
        //页码
        $page = isset($postdata['page']) && intval($postdata['page'])>1 ? $postdata['page'] : '1';
        //每页显示数量
        $perpage = isset($postdata['perpage']) && intval($postdata['perpage'])>1 ? $postdata['perpage'] : '10';
        $count = $list = $this->sell_petmodel
            ->alias('a')
            ->join('left join ego_pet_type as b on a.pet_variety = b.pet_variety_id')
            ->where($where)
            ->count();
        $list = $this->sell_petmodel
            ->alias('a')
            ->join('left join ego_pet_type as b on a.pet_variety = b.pet_variety_id')
            ->where($where)
            ->order('a.create_time desc')
            ->page($page, $perpage)
            ->field('a.id, a.status, a.se_count_male, a.se_count_female, a.se_price, a.status, b.pet_variety')
            ->select();


        foreach ($list as $k=>$v) {
            $data['list'][] = array(
                'id' => $v['id'],
                'pet_variety' => $v['pet_variety'],
                'sum' => $v['se_count_male'] + $v['se_count_female'],
                'se_price' => $v['se_price'].'元/每只',
                'status' => $v['status'],
            ) ;
        }
        if(!$list) {
            $data['list'] = array();
        }
        $totalpage = $count/ $perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $data['count'] = $totalpage;

        exit($this->returnApiSuccess($data));
    }

    /**
     * 获取详情
     */
    public function getDetail() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);

        $where['id'] = $postdata['id'];
        $info = $this->sell_petmodel
            ->alias('a')
            ->join('left join ego_pet_type as b on a.pet_variety = b.pet_variety_id')
            ->where($where)
            ->field('b.pet_variety, a.status, a.pet_type, a.se_age, a.se_vaccine,a.se_insert, a.se_count_male,a.se_count_female,a.se_price, a.se_deal_price, a.se_describe,a.se_phone, a.se_address, a.se_card, a.se_pic,a.se_ppic, a.create_time')
            ->find();
        if(!$info) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '未找到'));
        }
        $info['se_ppic'] = setUrl(json_decode($info['se_ppic'], true));
        $info['se_pic'] = setUrl(json_decode($info['se_pic'], true));
        $info['create_time'] = date('Y-m-d H:i');
        exit($this->returnApiSuccess($info));
    }

    /**
     * 获取服务协议
     */
    public function getService() {
        $postdata = get_data(1);
        $mid = I('post.mid');
        $token = I('post.token');
        $type = I('post.type');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);
        $where['type'] =   $type;
        $info = $this->sell_rulesmodel->where($where)->field('se_service')->find();
        exit($this->returnApiSuccess($info));
    }
}