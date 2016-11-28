<?php
/**
 * PHP����
 * ��;���ɼ�����ѧ�����뽡���ٽ�ר��ƽ̨���ϵĽ�����Ƶ��Դ
 * @author jianzi0307@icloud.com
 * @date 2016.11.29 00:02
 */
require 'vendor/autoload.php';
use QL\QueryList;

//��Ŀ¼
define('SCRIPT_ROOT', dirname(__FILE__) . '/');
//cookie�����ļ�
$cookieFile = SCRIPT_ROOT . 'cookie.tmp';
//��ѧ�����뽡���ٽ�ר��ƽ̨
$baseUrl = 'http://kxjs.org.cn';
$categories = ['1' => 1, '2' => 2, '3' => 1, '487870722' => 1, '734538812' => 1, '734539422' => 1, '532214197' => 1];

//��¼ҳ
$loginUrl = $baseUrl . '/p/account/ajaxData/checkLogin.aspx';
//��Դ
$refererUrl = $baseUrl . '/p/account/ajaxData/popLoginDialog.aspx';
$login = QueryList::run('Login', [
    'target' => $loginUrl,
    'referrer' => $refererUrl,
    'method' => 'post',
    //��½����Ҫ�ύ������
    'params' => ['un' => '28772016', 'pw' => 'jianzi0307'],
    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:21.0) Gecko/20100101 Firefox/21.0',
    'cookiePath' => $cookieFile
]);
//$d = $login->get('http://kxjs.org.cn/p/video/detail.aspx?id=202');
//preg_match_all("/'file': '(\/upload.*mp4)/u",$d->html, $matches);
//var_dump($matches);
//die;
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

//$ql = $login->get($baseUrl . '/p/video/list.aspx?category=1');
//$data = $ql->setQuery([
//    'imgUrl' => ['.videoImg img', 'src'],
//    'title' => ['.videoInfo a', 'text'],
//    'detail' => ['.videoInfo a', 'href']
//])->data;
////$ql = $login->get($baseUrl . '/p/video/detail.aspx?id=202');
////$data = $ql->setQuery([])->data;
//print_r($data);

//ɾ��cookie�ļ�
//@ unlink($cookieFile);