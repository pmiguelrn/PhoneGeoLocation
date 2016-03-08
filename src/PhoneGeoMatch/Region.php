<?php

namespace PhoneGeoMatch;
use LoadGeoPhoneCountryData;
use Mongo;
use PhoneGeoApp;


class Region{

	public $countryName;

	public static $countryRef;

	public static $collectionName = 'Regions';

	public static $collectionConn = Mongo\MongoHandler::get()->setCollection( self::$collectionName );

	private static $collSchema = array(

		'name' => 'string',
		'country.$id' => 'object',
		'coordinates' => 'object',
	);


	public static function setCollectionConn(){

		if( !empty( self::$collectionConn ) ){
			return;
		}
		
		self::$collectionConn = Mongo\MongoHandler::get();
		self::$collectionConn->setCollection( self::$collectionName );
		
	}

	/**
    * Transform an array that associates prefixes to regions to an array that associates regions to  prefixes ( one region to all its prefixes)
    *
    * @param array with prefixes => regions
     *
    * @return array with regions => prefixes
    * @access public
    */
	public static function getAllRegionPrefixes( $regions = array()){

		$transform = array();
		
		foreach( $regions as $prefix => $region ){
			$prefix = (string)$prefix;
			
			if( isset( $transform[$region] ) ){
				array_push($transform[$region], $prefix );
			}
			else{
				$transform[$region] = array($prefix);
			}
			
		}
		return 	$transform;	
	}

	public static function getCoordinates($region){

		$countryName = '';
		$regionCountry = Country::queryOne('_id', $region['country']['$id']);
		
		if( isset( $regionCountry['name'] ) ){
			$countryName = $regionCountry['name'];
		}

		$queryCoordinates = PhoneGeoApp\LoadGeoPhoneCountryData::getMapsApi()->getRegionCoordinates( $region['name'] . ',' . $countryName);
		$region = array_merge( $region, $queryCoordinates );
			
		return $region;
	}


	public static function createMongoRef( $query = array() ){
		
		self::setCollectionConn();
		$region  = self::$collectionConn->queryOnlyByOneResult( $query );
		return self::$collectionConn->createRef($region);
	
	}

	public static function validateRegion( $region ){

		if( !isset( $region['country'] ) || !isset( $region['name'] ) ){
			return false;
		}
		return true;
	}

	public static function save( $region ){
		
		$save = false;
		self::setCollectionConn();
		
		if( self::validateRegion($region) ){
			$region = self::getCoordinates($region);
			self::$collectionConn->insertData($region);
			$save = true;
		}
		
		return $save;
		
	}


	public static function organizeRegionsByCountry( $regions ){

		$regionsOrganizedByCountry = array();

		foreach( $regions as $region ){

			$countryMongoId = $region['country']['$id']->__toString();
			$regionInfo = array(
							'name' => $region['name'], 
							'_id' => $region['_id'] 
							);
			
			if( !isset($regionsOrganizedByCountry[$countryMongoId] ) ){
				$regionsOrganizedByCountry[$countryMongoId] = array($regionInfo);
			}
			else{
				array_push($regionsOrganizedByCountry[$countryMongoId], $regionInfo);
			}
		}

		return $regionsOrganizedByCountry;

	}

	public static function query( $fields, $values, $operator = '$and', $return = array() ){

		
		$args = Mongo\MongoHandler::setArgsArratToQuery($fields, $values, $operator);
		$query = self::$collectionConn->queryColl( $args, $return );

		return $query;
	}

	public static function updateCoordinates($docIdToUpdate, $newValue ){

		$valueUpdate = array( '$set' => array( 'coordinates' => $newValue ), '$unset' => array('fake' => true ) );
		self::$collectionConn->updateColl( array('_id' => $docIdToUpdate ), $valueUpdate );
	
	}

}



?>