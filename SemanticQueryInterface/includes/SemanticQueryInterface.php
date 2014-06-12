<?php

namespace SQI;

use SMW\DataValueFactory;
use SMW\Subobject;

/**
 * Class SemanticQueryInterface
 * @package SemanticQuery
 */
class SemanticQueryInterface {

	/** @var  \SMWStore */
	protected $store;
	/** @var  Array */
	protected $config;
	/** @var  string */
	protected $page;
	/** @var  Array[] */
	protected $conditions;
	/** @var  string[] */
	protected $printouts;
	/** @var  string[] */
	protected $categories;
	/** @var  int */
	protected $limit;
	/** @var  int */
	protected $offset;

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
	 */
	public function offset( $offset ) {
		$this->offset = $offset;
	}

	/**
	 * Set query results limit
	 * @param $limit
	 */
	public function limit( $limit ) {
		$this->limit = $limit;
	}

	/**
	 * Apply some condition to query.
	 * @param Array $condition should be array like (propertyName) | (propertyName,propertyValue)
	 * @param null $conditionValue
	 * @return $this
	 */
	public function condition( $condition, $conditionValue = null ) {
		if(!is_array($condition)) {
			if( $conditionValue !== null ) {
				//Lets handle free-way calling, why not?
				$condition = array( $condition, $conditionValue );
			}else{
				$condition = array( $condition );
			}
		}
		$this->conditions[] = $condition;
		return $this;
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
			if( isset($condition[1]) ) {
				//SMW >= 1.9
				if( class_exists('SMW\DataValueFactory') ) {
					/** @var \SMWDataValue $value */
					$value = DataValueFactory::newPropertyValue( $condition[0], $condition[1] );
				}else{
				//SMW < 1.9
					$prop = \SMWDIProperty::newFromUserLabel($condition[0]);
					$value = \SMWDataValueFactory::newPropertyObjectValue( $prop, $condition[1] );
				}
				$valueDescription = new \SMWValueDescription( $value->getDataItem() );
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

}