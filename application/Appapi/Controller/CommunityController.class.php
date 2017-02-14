<?php
/**
 * 社区相关接口
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/18
 * Time: 13:58
 */

namespace Appapi\Controller;


use Community\Model\ComArticleModel;
use Community\Model\ComCriticModel;
use Community\Model\ComKnowModel;
use Community\Model\ComLevelModel;
use Community\Model\ComRecordModel;
use Community\Model\ComRuleModel;
use Community\Model\ComScoreModel;
use Community\Model\ComTouchModel;
use Consumer\Model\MemberModel;

class CommunityController extends ApibaseController
{
    private $Com_artModel;//帖子
    private $Com_scoModel;//积分
    private $MemberModel;//用户信息
    private $Com_rulModel;//积分规则
    private $Com_recModel;//积分记录
    private $Com_criModel;//评论
    private $Com_knoModel;//宠物小知识
    private $Com_touModel;//点赞model
    private $Com_levModel;//会员等级
    public function __construct()
    {
        parent::__construct();
        $this->Com_artModel = new ComArticleModel();
        $this->Com_scoModel = new ComScoreModel();
        $this->MemberModel = new MemberModel();
        $this->Com_rulModel = new ComRuleModel();
        $this->Com_recModel = new ComRecordModel();
        $this->Com_criModel = new ComCriticModel();
        $this->Com_knoModel = new ComKnowModel();
        $this->Com_touModel = new ComTouchModel();
        $this->Com_levModel = new ComLevelModel();
    }

    /*
     * 用户社区信息获取
     */
    public function memberInfo() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);
        $Memberinfo = $this->MemberModel->where(array('id' => $mid))->field('id, nickname, headimg')->find();
        $info = $this->Com_scoModel->info($mid);
        $is_sign = $this->Com_recModel->is_sign($mid);
        //响应数据
        if($info) {
            $data = array(
                'nickname' => $Memberinfo['nickname']? $Memberinfo['nickname'] : '暂无昵称',//昵称
                'heading' => setUrl($Memberinfo['headimg']),//头像
                'sco_now' => $info['sco_now'],//现有积分
                'sco_history' => $info['sco_history'],//历史积分
                'sco_level' => $info['sco_level'],//会员等级
                'is_sign' => $is_sign ? '1' : '0',
            );
        } else {
            //没有积分信息则初始化
            $data['sco_member_id'] = $mid;
            $this->Com_scoModel->add($data);

            $data = array(
                'nickname' => $Memberinfo['nickname']? $Memberinfo['nickname'] : '暂无昵称',
                'heading' => setUrl($Memberinfo['headimg']),
                'sco_now' => '0',
                'sco_history' => '0',
                'sco_level' => '1',
                'is_sign' => $is_sign ? '1' : '0',
            );
        }
        exit($this->returnApiSuccess($data));
    }
    /*
     * 获取帖子列表
     */
    public function getList() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if($this->Com_artModel->gettype($postdata['tag']) == '我的话题') {
            if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
            $this->checkparam($postdata);
        }
        $order = 'art_gnum desc';
        $where = array();
        //筛选条件
        if($this->Com_artModel->gettype($postdata['tag']) == '最新帖子') {
            $order = 'art_time desc';
        }elseif($this->Com_artModel->gettype($postdata['tag']) == '精华帖') {
            $where['art_tags'] = 1;
            $order = 'art_time desc';
        } elseif($this->Com_artModel->gettype($postdata['tag']) == '宠物小知识') {
            $where['art_tagk'] = 1;
            $order = 'art_time desc';
        } elseif($this->Com_artModel->gettype($postdata['tag']) == '我的话题') {
            $where['art_member_id'] = $mid;
            $order = 'art_time desc';
        }
        //页码
        $page = isset($postdata['page']) && intval($postdata['page'])>1 ? $postdata['page'] : '1';
        //每页显示数量
        $perpage = isset($postdata['perpage']) && intval($postdata['perpage'])>1 ? $postdata['perpage'] : '10';
        $count = $this->Com_artModel
            ->where($where)
            ->join('ego_member ON ego_com_article.art_member_id = ego_member.id')
            ->count();
        $field = 'art_id, art_title, art_content, art_image, art_gnum, art_cnum, art_time, art_tags, art_tagk, nickname, headimg, sco_level';

        $lists = $this->Com_artModel
            ->where($where)
            ->field($field)
            ->order($order)
            ->join('ego_member ON ego_com_article.art_member_id = ego_member.id')
            ->join('left join ego_com_score ON ego_com_article.art_member_id = ego_com_score.sco_member_id')
            ->page($page,$perpage)
            ->select();


        if ($lists === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        //处理数据
        $row = array();
        foreach($lists as $k => $v) {
            $isclick = $this->Com_touModel->isClick($mid, $v['art_id']);
            $url = json_decode($v['art_image'], true);
            $row[]= array(
                'nickname' => $v['nickname'],
                'headimg' => setUrl($v['headimg']),
                'art_id' => $v['art_id'],
                'art_title' => $v['art_title'],
                'art_content' => $v['art_content'],
                'art_image' => setUrl($url),
                'art_gnum' => $v['art_gnum'],
                'art_cnum' => $v['art_cnum'],
                'art_time' => date('Y-m-d', $v['art_time']),
                'art_tags' => $v['art_tags'],
                'art_tagk' => $v['art_tagk'],
                'isclick' => $isclick ? '1' : '0',
                'sco_level' => $v['sco_level'],
            );

        }
        $data['article'] = $row;
        $totalpage = $count/$perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $data['count'] = $totalpage;
        exit($this->returnApiSuccess($data));
    }



    /*
     *发帖子
     */
    public function publish() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam(array($mid, $token, $postdata['art_title'], $postdata['art_content']));
        //处理上传图片
        $imgurl = upload_img('Community');
