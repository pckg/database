<?php

use Pckg\Database\Entity;
use Pckg\Database\Query;
use Test\Entity\Categories;
use Test\Entity\Users;

class CheckSelectQueryTest extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testSimpleQuery()
    {
        $categoriesEntity = new Categories();

        $this->tester->listenToQueries();

        /**
         * Test order by.
         */
        $categoriesEntity->orderBy('slug');
        $this->assertCount(10, $categoriesEntity->all());

        /**
         * Test IN.
         */
        $categoriesEntity->where('id', [1, 2, 3]);
        $this->assertCount(3, $categoriesEntity->all());

        /**
         * Test =.
         */
        $categoriesEntity->where('id', 1);
        $this->assertCount(1, $categoriesEntity->all());

        /**
         * Compare SQLs.
         */
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` ORDER BY slug',
                    'binds' => [],
                ],
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` WHERE (`categories`.`id` IN(?, ?, ?))',
                    'binds' => [1, 2, 3],
                ],
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` WHERE (`categories`.`id` = ?)',
                    'binds' => [1],
                ],
            ],
            $this->tester->getListenedQueries()
        );
    }

    public function testUsersMtmRelation()
    {
        $usersEntity = new Users();

        $this->tester->listenToQueries();

        $usersWithCategories = $usersEntity->where('id', [3, 4, 5])
                                           ->withCategories()
                                           ->orderBy('id')
                                           ->all();

        /**
         * 3 users should be found.
         */
        $this->assertEquals(3, $usersWithCategories->count());

        /**
         * With 2, 2 and 6 categories respectively.
         */
        $this->assertEquals(2, $usersWithCategories[0]->categories->count());
        $this->assertEquals(2, $usersWithCategories[1]->categories->count());
        $this->assertEquals(6, $usersWithCategories[2]->categories->count());

        /**
         * And those are SQL queries that should be generated.
         */
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `users`.* FROM `users` WHERE (`users`.`id` IN(?, ?, ?)) ORDER BY `users`.`id` ASC',
                    'binds' => [3, 4, 5],
                ],
                [
                    'sql'   => 'SELECT `users_categories`.* FROM `users_categories` WHERE (`users_categories`.`user_id` IN(?, ?, ?))',
                    'binds' => [3, 4, 5],
                ],
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` WHERE (`categories`.`id` IN(?, ?, ?, ?, ?, ?, ?))',
                    'binds' => [2, 3, 5, 7, 8, 9, 10],
                ],
            ],
            $this->tester->getListenedQueries()
        );
    }

    public function testCategoriesMtmRelation()
    {
        $categoriesEntity = new Categories();

        $this->tester->listenToQueries();

        $categoriesWithUsers = $categoriesEntity->where('id', [2, 3, 4])
                                                ->withUsers()
                                                ->all();

        /**
         * 3 categories should be found.
         */
        $this->assertEquals(3, $categoriesWithUsers->count());

        /**
         * With 3, 2 and 1 categories respectively.
         */
        $this->assertEquals(3, $categoriesWithUsers[0]->users->count());
        $this->assertEquals(2, $categoriesWithUsers[1]->users->count());
        $this->assertEquals(1, $categoriesWithUsers[2]->users->count());

        /**
         * And those are SQL queries that should be generated.
         */
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` WHERE (`categories`.`id` IN(?, ?, ?))',
                    'binds' => [2, 3, 4],
                ],
                [
                    'sql'   => 'SELECT `users_categories`.* FROM `users_categories` WHERE (`users_categories`.`category_id` IN(?, ?, ?))',
                    'binds' => [2, 3, 4],
                ],
                [
                    'sql'   => 'SELECT `users`.* FROM `users` WHERE (`users`.`id` IN(?, ?, ?, ?, ?))',
                    'binds' => [1, 2, 3, 4, 5],
                ],
            ],
            $this->tester->getListenedQueries()
        );
    }

    public function testUsersBelongsToRelation()
    {
        $usersEntity = new Users();

        $this->tester->listenToQueries();

        $usersWithUserGroupAndLanguage = $usersEntity->where('id', [2, 3, 4])
                                                     ->withUserGroup()
                                                     ->withLanguage()
                                                     ->all();

        $this->assertEquals(3, $usersWithUserGroupAndLanguage->count());
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `users`.* FROM `users` WHERE (`users`.`id` IN(?, ?, ?))',
                    'binds' => [2, 3, 4],
                ],
                [
                    'sql'   => 'SELECT `user_groups`.* FROM `user_groups` WHERE (`user_groups`.`id` IN(?, ?))',
                    'binds' => [1, 2],
                ],
                [
                    'sql'   => 'SELECT `languages`.* FROM `languages` WHERE (`languages`.`id` IN(?, ?))',
                    'binds' => ['en', 'si'],
                ],
            ],
            $this->tester->getListenedQueries()
        );
    }

    public function testUsersMorphedByRelation()
    {
        $usersEntity = new Users();

        $this->tester->listenToQueries();

        $usersWithSettings = $usersEntity->withSettings()
                                         ->where('id', [1, 2])
                                         ->all();

        $this->assertEquals(2, $usersWithSettings->count());
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `users`.* FROM `users` WHERE (`users`.`id` IN(?, ?))',
                    'binds' => [1, 2],
                ],
                [
                    'sql'   => 'SELECT `settings_morphs`.* FROM `settings_morphs` WHERE (`settings_morphs`.`poly_id` IN(?, ?)) AND (`settings_morphs`.`morph_id` = ?)',
                    'binds' => [1, 2, Users::class],
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