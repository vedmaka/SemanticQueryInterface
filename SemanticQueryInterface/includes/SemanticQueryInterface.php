<?php

namespace SQI;

use OOUI\Exception;
use SMW\DataValueFactory;
use SMW\Subobject;
use SMWDataItem;
use SMWDIError;
use SMWErrorValue as ErrorValue;
use SMWPropertyValue;
use SMWDataValue as DataValue;

/**
 * Class SemanticQueryInterface
 * @package SemanticQuery
 */
class SemanticQueryInterface {

	/** @var \SMWStore */
	protected $store;
	/** @var  array */
	protected $config;
	/** @var  string */
	protected $page;
	/** @var  array[] */
	protected $conditions;
	/** @var  string[] */
	protected $printouts;
	/** @var  string[] */
	protected $categories;
	/** @var  int */
	protected $limit;
	/** @var  int */
	protected $offset;
	/** @var string */
	protected $sortProperties;

	/** @var \SMWQueryResult */
	protected $result;

	/**
	 * Creates new class instance.
	 * Config array can be passed to override default options, array keys available:
	 * flat_prop_vals (false) - Fetch only last (first) property value instead of one element array;
	 * fetch_default_properties (true) - Fetch default semantic properties (like category) for every subject page;
	 * fetch_all_properties (false) - Fetch all subject properties and their values by default;
	 * @param null $config
	 */
	function __construct( $config = null ) {
		$this->reset( $config );
	}

	public function reset( $config = null ) {
		$this->page = null;
		$this->conditions = array();
		$this->printouts = array();
		$this->categories = array();
		$this->result = null;
		$this->limit = 1000;
		$this->offset = 0;
		$this->result = null;
		$this->sort = null;
		$this->sortProperties = null;
		/* Configuration Default */
		$this->config = array(
			//Fetch only last (first) property value instead of one element array
			'flat_prop_vals' => false,
			//Fetch default semantic properties (like category) for every subject page
			'fetch_default_properties' => true,
			//Fetch all subject properties and their values by default
			'fetch_all_properties' => false,
			//Flat results array: Only one (first) result will be returned instead of results array
			'flat_result_array' => false
		);
		if( $config !== null ) {
			$this->config = array_merge( $this->config, $config );
		}
		/* Semantic store */
		$this->store = smwfGetStore();
	}

	/**
	 * Set query results offset
	 * @param $offset
	 * @return $this
	 */
	public function offset( $offset ) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Set query results limit
	 * @param $limit
	 * @return $this
	 */
	public function limit( $limit ) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Set query sorting subject property
	 * @param string $sortProperty
	 * @param string $direction
	 * @return $this
	 */
	public function sort( $sortProperty = '', $direction = 'ASC' )
	{
		$sortProperty = SemanticUtils::stringToDbkey( $sortProperty );
		$this->sortProperties[$sortProperty] = $direction;
		// Add property to printouts
		if( $sortProperty != '' && !in_array( $sortProperty, $this->printouts ) ) {
			$this->printouts[] = $sortProperty;
		}
		return $this;
	}

	/**
	 * Apply some condition to query.
	 *
	 * @param array|string $condition should be array like (propertyName) | (propertyName,propertyValue)
	 * @param null         $conditionValue
	 * @param null         $comparator
	 *
	 * @return $this
	 */
	public function condition( $condition, $conditionValue = null, $comparator = null ) {
		if(!is_array($condition)) {
			if( $conditionValue !== null ) {
				//Lets handle free-way calling, why not?
				$condition = array( $condition, $conditionValue, $comparator );
			}else{
				$condition = array( $condition );
			}
		}
		$this->conditions[] = $condition;
		return $this;
	}

