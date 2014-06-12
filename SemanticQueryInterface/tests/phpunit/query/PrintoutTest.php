<?php
/*
 * Do not be surprised, MediaWikiTestCase have one own test method which runs before every implemented here.
 */

namespace SQI\Tests;

use SQI\SemanticQueryInterface;

class PrintoutTest extends \MediaWikiTestCase {

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

	public function testPagePrintout() {
		$result = $this->sq->from('SemanticQueryTestPage')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking if there some results" );
		$index = array_keys($result);
		$this->assertGreaterThan( 0, count($result[$index[0]]['properties']) );

		$this->assertArrayHasKey( 'SQTPropertySimple', $result[$index[0]]['properties'] );
		$this->assertEquals( 'SQTValueSimple', $result[$index[0]]['properties']['SQTPropertySimple'][0]->getText() );

		$this->assertArrayHasKey( 'SQTPropertyMultiple', $result[$index[0]]['properties'] );
		$this->assertEquals( 'SQTValueMultiple1', $result[$index[0]]['properties']['SQTPropertyMultiple'][0]->getText() );
		$this->assertEquals( 'SQTValueMultiple2', $result[$index[0]]['properties']['SQTPropertyMultiple'][1]->getText() );
		$this->assertEquals( 'SQTValueMultiple3', $result[$index[0]]['properties']['SQTPropertyMultiple'][2]->getText() );

		$this->assertArrayHasKey( 'Categories', $result[$index[0]]['properties'] );
		$this->assertEquals( 'SQTCategory1', $result[$index[0]]['properties']['Categories'][0]->getText() );
		$this->assertEquals( 'SQTCategory2', $result[$index[0]]['properties']['Categories'][1]->getText() );
	}

	public function testPropertyPrintout() {
		$result = $this->sq->condition('SQTPropertySimple')->printout('SQTPropertyMultiple')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking if there some results" );
		$index = array_keys($result);
		$this->assertGreaterThan( 0, count($result[$index[0]]['properties']) );

		$this->assertArrayHasKey( 'SQTPropertySimple', $result[$index[0]]['properties'] );
		$this->assertEquals( 'SQTValueSimple', $result[$index[0]]['properties']['SQTPropertySimple'][0]->getText() );

		$this->assertArrayHasKey( 'SQTPropertyMultiple', $result[$index[0]]['properties'] );
		$this->assertEquals( 'SQTValueMultiple1', $result[$index[0]]['properties']['SQTPropertyMultiple'][0]->getText() );
		$this->assertEquals( 'SQTValueMultiple2', $result[$index[0]]['properties']['SQTPropertyMultiple'][1]->getText() );
		$this->assertEquals( 'SQTValueMultiple3', $result[$index[0]]['properties']['SQTPropertyMultiple'][2]->getText() );
	}

	public function testCategoryPrintout() {
		$result = $this->sq->category('SQTCategory1')->printout('SQTPropertySimple')->printout('SQTPropertyMultiple')->execute()->toArray();
		$this->assertEquals( 1, $this->sq->count(), "Checking if there some results" );
		$index = array_keys($result);
		$this->assertGreaterThan( 0, count($result[$index[0]]['properties']) );

		$this->assertArrayHasKey( 'SQTPropertySimple', $result[$index[0]]['properties'] );
		$this->assertEquals( 'SQTValueSimple', $result[$index[0]]['properties']['SQTPropertySimple'][0]->getText() );

		$this->assertArrayHasKey( 'SQTPropertyMultiple', $result[$index[0]]['properties'] );
		$this->assertEquals( 'SQTValueMultiple1', $result[$index[0]]['properties']['SQTPropertyMultiple'][0]->getText() );
		$this->assertEquals( 'SQTValueMultiple2', $result[$index[0]]['properties']['SQTPropertyMultiple'][1]->getText() );
		$this->assertEquals( 'SQTValueMultiple3', $result[$index[0]]['properties']['SQTPropertyMultiple'][2]->getText() );

		$this->assertArrayHasKey( 'Categories', $result[$index[0]]['properties'] );
		$this->assertGreaterThan( 0, count($result[$index[0]]['properties']['Categories']) );
	}

}
