<?php

namespace Maps;

class MapsHandler{

	public $apiUrl;

	public static $api;

	private static $apiKey;

	public static $allowedApis = array('GoogleMaps', 'MapQuest');

	public function __construct(){
		$this->setApi();
	}
	
	public static function apiConfig($config = array()){
		
		if( isset( $config['api'] ) ){
			self::$api = $config['api'];
			self::$apiKey = $config['key'];
		}
		
	}

	public function setApi(){
		call_user_func_array( array( $this, 'set' . MapsHandler::$api . 'ApiCall'), array() );
	}

	public static function setApiKey( $key ){
		self::$apiKey = $key;
	}

	public function setGoogleMapsApiCall(){
		$this->apiUrl = 'https://maps.google.com/maps/api/geocode/json?address={%s}&sensor=true';//&key=' . MapsHandler::$apiKey;
	}

	public function setMapQuestApiCall(){
		$this->apiUrl = 'http://open.mapquestapi.com/geocoding/v1/address?key=' . MapsHandler::$apiKey . '&location=%s';
	}

	/**
    * Receive the query string to the maps api
    *
    * @param string maps query
     *
    * @return json with the api request result
    * @access public
    */
	public function madeCurlResquest($queryToMaps){

		$url = sprintf( $this->apiUrl, urlencode( $queryToMaps ) );
		$ch = curl_init( $url );
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$call_json = curl_exec($ch);

		$json = ( $call_json ) ? json_decode($call_json, true ) : false;
		return $json;

	}


	/**
    * Method that take the first result from the Google Maps Api
    *
    * @param string maps query
     *
    * @return array with the geo information about the region that was query about
    * @access public
    */
	public function callApiGoogleMaps($queryToMaps){
		
		$mapsJson = $this->madeCurlResquest($queryToMaps);
		if( $mapsJson['status'] != 'OK' ){
			return false;
		}
    	return $mapsJson['results'];
	}

	/**
    * Method that take the first result from the Map Quest Api
    *
    * @param string maps query
     *
    * @return array with the geo information about the region that was query about
    * @access public
    */
	public function callApiMapQuest($queryToMaps){
		
		$call_json = $this->madeCurlResquest($queryToMaps);
		return $call_json['results'][0]['locations'];
	}


	/**
    * Method that get region information from api response, like country, state, ...
    *
    * @param array that contains the api response
    * @param bool returns only country information
    *
    * @return array with the geo information about the region that was query about
    * @access public
    */
	public function getAddressComponents( $regionInfo, $getOnlyCountry = false){
		
		$addresses = call_user_func_array( array( $this, 'getAddressComponents' . MapsHandler::$api ), array( $regionInfo, $getOnlyCountry) );
		return $addresses;
	}

	public static function getAddressComponentsMapQuest( $regionInfo, $getOnlyCountry=false ){

		$addresses = array();
		$adminAreas = ($getOnlyCountry) ? array('adminArea1') : array('adminArea1', 'adminArea3', 'adminArea4','adminArea5' );	

		foreach($adminAreas as $adminArea ){
			if($adminArea == 'adminArea1'){
				$addresses['name'] = CountriesTables::$countryCodeToCountryNameMap[$regionInfo[$adminArea]];
				$addresses['code'] = $regionInfo[$adminArea];
			}
			else{
				$addresses[$adminArea]['name'] =  $regionInfo[$adminArea];
			}
		}
		
		return $addresses;

	}

	public static function getAddressComponentsGoogleMaps( $regionInfo, $getOnlyCountry=false ){

		$addresses = array();
		$addressInfo = $regionInfo['address_components'];
		$administrativeAreas = ($getOnlyCountry) ? array('country') : array('country','administrative_area_level_1', 'administrative_area_level_2');	

		foreach($addressInfo as $address ){
			
			$administrativeArea = array_intersect( $administrativeAreas, $address['types'] );
			
			if( !empty( $administrativeArea ) ){

				reset($administrativeArea);
				$administrativeArea = current($administrativeArea);
				
				if($administrativeArea == 'country'){
					$addresses['name'] = $address['long_name'];
					$addresses['code'] = $address['short_name'];
				}
				else{
					$addresses[$administrativeArea]['name'] =  $address['long_name'];
					$addresses[$administrativeArea]['code'] =  $address['short_name'];
				}
			}
		}
		return $addresses;
	}

