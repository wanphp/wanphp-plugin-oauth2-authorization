<?php

namespace Wanphp\Plugins\OAuth2Authorization\Repositories;


use Exception;
use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Wanphp\Plugins\OAuth2Authorization\Entities\ClientEntity;
use Wanphp\Plugins\OAuth2Authorization\Entities\ClientsEntity;

class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, 'clients', ClientsEntity::class);
  }

  /**
   * 获取客户端对象时调用方法，用于验证客户端
   * @param string $clientIdentifier
   * @return ClientEntity|ClientEntityInterface|null
   * @throws Exception
   */
  public function getClientEntity($clientIdentifier): ClientEntityInterface|ClientEntity|null
  {
    $client = $this->get('client_id,name,redirect_uri,confidential', ['client_id' => $clientIdentifier]);
    if (isset($_GET['redirect_uri']) && str_starts_with($_GET['redirect_uri'], $client['redirect_uri'])) $client['redirect_uri'] = $_GET['redirect_uri'];
    if ($client) return new ClientEntity($client);
    else return null;
  }

  /**
   * @param string $clientIdentifier 客户端ID
   * @param string|null $clientSecret 客户端密钥
   * @param string|null $grantType 授权类型
   * @return bool
   * @throws Exception
   */
  public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
  {
    $client_secret = $this->get('client_secret', ['client_id' => $clientIdentifier]);
    if (in_array($grantType, ['authorization_code', 'client_credentials', 'password', 'refresh_token'])) {
      return $client_secret == $clientSecret;
    } else {
      if ($client_secret) return true;
      else return false;
    }
  }
}
