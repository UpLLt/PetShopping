<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/27
 * Time: 11:44
 */

namespace Foster\Controller;


use Common\Controller\AdminbaseController;
use Common\Model\RegionModel;
use Foster\Model\FosterRulesModel;

class RuleController extends AdminbaseController
{
    private $region_model, $foster_rulemodel;
    public function __construct()
    {
        parent::__construct();
        $this->region_model = new RegionModel();
        $this->foster_rulemodel = new FosterRulesModel();
    }

    public function index() {
        $data = I('post.');

        foreach($data as $k => $v ){
            if( $v ) $where[$k] = $v;
        }
        $count = $this->foster_rulemodel->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->foster_rulemodel
            ->order('id desc')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
//        dump($result);exit;

        $lists = '';
        foreach( $result as $k => $v){
            $fos_province = $this->region_model->where('code='.$v['fos_province'])->field('name')->find();
            $fos_city = $this->region_model->where('code='.$v['fos_city'])->field('name')->find();
            $fos_country = $this->region_model->where('code='.$v['fos_country'])->field('name')->find();

            $result[$k]['str_manage'] = '<a class="" href="' . U('Rule/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Rule/delete', array('id' => $v['id'])) . '">删除</a>';

            $lists .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $fos_province['name'] . '</td>
            <td>' . $fos_city['name'] . '</td>
            <td>' . $fos_country['name'] . '</td>
            <td>' . $v['fos_price'] . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

//        if( !$data['fos_province'] ) $data['fos_province'] = '';
        if( !$data['fos_province'] ) {
            $data['fos_province'] = '110000';
            $data['fos_city'] = '110100';
            $data['fos_country'] = '110101';
        }
        $province = $this->region_model->getProvincetoStr($data['fos_province']);
        if($data['fos_city']) $city = $this->region_model->getCitytoStr($data['fos_province'],$data['fos_city']);
        if($data['fos_country']) $country = $this->region_model->getCitytoStr($data['fos_city'],$data['fos_country']);

        $this->assign('lists', $lists);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('country',$country);
        $this->display();
    }

    public function add() {

        $province = $this->region_model->getProvincetoStr();
        $this->assign('province',$province);
        $this->display();
    }

    public function edit() {
        $id = intval(I('id'));
        $result = $this->foster_rulemodel->where('id='.$id)->find();
        $province = $this->region_model->getProvincetoStr($result['fos_province']);
        $city     = $this->region_model->getCitytoStr($result['fos_province'],$result['fos_city']);
        $country  = $this->region_model->getCitytoStr($result['fos_city'],$result['fos_country']);
        $fos_weight = json_decode($result['fos_weight'], true);
        $fos_discount = json_decode($result['fos_discount'], true);

        foreach ($fos_weight as $k => $v) {
            $size .= '<dd style="margin-top: 10px">体重：<input value="'.$v['start'].'" name="size[start][]" type="number" style="width: 80px;">~<input value="'.$v['end'].'" name="size[end][]" type="number" style="width: 80px;">&nbsp;&nbsp;KG&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;价格：<input value="'.$v['price'].'" type="number" name="size[price][]" style="width: 80px;">&nbsp;&nbsp;元/天&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;"><img onclick="reduce(this)" style="width: 30px;height: 30px;" src="public/images/reduce.png"></a></dd>';
        }
        foreach($fos_discount as $k => $v) {
            $discount .= '<dd style="margin_top: 10px;"> <input name="discount[date][]" value="'.$v['date'].'" type="number" style="width: 80px; margin-top: 10px;" >&nbsp;&nbsp;天&nbsp;&nbsp; <input name="discount[num][]" value="'.$v['num'].'" type="number" style="width: 80px;"  >&nbsp;&nbsp;折&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;"><img onclick="reduce(this)" style="width: 30px;height: 30px;" src="public/images/reduce.png"></a></dd>';
        }

        $this->assign('size', $size);
        $this->assign('discount', $discount);
        $this->assign('result', $result);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('country',$country);
        $this->display();
    }

    public function add_post() {
        $data = I('post.');
        $is_area = $this->foster_rulemodel
            ->where(array('fos_province'=>$data['fos_province'],'fos_city'=>$data['fos_city'],'fos_country'=>$data['fos_country']))
            ->find();
        if( $is_area ) exit($this->error('该区域已设置运输规则。'));
        foreach( $data['size']['start'] as $k => $v ){
            $fos_weight[$k]['start'] = $v;
            $fos_weight[$k]['end']   = $data['size']['end'][$k];
            $fos_weight[$k]['price'] = $data['size']['price'][$k];
        }
        foreach($data['discount']['date'] as $k => $v) {
            $fos_discount[$k]['date'] = $v;
            $fos_discount[$k]['num'] = $data['discount']['num'][$k];
        }

        $data['fos_weight'] = json_encode($fos_weight);
        $data['fos_discount']   = json_encode($fos_discount);
        $data['create_time'] = time();
        $data['fos_service'] = htmlspecialchars_decode($data['fos_service']);
//        dump($data);exit;
        $rst = $this->foster_rulemodel->add($data);
        if($rst) {
            $this->success('', U('Rule/index'));
        } else{
            $this->error();
        }
    }

    public function edit_post() {
        $data = I('post.');

        foreach( $data['size']['start'] as $k => $v ){
            $fos_weight[$k]['start'] = $v;
            $fos_weight[$k]['end']   = $data['size']['end'][$k];
            $fos_weight[$k]['price'] = $data['size']['price'][$k];
        }
        foreach($data['discount']['date'] as $k => $v) {
            $fos_discount[$k]['date'] = $v;
            $fos_discount[$k]['num'] = $data['discount']['num'][$k];
        }

        $data['fos_weight'] = json_encode($fos_weight);
        $data['fos_discount']   = json_encode($fos_discount);
        $data['create_time'] = time();
        $data['fos_service'] = htmlspecialchars_decode($data['fos_service']);
        $rst = $this->foster_rulemodel->where(array('id' => $data['id']))->save($data);
        if($rst) {
            $this->success('', U('Rule/index'));
        } else{
            $this->error();
        }
    }

    public function delete() {
        $id = intval(I('id'));
        $result = $this->foster_rulemodel->delete($id);
        if($result){
            $this->success();
        }else{
            $this->error();
        }
    }

    public function getCity(){
        $province = I('post.province');
        $city     = $this->region_model->getCitytoStr( $province );
        $citycode = $this->region_model->getCity($province);
        $country  = $this->region_model->getCitytoStr( $citycode[0]['code'] );
        $data['city']    = $city;
        $data['country'] = $country;
        $this->ajaxReturn($data);

    }

    public function getcountry(){
        $city = I('post.city');
        $country  = $this->region_model->getCitytoStr( $city );
        $this->ajaxReturn($country);
    }
}