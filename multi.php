<?php
/**
 * Created by PhpStorm.
 * User: jianzi0307
 * Date: 2016/11/29
 * Time: 20:59
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

$list = [];
foreach ($categories as $key => $value) {
    $list[] = $baseUrl . '/p/video/list.aspx?category=' . $key;
}

//登录页
$loginUrl = $baseUrl . '/p/account/ajaxData/checkLogin.aspx?un=28831118&pw=abc123';
//来源
$refererUrl = $baseUrl . '/p/account/ajaxData/popLoginDialog.aspx';

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

$curl = QueryList::getInstance('QL\Ext\Lib\CurlMulti');
$curl->maxThread = 100; //100个线程


foreach ($list as $url) {
    $data = QueryList::run('Request', array(
        'http' => array(
            'target' => $url,
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/27.0.1453.93 Chrome/27.0.1453.93 Safari/537.36',
            'cookiePath' => './cookie.tmp'
        )
    ));

    $data = $data->setQuery([
        'imgUrl' => ['.videoImg img', 'src'],
        'title' => ['.videoInfo a', 'text'],
        'detail' => ['.videoInfo a', 'href']
    ]);

    $data->getData(function ($item) use ($curl, $baseUrl) {
        $curl->add(['url' => $baseUrl . '/p/video/' . $item['detail']], function ($a) use (&$item) {
            $data = QueryList::run('Request', array(
                'http' => array(
                    'target' => $a['info']['url'],
                    'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/27.0.1453.93 Chrome/27.0.1453.93 Safari/537.36',
                    'cookiePath' => './cookie.tmp'
                )
            ));
            preg_match_all("/'file': '(\/upload.*[MP4|mp4|flv])/u", $data->html, $matches);
            $mp4 = @$matches[1][0];
            $item['mp4'] = $mp4;
            unset($item['detail']);
            print_r($item);
        });
    });

    $curl->start();
}