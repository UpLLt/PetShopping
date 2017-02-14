<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/7/19
 * Time: 15:45
 */

namespace App\Controller;


use App\Model\AppapiModel;
use Common\Controller\AdminbaseController;


class RequestrecordController extends AdminbaseController
{
    protected $appapi_model;

    public function __construct()
    {
        parent::__construct();
        $this->appapi_model = D('appapi');
    }

    public function lists()
    {

        $this->_list();
        $this->display();
    }

    private function _list()
    {

        $fields = [
            'start_time'  => ["field" => "create_time", "operator" => ">"],
            'end_time'    => ["field" => "create_time", "operator" => "<"],
            'r_type'      => ["field" => "r_type", "operator" => "="],
            'model'       => ["field" => "model", "operator" => "="],
            'request_m_f' => ["field" => "request_m_f", "operator" => "="],
        ];

        $where_ands = [];

        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($get);
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($param == 'start_time' || $param == 'end_time') {
                        $get = strtotime($get);
                    }
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where = join(" and ", $where_ands);

        $count = $this->appapi_model->where($where)->count();
        $page = $this->page($count, C("PAGE_NUMBER"));
        $result = $this->appapi_model
            ->limit($page->firstRow . ',' . $page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();

        $categorys = '';
        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Requestrecord/delete', ['id' => $v['id']]) . '">删除</a>';
            $categorys .= '<tr>
            <td style="white-space:nowrap;">' . ($k + 1) . '</td>
            <td style="white-space:nowrap;">' . ($result[$k]['r_type'] == 'post' ? '<span class="text-error">' . $result[$k]['r_type'] . '</span>' : '<span class="text-info">' . $result[$k]['r_type'] . '</span>') . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['model'] . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['request_m_f'] . '</td>
            <td>' . ($this->arrayToString(json_decode($result[$k]['values'], true))) . '</td>
            <td style="white-space:nowrap;">' . date('Y-m-d H:i:s', $result[$k]['create_time']) . '</td>
            <td style="white-space:nowrap;">' . $result[$k]['str_manage'] . '</td>
        </tr>';
        }

        unset($v);

        $r_type = $this->appapi_model->field('r_type')->group('r_type')->select();
        $request_m_f = $this->appapi_model->field('request_m_f')->group('request_m_f')->select();
        $model = $this->appapi_model->field('model')->group('model')->select();

        $r_type_options = '';
        foreach ($r_type as $k => $v) {
            $selete = $_GET['r_type'] == $v['r_type'] ? 'selected' : '';
            $r_type_options .= '<option ' . $selete . ' value="' . $v['r_type'] . '">' . $v['r_type'] . '</option>';
        }
        unset($v);
        $request_m_f_options = '';
        foreach ($request_m_f as $k => $v) {
            $selete = $_GET['request_m_f'] == $v['request_m_f'] ? 'selected' : '';
            $request_m_f_options .= '<option ' . $selete . ' value="' . $v['request_m_f'] . '">' . $v['request_m_f'] . '</option>';
        }
        unset($v);
        $model_options = '';
        foreach ($model as $k => $v) {
            $selete = $_GET['model'] == $v['model'] ? 'selected' : '';
            $model_options .= '<option ' . $selete . ' value="' . $v['model'] . '">' . $v['model'] . '</option>';
        }
        unset($v);

        $this->assign('r_type', $r_type_options);
        $this->assign('request_m_f', $request_m_f_options);
        $this->assign('model', $model_options);

        $this->assign('formget', I(''));
        $this->assign('categorys', $categorys);
        $this->assign("Page", $page->show());
    }

    private function arrayToString(array $array)
    {
        $str = '{';
        foreach ($array as $k => $v) {
            $str .= "'$k'='$v'";
            if (count($array) > $v) $str .= ',';
        }
        $str .= '}';
        return $str;
    }

    private function _getRequestCount()
    {
        $count = $this->appapi_model
            ->where(['create_time' => ['gt', strtotime(date('Y-m-d', time()))]])
            ->count();
        $this->assign('requestCount', $count);
    }

    public function delete()
    {
        $id = I('id');
        if (empty($id)) $this->error('empty');
        $result = $this->appapi_model->delete($id);
        if ($result) $this->success('success');
        else $this->error('error');
    }


    public function truncate()
    {
        $this->appapi_model->where(['id' => ['neq', '-1']])->delete();
        $this->success('成功');
    }


    public function chartspage()
    {
        $data_group = $this->appapi_model
            ->field('id,request_m_f')
            ->select();

        $data = [];
        foreach ($data_group as $k => $v) {
            if ($data[$v['request_m_f']]) {
                $data[$v['request_m_f']]['count'] += 1;
            } else {
                $v['count'] = 1;
                $data[$v['request_m_f']] = $v;
            }
        }
        unset($v);

        foreach ($data as $k => $v) {
//            || !strstr($v['request_m_f'], ",") || !strstr($v['request_m_f'], ".") || !strstr($v['request_m_f'], '"')
            if (!strstr($v['request_m_f'], "'")) {
                $dataName[] = $v['request_m_f'];
                $dataGroup[] = $v['count'];
            }
        }


        $dataName = json_encode($dataName);
        $this->assign('dataName', $dataName);

        $dataGroup = json_encode($dataGroup);
        $this->assign('dataGroup', $dataGroup);

        $this->display();
    }


    public function equipment()
    {
        $data = $this->appapi_model
            ->field('model as name,count(id) as value')
            ->group('model')
            ->select();

        foreach ($data as $k => $v) {
            $dataName[] = $v['name'];
        }

        $dataList = json_encode($data);
        $this->assign('dataList', $dataList);

        $dataName = json_encode($dataName);
        $this->assign('dataName', $dataName);
        $this->display();
    }

    public function daycharts()
    {
        $data = $this->appapi_model
            ->field('count(id) as value,ctime')
            ->group('ctime')
            ->select();

        $data_ios = $this->appapi_model
            ->field('count(id) as value,ctime')
            ->group('ctime')
            ->where(['model' => 'IOS'])
            ->select();

        $data_android = $this->appapi_model
            ->field('count(id) as value,ctime')
            ->group('ctime')
            ->where(['model' => 'Android'])
            ->select();

        $data_other = $this->appapi_model
            ->field('count(id) as value,ctime')
            ->group('ctime')
            ->where(['model' => 'Other'])
            ->select();

        foreach ($data as $k => $v) {
            $dataName[] = date('m-d', strtotime($v['ctime']));

            $number = 0;
            foreach ($data_ios as $key => $value) {
                if ($value['ctime'] == $v['ctime']) {
                    $number = $value['value'];
                }
            }
            $dataListIos[] = $number;
            unset($value);
            $number = 0;

            foreach ($data_android as $key => $value) {
                if ($value['ctime'] == $v['ctime']) {
                    $number = $value['value'];
                }
            }
            $dataListAndroid[] = $number;
            unset($value);
            $number = 0;

            foreach ($data_other as $key => $value) {
                if ($value['ctime'] == $v['ctime']) {
                    $number = $value['value'];
                }
            }
            $dataListOther[] = $number;

            unset($value);
            $dataList[] = $v['value'];
        }


//        $this->assign('dataList', json_encode($dataList));
        $this->assign('dataListAndroid', json_encode($dataListAndroid));
        $this->assign('dataListIos', json_encode($dataListIos));
        $this->assign('dataListOther', json_encode($dataListOther));

        $this->assign('dataName', json_encode($dataName));
        $this->display();
    }
}