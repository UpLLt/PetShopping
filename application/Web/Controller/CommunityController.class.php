<?php
namespace Web\Controller;

use Advertisement\Model\BannerModel;
use Common\Model\ProductModel;
use Community\Model\ComArticleModel;
use Community\Model\ComCriticModel;
use Community\Model\ComKnowModel;
use Community\Model\ComLevelModel;
use Community\Model\ComRecordModel;
use Community\Model\ComRuleModel;
use Community\Model\ComScoreModel;
use Community\Model\ComTouchModel;
use Consumer\Model\MemberModel;
use Think\Controller;

/**
 * 宠物保险社区
 * Class IndexController
 * @package Web\Controller
 */

class CommunityController extends BaseController
{
    private $com_article_model, $banner_model, $com_critic_model, $member_model, $com_score_model, $com_level_model, $com_rule_model, $com_record_model, $com_touch_model, $com_knoe_model, $product_model;

    public function __construct()
    {
        parent::__construct();
        $this->banner_model = new BannerModel();
        $this->com_article_model = new ComArticleModel();
        $this->com_critic_model = new ComCriticModel();
        $this->member_model = new MemberModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_level_model = new ComLevelModel();
        $this->com_rule_model = new ComRuleModel();
        $this->com_record_model = new ComRecordModel();
        $this->com_touch_model = new ComTouchModel();
        $this->com_knoe_model = new ComKnowModel();
        $this->product_model = new ProductModel();
    }
    public function index()
    {
        $mid = session('mid');
        $this->is_login();
        $memberinfo = $this->member_model
            ->alias('a')
            ->join('LEFT JOIN ego_com_score as b on a.id = b.sco_member_id')
            ->field('a.nickname, a.headimg, b.sco_now')
            ->where(array('id' => $mid))
            ->find();
        $memberinfo['headimg'] = setUrl($memberinfo['headimg']);

        //轮播图
        //已启用的广告
        $bannerlist = M('banner')
            ->where(array('sign_key' => 'community', 'status' => 1))
            ->field('id')
            ->select();
        foreach ($bannerlist as $k => $v) {
            $ids[]= $v['id'];
        }
        $ids = implode(',', $ids);
        //已启用的广告所包含的图，限5 张
        $banner = M('banner_image')
            ->where(array('banner_id' =>array('in', $ids)))
            ->field('banner_id, title, link, image, type')
            ->order('sort_order asc')
            ->limit(5)
            ->select();
        foreach($banner as $k => $v) {
            if($v['type'] == 1) {
                $banners[] = array(
                    'title' => $v['title'],
                    'link' => U('Product/index', array('ptype' => 1, 'pid' => $v['link'])),
                    'image' => setUrl($v['image']),
                );
            } elseif ($v['type'] == 2) {
                $banners[] = array(
                    'title' => $v['title'],
                    'link' => U('Product/index', array('ptype' => 2, 'pid' => $v['link'])),
                    'image' => setUrl($v['image']),
                );
            } elseif($v['type'] == 3) {
                $banners[] = array(
                    'title' => $v['title'],
                    'link' => U('Web/Community/bannerDis', array('id' => $v['link'], 'banner_id' => $v['banner_id'], 'key' => $k)),
                    'image' => setUrl($v['image']),
                );
            }
        }
        $banner_num = array_slice($banners, 0, count($banners)-1);
        //条件筛选
        $order = 'art_gnum desc';
        $where = array();
        if(IS_GET) {
            $postdata = I('get.');
            if($postdata['data'] == 'newtime') {
                $order = 'art_time desc';
                $pet_munr = '   <li class="cation" ><a href="'.U('Web/Community/index', array('data'=>'newtime')).'">#最新</a></li>
                        <li><a href="'.U('Web/Community/index', array('data'=>'art_tags')).'">#精华</a></li>
                        <li><a href="'.U('Web/Community/index', array('data'=>'art_tagk')).'">#养宠小知识</a></li>
                        <li><a href="'.U('Web/Community/pet_answer').'">#宠物问答</a></li>
                        <li><a href="'.U('Web/Community/pet_munity').'">#我的话题</a></li>';
            }
            if($postdata['data'] == 'art_tags') {
                $where['art_tags'] = 1;
                $order = 'art_time desc';
                $pet_munr = '   <li  ><a href="'.U('Web/Community/index', array('data'=>'newtime')).'">#最新</a></li>
                        <li class="cation"><a href="'.U('Web/Community/index', array('data'=>'art_tags')).'">#精华</a></li>
                        <li><a href="'.U('Web/Community/index', array('data'=>'art_tagk')).'">#养宠小知识</a></li>
                        <li><a href="'.U('Web/Community/pet_answer').'">#宠物问答</a></li>
                        <li><a href="'.U('Web/Community/pet_munity').'">#我的话题</a></li>';
            }
            if($postdata['data'] == 'art_tagk') {
                $where['art_tagk'] = 1;
                $order = 'art_time desc';
                $pet_munr = '   <li ><a href="'.U('Web/Community/index', array('data'=>'newtime')).'">#最新</a></li>
                        <li><a href="'.U('Web/Community/index', array('data'=>'art_tags')).'">#精华</a></li>
                        <li  class="cation"><a href="'.U('Web/Community/index', array('data'=>'art_tagk')).'">#养宠小知识</a></li>
                        <li><a href="'.U('Web/Community/pet_answer').'">#宠物问答</a></li>
                        <li><a href="'.U('Web/Community/pet_munity').'">#我的话题</a></li>';
            }
        }

        if( !I('get.') ){
            $pet_munr = '   <li ><a href="'.U('Web/Community/index', array('data'=>'newtime')).'">#最新</a></li>
                        <li><a href="'.U('Web/Community/index', array('data'=>'art_tags')).'">#精华</a></li>
                        <li><a href="'.U('Web/Community/index', array('data'=>'art_tagk')).'">#养宠小知识</a></li>
                        <li><a href="'.U('Web/Community/pet_answer').'">#宠物问答</a></li>
                        <li><a href="'.U('Web/Community/pet_munity').'">#我的话题</a></li>';
        }
        //帖子列表
        $count = $this->com_article_model
            ->where($where)
            ->join('join ego_member ON ego_com_article.art_member_id = ego_member.id')
            ->join('join ego_com_score ON ego_com_article.art_member_id = ego_com_score.sco_member_id')
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
//        $page = $this->page($count, 2);
        $lists = $this->com_article_model
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->join(' join ego_member ON ego_com_article.art_member_id = ego_member.id')
            ->join(' join ego_com_score ON ego_com_article.art_member_id = ego_com_score.sco_member_id')
            ->field('art_id, art_title,art_gnum,art_cnum,art_time,art_image, art_tags,art_tagk,nickname, headimg,sco_level')
            ->order($order)
            ->select();

        $str = '';
        foreach($lists as $k => $v) {
            $img = json_decode($v['art_image'], true);
            if( $v['art_tags'] == 1 ) $str = '<span class="jing">精</span>';
            if( $v['art_tagk'] == 1 ) $str .= '<span class="zhi">知</span>';
            $articles[] = array(
                'art_id' => $v['art_id'],
                'art_title' => $v['art_title'],
                'art_gnum' => $v['art_gnum'],
                'art_cnum' => $v['art_cnum'],
                'art_time' => date('Y-m-d',$v['art_time']),
                'art_tags' => $v['art_tags'],
                'art_tagk' => $v['art_tagk'],
                'art_image' => setUrl($img),
                'nickname' => $v['nickname'],
                'headimg' => setUrl($v['headimg']),
                'sco_level' => 'red'.$v['sco_level'],
                'str'=>$str,
            );
        }

        $this->assign('pet_munr',$pet_munr);
        $this->assign('banner_num', $banner_num);
        $this->assign('articles', $articles);
        $this->assign("Page", $page->show('Admin'));
        $this->assign('banners', $banners);
        $this->display();
    }

