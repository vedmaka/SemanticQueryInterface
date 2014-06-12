<?php

namespace SQI;

use SMW\DataValueFactory;
use SMW\Subobject;

/**
 * awdawdawd ''awdawdaw'' awdawdawd '''dawdawdawad'''awdawdawd
 * awdawd
 * awdawdawdawdawdawd
 * awdawdawdawdaw
 * dawdawdawd
 * _markdown_
 * *dfawdawd
 * *dawdawdawd
 * **dawdawdawd
 *
 * The section after the long description contains the tags; which provide
 * structured meta-data concerning the given element.
 *
 * Class SemanticUtils
 * @package SemanticQuery
 *
 */
class SemanticUtils {

	/**
	 * Short desc
	 *
	 * Long _desc_ of '''block''' *dawd*
	 *
	 * `$x=2; $x-set();`
	 *
	 *     dawd_Dwadawd_dawdawd
	 *
	 * #dadawdawd
	 *
	 * ##dawdawdawd
	 *
	 * >dawdawdad
	 *
	 * [dawawd](http://ya.ru)
	 *
	 * [awdawdawd][tag]
	 *
	 * [tag]: http://ya.ru
	 *
	 * ![alt](http://ya.ru)
	 *
	 * @param \SMWDataItem $di
	 * @param bool $toString
	 * @return array|bool|int|mixed|\numeric|string
	 */
	public static function getPropertyValue( \SMWDataItem $di, $toString = false ) {
		switch($di->getDIType()) {
			case \SMWDataItem::TYPE_BLOB:
				/** @var \SMWDIBlob $di */
				return $di->getString();
				break;
			case \SMWDataItem::TYPE_NUMBER:
				/** @var \SMWDINumber $di */
				return $di->getNumber();
				break;
			case \SMWDataItem::TYPE_WIKIPAGE:
				/** @var \SMWDIWikiPage $di */
				if($toString) {
					return $di->getTitle()->getText();
				}
				return $di->getTitle();
				break;
			case \SMWDataItem::TYPE_TIME:
				/** @var \SMWDITime $di */
				return $di->getMwTimestamp();
				break;
			case \SMWDataItem::TYPE_GEO:
				/** @var \SMWDIGeoCoord $di */
				if($toString) {
					return implode(',',$di->getCoordinateSet());
				}
				return $di->getCoordinateSet();
				break;
			case \SMWDataItem::TYPE_ERROR:
				/** @var \SMWDIError $di */
				if($toString) {
					return implode(',',$di->getErrors());
				}
				return $di->getErrors();
				break;
			case \SMWDataItem::TYPE_URI:
				/** @var \SMWDIUri $di */
				return $di->getURI();
				break;
			case \SMWDataItem::TYPE_NOTYPE:
				/** @var \SMWDIBlob $di */
				return false;
				break;
			default:
				return $di->getSerialization();
				break;
		}
	}

	public static function getSubobjectProperties( \Title $title, $properties = array('*') ) {
		$propValues = array();

		//Single property to single item array
		if( !is_array($properties) ) {
			$properties = array( $properties );
		}

		/** @var \SMWSql3StubSemanticData $subObjectSemanticData */
		//$subObjectSemanticData = smwfGetStore()->getSemanticData( $subObject->getSemanticData()->getSubject() );
		$wikiPage = new \SMWDIWikiPage( $title->getDBkey(), $title->getNamespace(), '', '_'.trim($title->getFragment()) );
		$subObjectSemanticData = smwfGetStore()->getSemanticData( $wikiPage );

		//Fetch all props from page
		if ( $properties[0] == '*' ) {
			$propList = $subObjectSemanticData->getProperties();
			$properties = array();
			/** @var \SMWDIProperty $propDI */
			foreach($propList as $propDI) {
				$properties[] = $propDI->getLabel();
			}
		}

		//Fetch all props values from smwfStore
		foreach( $properties as $property ) {

			$property = self::stringToDbkey($property);

			if(empty($property) || $property == '') {
				continue;
			}

			$propertyDi = new \SMWDIProperty( $property );
			//$pageDi = new \SMWDIWikiPage( $subObject->getSemanticData(), $title->getNamespace(), '' );
			$valueDis = $subObjectSemanticData->getPropertyValues( $propertyDi );

			//If we have at least one value
			if( count($valueDis) ) {

				//Fetch all Dv values
				foreach( $valueDis as $valueDi ) {

					//SMW >= 1.9
					if( class_exists('SMW\DataValueFactory') ) {
						/** @var \SMWDataValue $valueDv */
						$valueDv = DataValueFactory::newDataItemValue( $valueDi, $propertyDi );
					}else{
						//SMW < 1.9
						$valueDv = \SMWDataValueFactory::newDataItemValue( $valueDi, $propertyDi );
					}

					$propValues[self::dbKeyToString($property)][] = $valueDv->getWikiValue();
				}

			}else{
				$propValues[self::dbKeyToString($property)] = array();
			}

		}

		return $propValues;

	}

