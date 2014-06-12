<?php
/*
 * Do not be surprised, MediaWikiTestCase have one own test method which runs before every implemented here.
 */

namespace SQI\Tests;

use SQI\SemanticQueryInterface;

class SubobjectTest extends \MediaWikiTestCase {

	/** @var SemanticQueryInterface */
	private $sq;

	protected function setUp() {
		parent::setUp();

		if( !class_exists('Subobject') ) {
			$this->markTestSkipped('SMW < 1.9 Subobjects not supported');
		}

		//Create query
		$this->sq = new SemanticQueryInterface();
	}

	protected function tearDown() {
		unset($sq);
		parent::tearDown();
	}

	public function testFindSubobjectByProperty() {
		$this->sq->condition('SQTPropertySubobjectRelatedTo','SemanticQueryTestPage')->execute();
		$result = $this->sq->toArray();
		$this->assertEquals( 1, $this->sq->count() );
	}

	public function testSubobjectPropertiesValues() {
		$this->sq->condition('SQTPropertySubobjectRelatedTo','SemanticQueryTestPage')->printout('*')->execute();
		$result = $this->sq->toArray();
		$index = array_keys($result);
		$this->assertArrayHasKey( 'SQTSubobjectProperty1', $result[$index[0]]['properties'] );
		$this->assertArrayHasKey( 'SQTSubobjectProperty2', $result[$index[0]]['properties'] );
		$this->assertCount( 2, $result[$index[0]]['properties']['SQTSubobjectProperty2'] );
	}

}
