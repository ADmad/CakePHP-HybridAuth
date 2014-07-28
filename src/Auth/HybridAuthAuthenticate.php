<?php
App::uses('CakeSession', 'Model/Datasource');
App::uses('FormAuthenticate', 'Controller/Component/Auth');

if (!class_exists('Hybrid_Auth')) {
	App::import('Vendor', 'HybridAuth.hybridauth/Hybrid/Auth');
}

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class HybridAuthAuthenticate extends FormAuthenticate {

/**
 * HybridAuth instance
 *
 * @var HybridAuth
 */
	public $hybridAuth = null;

/**
 * Constructor
 *
 * @param ComponentCollection $collection The Component collection used on this request.
 * @param array $settings Array of settings to use.
 */
	public function __construct(ComponentCollection $collection, $settings) {
		$this->settings = array_merge(
			$this->settings,
			array(
				'fields' => array(
					'provider' => 'provider',
					'provider_uid' => 'provider_uid',
					'openid_identifier' => 'openid_identifier'
				)
			)
		);

		parent::__construct($collection, $settings);
	}

/**
 * Checks the fields to ensure they are supplied.
 *
 * @param CakeRequest $request The request that contains login information.
 * @param string $model The model used for login verification.
 * @param array $fields The fields to be checked.
 * @return boolean|string False if the fields have not been supplied. Provider name if they exist.
 */
	protected function _checkFields(CakeRequest $request, $model, $fields) {
		if (empty($request->data[$model][$fields['provider']])) {
			return false;
		}

		$provider = $request->data[$model][$fields['provider']];
		if ($provider === 'OpenID' && empty($request->data[$model][$fields['openid_identifier']])) {
			return false;
		}

		return $provider;
	}

/**
 * Check if a provider already connected return user record if available
 *
 * @param CakeRequest $request CakeRequest instance
 * @return boolean|array User array or false
 */
	public function getUser(CakeRequest $request) {
		$this->_init();
		$idps = $this->hybridAuth->getConnectedProviders();
		foreach ($idps as $provider) {
			$adapter = $this->hybridAuth->getAdapter($provider);
			return $this->_getUser($provider, $adapter);
		}
		return false;
	}

/**
 * Authenticate a user based on the request information.
 *
 * @param CakeRequest $request Request to get authentication information from.
 * @param CakeResponse $response A response object that can have headers added.
 * @return mixed Either false on failure, or an array of user data on success.
 * @throws CakeException
 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$userModel = $this->settings['userModel'];
		list(, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		if (empty($request->data[$model])) {
			return $this->getUser($request);
		}

		$provider = $this->_checkFields($request, $model, $fields);
		if (!$provider) {
			return false;
		}

		$params = array();
		if ($provider === 'OpenID') {
			$params = array(
				'openid_identifier' => $request->data[$model][$fields['openid_identifier']]
			);
		}

		$this->_init();
		$adapter = $this->hybridAuth->authenticate($provider, $params);

		if ($adapter) {
			return $this->_getUser($provider, $adapter);
		}
		return false;
	}

/**
 * Initialize hybrid auth
 *
 * @return void
 * @throws CakeException
 */
	protected function _init() {
		CakeSession::start();
		$hybridConfig = Configure::read('HybridAuth');
		if (empty($hybridConfig['base_url'])) {
			$hybridConfig['base_url'] = Router::url(
				array(
					'plugin' => 'hybrid_auth',
					'controller' => 'hybrid_auth',
					'action' => 'endpoint'
				),
				true
			);
		}

		try {
			$this->hybridAuth = new Hybrid_Auth($hybridConfig);
		} catch (Exception $e) {
			if ($e->getCode() < 5) {
				throw new CakeException($e->getMessage());
			} else {
				$this->_Collection->Auth->flash($e->getMessage());
				$this->hybridAuth = new Hybrid_Auth($hybridConfig);
			}
		}
	}

/**
 * Get user record for hybrid auth adapter and try to get associated user record
 * from your application database. If app user record is not found and
 * `registrationCallback` is set the specified callback function of User model
 * is called.
 *
 * @param string $provider Provider name
 * @param object $adapter Hybrid auth adapter instance
 * @return array User record
 */
	protected function _getUser($provider, $adapter) {
		$fields = $this->settings['fields'];
		$userModel = $this->settings['userModel'];
		$providerProfile = $adapter->getUserProfile();
		$conditions = array(
			$userModel . '.' . $fields['provider'] => $provider,
			$userModel . '.' . $fields['provider_uid'] => $providerProfile->identifier
		);
		$user = $this->_findUser($conditions);
		if ($user) {
			return $user;
		}

		if (!empty($this->settings['registrationCallback'])) {
			$return = call_user_func_array(
				array(
					ClassRegistry::init($this->settings['userModel']),
					$this->settings['registrationCallback']
				),
				array($provider, $providerProfile)
			);
			if ($return) {
				$user = $this->_findUser($conditions);
				if ($user) {
					return $user;
				}
			}
		}

		return (array)$providerProfile;
	}

/**
 * Logout all providers
 *
 * @param array $user The user about to be logged out.
 * @return void
 */
	public function logout($user) {
		$this->_init();
		$this->hybridAuth->logoutAllProviders();
	}

}
