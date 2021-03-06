<?php
namespace ChangeTests\Change\Db\Query\Clauses;

use Change\Db\Query\Clauses\GroupByClause;

class GroupByClauseTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$i = new GroupByClause();
		$this->assertEquals('GROUP BY', $i->getName());	
		$this->assertNull($i->getExpressionList());
		
		
		$el = new \Change\Db\Query\Expressions\ExpressionList();
		$i = new GroupByClause($el);
		
		$this->assertEquals($el, $i->getExpressionList());
	}
	
	public function testExpressionList()
	{
		$i = new GroupByClause();
		$el = new \Change\Db\Query\Expressions\ExpressionList();
		$i->setExpressionList($el);
		$this->assertEquals($el, $i->getExpressionList());
		$ret = $i->addExpression(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals($ret, $i);
		$this->assertEquals(1, $i->getExpressionList()->count());
		
		try
		{
			$i->setExpressionList(null);
			$this->fail('Argument 1 must be a instance of \Change\Db\Query\Expressions\ExpressionList');
		}
		catch (\Exception $e)
		{
			$this->assertTrue(true);
		}
	}
	
	public function testToSQL92String()
	{		
		$i = new GroupByClause();
		try
		{
			$i->toSQL92String();
			$this->fail('ExpressionList can not be null');
		}
		catch (\RuntimeException $e)
		{
			$this->assertTrue(true);
		}
				
		$i->addExpression(new \Change\Db\Query\Expressions\Raw('raw'));
		$this->assertEquals("GROUP BY raw", $i->toSQL92String());
	}
}
