<?php 

require __DIR__ . '/../../vendor/autoload.php';


class MapsApiTest extends \PHPUnit_Framework_TestCase{


	public function testMapsApi(){
		
		$mapQuest = new Maps\MapsHandler();
		$response = $mapQuest->getRegionCoordinates('Lisbon,Portugal', true);
		$data = $mapQuest->getAddressComponents($response['callResponse'], false);
		
	}




}