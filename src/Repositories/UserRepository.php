<?php

namespace Wanphp\Plugins\OAuth2Authorization\Repositories;


use Exception;
use Wanphp\Libray\Mysql\BaseInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Wanphp\Plugins\OAuth2Authorization\Entities\UserEntity;

class UserRepository implements UserRepositoryInterface
{
  private BaseInterface $user;

  public function __construct(BaseInterface $user)
  {
    $this->user = $user;
  }

  /**
   * @param string $username
   * @param string $password
   * @param string $grantType
   * @param ClientEntityInterface $clientEntity
   * @return UserEntity|UserEntityInterface|null
   * @throws Exception
   * @throws OAuthServerException
   */
  public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity): UserEntity|UserEntityInterface|null
  {
    // 验证用户时调用此方法
    // 用于验证用户信息是否符合
    // 可以验证是否为用户可使用的授权类型($grantType)与客户端($clientEntity)
    // 验证成功返回 UserEntityInterface 对象
    $account = trim($username);
    $password = md5(trim($password));

    $user = $this->user->get('id,tel,password,salt,status', ['OR' => ['tel' => $account, 'email' => $account]]);
    if (empty($user)) {//用户还没有注册过
      throw new OAuthServerException('帐号不存在,请核实！', 3, 'invalid_request', 400);
    }

    if ($user['password'] !== md5(SHA1($user['salt'] . $password))) {
      throw new OAuthServerException('帐号密码不正确,请核实！', 3, 'invalid_request', 400);
    }

    if ($user['status']) {
      $user = new UserEntity();
      $user->setIdentifier($user['id']);
      return $user;
    } else {
      throw new OAuthServerException('帐号已被锁定,无法认证，请联系管理员！', 3, 'invalid_request', 400);
    }
  }
}
