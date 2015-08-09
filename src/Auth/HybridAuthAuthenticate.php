<?php
namespace ADmad\HybridAuth\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManagerTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class HybridAuthAuthenticate extends BaseAuthenticate
{

    use EventManagerTrait;

    /**
     * HybridAuth instance.
     *
     * @var \Hybrid_Auth
     */
    protected $_hybridAuth;

    /**
     * HybridAuth adapter.
     *
     * @var \Hybrid_Provider_Model
     */
    protected $_adapter;

    /**
     * HybridAuth user profile.
     *
     * @var \Hybrid_User_Profile
     */
    protected $_providerProfile;

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
                'openid_identifier' => 'openid_identifier',
                'email' => 'email'
            ],
            'profileModel' => 'ADmad/HybridAuth.SocialProfiles',
            'hauth_return_to' => null
        ]);

        parent::__construct($registry, $config);
    }

    /**
     * Get HybridAuth instance
     *
     * @param \Cake\Network\Request $request Request instance.
     * @return void
     * @throws \RuntimeException Incase case of unknown error.
     */
    public function hybridAuth(Request $request)
    {
        if ($this->_hybridAuth) {
            return $this->_hybridAuth;
        }

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
            $this->_hybridAuth = new \Hybrid_Auth($hybridConfig);
        } catch (\Exception $e) {
            if ($e->getCode() < 5) {
                throw new \RuntimeException($e->getMessage());
            } else {
                $this->_registry->Auth->flash($e->getMessage());
                $this->_hybridAuth = new \Hybrid_Auth($hybridConfig);
            }
        }

        return $this->_hybridAuth;
    }

    /**
     * Get / set hybridauth adapter instance.
     *
     * @param \Hybrid_Provider_Model $adapter
     * @return \Hybrid_Provider_Model|void
     */
    public function adapter($adapter = null)
    {
        if ($adapter === null) {
            return $this->_adapter;
        }

        $this->_adapter = $adapter;
    }

    /**
     * Get / set hybridauth user profile instance.
     *
     * @param \Hybrid_User_Profile $adapter
     * @return \Hybrid_User_Profile|void
     */
    public function profile($profile = null)
    {
        if ($profile === null) {
            return $this->_providerProfile;
        }

        $this->_providerProfile = $profile;
    }

    /**
     * Check if a provider is already connected, return user record if available.
     *
     * @param \Cake\Network\Request $request Request instance.
     * @return array|bool User array on success, false on failure.
     */
    public function getUser(Request $request)
    {
        $hybridAuth = $this->hybridAuth($request);
        $idps = $hybridAuth->getConnectedProviders();
        foreach ($idps as $provider) {
            $adapter = $hybridAuth->getAdapter($provider);
            return $this->_getUser($adapter);
        }
        return false;
    }

    /**
     * Authenticate a user based on the request information.
     *
     * @param \Cake\Network\Request $request Request to get authentication information from.
     * @param \Cake\Network\Response $response A response object that can have headers added.
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

        $hybridAuth = $this->hybridAuth($request);
        $adapter = $hybridAuth->authenticate($provider, $params);

        if ($adapter) {
            return $this->_getUser($provider, $adapter);
        }
        return false;
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
     * Get user record for hybrid auth adapter and try to get associated user record
     * from your application database. If app user record is not found and
     * `registrationCallback` is set the specified callback function of User model
     * is called.
     *
     * @param \Hybrid_Provider_Model $adapter Hybrid auth adapter instance.
     * @return array User record
     */
    protected function _getUser($adapter)
    {
        try {
            $providerProfile = $adapter->getUserProfile();
            $this->adapter($adapter);
            $this->profile($providerProfile);
        } catch (\Exception $e) {
            $adapter->logout();
            throw $e;
        }

        $config = $this->_config;

        $user = null;
        $profile = $this->_query($providerProfile->identifier)->first();

        if ($profile && !empty($profile->user)) {
            $user = $profile->user;
            $profile->unsetProperty('user');
        } elseif ($providerProfile->email) {
            $user = TableRegistry::get($this->_config['userModel'])
                ->find($config['finder'])
                ->where([$config['fields']['email'] => $providerProfile->email])
                ->first();
        }

        if (!$user) {
            $user = $this->_newUser($adapter, $providerProfile);
        }

        $profile = $this->_profileEntity($profile, $user);
        $result = TableRegistry::get($this->_config['profileModel'])->save($profile);
        if (!$result) {
            throw new \RuntimeException('Unable to save social profile');
        }

        $user->set('social_profile', $profile);
        $user->unsetProperty($this->_config['fields']['password']);
        return $user->toArray();
    }

    /**
     * Get query to fetch social profile record.
     *
     * @param string $identifier Provider's identifier.
     * @return \Cake\ORM\Query
     */
    protected function _query($identifier)
    {
        $config = $this->_config;
        list(, $userAlias) = pluginSplit($config['userModel']);
        $provider = $this->adapter()->id;

        $table = TableRegistry::get($config['profileModel']);
        $query = $table->find('all');

        $query
            ->where([
                $table->aliasField('provider') => $provider,
                $table->aliasField('identifier') => $identifier
            ])
            ->contain([$userAlias]);

        return $query;
    }

    /**
     * Get new user record
     *
     * @param \Hybrid_Provider_Model $adapter Hybrid auth adapter instance.
     * @param \Hybrid_User_Profile $providerProfile
     * @return \Cake\ORM\Entity
     */
    protected function _newUser($adapter, $providerProfile)
    {
        $event = $this->dispatchEvent(
            'HybridAuth.newUser',
            [
                'provider' => $adapter->id,
                'profile' => $providerProfile
            ]
        );

        if (!empty($event->result)) {
            return $event->result;
        }

        $config = $this->_config;
        $UsersTable = TableRegistry::get($config['userModel']);

        $user = $UsersTable->newEntity();
        $user->set($config['fields']['email'], $providerProfile->email);
        $user->set($config['fields']['username'], $providerProfile->email);

        $user = $UsersTable->save($user);
        if (!$user) {
            throw new \RuntimeException('Unable to create new user');
        }

        return $user;
    }

    /**
     * Get social profile entity
     *
     * @param \Cake\ORM\Entity $profile
     * @param \Cake\ORM\Entity $user
     * @return \Cake\ORM\Entity
     */
    protected function _profileEntity($profile, $user)
    {
        $ProfileTable = TableRegistry::get($this->_config['profileModel']);

        if (!$profile) {
            $profile = $ProfileTable->newEntity([
                'provider' => $this->adapter()->id,
                'user_id' => $user->id
            ]);
        }

        foreach (get_object_vars($this->profile()) as $key => $value) {
            switch ($key) {
                case 'webSiteURL':
                    $profile->set('website_url', $value);
                    break;

                case 'profileURL':
                    $profile->set('profile_url', $value);
                    break;

                case 'photoURL':
                    $profile->set('photo_url', $value);
                    break;

                default:
                    $profile->set(Inflector::underscore($key), $value);
                    break;
            }
        }

        return $profile;
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
        $this->hybridAuth($this->_registry->getController()->request)
            ->logoutAllProviders();
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
