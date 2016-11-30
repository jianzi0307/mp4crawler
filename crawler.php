<?php
/**
 * PHP爬虫
 * 用途：采集《科学健身与健康促进专家平台》上的健身视频资源
 * 使用方法： php -f crawler.php
 * @author jian0307@icloud.com
 * @date 2016.11.29 00:02
 */
require 'vendor/autoload.php';
use QL\QueryList;

//根目录
define('SCRIPT_ROOT', dirname(__FILE__) . '/');
//cookie保存文件
$cookieFile = SCRIPT_ROOT . 'cookie.tmp';
//科学健身与健康促进专家平台
$baseUrl = 'http://kxjs.org.cn';
$categories = ['1' => 1, '2' => 2, '3' => 1, '487870722' => 1, '734538812' => 1, '734539422' => 1, '532214197' => 1];

//登录页
$loginUrl = $baseUrl . '/p/account/ajaxData/checkLogin.aspx?un=28831118&pw=abc123';
//来源
$refererUrl = $baseUrl . '/p/account/ajaxData/popLoginDialog.aspx';
$login = QueryList::run('Login', [
    'target' => $loginUrl,
    'referrer' => $refererUrl,
    'method' => 'post',
    //登陆表单需要提交的数据
    'params' => ['un' => '28831118', 'pw' => 'abc123'],
    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
    'cookiePath' => $cookieFile
]);
$jsonCate = [];
foreach ($categories as $key => $value) {
    $jsonCate[$key] = [];
    $ql = $login->get($baseUrl . '/p/video/list.aspx?category=' . $key);
    $data = $ql->setQuery([
        'imgUrl' => ['.videoImg img', 'src'],
        'title' => ['.videoInfo a', 'text'],
        'detail' => ['.videoInfo a', 'href']
    ])->data;
    foreach ($data as &$obj) {
        $detail = $obj['detail'];
        $d = $login->get($baseUrl . '/p/video/' . $detail);
        preg_match_all("/'file': '(\/upload.*[mp4|flv])/u",$d->html, $matches);
        $mp4 = $matches[1][0];
        $obj['mp4'] = $mp4;
        unset($obj['detail']);
    }
    $jsonCate[$key] = $data;
}
file_put_contents('plist.txt', json_encode($jsonCate, JSON_UNESCAPED_UNICODE));

//删除cookie文件
//@unlink($cookieFile);