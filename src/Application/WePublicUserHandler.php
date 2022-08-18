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
        $userinfo = $weChatBase->getUserInfo($weUser['openid']);
        if ($userinfo['subscribe']) {//用户已关注公众号
          $pubData = [
            'subscribe' => $userinfo['subscribe'],
            'tagid_list' => $userinfo['tagid_list'],
            'subscribe_time' => $userinfo['subscribe_time'],
            'subscribe_scene' => $userinfo['subscribe_scene']
          ];
        }
        //检查数据库是否存在用户数据
        $user_id = $public->get('id', ['openid' => $accessToken['openid']]);
        if ($user_id) {// 已存在公众号关注信息
          if ($data['unionid']) $uid = $user->get('id', ['unionid' => $data['unionid']]);
          else $uid = $user->get('id', ['id' => $user_id]);
          if ($uid) {
            //更新用户
            if ($uid != $user_id) $data['id'] = $user_id;
            $user->update($data, ['id' => $uid]);
          } else {
            //添加用户
            $data['id'] = $user_id;
            $user->insert($data);
          }
          if (isset($pubData)) $public->update($pubData, ['id' => $user_id]);
        } else {
          // 不存在公众号关注信息
          //检查用户是否通过小程序等，存储到本地
          if ($data['unionid']) {
            $uid = $user->get('id', ['unionid' => $data['unionid']]);
            if ($uid) {
              $user->update($data, ['id' => $uid]);
              $user_id = $uid;
            } else {
              //添加用户
              $user_id = $user->insert($data);
            }
            //添加公众号数据
            if (isset($pubData)) {
              $pubData['id'] = $user_id;
              $pubData['openid'] = $weUser['openid'];
            } else {
              $pubData = ['id' => $user_id, 'openid' => $weUser['openid']];
            }
            $public->insert($pubData);
          } else {
            //添加公众号数据
            if (isset($pubData)) $pubData['openid'] = $weUser['openid'];
            else $pubData = ['openid' => $weUser['openid']];
            $data['id'] = $public->insert($pubData);
            //添加用户
            $user_id = $user->insert($data);
          }
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