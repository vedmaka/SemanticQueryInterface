<?php

namespace SQI\Tests;

/**
 * Run all phpUnit test suites
 * Class AllTestsSuite
 * @package SQI\Tests
 */
class AllTestsSuite extends \PHPUnit_Framework_TestSuite {

	public function __construct() {
		parent::__construct();
		$files = array();
		$testDir = __DIR__;
		$files = array_merge( $files, glob( "$testDir/*TestSuite.php" ) );
		foreach ( $files as $file ) {
			//$this->addTestSuite( str_replace('.php','',$file) );
			$this->addTestFile( $file );
		}
	}

	/**
	 * @return AllTestsSuite
	 */
	public static function suite() {
		return new self;
	}

}