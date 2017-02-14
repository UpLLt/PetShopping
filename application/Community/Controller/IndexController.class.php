<?php
/**
 *社区管理
 */
namespace Community\Controller;
use Common\Controller\AdminbaseController;
use Community\Model\ComArticleModel;
use Community\Model\ComRuleModel;

class IndexController extends AdminbaseController {

    private $Commit_model;
    private $Com_rulModel;

    public function __construct(){

    	parent::__construct();
    	$this->Commit_model = new ComArticleModel();
        $this->Com_rulModel = new ComRuleModel();
    }

	/**
	 *社区管理首页--帖子管理
	 */
    public function index(){
        $fields = array(
            'nickname' => array("field" => "ego_member.nickname", "operator" => "like", 'datatype' => 'string'),
            'art_title' => array("field" => "ego_com_article.art_title", "operator" => "like", 'datatype' => 'string'),
//            'tag' => array("field" => "tag", "operator" => "like", 'datatype' => 'string'),
        );
//        ($_POST['keyword'] == 1 && $_POST['keyword'] != 0)? "ego_com_article.art_tags" : "ego_com_article.art_tagk";
        $where_ands = array();
        if (IS_POST) {
//            dump($_POST);exit;
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }
        $where = join(" and ", $where_ands);
        if($_POST['tags'] == 1) {    //精华帖
            $where .= empty($where)? "ego_com_article.art_tags = 1 " : " and ego_com_article.art_tags = 1";
            $this->assign('tags', 'checked="checked"');
        }
        if($_POST['tagk'] == 1) {  //宠物小知识
            $where .= empty($where)? "ego_com_article.art_tagk = 1 " : " and ego_com_article.art_tagk = 1";
            $this->assign('tagk', 'checked="checked"');
        }
//        dump($where);exit;
        $count = $this->Commit_model->where($where)->join('ego_member ON ego_com_article.art_member_id = ego_member.id')->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $rst = $this->Commit_model
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->join('ego_member ON ego_com_article.art_member_id = ego_member.id')
            ->field('art_id, art_title,art_gnum,art_cnum,art_time, art_tags,art_tagk,username')
            ->order('art_time desc')
            ->select();
//        dump($rst);
        foreach ($rst as $k => $v) {
            $delete = '<a class="js-ajax-delete" href="' . U('Index/delete', array('id' => $v['art_id'])) . '">删除</a>';
            if($v['art_tags'] == 0 && $v['art_tagk'] == 0) {
                $tags = '无';
                $buttons = '<a class="js-ajax" href="' . U('Index/changeTags', array('id' => $v['art_id'], 'art_tags' =>1)) . '">设置精华帖</a>
                 | <a class="js-ajax" href="' . U('Index/changeTagk', array('id' => $v['art_id'], 'art_tagk' =>1)) . '">设置宠物小知识</a>';
            } elseif($v['art_tags'] == 0 && $v['art_tagk'] == 1) {
                $tags = '宠物小知识';
                $buttons = '<a class="js-ajax" href="' . U('Index/changeTags', array('id' => $v['art_id'], 'art_tags' =>1)) . '">设置精华帖</a>
                 | <a class="js-ajax" href="' . U('Index/changeTagk', array('id' => $v['art_id'], 'art_tagk' =>0)) . '">取消宠物小知识</a>';
            } elseif($v['art_tags'] == 1 && $v['art_tagk'] == 0){
                $tags = '精华帖';
                $buttons = '<a class="js-ajax" href="' . U('Index/changeTags', array('id' => $v['art_id'], 'art_tags' =>0)) . '">取消精华帖</a>
                 | <a class="js-ajax" href="' . U('Index/changeTagk', array('id' => $v['art_id'], 'art_tagk' =>1)) . '">设置宠物小知识</a>';
            } else {
                $tags = '精华帖 | 宠物小知识';
                $buttons = '<a class="js-ajax" href="' . U('Index/changeTags', array('id' => $v['art_id'], 'art_tags' =>0)) . '">取消精华帖</a>
                 | <a class="js-ajax" href="' . U('Index/changeTagk', array('id' => $v['art_id'], 'art_tagk' =>0)) . '">取消宠物小知识</a>';
            }
            $title = '<a href="' . U('Index/detail', array('id' => $v['art_id'])) . '">'.$v['art_title'].'</a>';
            $categorys .= '<tr>
                <td>' . ($k + 1) . '</td>
                <td>' . $title  .' </td>
                <td>' . $v['username'] . '</td>
                <td>' . date('Y/m/d',$v['art_time']) . '</td>
                <td>' . $v['art_gnum'] . '</td>
                <td>' . $v['art_cnum'] . '</td>
                <td>' .$tags.'</td>
                <td>'.$delete.' | ' .$buttons. '</td>
            </tr>';
        }
//        dump($categorys);
        $this->assign('nickname', I('post.nickname'));
        $this->assign('title', I('post.art_title'));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    	$this->display();
    }

    public function delete() {
        $art_id = intval(I('get.id'));
        if(empty($art_id)) {
            $this->error();
        }
        $rst = $this->Commit_model->delete($art_id);
        if($rst) {
            $this->success();
        }
    }

