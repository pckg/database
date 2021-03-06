<?php

use Pckg\Database\Relation\BelongsTo;
use Pckg\Database\Relation\HasAndBelongsTo;
use Pckg\Database\Relation\HasMany;
use Pckg\Database\Relation\MorphedBy;
use Pckg\Database\Test\MockDatabase;
use Test\Entity\Categories;
use Test\Entity\Languages;
use Test\Entity\UserGroups;
use Test\Entity\Users;

class CheckSelectQueryTest extends \Codeception\Test\Unit
{
    use \Pckg\Framework\Test\Codeception\Cest {
        _before as _beforeCest;
    }
    use MockDatabase;

    /**
     * @var UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->_beforeCest($this->tester);
        $this->tester->initConfigDatabases();
        $this->context->bind(\Pckg\Framework\Application::class, new \Pckg\Framework\Application(new \Pckg\Framework\Provider()));
        config()->set('database', $this->tester->getCodeceptConfig()['pckg']['database']);
    }

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
                    'sql'   => 'SELECT `categories`.* FROM `categories` AS `categories` ORDER BY slug',
                    'binds' => [],
                ],
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` AS `categories` WHERE (`categories`.`id` IN(?, ?, ?))',
                    'binds' => [1, 2, 3],
                ],
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` AS `categories` WHERE (`categories`.`id` = ?)',
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
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`id` IN(?, ?, ?)) ORDER BY `users`.`id` ASC',
                    'binds' => [3, 4, 5],
                ],
                [
                    'sql'   => 'SELECT `users_categories`.* FROM `users_categories` AS `users_categories` WHERE (`users_categories`.`user_id` IN(?, ?, ?))',
                    'binds' => [3, 4, 5],
                ],
                [
                    'sql'   => 'SELECT `categories`.* FROM `categories` AS `categories` WHERE (`categories`.`id` IN(?, ?, ?, ?, ?, ?, ?))',
                    'binds' => [3, 7, 5, 2, 8, 9, 10],
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
                    'sql'   => 'SELECT `categories`.* FROM `categories` AS `categories` WHERE (`categories`.`id` IN(?, ?, ?))',
                    'binds' => [2, 3, 4],
                ],
                [
                    'sql'   => 'SELECT `users_categories`.* FROM `users_categories` AS `users_categories` WHERE (`users_categories`.`category_id` IN(?, ?, ?))',
                    'binds' => [2, 3, 4],
                ],
                [
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`id` IN(?, ?, ?, ?, ?))',
                    'binds' => [1, 2, 3, 4, 5],
                ],
            ],
            $this->tester->getListenedQueries()
        );
    }

    public function testUserGroupsHasManyRelation()
    {
        $userGroupsEntity = new UserGroups();

        $this->tester->listenToQueries();

        $userGroupsWithUsers = $userGroupsEntity->where('id', [1, 2])
                                                ->withUsers()
                                                ->all();

        //$this->assertNotEmpty($userGroupsWithUsers[0]->users);
        //$this->assertNotEmpty($userGroupsWithUsers[1]->users);

        $this->assertEquals(2, $userGroupsWithUsers->count());
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `user_groups`.* FROM `user_groups` AS `user_groups` WHERE (`user_groups`.`id` IN(?, ?))',
                    'binds' => [1, 2],
                ],
                [
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`user_group_id` IN(?, ?))',
                    'binds' => [1, 2],
                ],
            ],
            $this->tester->getListenedQueries()
        );

        $this->tester->listenToQueries();
        $thirdGroup = $userGroupsEntity->where('id', 3)->one();
        //$thirdGroup->users;

        //$this->assertNotEmpty($thirdGroup->users);
        $this->assertEquals(2, $thirdGroup->users->count());
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `user_groups`.* FROM `user_groups` AS `user_groups` WHERE (`user_groups`.`id` = ?) LIMIT 1',
                    'binds' => [3],
                ],
                [
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`user_group_id` = ?)',
                    'binds' => [3],
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
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`id` IN(?, ?, ?))',
                    'binds' => [2, 3, 4],
                ],
                [
                    'sql'   => 'SELECT `user_groups`.* FROM `user_groups` AS `user_groups` WHERE (`user_groups`.`id` IN(?, ?))',
                    'binds' => [1, 2],
                ],
                [
                    'sql'   => 'SELECT `languages`.* FROM `languages` AS `languages` WHERE (`languages`.`slug` IN(?, ?))',
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
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`id` IN(?, ?))',
                    'binds' => [1, 2],
                ],
                [
                    'sql'   => 'SELECT `settings_morphs`.* FROM `settings_morphs` AS `settings_morphs` WHERE (`settings_morphs`.`poly_id` IN(?, ?)) AND (`settings_morphs`.`morph_id` = ?)',
                    'binds' => [1, 2, Users::class],
                ],
                [
                    'sql'   => 'SELECT `settings`.* FROM `settings` AS `settings` WHERE (`settings`.`id` IN(?, ?))',
                    'binds' => [1, 2],
                ],
            ],
            $this->tester->getListenedQueries()
        );
    }

    public function testAllTheWayRelations()
    {
        $languages = new Languages();

        $this->tester->listenToQueries();

        $all = $languages->withUsers(
            function(HasMany $users) {
                $users->withUserGroup()
                      ->withCategories(
                          function(HasAndBelongsTo $categories) {
                              $categories->withUsers(
                                  function(HasMany $users) {
                                      $users->withLanguage();
                                  }
                              );
                          }
                      )
                      ->withSettings();
            }
        )->all();

        $this->assertNotEmpty($all->first()->users->first());
        $this->assertNotEmpty($all->first()->users->first()->userGroup);
        $this->assertNotEmpty($all->first()->users->first()->categories->first());
        $this->assertNotEmpty($all->first()->users->first()->categories->first()->users->first());
        $this->assertNotEmpty($all->first()->users->first()->categories->first()->users->first()->language);
        $this->assertNotEmpty($all->first()->users->first()->settings->first());

        $this->assertCount(10, $this->tester->getListenedQueries());
    }

    public function testAllTheWayLazyRelations()
    {
        $languages = new Languages();

        $this->tester->listenToQueries();

        $language = $languages->where('id', 2)->one();

        $language->users(
            function(HasMany $users) {
                $users->withUserGroup()
                      ->withCategories(
                          function(HasAndBelongsTo $categories) {
                              $categories->withUsers(
                                  function(HasMany $users) {
                                      $users->withLanguage();
                                  }
                              );
                          }
                      )
                      ->withSettings();
            }
        );

        $this->assertNotEmpty($language->users->first());
        $this->assertNotEmpty($language->users->first()->userGroup);
        $this->assertNotEmpty($language->users->first()->categories->first());
        $this->assertNotEmpty($language->users->first()->categories->first()->users->first());
        $this->assertNotEmpty($language->users->first()->categories->first()->users->first()->language);
        $this->assertNotEmpty($language->users->first()->settings->first());
        $this->assertCount(10, $this->tester->getListenedQueries());
    }

    public function testDeepJoinRelation()
    {
        $this->tester->listenToQueries();

        $languages = (new Languages())
            ->joinUsers(function(HasMany $users) {
                $users->leftJoin();
                $users->joinUsersCategories(function(HasMany $usersCategories) {
                    $usersCategories->leftJoin();
                    $usersCategories->joinCategory(function(BelongsTo $category) {
                        $category->leftJoin();
                        $category->joinSettings(function(MorphedBy $settings) {
                            $settings->leftJoin();
                        });
                    });
                });
            })->all();

        $queries = $this->tester->getListenedQueries();

        $this->assertEquals([
                                [
                                    'sql'   => 'SELECT `settings_morphs`.*, `settings`.*, `languages`.* FROM `languages` AS `languages` ' .
                                               'LEFT JOIN `users` AS `users` ON `languages`.`slug` = `users`.`language_id` ' .
                                               'LEFT JOIN `users_categories` AS `users_categories` ON `users`.`id` = `users_categories`.`user_id` ' .
                                               'LEFT JOIN `categories` AS `categories` ON `users_categories`.`category_id` = `categories`.`id` ' .
                                               'LEFT JOIN `settings_morphs` AS `settings_morphs` ON `categories`.`id` = `settings_morphs`.`poly_id` AND `settings_morphs`.`morph_id` = ? ' .
                                               'LEFT JOIN `settings` AS `settings` ON `settings`.`id` = `settings_morphs`.`setting_id`',
                                    'binds' => [
                                        Categories::class,
                                    ],
                                ],
                            ], $queries);
    }

}
