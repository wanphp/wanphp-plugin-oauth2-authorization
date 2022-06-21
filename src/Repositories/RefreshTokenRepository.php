<?php

namespace Wanphp\Plugins\OAuth2Authorization\Repositories;


use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Predis\ClientInterface;
use Wanphp\Plugins\OAuth2Authorization\Entities\RefreshTokenEntity;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
  private ClientInterface $redis;

  public function __construct(ClientInterface $redis)
  {
    $this->redis = $redis;
  }

  /**
   * @return RefreshTokenEntityInterface
   */
  public function getNewRefreshToken(): RefreshTokenEntityInterface
  {
    // 创建新授权码时调用方法
    // 需要返回 RefreshTokenEntityInterface 对象
    return new RefreshTokenEntity();
  }

  public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
  {
    // 创建新刷新令牌时调用此方法
    // 用于持久化存储授刷新令牌
    // 可以使用参数中的 RefreshTokenEntityInterface 对象，获得有价值的信息：
    // $refreshTokenEntity->getIdentifier(); // 获得刷新令牌唯一标识符
    // $refreshTokenEntity->getExpiryDateTime(); // 获得刷新令牌过期时间
    // $refreshTokenEntity->getAccessToken()->getIdentifier(); // 获得访问令牌标识符
    $expires_in = $refreshTokenEntity->getExpiryDateTime()->getTimestamp() - time();
    $this->redis->setex($refreshTokenEntity->getIdentifier(), $expires_in, $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'));
  }

  public function revokeRefreshToken($tokenId)
  {
    // 当使用刷新令牌获取访问令牌时调用此方法
    // 原刷新令牌将删除，创建新的刷新令牌
    // 参数为原刷新令牌唯一标识
    // 可在此删除原刷新令牌
    $this->redis->del($tokenId);
  }

  public function isRefreshTokenRevoked($tokenId): bool
  {
    // 当使用刷新令牌获取访问令牌时调用此方法
    // 用于验证刷新令牌是否已被删除
    // return true 已删除，false 未删除
    $expiryDateTime = $this->redis->get($tokenId);
    return is_null($expiryDateTime);
  }
}