//        dump($imgurl);exit;
        if(empty($imgurl)) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '图片上传失败'));
        }
        $time = time();
        $keyword = $this->Com_rulModel->where(array('rul_key' => 'rul_pubword'))->field('rul_value')->find();
        $words = explode(',', $keyword['rul_value']);
        foreach ($words as $k => $v) {
            if(!(strpos($postdata['art_content'], $v) === false)) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '有关键字，不能发布'));
        }
        $this->Com_artModel->startTrans();
        $iscommit = true;
        $art_id = $this->Com_artModel->addOne($postdata['art_title'], $postdata['art_content'], json_encode($imgurl), $postdata['mid'], $time );
        if(!$art_id) {
            $iscommit = false;
        }
        //添加记录，修改积分信息
        $rule = $this->Com_rulModel->getValue('rul_pub');
        $rst = $this->Com_recModel->addOne($rule['rul_value'], '发帖获取', $mid);
        if(!$rst) {
            $iscommit = false;
        }
        //修改积分信息
        $scores = $this->Com_scoModel->info($mid);
        //获取操作积分后等级
        $level = $this->Com_levModel->getLev($scores['sco_history']+ $rule['rul_value']);
        //修改积分、等级信息
        $res = $this->Com_scoModel->updateSco($mid, $scores['sco_now'] + $rule['rul_value'], $scores['sco_history']+$rule['rul_value'], $level['lev_num']);
       if(!$res) {
           $iscommit = false;
       }
        if($iscommit) {
            $this->Com_artModel->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->Com_artModel->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '失败'));
        }

    }

    /*
     * 删帖
     */
    public function delArt() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);
        $rst = $this->Com_artModel->deleteOne($postdata['art_id']);
        if($rst) {
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '删除失败'));
        }
    }

    /*
     * 签到获取积分
     */
    public function signGet() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);
        //检查是否重复签到
        $is_sign = $this->Com_recModel->is_sign($mid);
        if($is_sign) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '不能重复签到'));
        }
        //获取积分规则
        $rule = $this->Com_rulModel->getValue('rul_click');
        $this->Com_recModel->startTrans();
        $iscommit = true;
        //添加记录
        $rst = $this->Com_recModel->addOne($rule['rul_value'], '签到获取', $mid);
        if(!$rst) {
            $iscommit = false;
        }
        $scores = $this->Com_scoModel->info($mid);
        //获取操作积分后等级
        $level = $this->Com_levModel->getLev($scores['sco_history']+ $rule['rul_value']);
        //修改积分、等级信息
        $res = $this->Com_scoModel->updateSco($mid, $scores['sco_now'] + $rule['rul_value'], $scores['sco_history']+$rule['rul_value'], $level['lev_num']);//echo json_encode($res);
