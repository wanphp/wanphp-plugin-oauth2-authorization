<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Wanphp\Plugins\OAuth2Authorization\Repositories\ClientRepository;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    ClientRepository::class => \DI\autowire(ClientRepository::class)
  ]);
};