	/**
    * Method that generate a point with random latitude and longitude. This method was developed to test the algorithm 
    * to not spend api limits
    *
    * @return array with random latitude and longitude 
    * @access public
    */
	public static function generateRandomCoordinates(){
		
		$minLat = -90.00;
    	$maxLat = 90.00;      
    	$minLon = 0.00;
    	$maxLon = 180.00;     
    	
    	$latitude 	= $minLat + (float)(( mt_rand() / mt_getrandmax() ) * (($maxLat - $minLat) + 1));
    	$longitude 	= $minLon + (float)(( mt_rand() / mt_getrandmax() ) * (($maxLon - $minLon) + 1));

    	$coordinates = array(
    		'coordinates' => 
    			array(
    				'lat' => $latitude,
    				'lng' => $longitude
    			),
    		'fake' => true
    	);
		
		return $coordinates;
	}

	/**
    * Method that decide which api use to get region coordinates
    *
    * @param string query to maps api
    * @param boolean return api response or not
    * @access public
    */
	public function getRegionCoordinates( $queryToMaps, $returnResponse = false, $random = true  ){
		
		$results = call_user_func_array( array( $this, 'getRegionCoordinates' . MapsHandler::$api ), array( $queryToMaps, $returnResponse, $random ) );
		return $results;
	}


	/**
    * Method that get region coordinates with Map Quest
    *
    * @param string query to maps api
    * @param boolean return api response or not
	*
    * @return array with corrdinates, fake a tru if the corrdinates were random generated and the api response if $returnRespose was true
    * @access public
    */
	public function getRegionCoordinatesMapQuest( $queryToMaps, $returnResponse = false, $random = true  ){
		
		if( empty( $queryToMaps ) ){ return false; }

		$results = array();
		$regionInfoCoord = $this->callApiMapQuest($queryToMaps);
		$regionInfoCoord = $regionInfoCoord[0];

		if( ! empty( $regionInfoCoord ) ){
			
			$location = $regionInfoCoord['latLng'];
			$results['coordinates'] = array(
				'lat' => floatval($location['lat']),
				'lng' => floatval($location['lng'])
			);
			
			if( $returnResponse ){
				$results['callResponse'] = $regionInfoCoord;	
			}
			
		}
		else{
			if( $random ){
				$results = self::generateRandomCoordinates();
			}
		}
		
		return $results;

		
	}

	/**
    * Method that get region coordinates with Google Maps
    *
    * @param string query to maps api
    * @param boolean return api response or not
	*
    * @return array with corrdinates, fake a tru if the corrdinates were random generated and the api response if $returnRespose was true
    * @access public
    */
	public function getRegionCoordinatesGoogleMaps( $queryToMaps, $returnResponse = false, $random = true  ){
		
		
		if( empty( $queryToMaps ) ){ return false; }

		$results = array();
		$regionInfoCoord = $this->callApiGoogleMaps($queryToMaps);
		$regionInfoCoord = $regionInfoCoord[0];
		
		if( ! empty( $regionInfoCoord ) ){
			
			$location = $regionInfoCoord['geometry']['location'];
			$results['coordinates'] =  array(
				'lat' => floatval($location['lat']),
				'lng' => floatval($location['lng'])
			);
			
			if( $returnResponse ){
				$results['callResponse'] = $regionInfoCoord;	
			}
		}
		else{
			if( $random ){
				$results = self::generateRandomCoordinates();
			}
		}
	
		return $results;
	}


	public static function setApiConfig(){
		
		$handle = fopen(__DIR__ . "/../../.env", "r");
		$api = array();

		if ($handle) {
			
		    while (($line = fgets($handle)) !== false) {
		       	$line = explode('=', $line);
		       	if( $line[0] == 'API'){
		       		$api['api'] = trim(preg_replace('/\s\s+/', ' ', $line[1]));
		       	}
		       	if( $line[0] == 'API_KEY'){
		       		$api['key'] = trim(preg_replace('/\s\s+/', ' ', $line[1]));
		       	}
		    }
			fclose($handle);
			self::apiConfig($api);
			
		}
		else {
		    
		    echo 'Configuration file not set';
		    die();
		} 
	}

}

?>