    /**
     * 帖子详细内容
     */
    public function pet_show(){
        $art_id = I('get.art_id');
        //详情
        $detail = $this->com_article_model
            ->where(array('art_id' => $art_id))
            ->find();
        //图片
        $images = setUrl(json_decode($detail['art_image'], true));
        //评论
        //帖子列表
        $count = $this->com_critic_model
            ->alias('a')
            ->join('LEFT JOIN ego_member as b on a.cri_member_id = b.id')
            ->join('left join ego_com_score as c ON a.cri_member_id = c.sco_member_id')
            ->where(array('cri_art_id' => $art_id))
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $critics = $this->com_critic_model
            ->alias('a')
            ->join('LEFT JOIN ego_member as b on a.cri_member_id = b.id')
            ->join('left join ego_com_score as c ON a.cri_member_id = c.sco_member_id')
            ->where(array('cri_art_id' => $art_id))
            ->field('a.*, b.nickname, b.headimg, c.sco_level')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('cri_time desc')
            ->select();

        foreach($critics as $k => $v) {
            $count = $this->com_critic_model
                ->where(array('cri_parent_id' => $v['cri_id'], 'cri_art_id' => $v['cri_art_id'], 'cri_parent_member_id' => $v['cri_member_id']))
                ->count();
            if($v['cri_parent_id'] == 0) {
                $arr[] = array(
                    'cri_id' => $v['cri_id'],
                    'cri_member_id' => $v['cri_member_id'],
                    'cri_content' => $v['cri_content'],
                    'cri_time' => date('Y-m-d',$v['cri_time']),
                    'nickname' => $v['nickname'],
                    'headimg' => setUrl($v['headimg']),
                    'cri_parent_id' => $v['cri_parent_id'],
                    'parent_nickname' => '',
                    'parent_content' => '',
                    'isfirst' => '0',
                    'count' => $count,
                    'sco_level' => 'red'.$v['sco_level'],
                );
            } else {
                //查父级信息
                $switch = $this->com_critic_model
                    ->join('ego_member ON ego_com_critic.cri_member_id = ego_member.id')
                    ->field('cri_id, cri_content, ego_member.id, ego_member.nickname')
                    ->where(array('ego_com_critic.cri_id' => $v['cri_parent_id']))
                    ->find();

                $arr[] = array(
                    'cri_id' => $v['cri_id'],
                    'cri_content' => $v['cri_content'],
                    'cri_member_id' => $v['cri_member_id'],
                    'cri_time' => date('Y-m-d',$v['cri_time']),
                    'nickname' => $v['nickname'],
                    'headimg' => setUrl($v['headimg']),
                    'cri_parent_id' => $v['cri_parent_id'],
                    'parent_nickname' => $switch['nickname'],
                    'parent_content' => $switch['cri_content'],
                    'isfirst' => '1',
                    'count' => $count,
                    'sco_level' => 'red'.$v['sco_level'],
                );
            }
        }
        $this->assign('detail', $detail);
        $this->assign('images', $images);
        $this->assign('critics', $arr);
        $this->assign("Page", $page->show('Admin'));
        $this->display();
    }

