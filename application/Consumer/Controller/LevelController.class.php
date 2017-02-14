<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/24
 * Time: 10:54
 */

namespace Consumer\Controller;


use Common\Controller\AdminbaseController;
use Community\Model\ComLevelModel;

class LevelController extends AdminbaseController
{
    private $Com_levModel;//会员积分等级规则
    public function __construct(){

        parent::__construct();
        $this->Com_levModel = new ComLevelModel();
    }

    public function setting() {
        $info = $this->Com_levModel->select();
        if(IS_POST) {
            $data = $_POST;
            $arr = array();
            foreach ($data['post'] as $k => $v) {
                if ($v == '') exit($this->error('有值为空，请完成'));
            }
            for ($i = 0; $i < count($data['post']); $i+=2) {
               $arr[] = array_values(array_slice($data['post'],$i,2));
            }
            foreach ($arr as $k=>$v) {
                $updata['lev_start'] = $v[0];
                $updata['lev_end'] = $v[1];
                $this->Com_levModel->where(array('lev_id' => $k+1))->save($updata);
            }
                $this->success('success', U('Level/setting'), 1);exit;

        }
        $this->assign('info', $info);
        $this->display();
    }
}