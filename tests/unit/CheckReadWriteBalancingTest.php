<?php

use Test\Record\User;

class CheckReadWriteBalancingTest extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testBalancing()
    {
        $this->tester->listenToQueries('Repo', false);

        $user = User::gets(['id' => 2]);

        $user->setAndSave(['language_id' => 'si']);

        $user->setAndSave(['language_id' => 'en']);

        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`id` = ?) LIMIT 1',
                    'binds' => [2],
                    'repo'  => 'default',
                ],
                [
                    'sql'   => 'UPDATE `users` SET `id` = ?, `language_id` = ? WHERE (`id` = ?)',
                    'binds' => [2, 'si', 2],
                    'repo'  => 'default:write',
                ],
                [
                    'sql'   => 'UPDATE `users` SET `id` = ?, `language_id` = ? WHERE (`id` = ?)',
                    'binds' => [2, 'en', 2],
                    'repo'  => 'default:write',
                ],
            ],
            $this->tester->getListenedQueries('Repo')
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