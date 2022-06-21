<?php

namespace Wanphp\Plugins\OAuth2Authorization\Entities;


use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
  use EntityTrait, ClientTrait;

  public function __construct(array $data)
  {
    $this->identifier = $data['client_id'];
    $this->name = $data['name'];
    $this->redirectUri = $data['redirect_uri'];
    $this->isConfidential = $data['confidential'];
  }
}
