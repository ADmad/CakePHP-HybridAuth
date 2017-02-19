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
     * The query string key used for remembering the referrered page when getting
     * redirected to login.
     */
    const QUERY_STRING_REDIRECT = 'redirect';

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
     * User model.
     *
     * @var \Cake\Datasource\RepositoryInterface
     */
    protected $_userModel;

    /**
     * Social profile model
     *
     * @var \Cake\Datasource\RepositoryInterface
     */
    protected $_profileModel;

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
     * Initialize HybridAuth and this authenticator.
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

        $this->_userModel = TableRegistry::get($this->_config['userModel']);
        $this->_profileModel = TableRegistry::get($this->_config['profileModel']);

        $request->session()->start();

        $hybridConfig = Configure::read('HybridAuth');

        if (empty($hybridConfig['base_url'])) {
            $hybridConfig['base_url'] = [
                'plugin' => 'ADmad/HybridAuth',
                'controller' => 'HybridAuth',
                'action' => 'endpoint',
                'prefix' => false
            ];
        }

        $hybridConfig['base_url'] = $this->_appendRedirectQueryString(
            $hybridConfig['base_url'],
            $request->query(static::QUERY_STRING_REDIRECT)
        );

        $hybridConfig['base_url'] = Router::url($hybridConfig['base_url'], true);

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

        $returnTo = [
            'plugin' => 'ADmad/HybridAuth',
            'controller' => 'HybridAuth',
            'action' => 'authenticated',
            'prefix' => false
        ];
        if ($this->config('hauth_return_to')) {
            $returnTo = $this->config('hauth_return_to');
        }

        $returnTo = $this->_appendRedirectQueryString(
            $returnTo,
            $request->query(static::QUERY_STRING_REDIRECT)
        );

        $params = ['hauth_return_to' => Router::url($returnTo, true)];
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
     * @throws \Exception Thrown when a profile cannot be retrieved.
     * @throws \RuntimeException If profile entity cannot be persisted.
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
        $userModel = $this->_userModel;

        $user = null;
        $profile = $this->_query($providerProfile->identifier)->first();

        if ($profile) {
            $userId = $profile->get($config['profileModelFkField']);
            $user = $this->_userModel->find($config['finder'])
                ->where([
                    $userModel->aliasField($userModel->primaryKey()) => $userId
                ])
                ->first();

            // User record exists but finder conditions did not match,
            // so just update social profile record and return false.
            if (!$user) {
                $profile = $this->_profileEntity($profile);
                if (!$this->_profileModel->save($profile)) {
                    throw new \RuntimeException('Unable to save social profile.');
                }

                return false;
            }
        } elseif ($providerProfile->email) {
            $user = $this->_userModel->find($config['finder'])
                ->where([
                    $this->_userModel->aliasField($config['fields']['email']) => $providerProfile->email
                ])
                ->first();
        }

        $profile = $this->_profileEntity($profile);
        if (!$user) {
            $user = $this->_newUser($profile);
        }

        $profile->{$config['profileModelFkField']} = $user->{$userModel->primaryKey()};
        $profile = $this->_profileModel->save($profile);
        if (!$profile) {
            throw new \RuntimeException('Unable to save social profile.');
        }

        $user->set('social_profile', $profile);
        $user->unsetProperty($config['fields']['password']);

        return $user->toArray();
    }

    /**
     * Get new user entity.
     *
     * It dispatches a `HybridAuth.newUser` event. A listener must return
     * an entity for new user record.
     *
     * @param \Cake\ORM\Entity $profile Social profile entity.
     * @return \Cake\ORM\Entity User entity.
     * @throws \RuntimeException Thrown when the user entity is not returned by event listener.
     */
    protected function _newUser($profile)
    {
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

        return $event->result;
    }

    /**
     * Get query to fetch social profile record.
     *
     * @param string $identifier Provider's identifier.
     * @return \Cake\ORM\Query
     */
    protected function _query($identifier)
    {
        list(, $userAlias) = pluginSplit($this->_config['userModel']);
        $provider = $this->adapter()->id;

        return $this->_profileModel->find()
            ->where([
                $this->_profileModel->aliasField('provider') => $provider,
                $this->_profileModel->aliasField('identifier') => $identifier
            ]);
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
            $profile = $this->_profileModel->newEntity([
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

    /**
     * Append the "redirect" query string param to URL.
     *
     * @param string|array $url URL
     * @param string $redirectQueryString Redirect query string
     * @return string URL
     */
    protected function _appendRedirectQueryString($url, $redirectQueryString)
    {
        if (!$redirectQueryString) {
            return $url;
        }

        if (is_array($url)) {
            $url['?'][static::QUERY_STRING_REDIRECT] = $redirectQueryString;
        } else {
            $char = strpos($url, '?') === false ? '?' : '&';
            $url .= $char . static::QUERY_STRING_REDIRECT . '=' . urlencode($redirectQueryString);
        }

        return $url;
    }
}
