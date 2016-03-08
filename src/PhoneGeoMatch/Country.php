<?php 

namespace PhoneGeoMatch;

use Mongo;
use Maps;
use libphonenumber\prefixmapper;
use libphonenumber\DefaultMetadataLoader;
use PhoneGeoApp;

class Country{

	public $name;
	
	public $code;
	
	public static $prefix;

	public $coordinates;

	public static $collectionName = 'Countries';

	public $connection; 
	
	public function __construct( $collectionName = '' ){
		
		if( empty( $collectionName ) ){
			$collectionName = static::$collectionName;
		}

		$this->setConnection( $collectionName );
	}

	public function setConnection( $collectionName ){
		$this->connection = Mongo\MongoHandler::get()->setCollection( $collectionName );
	}

	public function query( $field, $value, $return = array() ){
		
		$args = array( $field =>  $value );
		$result = $this->connection->queryColl( $args, $return	 );

		return $result;
	}

	public function queryOne( $field, $value, $return = array() ){

		$args = array( $field  =>  $value );
		$result = $this->connection->queryOnlyByOneResult( $args, $return );
		
		return $result;
	}

	public function save( $country ){
		
		$save = false;
		$existsCountry = self::query( 'prefix',  $country['prefix'] );
		
		if( empty( $existsCountry ) ){
			
			if( !isset(  $country['prefix'] ) ){
				$country['prefix'] = self::getPrefixByCode($country['code']);
			}

			$country['code'] = strtolower($country['code']);
			$this->connection->insertData($country);
			$save = true;
		}

		return $save;
		
	}


	/**
    * Return Country Object if the prefix can identify in an unique way the country. Otherwise returns false
    *
    * @param int prefix to analyse
     *
    * @return Object or false
    * @access public
    */
	public static function getCountryObjByPrefix( $prefix ){

		$prefixIdentifiesCountry = true;
		
		if( ! in_array( $prefix,  Maps\CountriesTables::$allCountryPrefixes ) ){ 
			$prefixIdentifiesCountry = 1; 
		}
		else{
			
			$countriesWithThisPrefix = Maps\CountriesTables::$countryPrefixToCountryCodeMap[$prefix];
			$nbCountriesWithThisPrefix = count($countriesWithThisPrefix);
			
			if( $nbCountriesWithThisPrefix == 1 ){
				$country = array();
				$countryCode =  $countriesWithThisPrefix[0];
				if( isset(Maps\CountriesTables::$countryCodeToCountryNameMap[$countryCode] ) ){
				
					$countryName = Maps\CountriesTables::$countryCodeToCountryNameMap[$countryCode];
				
					$coordinates = self::getCoordinatesFromMaps($countryName, false);
					$country = array(
						'name' => $countryName, 
						'code' => $countryCode
					);
					$country = array_merge( $country, $coordinates );
					
				}
				return $country;

			}
			elseif( $nbCountriesWithThisPrefix > 1 ){ 
				$prefixIdentifiesCountry = 2; 
			}
		}

		return $prefixIdentifiesCountry;
	}

	/**
    * Return country coordinates. If the prefix belong to a country with states, then the first region of the file is a state, and so we get the state country with getAddressComponents.
    * If it is a country without states we try get the country name, searching first a region of it. Then we search again, now with the country name to get acurate country coordinates
    *
    * @param string region
    * @param bool find a country given a region
    * @return array country coordinates
    * @access public
    */
	public static function getCoordinatesFromMaps( $region, $getCountryGivenRegion ){

		$countryCoordinates = PhoneGeoApp\LoadGeoPhoneCountryData::getMapsApi()->getRegionCoordinates( $region, $getCountryGivenRegion );
		
		if( isset($countryCoordinates['callResponse'] ) ){
			if( $getCountryGivenRegion ){
				$prefix = (string)self::$prefix;
				//check if it is a prefix of a country that have states
				$countryWithStates = ( substr($prefix,0,1) == '1' || substr($prefix,0,2) == '86')  ? true : false;

				if( $countryWithStates){
					$addressCoordinates = PhoneGeoApp\LoadGeoPhoneCountryData::getMapsApi()->getAddressComponents($countryCoordinates['callResponse'], false);
				}
				else{
					$addressCoordinates = PhoneGeoApp\LoadGeoPhoneCountryData::getMapsApi()->getAddressComponents($countryCoordinates['callResponse'], $getCountryGivenRegion );
					$countryCoordinates = PhoneGeoApp\LoadGeoPhoneCountryData::getMapsApi()->getRegionCoordinates( $addressCoordinates['name'] );	
				}

				unset($countryCoordinates['callResponse']);
				$countryCoordinates = array_merge($countryCoordinates, $addressCoordinates);
			}
		}
		
		return $countryCoordinates;


	}

	/**
    * Static method that tries to find a code to the country name given
    * @param string country name
    * @return Exception or code if the country exists
    * @access public
    */
	public static function getCodeByName( $name = false ){
		
		$code = array_search( $name, Maps\CountriesTables::$countryCodeToCountryNameMap ); 
		
		if( !$code ){
			throw new Exception('Country not found');		
		}
		
		return $code;
	}


	/**
    * Static method that find the country prefix if a country code is given
    * @param string country code
    * @return Exception or code if the country exists
    * @access public
    */
	public static function getPrefixByCode( $countryCode = false ){

		$countryCode = strtoupper($countryCode);
		$prefix = false;
		foreach( Maps\CountriesTables::$countryPrefixToCountryCodeMap as $countryPrefix => $countryCodes ){
			if( in_array($countryCode, $countryCodes ) ){ 
				 $prefix = $countryPrefix; 
			}
		}	
		
		if( !$prefix ){
			throw new Exception('Country prefix not found');		
		}

		return $prefix;

	}


	public function createMongoRef( $query = array() ){
		$country = $this->connection->queryOnlyByOneResult($query);
		return $this->connection->createRef($country);
	}


	public static function getCountryRegionsFromTables( $dir, $prefix ){

		$mappingFileProvider =  new \libphonenumber\prefixmapper\MappingFileProvider( Maps\CountriesTables::$countriesDirectoriesMap );
		$file_name = $mappingFileProvider->getFileName($prefix ,$dir, '','');
		
		$metadataLoader = new \libphonenumber\DefaultMetadataLoader();
		$regions = $metadataLoader->loadMetadata( Maps\CountriesTables::$mapsDir . $file_name );

		return $regions;
		

	}

	public function updateCoordinates($docIdToUpdate, $newValue ){

		$valueUpdate = array( '$set' => array( 'coordinates' => $newValue ), '$unset' => array('fake' => true ) );
		$this->connection->updateColl( array('_id' => $docIdToUpdate ), $valueUpdate );
	
	}

}





?>