//            echo json_encode($sco_history);exit;
        if(!$res) {
            $iscommit = false;
        }
        if($iscommit) {
            $this->Com_recModel->commit();
            exit($this->returnApiSuccess('+'.$rule['rul_value']));
        } else {
            $this->Com_recModel->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '签到失败'));
        }

    }

    /*
     * 获取评论
     */
    public function criticList() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if(!empty($mid) && !empty($token)) {
            if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
            $this->checkparam($postdata);
        }

        //页码
        $page = isset($postdata['page']) && intval($postdata['page'])>1 ? $postdata['page'] : '1';
        //每页显示数量
        $perpage = isset($postdata['perpage']) && intval($postdata['perpage'])>1 ? $postdata['perpage'] : '10';
        $count = $this->Com_criModel
            ->join('ego_member ON ego_com_critic.cri_member_id = ego_member.id')
            ->where(array('cri_art_id' => $postdata['art_id']))
            ->count();
        $lists = $this->Com_criModel
            ->join('ego_member ON ego_com_critic.cri_member_id = ego_member.id')
            ->join('left join ego_com_score ON ego_com_critic.cri_member_id = ego_com_score.sco_member_id')
            ->field('cri_id, cri_content, cri_time,cri_member_id, cri_parent_id,cri_parent_member_id, ego_member.id, ego_member.nickname, ego_member.headimg, sco_level')
            ->where(array('ego_com_critic.cri_art_id' => $postdata['art_id']))
            ->page($page.','.$perpage)
            ->order('cri_time desc')
            ->select();
        $arr = array();
        foreach ($lists as $k => $v) {
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
                    'sco_level' => $v['sco_level'],
                );
            } else {
                $switch = $this->searchInfo( $v['cri_parent_id']);//查父级信息
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
                    'sco_level' => $v['sco_level'],
                );
            }
        }
        $data['list'] = $arr;
        $totalpage = $count/$perpage;
        $totalpage = floor($totalpage);
        if($count % $perpage) {
            $totalpage += 1;
        }
        $data['count'] = $totalpage;
        exit($this->returnApiSuccess($data));
    }
    /*
     * 无限级评论查询
     */
    public function searchInfo($cid) {
        $lists = $this->Com_criModel
            ->join('ego_member ON ego_com_critic.cri_member_id = ego_member.id')
            ->field('cri_id, cri_content, ego_member.id, ego_member.nickname')
            ->where(array('ego_com_critic.cri_id' => $cid))
            ->find();
        return $lists;
    }
    /*
     * 评论/回复
     */
    public function criticIn() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);
        //关键字查找
        $keyword = $this->Com_rulModel->where(array('rul_key' => 'rul_repword'))->field('rul_value')->find();;
        $words = explode(',', $keyword['rul_value']);
        foreach ($words as $k => $v) {
            if(!(strpos($postdata['content'], $v) === false)) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '有关键字，不能回复'));
        }
        $cri_id = is_numeric($postdata['cri_id']) ? $postdata['cri_id'] : 0;
        $member_id = is_numeric($postdata['member_id']) ? $postdata['member_id'] : 0;
        $this->Com_criModel->startTrans();
        $iscommit = true;
        $rst = $this->Com_criModel->addOne($postdata['content'], $mid, $postdata['art_id'], $cri_id, $member_id);
        if(!$rst) {
            $iscommit = false;
        }
        //添加记录，修改积分信息
        $rule = $this->Com_rulModel->getValue('rul_reply');
        $rec = $this->Com_recModel->addOne($rule['rul_value'], '评论获取', $mid);
        if(!$rec) {
            $iscommit = false;
        }
        //修改积分信息
        $scores = $this->Com_scoModel->info($mid);
        //获取操作积分后等级
        $level = $this->Com_levModel->getLev($scores['sco_history']+ $rule['rul_value']);
        //修改积分、等级信息
        $res = $this->Com_scoModel->updateSco($mid, $scores['sco_now'] + $rule['rul_value'], $scores['sco_history']+$rule['rul_value'], $level['lev_num']);
        if(!$res) {
           $iscommit = false;
        }
        //评论数+1
        if($this->Com_artModel->where(array('art_id' => $postdata['art_id']))->setInc('art_cnum', '1') == false) {
            $iscommit = false;
        };