    /*
     * 帖子详情
     */
    public function detail() {
        $art_id = I('get.id');
        $rst = $this->Commit_model->where(array('art_id' => $art_id))->find();
        $delete = '<a class="js-ajax-delete " style="margin-left: 30%" href="' . U('Index/delete', array('id' => $rst['art_id'])) . '">删除</a>';
        if($rst['art_tags'] == 0 && $rst['art_tagk'] == 0) {
            $buttons = '<a class="js-ajax text-center" href="' . U('Index/changeTags', array('id' => $rst['art_id'], 'art_tags' =>1)) . '">设置精华帖</a>
                 | <a class="js-ajax" href="' . U('Index/changeTagk', array('id' => $rst['art_id'], 'art_tagk' =>1)) . '">设置宠物小知识</a>';
        } elseif($rst['art_tags'] == 0 && $rst['art_tagk'] == 1) {
            $buttons = '<a class="js-ajax text-center" href="' . U('Index/changeTags', array('id' => $rst['art_id'], 'art_tags' =>1)) . '">设置精华帖</a>
                 | <a class="js-ajax" href="' . U('Index/changeTagk', array('id' => $rst['art_id'], 'art_tagk' =>0)) . '">取消宠物小知识</a>';
        } elseif($rst['art_tags'] == 1 && $rst['art_tagk'] == 0){
            $buttons = '<a class="js-ajax text-center" href="' . U('Index/changeTags', array('id' => $rst['art_id'], 'art_tags' =>0)) . '">取消精华帖</a>
                 | <a class="js-ajax text-center" href="' . U('Index/changeTagk', array('id' => $rst['art_id'], 'art_tagk' =>1)) . '">设置宠物小知识</a>';
        } else {
            $buttons = '<a class="js-ajax" href="' . U('Index/changeTags', array('id' => $rst['art_id'], 'art_tags' =>0)) . '">取消精华帖</a>
                 | <a class="js-ajax text-center" href="' . U('Index/changeTagk', array('id' => $rst['art_id'], 'art_tagk' =>0)) . '">取消宠物小知识</a>';
        }
        $url = json_decode($rst['art_image'], true);
        $options_obj = M("Options");
        $option = $options_obj->where("option_name='cmf_settings'")->find();
        $settings = json_decode($option['option_value'], true);
        $http_host = 'http://'.$settings['storage']['Qiniu']['domain'].'/';
        foreach($url as $k => $v) {
            $img .= '<img style="margin-right: 25px; margin-left:13px; margin-bottom: 20px;" src="'.$http_host.$v.'?imageView2/1/w/200/h/200"/>';
        }

        $categorys = '<div style="margin-left: 20%; margin-right: 20%;"><h2 class="text-center">'.$rst['art_title'].'</h2><footer class="text-right" style="margin-right: 15px">'.'——'.date('Y/m/d H:i:s', $rst['art_time']).'——'.'</footer>'
            .'<p style="text-indent: 2em">'.$rst['art_content'].'</p>
            '.$img.'</br>'.$delete.' | '.$buttons.'</div>';

        $this->assign('categorys', $categorys);
        $this->display();
    }

    /*
     * 设置/取消精华帖
     */
    public function changeTags() {
        $art_id = I('get.id');
        $data['art_tags'] = I('get.art_tags');//是否精华帖标记值

        $rst = $this->Commit_model->saveTag($art_id, $data);
        if($rst) {
//            echo"<script>alert('成功');history.go(-1);</script>";
            $this->success();
        } else {
            $this->error('error');
        }
    }

    /*
     * 设置/取消宠物小知识
     */
    public function changeTagk() {
        $art_id = I('get.id');
        $data['art_tagk'] = I('get.art_tagk');//是否精华帖标记值

        $rst = $this->Commit_model->saveTag($art_id, $data);
        if($rst) {
            $this->success();
        } else {
            $this->error();
        }

    }

    /*
     * 规则配置
     */
    public function ruleSet() {
        $info = $this->Com_rulModel->select();
//        dump($info);//exit;
//        dump($info[0]['rul_value']);
        if(IS_POST) {
            $postdata = get_data(1);
            foreach ($postdata['post'] as $k => $v) {
//                dump($k);dump($v);
                $rst = $this->Com_rulModel->where(array('rul_key' => $k))->save(array('rul_value'=>$v));
                if($rst) {
                    $info = $this->Com_rulModel->select();
//                    $this->success();
                }
            }
//            dump($_POST);exit;
        }
        $this->assign('info', $info);
        $this->display();
    }

    public function uploadImg() {
//        $setting  = sp_get_cmf_settings('storage');
//        dump($setting);
        if (IS_POST) {
//            dump($_POST);exit;
//            $postData = get_data('1');
//            dump($postData);exit;
//            dump($_POST);exit;
//            dump($_FILES);exit;
            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['post']['smeta'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
                }
            }
            dump($_POST);exit;
            $imgurl = upload_img('Community');
            dump($imgurl);exit;
        }
        $this->display();
    }
}