<?php
session_start();
require_once 'function.php';
require_once 'config.php';
require_once 'smarty/libs/Smarty.class.php';
$smarty = new Smarty;
$mysqli = new mysqli(_DB_HOST, _DB_ID, _DB_PW, _DB_NAME);
if ($mysqli->connect_error) {
    die('無法連上資料庫 (' . $mysqli->connect_errno . ') '
        . $mysqli->connect_error);
}
$mysqli->set_charset("utf8");

if (!empty($_SESSION['user_sn']) and !empty($_COOKIE['token'])) {
    user_detail($_SESSION['user_sn'], $_COOKIE['token']);
}
post_list();
latest_post();

$is_top = $is_admin = $is_user = false;
if(isset($_SESSION['user_sn'])) {
    if(isset($_COOKIE['token'])) {
        if ($_SESSION['token'] == $_COOKIE['token']) {
            $is_user = true;
            if ($_SESSION['user_right'] == "admin") {
                $is_admin = true;
            }
            if ($_SESSION['user_right'] == "top") {
                $is_top = true;
                $is_admin = true;
            }
        }
    }
}
if (isset($_SESSION['user_sn'])) {
    $now_user_sn = $_SESSION['user_sn'];
    $smarty->assign('now_user_sn', $now_user_sn);
}
$smarty->assign('is_top', $is_top);
$smarty->assign('is_admin', $is_admin);
$smarty->assign('is_user', $is_user);

if (isset($_POST)) {
    foreach ($_POST as $var_name => $var_val) {
         $_POST[$var_name] = htmlspecialchars($var_val);
     } 
}


function user_detail($user_sn, $token) {
    global $mysqli, $smarty;
    if (!empty($_COOKIE['token'])) {
        if ($token === $_SESSION['token']) {
            $sql    = "SELECT * FROM `users` WHERE `user_sn`='{$user_sn}'";
            $result = $mysqli->query($sql) or die($mysqli->connect_error);
            $user   = $result->fetch_assoc();
            if(!empty($user)) {
                $user_detail = $user;
                $user_detail['pic'] = get_pic_path("./uploads/users/{$user_sn}/thumb_user_pic.png", "./img/thumb_user_pic.jpg");
                $smarty->assign("user_detail", $user_detail);
                return true;
            }
            return false;
        }
        return false;
    }
    return false;
}

function post_list() {
    global $mysqli, $smarty;
    $sql = "SELECT * FROM `post` ORDER BY `post_sn` DESC";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    $i = 0;
    while ($post = $result->fetch_assoc()) {
        $post_sn = $post['post_sn'];
        $ssql = "SELECT * FROM `users` WHERE `user_sn`='{$post['post_owner']}'";
        $rresult = $mysqli->query($ssql) or die($mysqli->connect_error);
        if ($user = $rresult->fetch_assoc()) {
            $post['post_owner'] = $user['user_name'];
        } else {
            $post['post_owner'] = "不明";
        }
        if (!file_exists("uploads/post/{$post_sn}/{$post_sn}.html")) {
            copy("templates/default.html", "uploads/post/{$post_sn}/{$post_sn}.html");
        }
        $tag_explode = explode(";", $post['post_tag']);
        $all_post[$i] = $post;
        $all_post[$i]["pic"] = get_pic_path("./uploads/post/{$post_sn}/normal_post_pic.png", "./img/normal_get_pic.jpg");
        $all_post[$i]['post_tag'] = $tag_explode;
        $i++;
    }
    $smarty->assign("all_post", $all_post);
}

function latest_post() {
    global $mysqli, $smarty;
    $sql = "SELECT * FROM `post` ORDER BY `post_sn` DESC";
    $result = $mysqli->query($sql) or die($mysqli->connect_error);
    $i = 0;
    while ($post = $result->fetch_assoc()) {
        $latest_post[$i] = $post;
        $i++;
        if ($i == 3) {
            $smarty->assign("latest_post", $latest_post);
            return true;
        }
    }
}