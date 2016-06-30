<?php
use Phinx\Migration\AbstractMigration;

class CreateSocialProfiles extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('social_profiles');
        $table
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('provider', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false
            ])
            ->addColumn('identifier', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false
            ])
            ->addColumn('profile_url', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('website_url', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('photo_url', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('display_name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('first_name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('last_name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('gender', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('language', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('age', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('birth_day', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('birth_month', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('birth_year', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('email_verified', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('phone', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('address', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('country', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('region', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('city', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('zip', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'user_id',
                ]
            )
            ->create();
    }
}
