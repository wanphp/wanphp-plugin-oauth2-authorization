<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Server\MiddlewareInterface as Middleware;

return function (App $app, Middleware $PermissionMiddleware, Middleware $OAuthServerMiddleware) {
  // 后台管理
  $app->group('/admin', function (Group $group) {
    // 用户基本信息管理
    $group->map(['GET', 'PUT', 'POST', 'DELETE'], '/clients[/{id:[0-9]+}]', \Wanphp\Plugins\OAuth2Authorization\Application\Manage\ClientsApi::class);
  })->addMiddleware($PermissionMiddleware);

  $app->group('/auth', function (Group $group) {
    $group->get('/authorize', \Wanphp\Plugins\OAuth2Authorization\Application\Auth\AuthorizeApi::class);
    $group->post('/accessToken', \Wanphp\Plugins\OAuth2Authorization\Application\Auth\AccessTokenApi::class);
    $group->post('/passwordAccessToken', \Wanphp\Plugins\OAuth2Authorization\Application\Auth\PasswordAccessTokenApi::class);
    $group->post('/refreshAccessToken', \Wanphp\Plugins\OAuth2Authorization\Application\Auth\RefreshAccessTokenApi::class);
  });
};


