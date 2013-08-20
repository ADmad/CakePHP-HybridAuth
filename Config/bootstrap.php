<?php
/**
 * HybridAuth Plugin bootstrap
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */

if (file_exists(APP . 'Config' . DS . 'hybridauth.php')) {
	Configure::load('hybridauth');
} else {
	$config = array(
		'providers' => array(
			'OpenID' => array(
				'enabled' => true
			)
		),
		'debug_mode' => (bool)Configure::read('debug'),
		'debug_file' => LOGS . 'hybridauth.log',
	);

	Configure::write('HybridAuth', $config);
}
