<?php

use Test\Entity\Users;
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

        User::gets(['id' => 2])->setAndSave(['language_id' => 'si']);

        (new Users())->set(['language_id' => 'en'])->where('id', 2)->update();

        $username = sha1(microtime());
        User::getOrNew(['username' => $username])->setAndSave(['user_group_id' => 2, 'language_id' => 'si']);
        (new Users())->where('username', $username)->delete();
        
        /*User::gets(['id' => 1])->setAndSave(['password' => sha1(microtime())]);
        (new Users())->where('id', 1)->one()->setAndSave(['password' => sha1(microtime())]);
        (new Users())->where('id', 1)->all()->first()->setAndSave(['password' => sha1(microtime())]);
        (new Users())->where('id', 1)->set(['password' => sha1(microtime())])->update();
        User::create(['email' => sha1(microtime())])->delete();
        User::create(['email' => sha1(microtime())]);
        (new Users())->set(['password' => sha1(microtime())])->where('id', 1, '>')->update();
        (new Users())->where('id', 1, '>')->delete();*/

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
                    'sql'   => 'UPDATE `users` SET `language_id` = ? WHERE (`users`.`id` = ?)',
                    'binds' => ['en', 2],
                    'repo'  => 'default:write',
                ],
                [
                    'sql'   => 'SELECT `users`.* FROM `users` AS `users` WHERE (`users`.`username` = ?) LIMIT 1',
                    'binds' => [$username],
                    'repo'  => 'default',
                ],
                [
                    'sql'   => 'INSERT INTO `users` (`username`, `user_group_id`, `language_id`) VALUES (?, ?, ?)',
                    'binds' => [$username, 2, 'si'],
                    'repo'  => 'default:write',
                ],
                [
                    'sql'   => 'DELETE FROM `users` WHERE (`users`.`username` = ?)',
                    'binds' => [$username],
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