	/**
	 * Apply LIKE condition to query, where $value can contain '*' and '?' wildcard characters
	 *
	 * @param string    $property
	 * @param string    $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function like( $property, $value ) {
		return $this->condition( $property, $value, SMW_CMP_LIKE );
	}

	/**
	 * Apply default equality comparison to query
	 *
	 * @param string    $property
	 * @param string    $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function equals( $property, $value ) {
		return $this->condition( $property, $value );
	}

	/**
	 * Apply NOT LIKE condition to query, where $value can contain '*' and '?' wildcard characters
	 *
	 * @param string    $property
	 * @param string    $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function notLike( $property, $value ) {
		return $this->condition( $property, $value, SMW_CMP_NLKE );
	}

	/**
	 * Apply NOT EQUAL condition to query
	 *
	 * @param string    $property
	 * @param string    $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function not( $property, $value ) {
		return $this->condition( $property, $value, SMW_CMP_NEQ );
	}

	/**
	 * Apply LESS condition to query ( should be used with numeric/dates properties )
	 *
	 * @param string $property
	 * @param string $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function less( $property, $value ) {
		return $this->condition( $property, $value, SMW_CMP_LESS );
	}

	/**
	 * Apply LESS or EQUAL condition to query ( should be used with numeric/dates properties )
	 *
	 * @param string $property
	 * @param string $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function lessOrEqual( $property, $value ) {
		return $this->condition( $property, $value, SMW_CMP_LEQ );
	}

	/**
	 * Apply GREATER condition to query ( should be used with numeric/dates properties )
	 *
	 * @param string $property
	 * @param string $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function greater( $property, $value ) {
		return $this->condition( $property, $value, SMW_CMP_GRTR );
	}

	/**
	 * Apply GREATER or EQUAL condition to query ( should be used with numeric/dates properties )
	 *
	 * @param string $property
	 * @param string $value
	 *
	 * @return SemanticQueryInterface
	 */
	public function greaterOrEqual( $property, $value ) {
		return $this->condition( $property, $value, SMW_CMP_GEQ );
	}

	/**
	 * Adds property to be fetched and printed out, use * to print out all properties
	 * @param $printout
	 * @return $this
	 */
	public function printout( $printout ) {
		if( $printout == '*' ) {
			$this->config['fetch_all_properties'] = true;
			return $this;
		}
		if( is_array($printout) ) {
			foreach ( $printout as $pt ) {
				$this->printouts[] = $pt;
			}

		}else{
			$this->printouts[] = $printout;
		}
		return $this;
	}

	/**
	 * Sets query limitation to category(ies)
	 * @param $category
	 * @return $this
	 */
	public function category( $category ) {
		$this->categories[] = SemanticUtils::stringToDbkey($category);
		return $this;
	}

	/**
	 * Sets query limitation to specified page (title as string)
	 * @param $page
	 * @param bool $flatResult
	 * @return $this
	 */
	public function from( $page, $flatResult = false ) {
		if( $flatResult ) {
			$this->config['flat_result_array'] = true;
		}
		$this->page = SemanticUtils::stringToDbkey($page);
		return $this;
	}

	/**
	 * Executes the query. Not necessary to call directly, all methods execute query on demand.
	 * @return $this
	 */
	public function execute() {
		$queryDescription = $this->buildQuery();
		$query = new \SMWQuery( $queryDescription );
		$query->setOffset( $this->offset );
		$query->setLimit( $this->limit );
		if( $this->sortProperties ) {
			$query->sort = true;
			$query->sortkeys = $this->sortProperties;
		}
		$this->result = $this->store->getQueryResult( $query );
		return $this;
	}

	/**
	 * Return raw query result as SMWQueryResult object
	 * @return \SMWQueryResult
	 */
	public function getResult() {
		if( $this->result === null ) {
			$this->execute();
		}
		return $this->result;
	}

	/**
	 * Counts query results
	 * @return int
	 */
	public function count() {
		if( $this->result === null ) {
			$this->execute();
		}
		return $this->result->getCount();
	}

	/**
	 * Return query affected subjects as Titles
	 * @return \Title[]
	 */
	public function getResultSubjects() {
		if( $this->result === null ) {
			$this->execute();
		}
		$subjects = array();
		$result = $this->result->getResults();
		/** @var \SMWDIWikiPage $subject */
		foreach($result as $subject) {
			$title = $subject->getTitle();
			if( $subject->getSubobjectName() != '' ) {
				$subjects[] = $title;
				continue;
			}
			if(!in_array( $title, $subjects )) {
				$subjects[] = $title;
			}
		}

		return $subjects;
	}

