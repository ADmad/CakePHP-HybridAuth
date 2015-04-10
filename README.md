CakePHP HybridAuth Plugin
=========================

[![Total Downloads](https://poser.pugx.org/admad/cakephp-hybridauth/downloads.svg)](https://packagist.org/packages/admad/cakephp-hybridauth.png)
[![License](https://poser.pugx.org/admad/cakephp-hybridauth/license.svg)](https://packagist.org/packages/admad/cakephp-hybridauth)

A CakePHP plugin which allows using the [HybridAuth](http://hybridauth.sourceforge.net/)
social sign on library.

Requirements
------------

* CakePHP 3.0+

Installation
------------

Run: `composer require admad/cakephp-hybridauth:~3.0` or add
`"admad/cakephp-hybridauth": "~3.0"` to the `require` section of your
application's `composer.json`.

Setup
-----

Load the plugin by adding following to your app's boostrap:
`Plugin::load('ADmad/HybridAuth', ['bootstrap' => true, 'routes' => true]);`

Configuration
-------------

Make a config file `config/hybridauth.php`
Eg.

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

For more information about the hybridauth configuration array check
http://hybridauth.sourceforge.net/userguide/Configuration.html

The plugin also expects that your users table used for authentication contains
fields `provider` and `provider_uid`. The fields are configurable through the
`HybridAuthAuthenticate` authenticator.

__Note:__ When specifying `loginRedirect` URL for AuthComponent be sure to add
`'plugin' => false` (or appropiate plugin name) to the URL array.

Usage
-----
Check the CakePHP manual on how to configure and use the `AuthComponent` with
required authenticator. You would have something like this in your `AppController`'s `initialize` method.

	<?php
	$this->loadComponent('Auth', [
            'authenticate' => [
                'ADmad/HybridAuth.HybridAuth'
            ]
        ]);

Your controller's login action should be similar to this:

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
