CakePHP HybridAuth Plugin
=========================

[![Total Downloads](https://img.shields.io/packagist/dt/ADmad/CakePHP-HybridAuth.svg?style=flat-square)](https://packagist.org/packages/admad/cakephp-hybridauth)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A CakePHP plugin which allows using the [HybridAuth](http://hybridauth.sourceforge.net/)
social sign on library.

Requirements
------------

* CakePHP 3.1+.

Installation
------------

Run:

```
composer require --prefer-dist admad/cakephp-hybridauth:dev-master
```

Setup
-----

Load the plugin by running following command in terminal:

```
bin/cake plugin load ADmad/HybridAuth
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
                    'id' => '<facebook-application-id>',
                    'secret' => '<secret-key>'
                ]
            ],
            'Facebook' => [
                'enabled' => true,
                'keys' => [
                    'id' => '<google-client-id>',
                    'secret' => '<secret-key>'
                ],
                'scope' => 'email, user_about_me, user_birthday, user_hometown'
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
        'ADmad/HybridAuth.HybridAuth'
    ]
]);
```

__Note:__ When specifying `loginRedirect` URL for AuthComponent be sure to add
`'plugin' => false` (or appropiate plugin name) to the URL array.

Your controller's login action should be similar to this:

```php
public function login() {
    if ($this->request->is('post')) {
        $user = $this->Auth->identify();
        if ($user) {
            $this->Auth->setUser($user);
            return $this->redirect($this->Auth->redirectUrl());
        }
        $this->Flash->error(__('Invalid username or password, try again'));
    }
}
```

__Note:__ When your action calls $this->Auth->identify() the method may not return.
The authenticator may need to redirect to the provider's site to complete the
identification procedure. It's important not to implement any important business
logic that depends upon the `identify()` method returning.

An eg. element `Template/Element/login.ctp` showing how to setup the login page
form is provided.

Once a user is authenticated through the provider the authenticator gets the user
profile from the identity provider and using that tries to find the corresponding
user record in your app's users table. If no user is found emits a `HybriAuth.newUser`
event. You must setup a listener for this event which save new user record to
your users table and return an entity for the new user. Here's how you can setup
a method of your `UsersTable` as callback for the event.

```php
public function initialize(array $config)
{
    $this->hasMany('ADmad/HybridAuth.SocialProfiles');

    EventManager::instance()->on('HybridAuth.newUser', [$this, 'createUser']);
}

public function createUser(Event $event) {
    // Entity representing record in social_profiles table
    $profile = $event->data()['profile'];

    $user = $this->newEntity(array('email' => $profile->email));
    $user = $this->save($user);

    if (!$user) {
        throw new \RuntimeException('Unable to save new user');
    }

    return $user;
}
```

Copyright
---------

Copyright 2015 ADmad
