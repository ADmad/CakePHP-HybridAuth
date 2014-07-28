<?php
/**
 * HybridAuth Plugin example config
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */

$config['HybridAuth'] = array(
	'providers' => array(
		'OpenID' => array(
			'enabled' => true
		),
	),
	'debug_mode' => (boolean)Configure::read('debug'),
	'debug_file' => LOGS . 'hybridauth.log',
);
