<?php
namespace ADmad\HybridAuth\Model\Entity;

use Cake\ORM\Entity;

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $identifier
 * @property string $profile_url
 * @property string $website_url
 * @property string $photo_url
 * @property string $display_name
 * @property string $description
 * @property string $first_name
 * @property string $last_name
 * @property string $gender
 * @property string $language
 * @property string $age
 * @property string $birth_day
 * @property string $birth_month
 * @property string $birth_year
 * @property string $email
 * @property string $email_verified
 * @property string $phone
 * @property string $address
 * @property string $country
 * @property string $region
 * @property string $city
 * @property string $zip
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 * @property \App\Model\Entity\User $user
 */
class SocialProfile extends Entity
{

    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}
