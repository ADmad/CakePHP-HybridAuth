<?php
namespace ADmad\HybridAuth\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Hybrid_Auth;

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class HybridAuthAuthenticate extends BaseAuthenticate
{

    use EventDispatcherTrait;

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
     * Whether hybrid auth intialization is done.
     *
     * @var bool
     */
    protected $_initDone = false;

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
            'profileModelFkField' => 'user_id',
            'hauth_return_to' => null
        ]);

        parent::__construct($registry, $config);
    }

    /**
     * Initialize HybridAuth
     *
     * @param \Cake\Network\Request $request Request instance.
     * @return void
     * @throws \RuntimeException Incase case of unknown error.
     */
    protected function _init(Request $request)
    {
        if ($this->_initDone) {
            return;
        }

        $request->session()->start();

        $hybridConfig = Configure::read('HybridAuth');

        if (empty($hybridConfig['base_url'])) {
            $hybridConfig['base_url'] = Router::url(
                [
                    'plugin' => 'ADmad/HybridAuth',
                    'controller' => 'HybridAuth',
                    'action' => 'endpoint',
                    'prefix' => false
                ],
                true
            );
        }

        try {
            Hybrid_Auth::initialize($hybridConfig);
        } catch (\Exception $e) {
            if ($e->getCode() < 5) {
                throw new \RuntimeException($e->getMessage());
            } else {
                $this->_registry->Auth->flash($e->getMessage());
                Hybrid_Auth::initialize($hybridConfig);
            }
        }
    }

    /**
     * Get / set hybridauth adapter instance.
     *
     * @param \Hybrid_Provider_Model $adapter Hybrid auth adapter instance
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
     * @param \Hybrid_User_Profile $profile Hybrid auth user profile instance
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
        $this->_init($request);

        $providers = Hybrid_Auth::getConnectedProviders();
        foreach ($providers as $provider) {
            $adapter = Hybrid_Auth::getAdapter($provider);
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
        if ($user = $this->getUser($request)) {
            return $user;
        }

        $provider = $this->_checkProvider($request->query);
        if (!$provider) {
            return false;
        }

        $returnTo = Router::url(
            [
                'plugin' => 'ADmad/HybridAuth',
                'controller' => 'HybridAuth',
                'action' => 'authenticated',
                'prefix' => false
            ],
            true
        );
        if (!empty($this->_config['hauth_return_to'])) {
            $returnTo = Router::url($this->_config['hauth_return_to'], true);
        }
        $params = ['hauth_return_to' => $returnTo];
        if ($provider === 'OpenID') {
            $params['openid_identifier'] = $request->query($this->_config['fields']['openid_identifier']);
        }

        $adapter = Hybrid_Auth::authenticate($provider, $params);

        if ($adapter) {
            return $this->_getUser($adapter);
        }
        return false;
    }

    /**
     * Checks whether provider is supplied.
     *
     * @param array $data Data array to check.
     * @return string|bool Provider name if it exists, false if required fields have
     *   not been supplied.
     */
    protected function _checkProvider($data)
    {
        $fields = $this->_config['fields'];

        if (empty($data[$fields['provider']])) {
            return false;
        }

        $provider = $data[$fields['provider']];

        if ($provider === 'OpenID' && empty($data[$fields['openid_identifier']])) {
            return false;
        }

        return $provider;
    }

    /**
     * Get user record for HybridAuth adapter and try to get associated user record
     * from your application's database.
     *
     * If app user record is not found a 'HybridAuth.newUser' event is dispatched
     * with profile info from HyridAuth. The event listener should create associated
     * user record and return user entity as event result.
     *
     * @param \Hybrid_Provider_Model $adapter Hybrid auth adapter instance.
     * @return array User record
     * @throws \Exception Thrown when a profile cannot be retrieved
     * @throws \RuntimeException Thrown when the user has not created a listener, or the entity cannot be persisted
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
        $UsersTable = TableRegistry::get($config['userModel']);
        
        if ($profile && !empty($profile->user)) {
            $user = $profile->user;
            $profile->unsetProperty('user');
        } elseif ($providerProfile->email) {
            $user = $UsersTable
                ->find($config['finder'])
                ->where([
                    $UsersTable->aliasField($config['fields']['email']) => $providerProfile->email
                ])
                ->first();
        }

        $profile = $this->_profileEntity($profile ?: null);

        if (!$user) {
            $event = $this->dispatchEvent(
                'HybridAuth.newUser',
                ['profile' => $profile]
            );

            if (empty($event->result) || !($event->result instanceof EntityInterface)) {
                throw new \RuntimeException('
                    You must attach a listener for "HybridAuth.newUser" event
                    which saves new user record and returns an user entity.
                ');
            }

            $user = $event->result;
        }

        $profile->{$config['profileModelFkField']} = $user->{$UsersTable->primaryKey()};
        $profile = TableRegistry::get($config['profileModel'])->save($profile);
        if (!$profile) {
            throw new \RuntimeException('Unable to save social profile.');
        }

        $user->set('social_profile', $profile);
        $user->unsetProperty($config['fields']['password']);
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
     * Get social profile entity
     *
     * @param \Cake\ORM\Entity $profile Social profile entity
     * @return \Cake\ORM\Entity
     */
    protected function _profileEntity($profile = null)
    {
        if (!$profile) {
            $ProfileTable = TableRegistry::get($this->_config['profileModel']);
            $profile = $ProfileTable->newEntity([
                'provider' => $this->adapter()->id,
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
        $this->_init($event->subject()->request);
        Hybrid_Auth::logoutAllProviders();
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
