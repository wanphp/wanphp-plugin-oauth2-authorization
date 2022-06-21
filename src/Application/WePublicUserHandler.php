<?php

namespace Wanphp\Plugins\OAuth2Authorization\Application;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Wanphp\Libray\Mysql\BaseInterface;
use Wanphp\Libray\Weixin\WeChatBase;

class WePublicUserHandler
{
  /**
   * 取用户ID
   * @param BaseInterface $public
   * @param BaseInterface $user
   * @param WeChatBase $weChatBase
   * @return int
   * @throws Exception
   */
  public static function getUserId(BaseInterface $public, BaseInterface $user, WeChatBase $weChatBase): int
  {
    $accessToken = $weChatBase->getOauthAccessToken();
    if ($accessToken) {
      //需要用户授权
      $weUser = $weChatBase->getOauthUserinfo($accessToken['access_token'], $accessToken['openid']);
      if (isset($weUser['openid'])) {
        //用户基本数据
        $data = [
          'unionid' => $weUser['unionid'] ?? null,
          'nickname' => $weUser['nickname'],
          'headimgurl' => $weUser['headimgurl'],
          'sex' => $weUser['sex']
        ];
        //检查数据库是否存在用户数据
        $user_id = $public->get('id', ['openid' => $accessToken['openid']]);
        if ($user_id) {
          if ($data['unionid']) $uid = $user->get('id', ['unionid' => $data['unionid']]);
          else $uid = $user->get('id', ['id' => $user_id]);
          if ($uid) {
            //更新用户
            $user->update($data, ['id' => $uid]);
          } else {
            //添加用户
            $data['id'] = $user_id;
            $user->insert($data);
          }
        } else {
          //添加公众号数据
          $data['id'] = $public->insert(['openid' => $weUser['openid']]);
          //添加用户
          $user_id = $user->insert($data);
        }
      }
    }
    return $user_id ?? 0;
  }

  /**
   * 公众号授权获取用户信息
   * @param Request $request
   * @param Response $response
   * @param WeChatBase $weChatBase
   * @return Response
   */
  public static function publicOauthRedirect(Request $request, Response $response, WeChatBase $weChatBase): Response
  {
    $redirectUri = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . $request->getUri()->getPath();
    $queryParams = $request->getQueryParams();
    $response_type = $queryParams['response_type'] ?? $queryParams['state'] ?? '';
    $url = $weChatBase->getOauthRedirect($redirectUri, $response_type);
    return $response->withHeader('Location', $url)->withStatus(301);
  }
}