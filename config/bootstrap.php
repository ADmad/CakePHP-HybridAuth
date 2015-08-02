<?php
/**
 * HybridAuth Plugin bootstrap
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */

use Cake\Core\Configure;

if (file_exists(CONFIG . 'hybridauth.php')) {
    Configure::load('hybridauth');
} else {
    $config = [
        'providers' => [
            'OpenID' => [
                'enabled' => true
            ]
        ],
        'debug_mode' => (bool)Configure::read('debug'),
        'debug_file' => LOGS . 'hybridauth.log',
    ];

    Configure::write('HybridAuth', $config);
}
