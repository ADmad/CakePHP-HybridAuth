<?php
namespace ADmad\HybridAuth\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

/**
 * HybridAuth Controller
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class HybridAuthController extends AppController
{

    /**
     * Allow methods 'endpoint' and 'authenticated'.
     *
     * @param \Cake\Event\Event $event Before filter event.
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['endpoint', 'authenticated']);
    }

    /**
     * Endpoint method
     *
     * @return void
     */
    public function endpoint()
    {
        $this->request->session()->start();
        \Hybrid_Endpoint::process();
    }

    /**
     * This action exists just to ensure AuthComponent fetches user info from
     * hybridauth after successful login
     *
     * Hyridauth's `hauth_return_to` is set to this action.
     *
     * @return \Cake\Network\Response
     */
    public function authenticated()
    {
        $user = $this->Auth->identify();
        if ($user) {
            $this->Auth->setUser($user);

            return $this->redirect($this->Auth->redirectUrl());
        }

        return $this->redirect($this->Auth->config('loginAction'));
    }
}
