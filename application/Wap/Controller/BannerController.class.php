<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/27
 * Time: 10:04
 */

namespace Wap\Controller;



use Portal\Model\PostsModel;
use Think\Controller;

class BannerController extends Controller
{

    private $posts_model;

    public function __construct()
    {
        parent::__construct();
        $this->posts_model = new PostsModel();
    }

    public function artdis() {
        $id= I('get.id');
//        $id = 4;
        $detail = M('posts')->where(array('id' => $id))->field('post_title, post_content')->find();

//        dump($detail);
        $this->assign('detail', $detail['post_content']);
        $this->display();

    }
}