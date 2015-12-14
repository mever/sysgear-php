<?php

/*
 * This file is part of the Sysgear package.
*
* (c) Martijn Evers <mevers47@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sysgear\Tests\Dql;

use Sysgear\Test\TestCase,
    Sysgear\Filter\Expression,
    Sysgear\Filter\Collection,
    Sysgear\Dql\Query as DqlQuery;

class QueryTest extends TestCase
{
    const C = 'Sysgear\Dql\Query';
    const C_EM = 'Doctrine\ORM\EntityManager';
    const C_QB = 'Doctrine\ORM\QueryBuilder';
    const C_CONN = 'Doctrine\DBAL\Connection';
    const C_DUMMY = 'Sysgear\Tests\Dql\DummyEntity';

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No entity class given.
     */
    public function testBuildWithoutEntityClass() {
        $q = new DqlQuery($this->mockEntityManager());
        $q->build();
    }

    public function testBuildSimpleQuery() {
        $query = new \stdClass();
        $em = $this->mockEntityManager();
        $qb = $this->mockQueryBuilder();
        $em->expects($this->any())->method('createQueryBuilder')->willReturn($qb);

        $qb->expects($this->once())->method('from')->with(self::C_DUMMY, '_');
        $qb->expects($this->never())->method('where');
        $qb->expects($this->once())->method('select')->with('_');
        $qb->expects($this->never())->method('innerJoin');
        $qb->expects($this->never())->method('addOrderBy');
        $qb->expects($this->never())->method('setFirstResult');
        $qb->expects($this->never())->method('setMaxResults');
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $q = new DqlQuery($em, self::C_DUMMY);
        $this->assertSame($query, $q->build());
    }

    public function testBuildQueryOrderBy() {
        $query = new \stdClass();
        $em = $this->mockEntityManager();
        $qb = $this->mockQueryBuilder();
        $em->expects($this->any())->method('createQueryBuilder')->willReturn($qb);

        $qb->expects($this->once())->method('from')->with(self::C_DUMMY, '_');
        $qb->expects($this->never())->method('where');
        $qb->expects($this->once())->method('select')->with('_');
        $qb->expects($this->never())->method('innerJoin');
        $qb->expects($this->once())->method('addOrderBy')->willReturnCallback(function($orderBy) {
            $this->assertInstanceOf('\Doctrine\ORM\Query\Expr\OrderBy', $orderBy);
            $this->assertEquals('_.foo DESC, _.bar ASC, foo.baz DESC', (string) $orderBy);
        });
        $qb->expects($this->never())->method('setFirstResult');
        $qb->expects($this->never())->method('setMaxResults');
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $q = new DqlQuery($em, self::C_DUMMY);
        $q->orderBy = array(
            array('foo', false),
            array('bar', true),
            array('foo.baz', false)
        );
        $this->assertSame($query, $q->build());
    }

    public function testBuildQueryLimitAndFilter() {
        $query = new \stdClass();
        $em = $this->mockEntityManager();
        $qb = $this->mockQueryBuilder();
        $em->expects($this->any())->method('createQueryBuilder')->willReturn($qb);

        $qb->expects($this->once())->method('from')->with(self::C_DUMMY, '_');
        $qb->expects($this->once())->method('where')
            ->with("(_.foo = '123' AND _.bar IS NULL AND _.baz = 'YH&8932'\\''yh%\"98')");
        $qb->expects($this->once())->method('select')->with('_');
        $qb->expects($this->never())->method('innerJoin');
        $qb->expects($this->once())->method('addOrderBy')->willReturnCallback(function($orderBy) {
            $this->assertInstanceOf('\Doctrine\ORM\Query\Expr\OrderBy', $orderBy);
            $this->assertEquals('_.foo DESC, _.bar ASC, foo.baz DESC', (string) $orderBy);
        });
        $qb->expects($this->once())->method('setFirstResult')->with(3);
        $qb->expects($this->once())->method('setMaxResults')->with(6);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $q = new DqlQuery($em, self::C_DUMMY);
        $q->limit = array(3, 6);
        $q->orderBy = array(
            array('foo', false),
            array('bar', true),
            array('foo.baz', false)
        );

        $q->filters = new Collection(array(
            new Expression('foo', 123),
            new Expression('bar', null),
            new Expression('baz', 'YH&8932\'yh%"98')
        ));

        $this->assertSame($query, $q->build());
    }

    public function testBuildQueryWithNestedFields() {
        $query = new \stdClass();
        $em = $this->mockEntityManager();
        $qb = $this->mockQueryBuilder();
        $em->expects($this->any())->method('createQueryBuilder')->willReturn($qb);

        $qb->expects($this->once())->method('from')->with(self::C_DUMMY, '_');
        $qb->expects($this->once())->method('where')
            ->with("(_.foo = '123' AND _.bar IS NULL AND foo.baz = 'test')");
        $qb->expects($this->once())->method('select')->with('DISTINCT _');
        $qb->expects($this->once())->method('innerJoin')->with('_.foo', 'foo');
        $qb->expects($this->once())->method('addOrderBy')->willReturnCallback(function($orderBy) {
            $this->assertInstanceOf('\Doctrine\ORM\Query\Expr\OrderBy', $orderBy);
            $this->assertEquals('_.foo DESC, _.bar ASC, foo.baz DESC', (string) $orderBy);
        });
        $qb->expects($this->once())->method('setFirstResult')->with(3);
        $qb->expects($this->once())->method('setMaxResults')->with(6);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $q = new DqlQuery($em, self::C_DUMMY);
        $q->limit = array(3, 6);
        $q->orderBy = array(
            array('foo', false),
            array('bar', true),
            array('foo.baz', false)
        );

        $q->filters = new Collection(array(
            new Expression('foo', 123),
            new Expression('bar', null),
            new Expression('foo.baz', 'test')
        ));

        $this->assertSame($query, $q->build());
    }

    public function testBuildQueryWithNestedFieldsAndCount() {
        $query = new \stdClass();
        $em = $this->mockEntityManager();
        $qb = $this->mockQueryBuilder();
        $em->expects($this->any())->method('createQueryBuilder')->willReturn($qb);

        $qb->expects($this->once())->method('from')->with(self::C_DUMMY, '_');
        $qb->expects($this->once())->method('where')
            ->with("(_.foo = '123' AND _.bar IS NULL AND foo.baz = 'test')");
        $qb->expects($this->once())->method('select')->with('COUNT(_.id)');
        $qb->expects($this->once())->method('innerJoin')->with('_.foo', 'foo');
        $qb->expects($this->never())->method('addOrderBy');
        $qb->expects($this->once())->method('setFirstResult')->with(3);
        $qb->expects($this->once())->method('setMaxResults')->with(6);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $q = new DqlQuery($em, self::C_DUMMY);
        $q->limit = array(3, 6);
        $q->orderBy = array(
            array('foo', false),
            array('bar', true),
            array('foo.baz', false)
        );

        $q->filters = new Collection(array(
            new Expression('foo', 123),
            new Expression('bar', null),
            new Expression('foo.baz', 'test')
        ));

        $this->assertSame($query, $q->build(true));
    }

    public function testConstruction() {
        $em = $this->mockEntityManager();
        $i = new DqlQuery($em);
        $this->assertSame($em, $i->entityManager);
        $this->assertNull($i->entityClass);
    }

    public function testConstructionWithOptionalClass() {
        $em = $this->mockEntityManager();
        $i = new DqlQuery($em, self::C_EM);
        $this->assertSame($em, $i->entityManager);
        $this->assertSame(self::C_EM, $i->entityClass);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Supplied field is invalid!
     */
    public function test_selectWrongFields() {
        $query = $this->mockQuery();
        $query->select(array('_.name', 'a.type', '_.abc', 'a.name', 'new.id'));
    }

    public function test_select() {
        $query = $this->mockQuery();
        $query->select(array('name', 'a.type', 'abc', 'a.name', 'new.id'));
        $this->assertEquals(array (
            '_.name', 'a.type', '_.abc', 'a.name', 'new.id'
        ), $this->getProp($query, 'selects'));
    }

    public function test_assertField_alphaNum() {
        $c = self::C;
        $c::assertField('abC1X23');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Supplied field is invalid!
     */
    public function test_assertField_start_numeric() {
        $c = self::C;
        $c::assertField('123asg');
    }

    public function test_assertField_alias() {
        $c = self::C;
        $c::assertField('a.abC1X23');
    }

    public function test_assertField_alias_alphaNum() {
        $c = self::C;
        $c::assertField('a52wwSD29.abC1X23');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Supplied field is invalid!
     */
    public function test_assertField_alias_start_numeric() {
        $c = self::C;
        $c::assertField('25.asdghy');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Supplied field is invalid!
     */
    public function test_assertField_alias_underscore() {
        $c = self::C;
        $c::assertField('_.asdghy');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\QueryBuilder
     */
    private function mockQueryBuilder() {
        return $this->mock(self::C_QB, array());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Sysgear\Dql\Query
     */
    private function mockQuery() {
        return $this->mock(self::C);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityManager
     */
    private function mockEntityManager() {
        $conn = $this->mock(self::C_CONN, array('quote'));
        $conn->expects($this->any())->method('quote')->willReturnCallback(function($value) {

            // this is good for our tests
           return escapeshellarg($value);
        });

        $em = $this->mock(self::C_EM, array('createQueryBuilder', 'createQuery', 'getConnection'));
        $em->expects($this->any())->method('getConnection')->willReturn($conn);
        return $em;
    }
}

class DummyEntity
{
}