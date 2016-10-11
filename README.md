CakePHP HybridAuth Plugin
=========================

[![Total Downloads](https://img.shields.io/packagist/dt/ADmad/CakePHP-HybridAuth.svg?style=flat-square)](https://packagist.org/packages/admad/cakephp-hybridauth)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A CakePHP plugin which allows using the [HybridAuth](http://hybridauth.github.io/hybridauth/)
social sign on library.

Requirements
------------

* CakePHP 3.1+.

Installation
------------

Run:

```
composer require --prefer-dist admad/cakephp-hybridauth
```

Setup
-----

Load the plugin by running following command in terminal:

```
bin/cake plugin load ADmad/HybridAuth -b -r
```

or by manually adding following line to your app's `config/bootstrap.php`:

```php
Plugin::load('ADmad/HybridAuth', ['bootstrap' => true, 'routes' => true]);
```

Configuration
-------------

Make a config file `config/hybridauth.php`:

```php
use Cake\Core\Configure;

return [
    'HybridAuth' => [
        'providers' => [
            'Google' => [
                'enabled' => true,
                'keys' => [
                    'id' => '<google-client-id>',
                    'secret' => '<secret-key>'
                ]
            ],
            'Facebook' => [
                'enabled' => true,
                'keys' => [
                    'id' => '<facebook-application-id>',
                    'secret' => '<secret-key>'
                ],
                'scope' => 'email, user_about_me, user_birthday, user_hometown'
            ],
            'Twitter' => [
                'enabled' => true,
                'keys' => [
                    'key' => '<twitter-key>',
                    'secret' => '<twitter-secret>'
                ],
                'includeEmail' => true // Only if your app is whitelisted by Twitter Support
            ]
        ],
        'debug_mode' => Configure::read('debug'),
        'debug_file' => LOGS . 'hybridauth.log',
    ]
];
```

For more information about the hybridauth configuration array check
http://hybridauth.github.io/hybridauth/userguide/Configuration.html

Database
--------

The plugin expects that you have a users table with at least `email` field
and a `social_profiles` table. You can run

```
bin/cake migrations migrate -p ADmad/HybridAuth
```

to generate the `social_profiles` tabel using a migration file provided with
the plugin.

Usage
-----

Check the CakePHP manual on how to configure and use the `AuthComponent` with
required authentication handler. You would have something like this in your
`AppController`'s `initialize()` method.

```php
$this->loadComponent('Auth', [
    'authenticate' => [
        'Form',
        'ADmad/HybridAuth.HybridAuth' => [
            // All keys shown below are defaults
            'fields' => [
                'provider' => 'provider',
                'openid_identifier' => 'openid_identifier',
                'email' => 'email'
            ],

            'profileModel' => 'ADmad/HybridAuth.SocialProfiles',
            'profileModelFkField' => 'user_id',

            'userModel' => 'Users',

            // The URL Hybridauth lib should redirect to after authentication.
            // If no value is specified you are redirect to this plugin's
            // HybridAuthController::authenticated() which handles persisting
            // user info to AuthComponent and redirection.
            'hauth_return_to' => null
        ]
    ]
]);
```

__Note:__ When specifying `loginRedirect` URL for AuthComponent be sure to add
`'plugin' => false` (or appropiate plugin name) to the URL array.

Your controller's login action should be similar to this:

```php
public function login() {
    if ($this->request->is('post') || $this->request->query('provider')) {
        $user = $this->Auth->identify();
        if ($user) {
            $this->Auth->setUser($user);
            return $this->redirect($this->Auth->redirectUrl());
        }
        $this->Flash->error(__('Invalid username or password, try again'));
    }
}
```

__Note:__ When your action calls `$this->Auth->identify()` the method may not return.
The authenticator may need to redirect to the provider's site to complete the
identification procedure. It's important not to implement any important business
logic that depends upon the `identify()` method returning.

On your login page you can create links to initiate authentication using required
providers. Specify the provider name using variable named `provider` in query string.

```php
echo $this->Html->link(
    'Login with Google',
    ['controller' => 'Users', 'action' => 'login', '?' => ['provider' => 'Google']]
);
```

Once a user is authenticated through the provider the authenticator gets the user
profile from the identity provider and using that tries to find the corresponding
user record in your app's users table. If no user is found emits a `HybridAuth.newUser`
event. You must setup a listener for this event which save new user record to
your users table and return an entity for the new user. Here's how you can setup
a method of your `UsersTable` as callback for the event.

```php
public function initialize(array $config)
{
    $this->hasMany('ADmad/HybridAuth.SocialProfiles');

    \Cake\Event\EventManager::instance()->on('HybridAuth.newUser', [$this, 'createUser']);
}

public function createUser(\Cake\Event\Event $event) {
    // Entity representing record in social_profiles table
    $profile = $event->data()['profile'];

    $user = $this->newEntity(['email' => $profile->email]);
    $user = $this->save($user);

    if (!$user) {
        throw new \RuntimeException('Unable to save new user');
    }

    return $user;
}
```

Twitter & email addresses
-------------------------
If you are trying to achieve a 'Sign in using Twitter' functionality, and you require the users *email address*, you need to specifically get your application [white-listed by Twitter Support using this form](https://support.twitter.com/forms/platform) and selecting 'I need access to special permissions'. Then you can use the `'includeEmail' => true` configuration option.

Copyright
---------
Copyright 2016 ADmad

License
-------
[See LICENSE](LICENSE.txt)
