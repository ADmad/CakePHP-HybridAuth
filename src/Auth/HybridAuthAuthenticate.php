<?php
namespace ADmad\HybridAuth\Auth;

use Cake\Auth\FormAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class HybridAuthAuthenticate extends FormAuthenticate
{

    /**
     * HybridAuth instance
     *
     * @var \Hybrid_Auth
     */
    public $hybridAuth = null;

    /**
     * Constructor
     *
     * @param \Cake\Controller\ComponentRegistry $registry The Component registry
     *   used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, $config)
    {
        $this->config([
            'fields' => [
                'provider' => 'provider',
                'provider_uid' => 'provider_uid',
                'openid_identifier' => 'openid_identifier'
            ],
            'hauth_return_to' => null
        ]);

        parent::__construct($registry, $config);
    }

    /**
     * Checks the fields to ensure they are supplied.
     *
     * @param \Cake\Network\Request $request The request that contains login
     *   information.
     * @param array $fields The fields to be checked.
     * @return string|bool Provider name if it exists, false if required fields have
     *   not been supplied.
     */
    protected function _checkFields(Request $request, array $fields)
    {
        $provider = $request->data($fields['provider']);
        if (empty($provider) ||
            ($provider === 'OpenID' && !$request->data($fields['openid_identifier']))
        ) {
            return false;
        }

        return $provider;
    }

    /**
     * Check if a provider already connected return user record if available
     *
     * @param Request $request Request instance.
     * @return array|bool User array on success, false on failure.
     */
    public function getUser(Request $request)
    {
        $this->_init($request);
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
     * @param Request $request Request to get authentication information from.
     * @param Response $response A response object that can have headers added.
     * @return array|bool User array on success, false on failure.
     */
    public function authenticate(Request $request, Response $response)
    {
        $fields = $this->_config['fields'];

        if (!$request->data($fields['provider'])) {
            return $this->getUser($request);
        }

        $provider = $this->_checkFields($request, $fields);
        if (!$provider) {
            return false;
        }

        if ($this->_config['hauth_return_to']) {
            $returnTo = Router::url($this->_config['hauth_return_to'], true);
        } else {
            $returnTo = Router::url(
                [
                    'plugin' => 'ADmad/HybridAuth',
                    'controller' => 'HybridAuth',
                    'action' => 'authenticated'
                ],
                true
            );
        }
        $params = ['hauth_return_to' => $returnTo];
        if ($provider === 'OpenID') {
            $params['openid_identifier'] = $request->data[$fields['openid_identifier']];
        }

        $this->_init($request);
        $adapter = $this->hybridAuth->authenticate($provider, $params);

        if ($adapter) {
            return $this->_getUser($provider, $adapter);
        }
        return false;
    }

    /**
     * Initialize hybrid auth
     *
     * @param \Cake\Network\Request $request Request instance.
     * @return void
     * @throws \RuntimeException Incase case of unknown error.
     */
    protected function _init(Request $request)
    {
        $request->session()->start();
        $hybridConfig = Configure::read('HybridAuth');
        if (empty($hybridConfig['base_url'])) {
            $hybridConfig['base_url'] = Router::url(
                [
                    'plugin' => 'ADmad/HybridAuth',
                    'controller' => 'HybridAuth',
                    'action' => 'endpoint'
                ],
                true
            );
        }

        try {
            $this->hybridAuth = new \Hybrid_Auth($hybridConfig);
        } catch (\Exception $e) {
            if ($e->getCode() < 5) {
                throw new \RuntimeException($e->getMessage());
            } else {
                $this->_registry->Auth->flash($e->getMessage());
                $this->hybridAuth = new \Hybrid_Auth($hybridConfig);
            }
        }
    }

    /**
     * Get user record for hybrid auth adapter and try to get associated user record
     * from your application database. If app user record is not found and
     * `registrationCallback` is set the specified callback function of User model
     * is called.
     *
     * @param string $provider Provider name.
     * @param object $adapter Hybrid auth adapter instance.
     * @return array User record
     */
    protected function _getUser($provider, $adapter)
    {
        try {
            $providerProfile = $adapter->getUserProfile();
        } catch (\Exception $e) {
            $adapter->logout();
            throw $e;
        }

        $userModel = $this->_config['userModel'];
        list(, $model) = pluginSplit($userModel);
        $fields = $this->_config['fields'];

        $conditions = [
            $model . '.' . $fields['provider'] => $provider,
            $model . '.' . $fields['provider_uid'] => $providerProfile->identifier
        ];

        $user = $this->_fetchUserFromDb($conditions);
        if ($user) {
            return $user;
        }

        if (!empty($this->_config['registrationCallback'])) {
            $return = call_user_func_array(
                [
                    TableRegistry::get($userModel),
                    $this->_config['registrationCallback']
                ],
                [$provider, $providerProfile]
            );
            if ($return) {
                $user = $this->_fetchUserFromDb($conditions);
                if ($user) {
                    return $user;
                }
            }
        }

        return (array)$providerProfile;
    }

    /**
     * Fetch user from database matching required conditions
     *
     * @param array $conditions Query conditions.
     * @return array|bool User array on success, false on failure.
     */
    protected function _fetchUserFromDb(array $conditions)
    {
        $scope = $this->_config['scope'];
        if ($scope) {
            $conditions = array_merge($conditions, $scope);
        }

        $table = TableRegistry::get($this->_config['userModel'])->find('all');

        $contain = $this->_config['contain'];
        if ($contain) {
            $table = $table->contain($contain);
        }

        $result = $table
        ->where($conditions)
        ->hydrate(false)
        ->first();

        if ($result) {
            if (isset($this->_config['fields']['password'])) {
                unset($result[$this->_config['fields']['password']]);
            }
            return $result;
        }
        return false;
    }

    /**
     * Logout all providers
     *
     * @param \Cake\Event\Event $event Event.
     * @param array $user The user about to be logged out.
     * @return void
     */
    public function logout(Event $event, array $user)
    {
        $this->_init($this->_registry->getController()->request);
        $this->hybridAuth->logoutAllProviders();
    }

    /**
     * Returns a list of all events that this authenticate class will listen to.
     *
     * @return array List of events this class listens to.
     */
    public function implementedEvents()
    {
        return ['Auth.logout' => 'logout'];
    }
}
