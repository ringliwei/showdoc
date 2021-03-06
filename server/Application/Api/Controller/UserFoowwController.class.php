<?php

namespace Api\Controller;

use Think\Controller;

class UserFoowwController extends BaseController
{
    //注册
    public function register()
    {
        $username = trim(I("username"));
        $name = trim(I("name"));
        $password = I("password");
        $confirm_password = I("confirm_password");

        if ($username == '' && !empty($_COOKIE['showdoc_fooww_username'])) {
            $username = $_COOKIE['showdoc_fooww_username'];
        }

        if ($username == '') {
            header("location: https://staservice.fooww.com/Account/Login?returnUrl=" . "https%3A//showdoc.fooww.com/server/index.php%3Fs%3Dapi/userFooww/register", true);
            return;
        }

        header("location: https://showdoc.fooww.com/web/#/item/index", true);

        if ($password == '') {
            $password = $username . "@123456";
            $confirm_password = $password;
        }


        if ($password != '' && $password == $confirm_password) {
            if (!D("User")->checkDbOk()) {
                $this->sendError(100100, "数据库连接不上。请确保安装了php-sqlite扩展以及数据库文件Sqlite/showdoc.db.php可用");
                return;
            }
            if (!D("User")->isExist($username)) {
                $new_uid = D("User")->register($username, $password);
                if ($new_uid) {

                    D("User")->where(" uid = '$new_uid' ")->save(array("name" => $name));

                    //设置自动登录
                    $ret = D("User")->where("uid = '$new_uid' ")->find();
                    unset($ret['password']);
                    session("login_user", $ret);
                    $token = D("UserToken")->createToken($ret['uid']);
                    cookie('cookie_token', $token, array('expire' => 60 * 60 * 24 * 90, 'httponly' => 'httponly')); //此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
                    $this->setcookie_fooww_username($username);
                    $this->sendResult(array());
                } else {
                    $this->sendError(10101, 'register fail');
                }
            } else {
                $this->login();
            }
        } else {
            $this->sendError(10101, L('code_much_the_same'));
        }
    }
    
    public function setcookie_fooww_username($username)
    {
        cookie('showdoc_fooww_username', $username, array('expire' => time() + 60 * 60 * 24 * 90, 'httponly' => 'httponly'));
    }

    // 登录
    public function login()
    {
        header("location: https://showdoc.fooww.com/web/#/item/index", true);

        $username = I("username");
        $password = I("password");

        if ($username == '' && !empty($_COOKIE['showdoc_fooww_username'])) {
            $username = $_COOKIE['showdoc_fooww_username'];
        }

        if ($username == '') {
            header("location: https://staservice.fooww.com/Account/Login?returnUrl=" . "https%3A//showdoc.fooww.com/server/index.php%3Fs%3Dapi/userFooww/register", true);
            return;
        }

        /*
        if (!$password) {
            $this->sendError(10206, "no empty password");
            return;
        }
        */

        if (!D("User")->checkDbOk()) {
            $this->sendError(100100, "数据库连接不上。请确保安装了php-sqlite扩展以及数据库文件Sqlite/showdoc.db.php可用");
            return;
        }

        $ret = D("User")->isExist($username);

        if ($ret) {
            unset($ret['password']);
            session("login_user", $ret);
            D("User")->setLastTime($ret['uid']);
            $token = D("UserToken")->createToken($ret['uid'], 60 * 60 * 24 * 180);
            cookie('cookie_token', $token, array('expire' => 60 * 60 * 24 * 180, 'httponly' => 'httponly')); //此处由服务端控制token是否过期，所以cookies过期时间设置多久都无所谓
            $this->setcookie_fooww_username($username);
            $this->sendResult(array());
        } else {
            $error_code = 123123123;
            $this->sendError($error_code, L('username_or_password_incorrect'));
            return;
        }
    }
}
