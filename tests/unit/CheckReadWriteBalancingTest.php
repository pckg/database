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
        $this->tester->listenToQueries('Repo');

        $user = User::gets(['id' => 1])->setAndSave(['language' => 'si']);
        
        $this->assertEquals(
            [
                [
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`id` = ?) LIMIT 1',
                    'binds' => [1],
                    'repo'  => 'default',
                ],
                [
                    'sql'   => 'UPDATE `users` SET `id` = ?, `language_id` = ? WHERE (`id` = ?)',
                    'binds' => [1, 'si', 1],
                    'repo'  => 'default:write',
                ],
            ],
            $this->tester->getListenedQueries('Repo')
        );

        $this->tester->ignoreQueryListening('Repo');
        dd();
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