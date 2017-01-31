<?php

use Pckg\Database\Query;
use Test\Record\UserGroup;

class CheckTranslatableExtensionTest extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testUserGroup()
    {
        $this->tester->importDatabase(realpath(__DIR__ . '/../_data/') . '/pckg_database_001.sql');

        $this->tester->listenToQueries();

        $userGroup = UserGroup::getOrCreate(
            [
                'slug' => 'test1',
            ]
        );

        $userGroup->title = 'test2title';
        $userGroup->slug = 'test2';

        $userGroup->save();

        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `user_groups`.* FROM `user_groups` AS `user_groups` WHERE (`user_groups`.`slug` = ?) LIMIT 1',
                    'binds' => ['test1'],
                ],
                [
                    'sql'   => 'INSERT INTO `user_groups` (`slug`) VALUES (?)',
                    'binds' => ['test1'],
                ],
                [
                    'sql'   => 'INSERT INTO `user_groups_i18n` (`id`, `language_id`) VALUES (?, ?)',
                    'binds' => [5, 'en'],
                ],
                [
                    'sql'   => 'UPDATE `user_groups` SET `id` = ?, `slug` = ? WHERE (`id` = ?)',
                    'binds' => [5, 5, 'test2'],
                ],
                [
                    'sql'   => 'SELECT `user_groups_i18n`.* FROM `user_groups_i18n` AS `user_groups_i18n` WHERE (`user_groups_i18n`.`id` = ?) AND (`user_groups_i18n`.`language_id` = ?) LIMIT 1',
                    'binds' => [5, 'en'],
                ],
                [
                    'sql'   => 'UPDATE `user_groups_i18n` SET `id` = ?, `language_id` = ?, `title` = ? WHERE (`id` = ?) AND (`language_id` = ?)',
                    'binds' => [5, 5, 'en', 'en', 'test2title'],
                ],
            ],
            $this->tester->getListenedQueries()
        );

        $this->tester->listenToQueries();
        $userGroup = UserGroup::getOrCreate(
            [
                'slug' => 'untranslated',
            ]
        );
        $userGroup->title = 'translated';
        $userGroup->save();

        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `user_groups`.* FROM `user_groups` AS `user_groups` WHERE (`user_groups`.`slug` = ?) LIMIT 1',
                    'binds' => ['untranslated'],
                ],
                [
                    'sql'   => 'UPDATE `user_groups` SET `id` = ?, `slug` = ? WHERE (`id` = ?)',
                    'binds' => [4, 4, 'untranslated'],
                ],
                [
                    'sql'   => 'SELECT `user_groups_i18n`.* FROM `user_groups_i18n` AS `user_groups_i18n` WHERE (`user_groups_i18n`.`id` = ?) AND (`user_groups_i18n`.`language_id` = ?) LIMIT 1',
                    'binds' => [4, 'en'],
                ],
                [
                    'sql'   => 'INSERT INTO `user_groups_i18n` (`id`, `language_id`, `title`) VALUES (?, ?, ?)',
                    'binds' => [4, 'en', 'translated'],
                ],
            ],
            $this->tester->getListenedQueries()
        );
    }

    // executed before each test
    protected function _before()
    {
        $this->tester->initPckg(__DIR__);
    }

    // executed after each test
    protected function _after()
    {
    }

}