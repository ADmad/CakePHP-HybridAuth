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
     * Allow method "endpoint"
     *
     * @param \Cake\Event\Event $event Before filter event.
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow('endpoint');
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
}
