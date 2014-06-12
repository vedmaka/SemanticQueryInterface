<?php

/**
 * Class SemanticXMLSpecial
 *
 * @package SemanticXML
 */

class SemanticQueryInterfaceSpecial extends SpecialPage {

	function __construct() {
		parent::__construct( 'SemanticQueryInterface', 'usesemanticxml' );
	}

	public function execute( $params ) {

		$out = $this->getOutput();
		$out->setHTMLTitle( wfMessage('semanticxml')->text() );

		//test functions here

		$store = smwfGetStore();

		/* Get all page with property and printout prop value */
		$propertyValue = SMWPropertyValue::makeUserProperty('MyProp1');
		$property = new SMWSomeProperty( $propertyValue->getDataItem(), new SMWThingDescription );
		$property->addPrintRequest( new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, null, $propertyValue ) );
		$query = new SMWQuery( $property );

		$result = $store->getQueryResult( $query );
		$allResults = $result->getResults();

		/* Iterate threw results */
		/** @var SMWResultArray[] $allPrints */
		while( $allPrints = $result->getNext() ) {
			/** @var Array $value array of property values (SMWDIWikiPage, etc)*/
			$value = $allPrints[0]->getContent();
		}

		/* Get all pages with property value */
		$stringValue = new SMWStringValue('string');
		$stringValue->setUserValue('MyProp1ValueX');

		$desc = new SMWValueDescription($stringValue->getDataItem());
		$prop = SMWPropertyValue::makeProperty('MyProp1');
		$queryDesc = new SMWSomeProperty($prop->getDataItem(), $desc);

		$query = new SMWQuery( $queryDesc );

		$result = $store->getQueryResult( $query );
		$allResults = $result->getResults();

		/* Get all pages with conjunction */
		$stringValue = new SMWStringValue('string');
		$stringValue->setUserValue('MyProp1Value');

		$desc = new SMWValueDescription($stringValue->getDataItem());
		$prop = SMWPropertyValue::makeProperty('MyProp1');
		$stringDesc = new SMWSomeProperty( $prop->getDataItem(), $desc );

		$pageValue = SMWWikiPageValue::makePage('MyProp1Value2', NS_MAIN);
		$desc2 = new SMWValueDescription($pageValue->getDataItem());
		$property = SMWPropertyValue::makeProperty('MyProp1');
		$pageDesc = new SMWSomeProperty($property->getDataItem(), $desc2);

		$queryDesc = new SMWConjunction( array( $stringDesc, $pageDesc ) );

		$query = new SMWQuery( $queryDesc );
		$result = $store->getQueryResult( $query );
		$allResults = $result->getResults();

		$query = new SMWQuery( $stringDesc );
		$result = $store->getQueryResult( $query );
		$allResults = $result->getResults();

		$query = new SMWQuery( $pageDesc );
		$result = $store->getQueryResult( $query );
		$allResults = $result->getResults();


		/* Test with property */
		$prop = SMWDIProperty::newFromUserLabel('MyProp1');

		$val = new SMWWikiPageValue('page');
		$val->setUserValue('MyProp1Value');

		$desc = new SMWValueDescription( $val->getDataItem() );
		$queryDesc = new SMWSomeProperty( $prop, $desc );

		$query = new SMWQuery( $queryDesc );
		$result = $store->getQueryResult( $query );
		$allResults = $result->getResults();

		/* ========================================================= CLASS TEST STARTS HERE! ==================== */

		/* Test with Semantic Query class */

		$sq = new \SQI\SemanticQueryInterface();
		$sq->condition( array('MyProp1') );
		$sq->execute();
		$result = $sq->getResult();

		/* Test with Semantic Query class with value */

		$sq = new \SQI\SemanticQueryInterface();
		$sq->condition( array('MyProp1','MyProp1Value') );
		$sq->execute();
		$result = $sq->getResult();

		/* Test with Semantic Query class with conjunction */

		$sq = new \SQI\SemanticQueryInterface();
		$sq->condition( array('MyProp1') )
			->condition( array('MyProp2') )
			->condition( array('MyProp3') )
			->condition( array('MyProp4') );
		$sq->execute();
		$result = $sq->getResult();

		/* Test with target page */
		$sq = new \SQI\SemanticQueryInterface();
		$sq->from('QueryMe3');
		$sq->execute();
		$result = $sq->getResult();

		/* Test with some property value */
		$sq = new \SQI\SemanticQueryInterface();
		$sq->condition('PropA')->condition('PropB');
		$sq->execute();
		$result = $sq->toArray();

		/* Query: {{#ask: [[PropA::+]] [[PropB::ValueB3]] |?PropA |?PropB }} */
		$sq = new \SQI\SemanticQueryInterface();
		$sq->condition('PropA')->condition( array('PropB','ValueB3') );
		$sq->execute();
		$result = $sq->toArray();

		/* Query: {{#ask: [[QueryMe4]] |?PropA |?PropB }} */
		$sq = new \SQI\SemanticQueryInterface();
		$sq->from('QueryMe4')->printout('PropA')->printout('PropB');
		$sq->execute();
		$result = $sq->toArray();

		/* Query: {{#ask: [[QueryMe4]] |?PropA |?PropB }} with flattern values */
		$sq = new \SQI\SemanticQueryInterface(
			array(
				'flat_prop_vals' => true
			) );
		$sq->from('QueryMe4')->printout('PropA')->printout('PropB');
		$sq->execute();
		$result = $sq->toArray();

		/* Query: {{#ask: [[Кюрими5]] }} */
		$sq = new \SQI\SemanticQueryInterface();
		$sq->from('Кюрими5');
		$sq->execute();
		$result = $sq->toArray();

		/* Query: {{#ask: [[Category::Querable]] }} */
		$sq = new \SQI\SemanticQueryInterface( array('flat_prop_vals' => true) );
		$sq->category('Querable')->condition('NotExist');
		$sq->execute();
		$result = $sq->toArray();

		/* Testing subobjects like they set in PageSchemas now */
		$sq = new \SQI\SemanticQueryInterface();
		$result = $sq->condition('Список организаций относится к','TestSub')->printout('*')->execute()->toArray();

		$sq = new \SQI\SemanticQueryInterface();
		$sq->condition('SBList related to','TestSub')->printout('*')->execute();
		$result = $sq->toArray();

	}


}