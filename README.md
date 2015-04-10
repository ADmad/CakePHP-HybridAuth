CakePHP HybridAuth Plugin
=========================

[![Total Downloads](https://poser.pugx.org/admad/cakephp-hybridauth/downloads.svg)](https://packagist.org/packages/admad/cakephp-hybridauth.png)
[![License](https://poser.pugx.org/admad/cakephp-hybridauth/license.svg)](https://packagist.org/packages/admad/cakephp-hybridauth)

A CakePHP plugin that adds support for the [HybridAuth](http://hybridauth.sourceforge.net/) social sign on library.

Requirements
------------

* CakePHP 3.0+

Installation
------------

This plugin can be installed using composer.

```
    $ composer require admad/cakephp-hybridauth:~3.0
```

Or manually update your `composer.json`.

```JavaScript
    "require": {
        "admad/cakephp-hybridauth": "~3.0"
    },
```

Setup
-----

Load the plugin by adding the following to your `App/config/bootstrap.php` file:

```PHP
    Plugin::load('ADmad/HybridAuth', ['bootstrap' => true, 'routes' => true]);
```

Configuration
-------------

Make a config file `App/config/hybridauth.php`
Eg.

```PHP
	<?php
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

Database
--------

The plugin also expects that your users table used for authentication to contain
the fields `provider` and `provider_uid`. The fields are configurable through the
`HybridAuthAuthenticate` authenticator.

Here is a sample user table:

```MySQL
    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `email` varchar(200) NOT NULL,
      `password` varchar(200) NOT NULL,
      `first_name` varchar(200) NOT NULL,
      `last_name` varchar(200) NOT NULL,
      `provider` varchar(100) NOT NULL,
      `provider_uid` varchar(255) NOT NULL,
      `created_at` datetime NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
```

__Note:__ When specifying `loginRedirect` URL for AuthComponent be sure to add
`'plugin' => false` (or appropiate plugin name) to the URL array.

Usage
-----
Check the CakePHP manual on how to configure and use the `AuthComponent` with
required authenticator. You would have something like this in your `AppController`'s `initialize` method.

```PHP
	<?php
	$this->loadComponent('Auth', [
            'authenticate' => [
                'ADmad/HybridAuth.HybridAuth'
            ]
        ]);
```        

Your controller's login action should be similar to this:

```PHP
	<?php
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

License
-------

[The MIT License](http://opensource.org/licenses/mit-license.php)
