<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/6
 * Time: 13:52
 */

/**
 * 登录验证
 * @return bool
 */
    function checkLogin() {
        $username = session('username');
        $id = session('id');
        $hid = session('hid');
        $password = session('password');
        $img = session('img');
        if(empty($username) || empty($id) || empty($hid)|| empty($password)) {
            return array();
        } else {
            return array('username' => $username, 'id' => $id, 'hid' => $hid, 'password' => $password, 'img' => $img);
        }
    }