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
 * Allow methods 'endpoint' and 'authenticated'.
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow(array('endpoint', 'authenticated'));
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

/**
 * This action exists just to ensure AuthComponent fetch user info from
 * hybridauth after successful login
 *
 * Hyridauth's `hauth_return_to` is set to this action.
 *
 * @return \Cake\Network\Response
 */
	public function authenticated() {
		$user = $this->Auth->identify($this->request, $this->response);
		if ($user) {
			$this->Auth->Session->renew();
			$this->Auth->Session->write(AuthComponent::$sessionKey, $user);
			$event = new CakeEvent('Auth.afterIdentify', $this->Auth, array('user' => $user));
			$this->getEventManager()->dispatch($event);
			return $this->redirect($this->Auth->redirectUrl());
		}
		return $this->redirect($this->Auth->loginAction);
	}

}
