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

    public function initialize(array $config)
    {
        parent::initialize();

        $this->belongsTo('Users');
    }
}