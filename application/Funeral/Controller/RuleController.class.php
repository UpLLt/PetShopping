<?php
namespace Funeral\Controller;
use Common\Controller\AdminbaseController;
use Common\Model\RegionModel;
use Funeral\Model\BuriedRulesModel;

/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/25
 * Time: 11:27
 */
class RuleController extends AdminbaseController
{

    private $region_model, $Buried_ruleModel;

    public function __construct() {
        parent::__construct();
        $this->region_model = new RegionModel();
        $this->Buried_ruleModel = new BuriedRulesModel();
    }
    public function index(){
        $data = I('post.');
//        dump($data);
        foreach($data as $k => $v ){
            if( $v ) $where[$k] = $v;
        }

        $count = $this->Buried_ruleModel->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->Buried_ruleModel
            ->order('id desc')
            ->field('bu_province, bu_country, bu_city, bu_price, id')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $lists = '';
        foreach( $result as $k => $v){
            $tr_province = $this->region_model->where('code='.$v['bu_province'])->field('name')->find();
            $tr_city = $this->region_model->where('code='.$v['bu_city'])->field('name')->find();
            $tr_country = $this->region_model->where('code='.$v['bu_country'])->field('name')->find();

            $result[$k]['str_manage'] = '<a class="" href="' . U('Rule/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Rule/delete', array('id' => $v['id'])) . '">删除</a>';

            $lists .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $tr_province['name'] . '</td>
            <td>' . $tr_city['name'] . '</td>
            <td>' . $tr_country['name'] . '</td>
            <td>' . $v['bu_price'] . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }
//        dump($data);
//        if( !$data['bu_province'] ) $data['bu_province'] = '';
        if( !$data['bu_province'] ) {
            $data['bu_province'] = '110000';
            $data['bu_city'] = '110100';
            $data['bu_country'] = '110101';
        }
        $province = $this->region_model->getProvincetoStr($data['bu_province']);
        if($data['bu_city']) $city = $this->region_model->getCitytoStr($data['bu_province'],$data['bu_city']);
        if($data['bu_country']) $country = $this->region_model->getCitytoStr($data['bu_city'],$data['bu_country']);

        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('country',$country);
        $this->assign('lists', $lists);
        $this->display();
    }

    /**
     * 添加页面
     */
    public function add() {
        $province = $this->region_model->getProvincetoStr();
        $this->assign('province',$province);
        $this->display();
    }

    /**
     * 编辑页面
     */
    public function edit() {
        $id = intval(I('id'));

        $result = $this->Buried_ruleModel->where(array('id' => $id))->find();

        $province = $this->region_model->getProvincetoStr($result['bu_province']);
        $city     = $this->region_model->getCitytoStr($result['bu_province'],$result['bu_city']);
        $country  = $this->region_model->getCitytoStr($result['bu_city'],$result['bu_country']);

        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('country',$country);
        $this->assign('result', $result);
        $this->display();
    }

    /**
     *
     */
    public function delete(){
        $id = intval(I('id'));
        $result = $this->Buried_ruleModel->delete($id);
        if($result){
            $this->success('success');
        }else{
            $this->error('error');
        }
    }

    public function add_post() {
        $data = I('post.');
        $is_area = $this->Buried_ruleModel->getOne($data['bu_province'],$data['bu_city'], $data['bu_country'] );
        if( $is_area ) exit($this->error('该区域已设置运输规则。'));
        $data['creat_time'] = time();
        $data['bu_service'] = $data['bu_service'];
        $rst = $this->Buried_ruleModel->add($data);
//        dump($data);
//        dump($rst);
        if($rst) {
            $this->success('', U('Rule/index'));
        } else {
            $this->error();
        }
    }
    /**
     *
     */
    public function edit_post() {
        $data= I('post.');
        $data['creat_time'] = time();
        $data['bu_service'] = $data['bu_service'];
        $rst = $this->Buried_ruleModel->save($data);
        if($rst) {
            $this->success('', U('Rule/index'));
        } else {
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