    /**
     * 宠物问答
     */
    public function pet_answer(){
        if(IS_POST) {
            $keyword = I('post.keyword');
            $lists = $this->com_knoe_model->searchList($keyword, 15);
//            dump($this->com_knoe_model->getLastSql());
            $this->assign('lists', $lists);
        }


        $this->display();
    }

    public function answer_detail() {
       $kno_id = I('get.kno_id');
        $info = $this->com_knoe_model->detail($kno_id);
        $this->assign('detail', $info);
        $this->display();
    }

    /**
     * 我的帖子
     */
    public function pet_munity(){
        $mid = session('mid');
        $this->is_login();

        $where['art_member_id'] = $mid;
        //帖子列表
        $count = $this->com_article_model
            ->where($where)
            ->join('left join ego_member ON ego_com_article.art_member_id = ego_member.id')
            ->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
//        $page = $this->page($count, 1);
        $lists = $this->com_article_model
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->join('left join ego_member ON ego_com_article.art_member_id = ego_member.id')
            ->field('art_id, art_title,art_gnum,art_cnum,art_time,art_image, art_tags,art_tagk,username, headimg')
            ->order('art_time desc')
            ->select();
        foreach($lists as $k => $v) {
            $img = json_decode($v['art_image'], true);
            $articles[] = array(
                'art_id' => $v['art_id'],
                'art_title' => $v['art_title'],
                'art_gnum' => $v['art_gnum'],
                'art_cnum' => $v['art_cnum'],
                'art_time' => date('Y-m-d',$v['art_time']),
                'art_tags' => $v['art_tags'],
                'art_tagk' => $v['art_tagk'],
                'art_image' => setUrl($img),
                'username' => $v['username'],
                'headimg' => setUrl($v['headimg']),
            );
        }

        $this->assign('articles', $articles);
        $this->assign("Page", $page->show('Admin'));
        $this->display();
    }

    /**
     *删帖
     */
    public function delArticle() {
        $art_id = I('post.art_id');
        $rst = $this->com_article_model->where(array('art_id' => $art_id))->delete();
        if($rst) {
            echo json_encode(array('ret' => 1, 'res' => '删除成功！'));
        } else {
            echo json_encode(array('ret' => 0, 'res' => '删除失败1'));
        }

    }

