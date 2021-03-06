<?php

namespace Wanphp\Plugins\OAuth2Authorization\Entities;


use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

class RefreshTokenEntity implements RefreshTokenEntityInterface
{
  use RefreshTokenTrait, EntityTrait;
}