	/**
	 * Main method to get query results. Converts raw semantic result to human readable array.
	 * //TODO: This method need slight refactoring about array keys organisation
	 * @param bool $stringifyPropValues cast all properties values types to string
	 * @return array
	 */
	public function toArray( $stringifyPropValues = false ) {

		if( $this->result === null ) {
			$this->execute();
		}

		$array = array();

		//Main mystery here, that if we have some printouts - $result->getNext will work,
		//but if there no printouts, only pages - method will return false!

		//Fill array with subjects
		$resultSubjects = $this->getResultSubjects();
		foreach($resultSubjects as $title) {
			$properties = array();

			//TODO: There should be more beautiful way to form array keys ...
			$arrayKey = $title->getArticleID() . ( ($title->getFragment()) ? '#'.trim($title->getFragment(),'_ ') : '' );

			//Fetch all subject properties if config set to true
			if( $this->config['fetch_all_properties'] ) {
				if( $title->getFragment() ) {
					$properties = SemanticUtils::getSubobjectProperties( $title );
				}else{
					$properties = SemanticUtils::getPageProperties( $title->getText(), $title->getNamespace() );
				}
				if( $this->config['flat_prop_vals'] ) {
					foreach ( $properties as &$property ) {
						if( is_array($property) && count($property) ) {
							$property = $property[0];
						}
					}

				}
			}
			//Push subject to array
			$array[$arrayKey] = array(
				'title' => $title,
				'properties' => $properties
			);
		}

		//If there is something to "print"
		$test = clone $this->result;
		$check = $test->getNext();
		if( $check !== false && count($check) ) {
			//We have something to "print"
			//Copy result object to iterate
			$result = clone $this->result;
			/** @var \SMWResultArray[] $row */
			while( $row = $result->getNext() ) {
				//Iterate through properties and subjects
				foreach( $row as $rowItem ) {
					$subject = $rowItem->getResultSubject();

					//TODO: There should be more beautiful way to form array keys ...
					$arrayKey = $subject->getTitle()->getArticleID() . ( ($subject->getSubobjectName()) ? '#'.trim($subject->getSubobjectName(),'_ ') : '' );

					/** @var \SMWDataItem[] $propValues */
					$propValues = $rowItem->getContent();
					$propName = $rowItem->getPrintRequest()->getLabel();
					$propName = SemanticUtils::dbKeyToString($propName);
					foreach($propValues as $propValue) {
						$value = SemanticUtils::getPropertyValue( $propValue, $stringifyPropValues );
						//If option enabled, flat every property except system arrays
						if( $this->config['flat_prop_vals'] && $propName != 'Categories' && $propName != 'SubcategoryOf' ) {
							$array[$arrayKey]['properties'][$propName] = $value;
						}else{
							$array[$arrayKey]['properties'][$propName][] = $value;
						}
					}
				}
			}
		}

		if( $this->config['flat_result_array'] && count($array) ) {
			return array_shift($array);
		}
		return $array;

	}

	/**
	 * Builds query from options set initially
	 * @return \SMWConjunction
	 */
	private function buildQuery() {
		$queryDescription = new \SMWThingDescription();
		$conditionDescriptions = array();

		//Target page
		if( $this->page !== null ) {
			$page = new \SMWWikiPageValue('_wpg');
			$page->setUserValue($this->page);
			$pageDescription = new \SMWValueDescription( $page->getDataItem() );
			$conditionDescriptions[] = $pageDescription;
		}

		//Create category scope
		if( count($this->categories) ) {
			foreach($this->categories as $category) {
				$categoryTitle = new \SMWDIWikiPage( $category, NS_CATEGORY, '' );
				$categoryDescription = new \SMWClassDescription($categoryTitle);
				$conditionDescriptions[] = $categoryDescription;
			}
		}

		//Create conditions array
		foreach($this->conditions as $condition) {
			$property = \SMWDIProperty::newFromUserLabel($condition[0]);
			$valueDescription = new \SMWThingDescription();

			// Handle custom comparator value
			$comparator = SMW_CMP_EQ;
			if( count($condition) > 2 && $condition[2] !== null ) {
				$comparator = $condition[2];
			}

			if( isset($condition[1]) ) {
				//SMW >= 1.9
				if( class_exists('SMW\DataValueFactory') )
				{
					// In some cases we will receive error probably because of PHP-version
					$value = $this->newPropertyValue( $condition[0], $condition[1] );
				}else{
				//SMW < 1.9
					$prop = \SMWDIProperty::newFromUserLabel($condition[0]);
					$value = \SMWDataValueFactory::newPropertyObjectValue( $prop, $condition[1] );
				}
				$valueDescription = new \SMWValueDescription( $value->getDataItem(), null, $comparator );
			}
			$description = new \SMWSomeProperty( $property, $valueDescription );
			//Add condition properties to printouts
			if(!in_array($condition[0],$this->printouts)) {
				$this->printouts[] = $condition[0];
			}
			//Store description in conditions array
			$conditionDescriptions[] = $description;
		}

		//Build up query
		if( count($conditionDescriptions) > 1 ) {
			//Conjunction
			$queryDescription = new \SMWConjunction( $conditionDescriptions );
		}else{
			//Simple
			$queryDescription = $conditionDescriptions[0];
		}

		//Create printouts array if was not set
		if( (count($this->printouts) == 0) && ($this->page !== null) ) {
			//Everything
			$propList = SemanticUtils::getPageProperties( $this->page );
			$propList = array_keys($propList);
			$this->printouts = $propList;
		}

		//Add printouts to query
		foreach( $this->printouts as $printout ) {
			$printProp = \SMWPropertyValue::makeUserProperty($printout);
			$queryDescription->addPrintRequest( new \SMWPrintRequest( \SMWPrintRequest::PRINT_PROP, $printout, $printProp ) );
		}

		//If config variable set, fetch SMW system properties also
		if( $this->config['fetch_default_properties'] ) {
			//Fetch every subject categories
			$printProp = \SMWPropertyValue::makeProperty('_INST');
			$queryDescription->addPrintRequest( new \SMWPrintRequest( \SMWPrintRequest::PRINT_PROP, 'Categories', $printProp ) );
			//Fetch every subject subcategory of
			$printProp = \SMWPropertyValue::makeProperty('_SUBC');
			$queryDescription->addPrintRequest( new \SMWPrintRequest( \SMWPrintRequest::PRINT_PROP, 'SubcategoryOf', $printProp ) );
			//Fetch every subject modification date
			$printProp = \SMWPropertyValue::makeProperty('_MDAT');
			$queryDescription->addPrintRequest( new \SMWPrintRequest( \SMWPrintRequest::PRINT_PROP, 'ModificationDate', $printProp ) );
			//Fetch every subject creation date
			$printProp = \SMWPropertyValue::makeProperty('_CDAT');
			$queryDescription->addPrintRequest( new \SMWPrintRequest( \SMWPrintRequest::PRINT_PROP, 'CreationDate', $printProp ) );
			//Fetch every subject last editor user
			$printProp = \SMWPropertyValue::makeProperty('_LEDT');
			$queryDescription->addPrintRequest( new \SMWPrintRequest( \SMWPrintRequest::PRINT_PROP, 'LastEditor', $printProp ) );
		}

		return $queryDescription;
	}

