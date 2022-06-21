<?php

namespace Wanphp\Plugins\OAuth2Authorization\Application\Auth;


use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Stream;

class PasswordAccessTokenApi extends OAuth2Api
{
  public function __construct(ContainerInterface $container)
  {
    parent::__construct($container);
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Post(
   *   path="/auth/passwordAccessToken",
   *   tags={"Auth"},
   *   summary="用户账号密码，获取访问令牌",
   *   operationId="passwordAccessToken",
   *   @OA\RequestBody(
   *     description="获取access_token",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         type="object",
   *         @OA\Property(
   *           property="grant_type",
   *           type="string",
   *           example="password",
   *           description="授权模式，值固定为：password"
   *         ),
   *         @OA\Property(
   *           property="client_id",
   *           type="string",
   *           description="客户端ID,由服务端分配"
   *         ),
   *         @OA\Property(
   *           property="client_secret",
   *           type="string",
   *           description="客户端 secret,由服务端分配"
   *         ),
   *         @OA\Property(
   *           property="scope",
   *           type="string",
   *           description="授权范围，可选项，以空格分隔"
   *         ),
   *         @OA\Property(
   *           property="username",
   *           type="string",
   *           description="用户账号"
   *         ),
   *         @OA\Property(
   *           property="password",
   *           type="string",
   *           description="用户密码"
   *         )
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *    response="201",
   *    description="获取AccessToken成功",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(
   *         @OA\Property(
   *           property="datas",
   *           @OA\Property(property="token_type",type="string"),
   *           @OA\Property( property="expires_in",type="integer"),
   *           @OA\Property(property="access_token",type="string"),
   *           @OA\Property(property="refresh_token",type="string")
   *        )
   *       )
   *      }
   *    )
   *   ),
   *   @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    try {
      $this->password();
      // 这里只需要这一行就可以，具体的判断在 Repositories 中
      return $this->server->respondToAccessTokenRequest($this->request, $this->response);
    } catch (OAuthServerException $exception) {
      return $exception->generateHttpResponse($this->response);
    } catch (Exception $exception) {
      $body = new Stream(fopen('php://temp', 'r+'));
      $body->write($exception->getMessage());
      return $this->response->withStatus(400)->withBody($body);
    }
  }
}
