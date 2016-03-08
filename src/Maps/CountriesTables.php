<?php 

namespace Maps;
use libphonenumber\CountryCodeToRegionCodeMap as libphonenumber;
use PhoneGeoMatch;

class CountriesTables{
	
	public static $mapsDir;

	public static $phoneGeoDataDir =  __DIR__ . '/../data/';
	
	public static $countryCodeToCountryNameMap;
	
	public static $countriesDirectoriesMap;

	public static $countryPrefixToCountryCodeMap;

	public static $allCountryPrefixes;

	public static $languages;

	
	public static function loadAllTables(){
		
		self::$mapsDir =  __DIR__ . '/../../../vendor/giggsey/libphonenumber-for-php/src/libphonenumber/geocoding/data/';
		self::loadCountryCodeToCountryNameTable();
		self::loadCountriesDirectoriesTable();
		self::loadCountryPrefixToCountryCodeTable();
	}

	public static function loadCountryCodeToCountryNameTable(){
		self::$countryCodeToCountryNameMap = require  self::$phoneGeoDataDir .'CountryCodes.php';
	}


	public static function loadAllContinentCountries( $continent = ''){
		
		if( empty( $continent ) ){
			return false;
		}

		$pathToFile = self::$phoneGeoDataDir . $continent . 'Countries.php';
		return require $pathToFile;
	}

	public static function loadAllCountries(){
	
		$files = self::getAllFilesWithCountriesPrefixes('en');
		$allPrefixes = array();

		foreach( $files as $file ){
			
			$file = explode('.', $file);
			array_push( $allPrefixes, $file[0]);
		
		}

		return $allPrefixes;
	}


	public function loadAllOnePrefixCountries(){

		$files = self::getAllFilesWithCountriesPrefixes('en');
		$onePrefixes = array();

		foreach( $files as $file ){
			
			$file = explode('.', $file);
			if( $file[0][0] == '1'){
				array_push( $onePrefixes, $file[0]);
			}
		}

		return $onePrefixes;

	}

	public static function loadAllEuropeCountries(){
	
		$europeCountries = self::loadAllContinentCountries('Europe');
		$prefixes = array();
		
		foreach( $europeCountries as $country ){
			
			try {
	    		$code = PhoneGeoMatch\Country::getCodeByName($country);
				
			} catch (Exception $e) {
	    		echo 'Exceção capturada: ',  $e->getMessage(), "\n";
	    		continue;
			}

			$prefix = Country::getPrefixByCode($code);
			$prefixes[$country] = $prefix;
		}

		return $prefixes;
	}

	public static function loadCountriesDirectoriesTable(){
		
		$mapPath = self::$mapsDir . 'Map.php';
		self::$countriesDirectoriesMap = require $mapPath;
	
	}

	public static function loadCountryPrefixToCountryCodeTable(){
		
		self::$countryPrefixToCountryCodeMap = libphonenumber::$countryCodeToRegionCodeMap;
		self::$allCountryPrefixes = array_keys(self::$countryPrefixToCountryCodeMap);
	
	}


	public static function getAllFilesWithCountriesPrefixes($language){
		
		$files = scandir( CountriesTables::$mapsDir . $language );
		
		foreach( $files as $key => $file ){
			
			$filename = explode('.', $file );
			if( ! is_numeric ( $filename[0] ) ){
				unset($files[$key]);
			}

		}

		return $files;

	}

	

}

?>