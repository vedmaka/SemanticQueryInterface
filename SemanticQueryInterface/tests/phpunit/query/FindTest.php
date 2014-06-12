<?php
/*
 * Do not be surprised, MediaWikiTestCase have one own test method which runs before every implemented here.
 */

namespace SQI\Tests;

use SQI\SemanticQueryInterface;

class FindTest extends \MediaWikiTestCase {

	/** @var SemanticQueryInterface */
	private $sq;

	protected function setUp() {
		parent::setUp();
		//Create query
		$this->sq = new SemanticQueryInterface();
	}

	protected function tearDown() {
		unset($sq);
		parent::tearDown();
	}

	public function testFindPageByName() {
		$result = $this->sq->from('SemanticQueryTestPage')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count() , "Testing page search" );
	}

	public function testFindPageByPropertyExist() {
		$result = $this->sq->condition('SQTPropertySimple')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking there are some results" );
		$index = array_keys($result);
		$this->assertTrue( array_key_exists( 'SQTPropertySimple', $result[$index[0]]['properties'] ) , "Checking if there are property in result array" );
	}

	public function testFindPageByPropertyValue() {
		$result = $this->sq->condition('SQTPropertySimple','SQTValueSimple')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking there are some results" );
		$index = array_keys($result);
		$this->assertTrue( array_key_exists( 'SQTPropertySimple', $result[$index[0]]['properties'] ) , "Checking if there are property in result array" );
		$this->assertTrue( isset( $result[$index[0]]['properties']['SQTPropertySimple'][0] ) , "Checking if there are value in result array" );
		/** @var \Title $value */
		$value = $result[$index[0]]['properties']['SQTPropertySimple'][0];
		$this->assertInstanceOf( 'Title', $value, "Checking if value is Title" );
		$this->assertEquals( 'SQTValueSimple', $value->getText(), "Checking if title is right" );
	}

	public function testFindPageByManyProperties() {
		$result = $this->sq->condition('SQTPropertySimple')->condition('SQTPropertyMultiple')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking there are some results" );
		$index = array_keys($result);
		$this->assertTrue( array_key_exists( 'SQTPropertySimple', $result[$index[0]]['properties'] ) , "Checking if there are property in result array" );
		$this->assertTrue( array_key_exists( 'SQTPropertyMultiple', $result[$index[0]]['properties'] ) , "Checking if there are property in result array" );
	}

	public function testFindPageByCategory() {
		$result = $this->sq->category('SQTCategory1')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking there are some results" );
		$index = array_keys($result);
		$this->assertTrue( array_key_exists( 'Categories', $result[$index[0]]['properties'] ) , "Checking if there are property in result array" );
	}

	public function testFindPageByManyCategories() {
		$result = $this->sq->category('SQTCategory1')->category('SQTCategory2')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking there are some results" );
		$index = array_keys($result);
		$this->assertTrue( array_key_exists( 'Categories', $result[$index[0]]['properties'] ) , "Checking if there are property in result array" );
	}

	public function testFindByComplexQuery() {
		$result = $this->sq->category('SQTCategory1')->condition('SQTPropertySimple')->condition('SQTPropertyMultiple','SQTValueMultiple3')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking there are some results" );
	}

}