	/**
	 * Get property values of requested wiki-page.
	 * By default * - reutrns all properties. Array of properties to return can be passed.
	 *
	 * @param string $title
	 * @param int $namespace
	 * @param array $properties
	 * @return array
	 */
	public static function getPageProperties( $title, $namespace = NS_MAIN , $properties = array('*') ) {

		$propValues = array();

		$title = self::stringToDbkey($title);

		//Single property to single item array
		if( !is_array($properties) ) {
			$properties = array( $properties );
		}

		//Fetch all props from page
		if ( $properties[0] == '*' ) {
			$propList = smwfGetStore()->getProperties( new \SMWDIWikiPage( $title, $namespace, '' ) );
			$properties = array();
			/** @var \SMWDIProperty $propDI */
			foreach($propList as $propDI) {
				$properties[] = $propDI->getLabel();
			}
		}

		//Fetch all props values from smwfStore
		foreach( $properties as $property ) {

			$property = self::stringToDbkey($property);

			if(empty($property) || $property == '') {
				continue;
			}

			$propertyDi = new \SMWDIProperty( $property );
			$pageDi = new \SMWDIWikiPage( $title, $namespace, '' );
			$valueDis = smwfGetStore()->getPropertyValues( $pageDi, $propertyDi );

			//If we have at least one value
			if( count($valueDis) ) {

				//Fetch all Dv values
				/** @var \SMWDataItem $valueDi */
				foreach( $valueDis as $valueDi ) {

					//SMW >= 1.9
					if( class_exists('SMW\DataValueFactory') ) {
						/** @var \SMWDataValue $valueDv */
						$valueDv = DataValueFactory::newDataItemValue( $valueDi, $propertyDi );
					}else{
						//SMW < 1.9
						$valueDv = \SMWDataValueFactory::newDataItemValue( $valueDi, $propertyDi );
					}

					$propValues[self::dbKeyToString($property)][] = $valueDv->getWikiValue();
				}

			}else{
				$propValues[self::dbKeyToString($property)] = array();
			}

		}

		return $propValues;
	}

	/**
	 *
	 * Wrap for getPageProperties in case of single property.
	 * Returns array of request property values
	 *
	 * @param string $title
	 * @param int $namespace
	 * @param string $property
	 * @return Array
	 */
	public static function getPageProperty( $title, $namespace, $property ) {

		$value = SMWBridge::getPageProperties( $title, $namespace, $property );
		return array_shift($value);

	}

	public static function setPageProperties( $title, $namespace = NS_MAIN, $properties = array() ) {

		if ( !count($properties) ) return;

		$semandticData = smwfGetStore()->getSemanticData( new SMWDIWikiPage( $title, $namespace, '' ) );

		foreach( $properties as $propertyName => $propertyValue ) {

			$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );
			$propertyDi = $propertyDv->getDataItem();

			$result = SMWDataValueFactory::newPropertyObjectValue(
				$propertyDi,
				$propertyValue,
				$propertyName,
				$semandticData->getSubject()
			);
			$semandticData->addPropertyObjectValue( $propertyDi, $result->getDataItem() );

		}

		smwfGetStore()->updateData( $semandticData );
		$a = Title::newFromText($title)->getDBkey();
		smwfGetStore()->refreshData( Title::newFromText($title)->getArticleID(), 1 );

		//echo '<pre>';print_r($result);echo '</pre>';

		return $result->isValid();

	}

	public static function setPageProperty( $title, $namespace, $property, $value ) {

		$result = SMWBridge::setPageProperties( $title, $namespace, array($property => $value) );
		return true;

	}

	/**
	 * Makes Title from string if string passed. Title also can be passed.
	 *
	 * @param string $title
	 * @param int $namespace
	 * @return Title
	 */
	protected static function prepareTitle( $title, $namespace = NS_MAIN ) {

		if ( $title instanceof Title ) {
			//good
		}else{
			//Make title from text
			$title = Title::newFromText( $title, $namespace );
		}

		return $title;

	}

	/**
	 * Converts string to dbkey format
	 * copied from Title
	 * @param $string
	 * @return string
	 */
	public static function stringToDbkey( $string ) {
		# Strip Unicode bidi override characters.
		# Sometimes they slip into cut-n-pasted page titles, where the
		# override chars get included in list displays.
		$dbkey = preg_replace( '/\xE2\x80[\x8E\x8F\xAA-\xAE]/S', '', $string );

		# Clean up whitespace
		# Note: use of the /u option on preg_replace here will cause
		# input with invalid UTF-8 sequences to be nullified out in PHP 5.2.x,
		# conveniently disabling them.
		$dbkey = preg_replace( '/[ _\xA0\x{1680}\x{180E}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+/u', '_', $dbkey );
		$dbkey = trim( $dbkey, '_' );
		return $dbkey;
	}

	/**
	 * Converts dbkey to string format
	 * copied from Title
	 * @param $dbkey
	 * @internal param $string
	 * @return string
	 */
	public static function dbKeyToString( $dbkey ) {
		return str_replace('_',' ',$dbkey);
	}

}