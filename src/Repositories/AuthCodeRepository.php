<?php

namespace Wanphp\Plugins\OAuth2Authorization\Repositories;


use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Predis\ClientInterface;
use Wanphp\Plugins\OAuth2Authorization\Entities\AuthCodeEntity;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
  private ClientInterface $redis;

  public function __construct(ClientInterface $redis)
  {
    $this->redis = $redis;
  }

  /**
   * @return AuthCodeEntityInterface
   */
  public function getNewAuthCode(): AuthCodeEntityInterface
  {
    // 创建新授权码时调用方法
    // 需要返回 AuthCodeEntityInterface 对象
    return new AuthCodeEntity();
  }

  public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
  {
    // 创建新授权码时调用此方法
    // 可以用于持久化存储授权码，持久化数据库自行选择
    // 可以使用参数中的 AuthCodeEntityInterface 对象，获得有价值的信息：
    // $authCodeEntity->getIdentifier(); // 获得授权码唯一标识符
    // $authCodeEntity->getExpiryDateTime(); // 获得授权码过期时间
    // $authCodeEntity->getUserIdentifier(); // 获得用户标识符
    // $authCodeEntity->getScopes(); // 获得权限范围
    // $authCodeEntity->getClient()->getIdentifier(); // 获得客户端标识符
    $expires_in = $authCodeEntity->getExpiryDateTime()->getTimestamp() - time();
    $this->redis->setex($authCodeEntity->getIdentifier(), $expires_in, $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s'));
  }

  public function revokeAuthCode($codeId)
  {
    // 当使用授权码获取访问令牌时调用此方法
    // 可以在此时将授权码从持久化数据库中删除
    // 参数为授权码唯一标识符
    $this->redis->del($codeId);
  }

  public function isAuthCodeRevoked($codeId): bool
  {
    // 当使用授权码获取访问令牌时调用此方法
    // 用于验证授权码是否已被删除
    // return true 已删除，false 未删除
    $expiryDateTime = $this->redis->get($codeId);
    return is_null($expiryDateTime);
  }
}
