CakePHP HybridAuth Plugin
=========================

[![Total Downloads](https://img.shields.io/packagist/dt/ADmad/CakePHP-HybridAuth.svg?style=flat-square)](https://packagist.org/packages/admad/cakephp-hybridauth)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

A CakePHP plugin which allows using the [HybridAuth](http://hybridauth.sourceforge.net/)
social sign on library.

Requirements
------------

* CakePHP 3.0+ (Refer to the `cake2` branch README for CakePHP 2.x)

Installation
------------

Run:

```
composer require admad/cakephp-hybridauth:~3.0
```

Setup
-----

Load the plugin by adding following to your app's boostrap:

```php
Plugin::load('ADmad/HybridAuth', ['bootstrap' => true, 'routes' => true]);
```

Configuration
-------------

Make a config file `config/hybridauth.php`
Eg.

```php
use Cake\Core\Configure;

$config['HybridAuth'] = [
    'providers' => [
        'OpenID' => [
            'enabled' => true
        ]
    ],
    'debug_mode' => Configure::read('debug'),
    'debug_file' => LOGS . 'hybridauth.log',
];
```

For more information about the hybridauth configuration array check
http://hybridauth.sourceforge.net/userguide/Configuration.html

__Note:__ When specifying `loginRedirect` URL for AuthComponent be sure to add
`'plugin' => false` (or appropiate plugin name) to the URL array.

Database
--------

The plugin also expects that your users table used for authentication contains
fields `provider` and `provider_uid`. The fields are configurable through the
`HybridAuthAuthenticate` authenticator.

```MySQL
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(200) NOT NULL,
    `password` varchar(200) NOT NULL,
    `name` varchar(200) NOT NULL,
    `provider` varchar(100) NOT NULL,
    `provider_uid` varchar(255) NOT NULL,
    `created` datetime NOT NULL,
    `modified` datetime NOT NULL
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
```

Usage
-----

Check the CakePHP manual on how to configure and use the `AuthComponent` with
required authenticator. You would have something like this in your `AppController`'s `initialize` method.

```php
$this->loadComponent('Auth', [
        'authenticate' => [
            'Form',
            'ADmad/HybridAuth.HybridAuth'
        ]
    ]);
```

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
logic that depends upon the identify() method returning.

An eg. element `Template/Element/login.ctp` showing how to setup the login page
form is provided. Checkout the various
[examples](http://hybridauth.sourceforge.net/userguide/Examples_and_Demos.html)
in hybridauth documentation to see various ways to setup your login page.

Once a user is authenticated through the provider the authenticator gets the user
profile from the identity provider and using that tries to find the corresponding
user record in your app's users table. If no user is found and `registrationCallback`
option is specified the specified method from the `User` model is called. You
can use the callback to save user record to database.

If no callback is specified the profile returned by identity provider itself is
returned by the authenticator.

Copyright
---------

Copyright 2015 ADmad
