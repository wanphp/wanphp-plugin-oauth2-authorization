<?php

namespace Wanphp\Plugins\OAuth2Authorization\Application\Auth;


use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Defuse\Crypto\Crypto;
use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use Wanphp\Libray\Mysql\BaseInterface;
use Wanphp\Libray\Weixin\WeChatBase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Stream;
use Wanphp\Plugins\OAuth2Authorization\Application\WePublicUserHandler;
use Wanphp\Plugins\OAuth2Authorization\Entities\UserEntity;

class AuthorizeApi extends OAuth2Api
{
  private WeChatBase $weChatBase;
  private BaseInterface $publicUser;//用户关注公众号信息

  public function __construct(WeChatBase $weChatBase, ContainerInterface $container)
  {
    parent::__construct($container);
    $this->weChatBase = $weChatBase;
    $this->publicUser = $container->get('Wanphp\Plugins\Weixin\Domain\PublicInterface');
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Get(
   *   path="/auth/authorize",
   *   tags={"Auth"},
   *   summary="公众号用户登录，获取授权码或访问令牌",
   *   operationId="userAuthorize",
   *   @OA\Parameter(
   *    name="response_type",
   *    in="query",
   *    required=true,
   *    description="授权类型，必选项，值固定为：code或token",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="client_id",
   *    in="query",
   *    required=true,
   *    description="客户端ID,由服务端分配",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="redirect_uri",
   *    in="query",
   *    description="重定向URI，可选项，不填写时默认预先注册的重定向URI， 请使用 urlEncode 对链接进行处理",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="scope",
   *    in="query",
   *    description="授权范围，可选项，以空格分隔",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="state",
   *    in="query",
   *    description="CSRF令牌，可选项，但强烈建议使用，应将该值存储与用户会话中，以便在返回时验证.",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Response(response="200",description="获取Code成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *   @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    $queryParams = $this->request->getQueryParams();
    $response_type = $queryParams['response_type'] ?? $queryParams['state'];
    if ($response_type == 'code') $this->authorization_code();
    if ($response_type == 'token') $this->implicit();
    try {
      //使用微信公众号授权登录
      if (!in_array($queryParams['state'], ['code', 'token']) && str_contains($this->request->getServerParams()['HTTP_USER_AGENT'], 'MicroMessenger')) {
        // 验证 HTTP 请求，并返回 authRequest 对象
        $authRequest = $this->server->validateAuthorizationRequest($this->request);
        // 此时应将 authRequest 对象序列化后存在当前会话(session)中
        $_SESSION['authRequest'] = serialize($authRequest);

        // 跳转到微信，获取OPENID
        return WePublicUserHandler::publicOauthRedirect($this->request, $this->response, $this->weChatBase);
      }
      if (isset($queryParams['code'])) {//微信公众号认证回调
        $user_id = WePublicUserHandler::getUserId($this->publicUser, $this->user, $this->weChatBase);
      } else {
        //用户自定义登录方式
        switch ($this->request->getMethod()) {
          case  'POST':
            if (isset($_SESSION['login_user_id']) && is_numeric($_SESSION['login_user_id'])) {
              $user_id = $_SESSION['login_user_id'];
              unset($_SESSION['login_user_id']);
            } else {
              $data = $this->getFormData();
              $password = md5(trim($data['password']));

              $user = $this->user->get('id,tel,password,salt,status', ['OR' => ['tel' => $data['tel'], 'email' => $data['email']]]);
              if ($user) {
                if ($user['password'] !== md5(SHA1($user['salt'] . $password))) {
                  return $this->respondWithError('帐号密码不正确,请核实！');
                }
                if ($user['status'] == 0) {
                  $_SESSION['login_user_id'] = $user['id'];
                  return $this->respondWithData(['res' => 'OK']);
                } else {
                  return $this->respondWithError('帐号已被锁定,无法认证，请联系管理员！');
                }
              } else {
                return $this->respondWithError('帐号不存在,请核实！');
              }
            }
            break;
          case 'GET';
            // 验证 HTTP 请求，并返回 authRequest 对象
            $authRequest = $this->server->validateAuthorizationRequest($this->request);
            // 此时应将 authRequest 对象序列化后存在当前会话(session)中
            $_SESSION['authRequest'] = serialize($authRequest);

            $code = Crypto::encrypt(session_id(), $this->encryptionKey);
            $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
            $writer = new Writer($renderer);
            $data['loginQr'] = $writer->writeString($this->request->getUri()->getScheme() . '://' . $this->request->getUri()->getHost() . '/qrlogin?tk=' . $code);
            return $this->respondView('@oauth2-authorization/login.html');
        }
      }

      // 在会话(session)中取出 authRequest 对象
      $authRequest = unserialize($_SESSION['authRequest']);
      unset($_SESSION['authRequest']);
      // 设置用户实体(userEntity)
      if (isset($user_id) && $user_id > 0) {
        $userEntity = new UserEntity();
        $userEntity->setIdentifier($user_id);
        $authRequest->setUser($userEntity);

        // 设置权限范围
        //$authRequest->setScopes(['basic']);
        // true = 批准，false = 拒绝
        $authRequest->setAuthorizationApproved(true);
      } else {
        $authRequest->setAuthorizationApproved(false);
      }

      // 完成后重定向至客户端请求重定向地址
      return $this->server->completeAuthorizationRequest($authRequest, $this->response);
    } catch (OAuthServerException $exception) {
      return $exception->generateHttpResponse($this->response);
    } catch (Exception $exception) {
      $body = new Stream(fopen('php://temp', 'r+'));
      $body->write($exception->getMessage());
      return $this->response->withStatus(400)->withBody($body);
    }
  }
}
