<?php
App::uses('AppController', 'Controller');
App::uses('CakeSession', 'Model/Datasource');
App::import('Vendor', 'HybridAuth.hybridauth/Hybrid/Auth');
App::import('Vendor', 'HybridAuth.hybridauth/Hybrid/Endpoint');

/**
 * HybridAuth Controller
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class HybridAuthController extends AppController {

	public $uses = false;

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('endpoint');
	}

	public function endpoint() {
		CakeSession::start();
		Hybrid_Endpoint::process();
	}

}
