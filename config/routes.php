<?php
/**
 * HybridAuth Plugin routes
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */

namespace ADmad\HybridAuth\Config;

use Cake\Routing\Router;

Router::plugin('ADmad/HybridAuth', ['path' => '/hybrid-auth'], function ($routes) {
    $routes->connect(
        '/endpoint',
        ['controller' => 'HybridAuth', 'action' => 'endpoint']
    );
    $routes->connect(
        '/authenticated',
        ['controller' => 'HybridAuth', 'action' => 'authenticated']
    );
});