	/*
	 * Merged from DataValueFactory
	 */

	public function newPropertyValue( $propertyName, $valueString,
		$caption = false, \SMW\DIWikiPage $contextPage = null ) {

		// Enforce upper case for the first character on annotations that are used
		// within the property namespace in order to avoid confusion when
		// $wgCapitalLinks setting is disabled
		if ( $contextPage !== null && $contextPage->getNamespace() === SMW_NS_PROPERTY ) {
			$propertyName = ucfirst( $propertyName );
		}

		$propertyDV = SMWPropertyValue::makeUserProperty( $propertyName );

		if ( !$propertyDV->isValid() ) {
			return $propertyDV;
		}

		if ( !$propertyDV->canUse() ) {
			return new \SMW\ErrorValue(
				$propertyDV->getPropertyTypeID(),
				wfMessage( 'smw-datavalue-property-restricted-use', $propertyName )->inContentLanguage()->text(),
				$valueString,
				$caption
			);
		}

		$propertyDI = $propertyDV->getDataItem();

		if ( $propertyDI instanceof \SMW\SMWDIError ) {
			return $propertyDV;
		}

		if ( $propertyDI instanceof \SMW\DIProperty && !$propertyDI->isInverse() ) {
			$dataValue = DataValueFactory::newPropertyObjectValue(
				$propertyDI,
				$valueString,
				$caption,
				$contextPage
			);
		} elseif ( $propertyDI instanceof \SMW\DIProperty && $propertyDI->isInverse() ) {
			$dataValue = new \SMW\ErrorValue( $propertyDV->getPropertyTypeID(),
				wfMessage( 'smw_noinvannot' )->inContentLanguage()->text(),
				$valueString,
				$caption
			);
		} else {
			$dataValue = new \SMW\ErrorValue(
				$propertyDV->getPropertyTypeID(),
				wfMessage( 'smw-property-name-invalid', $propertyName )->inContentLanguage()->text(),
				$valueString,
				$caption
			);
		}

		if ( $dataValue->isValid() && !$dataValue->canUse() ) {
			$dataValue = new \SMW\ErrorValue(
				$propertyDV->getPropertyTypeID(),
				wfMessage( 'smw-datavalue-restricted-use', implode( ',', $datavalue->getErrors() ) )->inContentLanguage()->text(),
				$valueString,
				$caption
			);
		}

		return $dataValue;
	}

}