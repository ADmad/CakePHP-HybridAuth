<?php
namespace ADmad\HybridAuth\Model\Table;

use Cake\ORM\Table;

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class SocialProfilesTable extends Table
{

    /**
     * Initialize table.
     *
     * @param array $config Configuration
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users');
    }
}