    /**
     * 发帖
     */
    public function card(){
        $mid = session('mid');
        $this->is_login();
        if(IS_POST) {
//            dump($_FILES);
//            dump($_POST);exit;
            $postdata = I('post.');
            $keyword = $this->com_rule_model->where(array('rul_key' => 'rul_pubword'))->field('rul_value')->find();
//            dump($keyword);exit;
            $words = explode(',', $keyword['rul_value']);
            foreach ($words as $k => $v) {
                if(!(strpos($postdata['art_content'], $v)  === false)) {
                    $this->error('内容包含关键字，请重试');
                }
            }
            $imgurl = upload_img('Community');
//        dump($imgurl);exit;
            if(empty($imgurl)) {
                $this->error('图片上传失败');
            }
            $time = time();


            $this->com_article_model->startTrans();
            $iscommit = true;
            $art_id = $this->com_article_model->addOne($postdata['art_title'], $postdata['art_content'], json_encode($imgurl), $mid, $time );
            if(!$art_id) {
                $iscommit = false;
            }
            //添加记录，修改积分信息
            $rule = $this->com_rule_model->getValue('rul_pub');
            $rst = $this->com_record_model->addOne($rule['rul_value'], '发帖获取', $mid);
            if(!$rst) {
                $iscommit = false;
            }
            //查找积分信息
            $scores = $this->com_score_model->info($mid);
            //获取操作积分后等级
            $level = $this->com_level_model->getLev($scores['sco_history']+ $rule['rul_value']);
            //修改积分、等级信息
            $res = $this->com_score_model->updateSco($mid, $scores['sco_now'] + $rule['rul_value'], $scores['sco_history']+$rule['rul_value'], $level['lev_num']);
            if(!$res) {
                $iscommit = false;
            }
            if($iscommit) {
                $this->com_article_model->commit();
                $this->success('发布成功', U('Web/Community/pet_munity'));
            } else {
                $this->com_article_model->rollback();
                $this->error('发布失败');
            }
        }

        $this->display();
    }

    /**
     * 评论、回复
     */
    public function commting() {
        $this->is_login();
        $mid = session('mid');
        if(IS_GET) {
            $cri_id = I('get.cri_id');
            $cri_info = $this->com_critic_model->where(array('cri_id' => $cri_id))->field('cri_art_id, cri_member_id, cri_id')->find();
        }
        if(IS_POST) {
            $data = I('post.data');

            foreach($data as $k => $v) {
                $datas[$v['name']] = $v['value'];
            }
            if(empty($datas['cri_content'])) {
                echo json_encode(array('code' => 210, 'alert'=>'内容为空'));exit;
            }
            //关键字查找
            $keyword = $this->com_rule_model->where(array('rul_key' => 'rul_repword'))->field('rul_value')->find();
//            dump($keyword);exit;
            $words = explode(',', $keyword['rul_value']);
            foreach ($words as $k => $v) {
//                dump(strpos($datas['cri_content'], $v));
                if(!(strpos($datas['cri_content'], $v) === false)) {
                    echo json_encode(array('code' => 210, 'alert' => '内容包含关键字', 'res' => $error));exit;
                }
//                dump($v);
            }
//            exit;
//            echo json_encode($datas);exit;
            $datas['cri_time'] = time();
            $datas['cri_member_id'] = $mid;

            $this->com_critic_model->startTrans();
            $iscommit = true;
            $error = '';
            //添加评论
            if($this->com_critic_model->add($datas) == false) {
                $iscommit = false;
                $error = '1';
            }
            //添加记录，修改积分信息
            $rule = $this->com_rule_model->getValue('rul_reply');
            if($this->com_record_model->addOne($rule['rul_value'], '评论获取', $mid) == false) {
                $iscommit = false;
                $error = '2';
            }

            //查询积分信息
            $scores = $this->com_score_model->info($mid);
            //获取操作积分后等级
            $level = $this->com_level_model->getLev($scores['sco_history']+ $rule['rul_value']);
            //修改积分、等级信息
            $res = $this->com_score_model->updateSco($mid, $scores['sco_now'] + $rule['rul_value'], $scores['sco_history']+$rule['rul_value'], $level['lev_num']);
            if(!$res) {
                $iscommit = false;
                $error = '3';
            }
            //评论数+1
            $rst= $this->com_article_model->where(array('art_id' => $data[0]['value']))->setInc('art_cnum', '1');

            if(!$rst) {
                $iscommit = false;
                $error = '4';
            }

            if($iscommit) {
                $this->com_article_model->commit();
                echo json_encode(array('code' => 200, 'alert' => '评论成功'));
                exit;
            } else {
                $this->com_article_model->rollback();
                echo json_encode(array('code' => 210, 'alert' => '失败,请重试', 'res' => $error));exit;
            }
//            echo json_encode($datas);exit;
        }

        $this->assign('cri_info', $cri_info);
        $this->display();
    }