//        $iscommit = false;
        if($iscommit) {
            $this->Com_criModel->commit();
            exit($this->returnApiSuccess());
        } else {
            $this->Com_criModel->rollback();
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '评论失败'));
        }

    }
    /*
     * 点赞
     */
    public function clickGood() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        $this->checkparam($postdata);
        $isclick = $this->Com_touModel->isClick($mid, $postdata['art_id']);
        if($isclick) {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '不能重复点赞'));
        }
        $rst = $this->Com_touModel->addOne($mid, $postdata['art_id']);
        if($rst) {
            $res = $this->Com_artModel->where(array('art_id' => $postdata['art_id']))->setInc('art_gnum', '1');
        }
        if($rst && $res) {
            exit($this->returnApiSuccess());
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '点赞失败'));
        }
    }
    /*
     * 宠物小知识搜索
     */
    public function knowList() {
        if( !IS_POST ) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
//        $mid = I('post.mid');
//        $token = I('post.token');
        $postdata = get_data(1);//获取请求数据

//        if( !$this->checktoken($mid,$token) ) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
//        $this->checkparam($postdata);
//        $sql = 'select kno_id, kno_title from ego_com_know where kno_keyword regexp "['.$postdata['keyword'].']" order by kno_id desc';
        $lists = $this->Com_knoModel->searchList($postdata['keyword'], 15);
//        echo json_encode($lists);exit;
        if($lists) {
            exit($this->returnApiSuccess($lists));
        } else {
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '暂时没有相关知识'));
        }
    }

    /**
     * 获取社区首页轮播图
     */
    public function getBanner(){
        //已启用的广告
        $list = M('banner')
            ->where(array('sign_key' => 'community', 'status' => 1))
            ->field('id')
            ->select();
        foreach ($list as $k => $v) {
            $ids[]= $v['id'];
        }
        $ids = implode(',', $ids);
        //已启用的广告所包含的图，限5 张
        $info = M('banner_image')
            ->where(array('banner_id' =>array('in', $ids)))
            ->field('title, link, image, type')
            ->order('sort_order asc')
            ->limit(5)
            ->select();
        foreach($info as $k => $v) {
            if($v['type'] == 3) {
                $data[] = array(
                    'type' => $v['type'],
                    'link' => 'https://www.mixiupet.com/Wap/Banner/artdis?id='.$v['link'],
                    'image' => setUrl($v['image']),
                );
            } else {
                $data[] = array(
                    'type' => $v['type'],
                    'link' => $v['type'],
                    'image' => setUrl($v['image']),
                );
            }

        }
        if($data) {
            exit($this->returnApiSuccess($data));
        } else {
            exit($this->returnApiSuccess(array()));
        }
    }
}