<?php

use Pckg\Database\Entity;
use Test\Entity\Categories;

class CheckSelectQueryTest extends \Codeception\Test\Unit
{

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testSimpleQuery()
    {
        $categoriesEntity = new Categories();

        $sql = 'SELECT `categories`.* FROM `categories`';
        $this->assertEquals($sql, $categoriesEntity->getQuery()->buildSQL());
        $this->assertEquals([], $categoriesEntity->getQuery()->buildBinds());
        $this->assertCount(10, $categoriesEntity->all());

        $categoriesEntity->where('id', [1, 2, 3]);
        $sql = 'SELECT `categories`.* FROM `categories` WHERE (`categories`.`id` IN(?,?,?))';
        $this->assertEquals($sql, $categoriesEntity->getQuery()->buildSQL());
        $this->assertEquals([], $categoriesEntity->getQuery()->buildBinds());
        $this->assertCount(3, $categoriesEntity->all());

        $categoriesEntity->where('id', 1);
        $sql = 'SELECT `categories`.* FROM `categories` WHERE (`categories`.`id` = ?)';
        $this->assertEquals($sql, $categoriesEntity->getQuery()->buildSQL());
        $this->assertEquals([1], $categoriesEntity->getQuery()->buildBinds());
        $this->assertCount(1, $categoriesEntity->all());
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