    /**
     * 点赞
     */
    public function clickGood() {
        $this->is_login();
        $mid = session('mid');
        $art_id = I('post.data');//dump($art_id);exit;
        $isclick = $this->com_touch_model->isClick($mid, $art_id);
        if($isclick) {
            echo json_encode(array('ret' => 210, 'alert' => '不能重复点赞'));exit;
        }
        $this->com_touch_model->startTrans();
        $iscommit = true;
        $error = '';
        $rst = $this->com_touch_model->addOne($mid, $art_id);
        if(!$rst) {
            $iscommit = false;
            $error = '1';
        }
        $res = $this->com_article_model->where(array('art_id' => $art_id))->setInc('art_gnum', '1');
        if(!$res) {
            $iscommit = false;
            $error = '2';
        }
        if($iscommit) {
            $this->com_touch_model->commit();
            echo json_encode(array('ret' => 200, 'alert' => '点赞成功'));exit;
        } else {
            $this->com_touch_model->rollback();
            echo json_encode(array('ret' => 210, 'alert' => '点赞失败', 'code' => $error));exit;
        }

    }

    /**
     * 获取头部信息
     */
   /* public function getMemberinfo() {
        $mid = session('mid');
        $this->is_login();
        $memberinfo = $this->member_model
            ->alias('a')
            ->join('LEFT JOIN ego_com_score as b on a.id = b.sco_member_id')
            ->field('a.nickname, a.headimg, b.sco_now')
            ->where(array('id' => $mid))
            ->find();
        $memberinfo['headimg'] = setUrl($memberinfo['headimg']);
        echo json_encode(array('code' == 200, 'data' => $memberinfo));
    }*/
    /**
     * 签到
     */
    public function signIn() {
        $this->is_login();
        $mid = session('mid');

        //检查是否重复签到
        $is_sign = $this->com_record_model->is_sign($mid);
        if($is_sign) {
            echo json_encode(array('code' => 210, 'alert' => '不能重复签到'));exit;
        }
        //获取积分规则
        $rule = $this->com_rule_model->getValue('rul_click');
        $this->com_score_model->startTrans();
        $iscommit = true;
        $error = '';
        //添加记录
        $rst = $this->com_record_model->addOne($rule['rul_value'], '签到获取', $mid);//dump($this->com_record_model->getLastSql());
        if(!$rst) {
            $iscommit = false;
            $error = '1';
        }
        $scores = $this->com_score_model->info($mid);
        //获取操作积分后等级
        $level = $this->com_level_model->getLev($scores['sco_history']+ $rule['rul_value']);
        //修改积分、等级信息
        $now_score = $scores['sco_now'] + $rule['rul_value'];
        $res = $this->com_score_model->updateSco($mid, $now_score, $scores['sco_history']+$rule['rul_value'], $level['lev_num']);//echo json_encode($res);
//            echo json_encode($sco_history);exit;
        if(!$res) {
            $iscommit = false;
            $error = '2';
        }
        if($iscommit) {
            $this->com_score_model->commit();
            echo json_encode(array('code' => 200, 'alert' => '签到成功', 'score' => $rule['rul_value'], 'now_score' => $now_score));exit;
        } else {
            $this->com_article_model->rollback();
            echo json_encode(array('code' => 210, 'alert' => '签到失败',  'error' => $error));exit;
        }
    }

    public function bannerDis() {
        $id= I('get.id');
        $banner_id = I('get.banner_id');
        $key = I('get.key');

        $imgurl = M('banner_image')
            ->where(array('banner_id' => $banner_id))
            ->select();
//        $id = 4;
        $detail = M('posts')->where(array('id' => $id))->field('post_title, post_content')->find();
//        dump($detail);
        $img = '<img src="'.setUrl($imgurl[$key]['image']).'" alt="">';
        $this->assign('img', $img);
        $this->assign('detail', $detail['post_content']);
        $this->display();
    }
}