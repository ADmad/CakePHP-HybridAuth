<?php
namespace ADmad\HybridAuth\Model\Entity;

use Cake\ORM\Entity;

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class SocialProfile extends Entity
{

    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}
