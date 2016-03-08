<?php

require __DIR__ . '/../../vendor/autoload.php';

class CountriesClassTest extends \PHPUnit_Framework_TestCase{
	
	public function test_getCountryObjByPrefix_validPrefix_returnObject(){


		$prefix = 351;
		$keysExpected = array('name', 'code', 'coordinates'); 
		$countryObj = PhoneGeoMatch\Country::getCountryObjByPrefix($prefix);
		
		$objEqual = ( $keysExpected == array_intersect( $keysExpected, array_keys($countryObj) ) ) ? true : false;
		$this->assertTrue($objEqual);

	}

	public function test_getCountryObjByPrefix_inValidPrefix_returnFlag(){


		$prefix = 7;
		$keysExpected = array('name', 'code', 'coordinates'); 
		$countryObj = PhoneGeoMatch\Country::getCountryObjByPrefix($prefix);
		
		$this->assertEquals($countryObj, 2);

	}

	

	public static $regions = array( 
								array( 
									'name' =>  'Lisbon', 
									'country' => 'Portugal' 
									),
									array( 
									'name' =>  'Ontario, ON', 
									'country' => 'Canada' 
									)  
								);

		

	public function test_getCoordinatesFromMaps_validName_returnOnlyCoordinates(){

		foreach(self::$regions as $region ){
			
			$countryObj = PhoneGeoMatch\Country::getCoordinatesFromMaps( $region['name'], false );
			$onlyCoordinates = (count($countryObj) == 1 && !empty($countryObj['coordinates'] ) ) ? true : false;
			$this->assertTrue($onlyCoordinates);
		
		} 
		
		

	}

	public function test_getCoordinatesFromMaps_validName_returnCountry(){
		
		$keysExpected = array('name', 'code', 'coordinates');
		
		foreach(self::$regions as $region){

			$countryObj = PhoneGeoMatch\Country::getCoordinatesFromMaps($region['name'], true );
			$objEqual = ( $keysExpected == array_intersect( $keysExpected, array_keys($countryObj) ) ) ? true : false; 
			$this->assertTrue($objEqual );
			$this->assertEquals($region['country'], $countryObj['name']);
		
		}

	}

}