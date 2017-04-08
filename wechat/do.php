<meta charset="utf-8">
<?php
//测试调用wechat类的方法
//引入类文件
require './wechat.class.php';
//实例化
$wechat = new Wechat();
//获取access_token
// $wechat->getAccessToken();
//文件获取access_token
// $wechat->getAccessTokenByFile();
//mem获取access_token
$wechat->getAccessTokenByMem();
echo '';
//获取ticket
// $wechat->getTicket(666);
// 换取二维码图片
// $wechat->getQRCode();
// 创建菜单
//$wechat->createMenu();
// 查看菜单
// $wechat->showMenu();
// 删除菜单
// $wechat->delMenu();

$wechat->getUserlist();

$wechat->getUserInfo();

//获取图片
$wechat->getMediaFile();