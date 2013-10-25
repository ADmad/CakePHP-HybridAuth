CakePHP HybridAuth Plugin
=========================

A CakePHP plugin which allows using the [HybridAuth](http://hybridauth.sourceforge.net/) social sign on library.

Requirements
------------

* CakePHP 2.4+

Installation
------------

`git submodule add git://github.com/ADmad/CakePHP-HybridAuth.git app/Plugin/HybridAuth`
or download from http://github.com/ADmad/CakePHP-HybridAuth

Setup
-----

In app/Config/bootstrap.php add:
`CakePlugin::load('HybridAuth', array('bootstrap' => true));`

Configuration
-------------

Make a config file App/Config/hybridauth.php
Eg.

    <?php
    $config['HybridAuth'] = array(
        'providers' => array(
            'OpenID' => array(
                'enabled' => true
            )
        ),
        'debug_mode' => (bool)Configure::read('debug'),
        'debug_file' => LOGS . 'hybridauth.log',
    );

For more information about the hybridauth configuration array check
http://hybridauth.sourceforge.net/userguide/Configuration.html

The plugin also expects that your users table used for authentication contains
fields `provider` and `provider_uid`. The fields are configurable through the
`HybridAuthAuthenticate` authenticator.

Usage
-----
Check the CakePHP manual on how to configure and use the `AuthComponent` with
required authenticator. You would have something like this in your `AppController`.

    <?php
    public $components = array(
        'Auth' => array(
            'authenticate' => array(
                'HybridAuth.HybridAuth' => array(
                    'registrationCallback' => 'hybridRegister'
                )
            )
        )
    );

Your controller's login action should be similar to this:

    <?php
    public function login() {
        if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                return $this->redirect($this->Auth->redirect());
            } else {
                $this->Session->setFlash('Username or password is incorrect');
            }
        }
    }

An eg. element `View/Elements/login.ctp` showing how to setup the login page
form is provided. The fields should use the same model name as the configured
`userModel` setting for the authenticator. Checkout the various [examples](http://hybridauth.sourceforge.net/userguide/Examples_and_Demos.html) in
hybridauth documentation to see various ways to setup your login page.

Once a user is authenticated through the provider the authenticator gets the user
profile from the identity provider and using that tries to find the corresponding
user record in your app's users table. If no user is found and `registrationCallback`
option is specified as shown in example above the specified method from the `User`
model is called. The callback method can be similar to this:

    <?php
    public function hybridRegister($provider, stdClass $profile) {
        $profile = (array)$profile;
        $data = array(
            'provider' => $provider,
            'provider_uid' => $profile['identifier'],
            //Extra fields here as needed like group_id, status etc.
        );
        unset($profile['identifier']);
        foreach ($profile as $field => $value) {
            $data[Inflector::underscore($field)] = $value;
        }
        $this->create();
        return $this->save($data, false);
    }

If no callback is specified the profile returned by identity provider itself is
returned by the authenticator.

Copyright
---------

Copyright 2013 ADmad

License
-------

[The MIT License](http://opensource.org/licenses/mit-license.php)

