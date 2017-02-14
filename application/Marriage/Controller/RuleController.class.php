<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/25
 * Time: 17:03
 */

namespace Marriage\Controller;


use Common\Controller\AdminbaseController;
use Marrage\Model\WeddingRulesModel;

class RuleController extends AdminbaseController
{
    private $Wedding_Model;

    public function __construct()
    {
        parent:: __construct();
        $this->Wedding_Model = new \Marriage\Model\WeddingRulesModel();
    }

    public function index() {
        $info = $this->Wedding_Model->find();
        if(IS_POST) {
            $data = get_data(1);
            $data['create_time'] = time();
            $rst = $this->Wedding_Model->save($data);
            if($rst) {
                $this->success('success');exit;
            } else {
                $this->error('error');
            }
        }
//        dump($info);
        $this->assign('info', $info);
        $this->display();
    }
}