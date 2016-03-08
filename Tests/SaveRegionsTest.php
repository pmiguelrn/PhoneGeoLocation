<?php 

require __DIR__ . '/../../vendor/autoload.php';

use PhoneGeoMatch\Country;
use PhoneGeoMatch\Prefix;



class SaveRegionsTest extends \PHPUnit_Framework_TestCase{


	public function testSaveOnlyOneFile(){
		
		$countryQuery = Country::queryOne('prefix', 351, array( '_id', 'name' ) );
		$this->assertEquals( $countryQuery['name'], 'Portugal');	
		$prefixes = Prefix::query('country.$id', $countryQuery['_id'] , array( 'region', 'prefix') );
	}

}