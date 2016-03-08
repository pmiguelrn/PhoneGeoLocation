<?php

require __DIR__ . '/../../vendor/autoload.php';



class CountriesTablesTest extends \PHPUnit_Framework_TestCase{
	
	public function testTablesSetting(){
		
		
		Maps\CountriesTables::loadAllTables();

		$this->assertArrayHasKey('PT', Maps\CountriesTables::$countryCodeToCountryNameMap);
		$this->assertArrayHasKey('pt', Maps\CountriesTables::$countriesDirectoriesMap);
		$this->assertArrayHasKey('351', Maps\CountriesTables::$countryPrefixToCountryCodeMap);

	}

	

}