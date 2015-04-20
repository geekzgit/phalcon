<?php

/**
 * 路由过滤
 */
class RouterFilter
{
    /**
     * 微信授权验证
     */
    public function wxCheck($uri, $route)
    {
        //var_dump($route);
        //exit;
        $di = Phalcon\DI::getDefault();
        $session = $di->getSession();
        $sign = $session->has('wechat_login');
        $auth = $di->getAuth();
        $response = $di->getResponse();
        if ($auth->guest() || ! $sign) {
            if ($di->getRequest()->isAjax())
            {
                $response->setStatusCode(401, 'Unauthorized');
            }
            else
            {
                $session->set('wechat_login', true);
                $session->set('login_referer', getCurrentUrl());
                $response->redirect('wx/oauth2/login');
                $response->send();
            }
            exit;
        }
        if (! $di->getRequest()->isAjax()) {
            if ($di->getRequest()->getURI() != '/wx/page/gameover') {
                $response->redirect('wx/page/gameover');
                $response->send();
                exit;
            }
        }
        return true;
    }
}
