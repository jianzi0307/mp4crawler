<?php
/**
 * PHP爬虫（多线程版）
 * 用途：采集《科学健身与健康促进专家平台》上的健身视频资源
 * 使用方法： php -f multiCrawler.php
 * @author jian0307@icloud.com
 * @date 2016.11.30 20:16
 */
require 'vendor/autoload.php';

use QL\QueryList;

set_time_limit(0);

//-------------------- 配置

//根目录
define('SCRIPT_ROOT', dirname(__FILE__) . '/');

//科学健身与健康促进专家平台
$baseUrl = 'http://kxjs.org.cn';

//用户名
$account = '28831118';

//密码
$password = 'abc123';

//视频分类
$categories = array(
    '1' => ['name' => '科学健身', 'newId' => 1],
    '2' => ['name' => '塑身减肥', 'newId' => 2],
    '3' => ['name' => '营养膳食', 'newId' => 3],
    '487870722' => ['name' => '大众健身', 'newId' => 4],
    '734538812' => ['name' => '养生保健', 'newId' => 5],
    '734539422' => ['name' => '运动康复', 'newId' => 6],
    '532214197' => ['name' => '运动技能', 'newId' => 7]
);

//-------------------- step1.模拟登录，登录成功保存cookie到文件，后续采集将使用到

//cookie保存文件
$cookieFile = SCRIPT_ROOT . 'cookie.tmp';
//登录页
$loginUrl = $baseUrl . '/p/account/ajaxData/checkLogin.aspx?un=' . $account . '&pw=' . $password;
//来源
$refererUrl = $baseUrl . '/p/account/ajaxData/popLoginDialog.aspx';

$login = QueryList::run('Login', [
    'target' => $loginUrl,
    'referrer' => $refererUrl,
    'method' => 'post',
    //登陆表单需要提交的数据
    'params' => ['un' => $account, 'pw' => $password],
    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
    'cookiePath' => $cookieFile
]);

//-------------------- step2.启用多线程模块，采集列表页和视频页面，将采集结果保存为json文件

$curl = QueryList::getInstance('QL\Ext\Lib\CurlMulti');
$curl->maxThread = 100; //100个线程

$sql = "INSERT INTO `pkuvr_video` (`v_title`, `v_url`, `v_thumb`, `v_desc`, `v_type`, `status`, `v_view_count`) VALUES ";

file_put_contents('data.txt', $sql . PHP_EOL);

foreach ($categories as $key => $cate) {
    //列表页
    $url = $baseUrl . '/p/video/list.aspx?category=' . $key;

    $data = QueryList::run('Request', array(
        'http' => array(
            'target' => $url,
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/27.0.1453.93 Chrome/27.0.1453.93 Safari/537.36',
            'cookiePath' => './cookie.tmp'
        )
    ))->setQuery([
        'imgUrl' => ['.videoImg img', 'src'],
        'title' => ['.videoInfo a', 'text'],
        'detail' => ['.videoInfo a', 'href']
    ])->getData(function ($item) use ($curl, $baseUrl, $cate, $sql) {
        $curl->add(['url' => $baseUrl . '/p/video/' . $item['detail']], function ($a) use (&$item, $baseUrl, $cate, $sql) {
            $data = QueryList::run('Request', array(
                'http' => array(
                    'target' => $a['info']['url'],
                    'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/27.0.1453.93 Chrome/27.0.1453.93 Safari/537.36',
                    'cookiePath' => './cookie.tmp'
                )
            ));
            preg_match_all("/'file': '(\/upload.*[MP4|mp4|flv])/u", $data->html, $matches);
            $mp4 = @$matches[1][0];
            $orgImgUrl = $baseUrl . $item['imgUrl'];
            $md5file = md5($orgImgUrl);
            $ext = end(explode(".", $orgImgUrl));
            $item['imgUrl'] = '/Uploads/Video/' . $md5file . '.' . $ext;
            $item['mp4'] = $baseUrl . $mp4;
            $item['cateName'] = $cate['name'];
            $item['cateId'] = $cate['newId'];
            unset($item['detail']);


            if (!file_exists('v/imgs/' . $md5file . '.' . $ext)) {
                $thumb = file_get_contents($orgImgUrl);
                if ($thumb && file_put_contents('v/imgs/' . $md5file . '.' . $ext, $thumb)) {
                    echo 'save thumb : ' . $md5file . '  ' . $orgImgUrl . PHP_EOL;
                }
            }

            $sql = " ('" . $item['title'] . "', '" . $item['mp4'] . "', '" . $item['imgUrl'] . "', '', '" . $item['cateId'] . "', 1, " . rand(100, 9000) . "),";
            echo ">>>>>" . $sql . PHP_EOL;
            file_put_contents('data.txt', $sql . PHP_EOL, FILE_APPEND);
        });
    });

}

$curl->start();


