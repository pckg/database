<?php

use Pckg\Database\Entity;

class CheckSelectQueryTest extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testSimpleQuery()
    {
        $entity = new Entity();
        $entity->setTable('test_table');

        $sql = 'SELECT `test_table`.* FROM `test_table`';
        $this->assertEquals($sql, $entity->getQuery()->buildSQL());
        $this->assertEquals([], $entity->getQuery()->buildBinds());

        $entity->where('test', 'val');
        $sql = 'SELECT `test_table`.* FROM `test_table` WHERE (`test_table`.`test` = ?)';
        $this->assertEquals($sql, $entity->getQuery()->buildSQL());
        $this->assertEquals(['val'], $entity->getQuery()->buildBinds());

        $entity->where('test2', 'val2');
        $sql = 'SELECT `test_table`.* FROM `test_table` WHERE (`test_table`.`test` = ?) AND (`test_table`.`test2` = ?)';
        $this->assertEquals($sql, $entity->getQuery()->buildSQL());
        $this->assertEquals(['val', 'val2'], $entity->getQuery()->buildBinds());
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