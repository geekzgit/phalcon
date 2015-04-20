<?php
// Create the router
$router = new \Phalcon\Mvc\Router(false);

$router->notFound([
    'controller' => 'wx_login',
    'action' => 'notFound'
]);

// PC
$router->addGet('/admin/data/overall', 'Admin::overallData');
$router->addGet('/admin/repair', 'Admin::repair');
$router->addGet('/admin/index', 'Admin::index');

// 微信
$router->addGet('/wx/oauth2/login', 'WxLogin::login');
$router->addGet('/wx/oauth2/callback', 'WxLogin::callback');
$router->addGet('/wx/oauth2/public/login', 'WxLogin::publicLogin');
$router->addGet('/wx/oauth2/logout', 'WxLogin::logout');
$router->addGet('/wx/oauth2/public/oauth2', 'WxLogin::publicOauth2Action');

// 微信授权 {
    $wxgroup = new \Phalcon\Mvc\Router\Group();
    $wxgroup->beforeMatch([new RouterFilter(), 'wxCheck']);
    $wxgroup->addGet('/', 'WxPage::index');
    $wxgroup->addGet('/wx/page/index', 'WxPage::index');
    $wxgroup->addGet('/wx/page/rule', 'WxPage::rule');
    $wxgroup->addGet('/wx/page/shake', 'WxPage::shake');
    $wxgroup->addGet('/wx/page/home', 'WxPage::home');
    $wxgroup->addGet('/wx/page/click', 'WxPage::click');
    $wxgroup->addGet('/wx/page/click/result', 'WxPage::clickResult');
    $wxgroup->addGet('/wx/page/success', 'WxPage::success');
    $wxgroup->addGet('/wx/page/success/click', 'WxPage::successClick');
    $wxgroup->addGet('/wx/page/remind', 'WxPage::remind');
    $wxgroup->addGet('/wx/page/gameover', 'WxPage::gameover');

    $wxgroup->addGet('/wx/init/gift', 'WxPage::initGift');

//微信Api
    $wxgroup->addGet('/wx/api/jssdk/signpackage', 'WxApi::getJSSDKSignPackage');
    $wxgroup->addPost('/wx/api/share/record', 'WxApi::postShareRecord');
    $wxgroup->addGet('/wx/api/click/record', 'WxApi::getFriendClickRecord');
    $wxgroup->addPost('/wx/api/click', 'WxApi::postClickRecord');
    $wxgroup->addPost('/wx/api/redpack', 'WxApi::postRedPack');
// }

$router->mount($wxgroup);

//$router->handle();
