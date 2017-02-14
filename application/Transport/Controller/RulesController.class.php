<?php
namespace Transport\Controller;
use Common\Controller\AdminbaseController;
use Common\Model\RegionModel;
use Think\Controller;
use Transport\Model\TransportModel;
use Transport\Model\TransportRulesModel;

class RulesController extends  AdminbaseController {
    private $region_model , $transport_model , $transport_rules_model;

    public function __construct()
    {
        parent::__construct();
        $this->region_model = new RegionModel();
        $this->transport_model = new TransportModel();
        $this->transport_rules_model = new TransportRulesModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    public function _lists(){

        $data = I('post.');

        foreach($data as $k => $v ){
            if( $v ) $where[$k] = $v;
        }

        $count = $this->transport_rules_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->transport_rules_model
                ->order('id desc')
                ->where($where)
                ->limit($page->firstRow . ',' . $page->listRows)
                ->select();

        $categorys = '';
        foreach( $result as $k => $v){
            $tr_province = $this->region_model->where('code='.$v['tr_province'])->field('name')->find();
            $tr_city = $this->region_model->where('code='.$v['tr_city'])->field('name')->find();
            $tr_country = $this->region_model->where('code='.$v['tr_country'])->field('name')->find();

            $result[$k]['str_manage'] = '<a class="" href="' . U('Rules/edit', array('id' => $v['id'])) . '">编辑</a>';
            $result[$k]['str_manage'] .= " | ";
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Rules/delete', array('id' => $v['id'])) . '">删除</a>';

            $categorys .= '<tr>
            <td>' . ($k + 1) . '</td>
            <td>' . $tr_province['name'] . '</td>
            <td>' . $tr_city['name'] . '</td>
            <td>' . $tr_country['name'] . '</td>
            <td>' . $v['tr_price'] . '</td>
            <td>' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }


         if( !$data['tr_province'] ) {
             $data['tr_province'] = '110000';
             $data['tr_city'] = '110100';
             $data['tr_country'] = '110101';
         }
        $province = $this->region_model->getProvincetoStr($data['tr_province']);
        if($data['tr_city']) $city     = $this->region_model->getCitytoStr($data['tr_province'],$data['tr_city']);
        if($data['tr_country']) $country = $this->region_model->getCitytoStr($data['tr_city'],$data['tr_country']);

        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('country',$country);
        $this->assign('categorys',$categorys);
        $this->assign('Page',$page->show('Admin'));

    }


    public function delete(){
        $id = intval(I('id'));
        $result = $this->transport_rules_model->delete($id);
        if($result){
            $this->success('success');
        }else{
            $this->error('error');
        }
    }


    public function edit(){
        $id = intval(I('id'));
        $result = $this->transport_rules_model->where('id='.$id)->find();
        $province = $this->region_model->getProvincetoStr($result['tr_province']);
        $city     = $this->region_model->getCitytoStr($result['tr_province'],$result['tr_city']);
        $country  = $this->region_model->getCitytoStr($result['tr_city'],$result['tr_country']);
        $result['tr_weight'] = json_decode( $result['tr_weight'] ,true);
        $weight = "";
        foreach( $result['tr_weight'] as $k => $v ){
            $weight .= '<dd style="margin-top: 10px">重量：<input value="'.$v['start'].'" name="weight[start][]" type="number" style="width: 50px;">~<input value="'.$v['end'].'" name="weight[end][]" type="number" style="width: 50px;">&nbsp;&nbsp; KG&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;价格：<input value="'.$v['price'].'" name="weight[price][]" type="number" style="width: 50px;">&nbsp;&nbsp;元/KG&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;"><img onclick="reduce(this)" style="width: 30px;height: 30px;" src="public/images/reduce.png"></a></dd>';
        }
        $result['tr_cage']= json_decode( $result['tr_cage'] ,true);

        $this->assign('weight',$weight);
        $this->assign('result',$result);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('country',$country);
        $this->display();
    }

    public function edit_post(){
        $data = I('post.');

        foreach( $data['weight']['start'] as $k => $v ){
            $tr_weight[$k]['start'] = $v;
            $tr_weight[$k]['end']   = $data['weight']['end'][$k];
            $tr_weight[$k]['price'] = $data['weight']['price'][$k];
        }

        $data['tr_weight'] = json_encode($tr_weight);
        $data['tr_cage']   = json_encode($data['tr_cage']);
        $data['create_time'] = time();
        $data['tr_service'] = htmlspecialchars_decode($data['tr_service']);

        $result = $this->transport_rules_model->save($data);
        if( $result === false ){
            $this->error('error');
        }else{
            $this->success('success');
        }
    }

    public function add(){
        $province = $this->region_model->getProvincetoStr();
        $this->assign('province',$province);
        $this->display();
    }


    public function add_post(){
        $data = I('post.');
        $is_area = $this->transport_rules_model
            ->where(array('tr_province'=>$data['tr_province'],'tr_city'=>$data['tr_city'],'tr_country'=>$data['tr_country']))
            ->select();
        if( $is_area ) exit($this->error('该区域已设置运输规则。'));
        foreach( $data['weight']['start'] as $k => $v ){
            $tr_weight[$k]['start'] = $v;
            $tr_weight[$k]['end']   = $data['weight']['end'][$k];
            $tr_weight[$k]['price'] = $data['weight']['price'][$k];
        }

        $data['tr_weight'] = json_encode($tr_weight);
        $data['tr_cage']   = json_encode($data['tr_cage']);
        $data['create_time'] = time();
        $data['tr_service'] = htmlspecialchars_decode($data['tr_service']);
        $result = $this->transport_rules_model->add($data);
        if($result){
            $this->success('success');
        }else{
            $this->error('error');
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