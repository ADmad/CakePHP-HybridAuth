<?php
App::uses('AppController', 'Controller');
App::uses('CakeSession', 'Model/Datasource');

if (!class_exists('Hybrid_Auth')) {
	App::import('Vendor', 'hybridauth/Hybrid/Auth');
	App::import('Vendor', 'hybridauth/Hybrid/Endpoint');
}

/**
 * HybridAuth Controller
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class HybridAuthController extends AppController {

/**
 * Don't inherit any models from AppController
 *
 * @var bool
 */
	public $uses = false;

/**
 * Allow method "endpoint"
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('endpoint');
	}

/**
 * Endpoint method
 *
 * @return void
 */
	public function endpoint() {
		CakeSession::start();
		Hybrid_Endpoint::process();
	}

}
