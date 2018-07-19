<?php
namespace ADmad\HybridAuth\Model\Table;

use Cake\ORM\Table;

/**
 * HybridAuth Authenticate
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile get($primaryKey, $options = [])
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile newEntity($data = null, array $options = [])
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile[] newEntities(array $data, array $options = [])
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile[] patchEntities($entities, array $data, array $options = [])
 * @method \ADmad\HybridAuth\Model\Entity\SocialProfile findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
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
