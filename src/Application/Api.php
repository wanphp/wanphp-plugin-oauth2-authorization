<?php

namespace Wanphp\Plugins\OAuth2Authorization\Application;

/**
 * @OA\Info(
 *     description="OAuth 2.0 授权服务器扩展插件，插件不能单独运行",
 *     version="1.0.0",
 *     title="OAuth 2.0 授权服务器扩展插件"
 * )
 * @OA\Tag(
 *     name="Auth",
 *     description="认证授权,获取访问令牌"
 * )
 */

/**
 * @OA\Schema(
 *   title="出错提示",
 *   schema="Error",
 *   type="object"
 * )
 * @OA\Schema(
 *   title="成功提示",
 *   schema="Success",
 *   type="object"
 * )
 */

use Wanphp\Libray\Slim\Action;

abstract class Api extends Action
{

}
