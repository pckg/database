<?php

class CheckRecordSaveTest extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testIsSavedAndIsDeleted()
    {
        $user = new \Pckg\Auth\Record\User();

        $this->assertFalse($user->isSaved(), 'User isn\'t saved on construction.');
        $this->assertFalse($user->isDeleted(), 'User isn\'t deleted on construction.');

        $user->email = 'test@test.si';
        $user->user_group_id = 1;

        $this->assertFalse($user->isSaved(), 'User isn\t saved when changing data.');
        $this->assertFalse($user->isDeleted(), 'User isn\t deleted when changing data.');

        $user->save();

        $this->assertTrue($user->isSaved(), 'User is saved after save().');
        $this->assertTrue($user->isDeleted(), 'User isn\t deleted after save().');

        $user->update();

        $this->assertTrue($user->isSaved(), 'User is saved after update().');
        $this->assertTrue($user->isDeleted(), 'User is deleted after update().');

        $user->delete();

        $this->assertFalse($user->isSaved(), 'User isn\'t saved after delete().');
        $this->assertFalse($user->isDeleted(), 'User is deleted after delete().');
    }

    // executed before each test
    protected function _before()
    {
        $this->tester->loadPckg();
    }

    // executed after each test
    protected function _after()
    {
    }

}