<?php

namespace SQI\Tests;

/**
 * Run tests from tests\phpunit\query folder
 * Class QueryTestSuite
 * @package SQI\Tests
 */
class QueryTestSuite extends \PHPUnit_Framework_TestSuite {

	/** @var \Article */
	private $page;
	private $pageText = <<<TEXT

	This is test page for SemanticQuery extension.

		Set simple undefined property with default _wpg value:
		[[SQTPropertySimple::SQTValueSimple]]

		Set simple undefined property with multiple _wpg values:
		[[SQTPropertyMultiple::SQTValueMultiple1]]
		[[SQTPropertyMultiple::SQTValueMultiple2]]
		[[SQTPropertyMultiple::SQTValueMultiple3]]

		Set page category to simple undefined category
		[[Category:SQTCategory1]]

		Set additional page category to simple undefined category
		[[Category:SQTCategory2]]

		Set internal (truly, sub) -object on this page
		{{#set_internal:SQTPropertySubobjectRelatedTo
			|SQTSubobjectProperty1=SQTSubobjectProperty1Value1
			|SQTSubobjectProperty2=SQTSubobjectProperty2Value1
			|SQTSubobjectProperty2=SQTSubobjectProperty2Value2
		}}

TEXT;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$files = array();
		$testDir = __DIR__.'/query';
		$files = array_merge( $files, glob( "$testDir/*Test.php" ) );
		foreach ( $files as $file ) {
			$this->addTestFile( $file );
		}
	}

	/**
	 * @return QueryTestSuite
	 */
	public static function suite() {
		return new self;
	}

	protected function setUp() {
		parent::setUp();
		//Create dummy page
		$pageTitle = \Title::newFromText('SemanticQueryTestPage');
		$context = new \RequestContext();
		$context->setTitle( $pageTitle );
		$pageArticle = \Article::newFromTitle( $pageTitle, $context );
		$pageArticle->getPage()->doEdit( $this->pageText, 'tests start ' );
		$this->page = $pageArticle;
	}

	protected function tearDown() {
		parent::tearDown();
		$this->page->doDelete('tests end ');
	}


}