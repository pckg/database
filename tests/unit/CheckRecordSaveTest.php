<?php

use Test\Record\User;

class CheckRecordSaveTest extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function atestIsSavedAndIsDeleted()
    {
        $user = new User();

        $this->assertFalse($user->isSaved(), 'User isn\'t saved on construction.');
        $this->assertFalse($user->isDeleted(), 'User isn\'t deleted on construction.');

        $user->username = 'testuser1';
        $user->user_group_id = 1;
        $user->language_id = 'si';

        $this->assertFalse($user->isSaved(), 'User isn\'t saved when changing data.');
        $this->assertFalse($user->isDeleted(), 'User isn\'t deleted when changing data.');

        $user->save();

        $this->assertTrue($user->isSaved(), 'User is saved after save().');
        $this->assertFalse($user->isDeleted(), 'User isn\'t deleted after save().');

        $user->update();

        $this->assertTrue($user->isSaved(), 'User is saved after update().');
        $this->assertFalse($user->isDeleted(), 'User isn\'t deleted after update().');

        $user->delete();

        $this->assertFalse($user->isSaved(), 'User isn\'t saved after delete().');
        $this->assertTrue($user->isDeleted(), 'User is deleted after delete().');
    }
}
