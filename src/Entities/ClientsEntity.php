<?php

namespace Wanphp\Plugins\OAuth2Authorization\Entities;


use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class ClientsEntity
 * @package App\Entities\Common
 * @OA\Schema(
 *   title="客户端",
 *   description="客户端数据结构",
 *   required={"name","client_id","client_secret"}
 * )
 */
class ClientsEntity implements \JsonSerializable
{
  use EntityTrait;

  /**
   * @DBType({"key":"PRI","type":"smallint(6) NOT NULL AUTO_INCREMENT"})
   * @OA\Property(format="int32", description="ID")
   * @var integer|null
   */
  private ?int $id;
  /**
   * @DBType({"type":"varchar(20) NOT NULL DEFAULT ''"})
   * @OA\Property(description="客户端名称")
   * @var string
   */
  private string $name;
  /**
   * @DBType({"key":"UNI","type":"varchar(20) NOT NULL DEFAULT ''"})
   * @OA\Property(description="客户端ID")
   * @var string
   */
  private string $client_id;
  /**
   * @DBType({"type":"char(32) NOT NULL DEFAULT ''"})
   * @OA\Property(description="客户端密钥")
   * @var string
   */
  private string $client_secret;
  /**
   * @DBType({"type":"varchar(100) NOT NULL DEFAULT ''"})
   * @OA\Property(description="客户端回调URL")
   * @var string
   */
  private string $redirect_uri;
  /**
   * @DBType({"type":"varchar(200) NOT NULL DEFAULT '[]'"})
   * @OA\Property(@OA\Items(),description="客户端授权IP")
   * @var array
   */
  private array $client_ip;
  /**
   * @DBType({"type":"varchar(500) NOT NULL DEFAULT '[]'"})
   * @OA\Property(@OA\Items(),description="授权范围")
   * @var array
   */
  private array $scopes;
  /**
   * @DBType({"type":"tinyint(1) NOT NULL DEFAULT '1'"})
   * @OA\Property(description="是否机密")
   * @var integer
   */
  private int $confidential;
}
