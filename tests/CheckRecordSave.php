<?php

class CheckRecordSave extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testSave()
    {
        $user = new \Pckg\Auth\Record\User();

        $this->assertFalse($user->isSaved(), 'User isn\'t saved on construction.');

        $user->email = 'test@test.si';
        $user->user_group_id = 1;

        $this->assertFalse($user->isSaved(), 'User isn\t saved when changing data.');

        $user->save();

        $this->assertTrue($user->isSaved(), 'User is saved after save().');

        $user->update();

        $this->assertTrue($user->isSaved(), 'User is saved after update().');

        $user->delete();

        $this->assertFalse($user->isSaved(), 'User isn\'t saved after delete().');
    }

    // executed before each test
    protected function _before()
    {
    }

    // executed after each test
    protected function _after()
    {
    }

}