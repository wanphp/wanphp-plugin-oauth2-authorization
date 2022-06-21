<?php

namespace App\Application\Actions\Common;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Mysql\BaseInterface;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\OAuth2Authorization\Application\Api;
use Wanphp\Plugins\OAuth2Authorization\Application\WePublicUserHandler;

class QrLoginApi extends Api
{
  private WeChatBase $weChatBase;
  private BaseInterface $public;
  private BaseInterface $user;

  public function __construct(ContainerInterface $container, WeChatBase $weChatBase)
  {
    $this->public = $container->get('Wanphp\Plugins\Weixin\Domain\UserInterface');;
    $this->user = $container->get('Wanphp\Plugins\Weixin\Domain\PublicInterface');;
    $this->weChatBase = $weChatBase;
  }

  /**
   * @inheritDoc
   */
  protected function action(): Response
  {
    if ($this->isPost()) {
      if (isset($_SESSION['login_user_id']) && is_numeric($_SESSION['login_user_id'])) return $this->respondWithData(['res' => 'OK']);
      else return $this->respondWithError('尚未授权！');
    } else {
      $queryParams = $this->request->getQueryParams();
      if (isset($queryParams['code'])) {//微信公众号认证回调
        $user_id = WePublicUserHandler::getUserId($this->public, $this->user, $this->weChatBase);
        $status = $this->user->get('status', ['id' => $user_id]);
        if ($status) {
          return $this->respondWithError('帐号已被锁定,无法认证，请联系管理员！');
        }
        // 检查绑定管理员
        if ($user_id > 0) {
          $_SESSION['login_user_id'] = $user_id;
          $data = ['title' => '登录成功',
            'msg' => '您已成功授权，详情查看PC端扫码页面！',
            'icon' => 'weui-icon-success'
          ];
          return $this->respondView('admin/error/wxerror.html', $data);
        } else {
          return $this->respondWithError('未知用户！');
        }
      } else {
        return WePublicUserHandler::publicOauthRedirect($this->request, $this->response, $this->weChatBase);
      }
    }
  }
}