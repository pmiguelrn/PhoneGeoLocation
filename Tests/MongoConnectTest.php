<?php

Mongo\MongoHandler::setConnectionServerData();
Maps\MapsHandler::setApiConfig();

class MongoConnectTest extends \PHPUnit_Framework_TestCase{
		
		public function testMongoConnection(){
			
			$connected = false;
			$mongoInstance = Mongo\MongoHandler::get();
            
          	if( get_class($mongoInstance->getMongoInstance() ) == 'MongoClient' ){
            	$connected = true;
            }
            $this->assertTrue($connected);
		}

		public function testMongoDbConnection(){

			$mongoInstance = Mongo\MongoHandler::get();
			$this->assertTrue(is_object($mongoInstance->getDatabaseConnection()));
		
		}

		public function testCollectionChange(){
			
			$mongoInstance = Mongo\MongoHandler::get();
			
			$collections = array( 'Countries', 'Regions', 'Prefixes');
			foreach( $collections as $collection ){
				$mongoInstance->setCollection($collection);
				$this->assertEquals( $mongoInstance->collection->getName(), $collection);
			}
			
		}


		public function testCountriesCollectionQuery(){
			
			$mongoInstance = Mongo\MongoHandler::get();
			$mongoInstance->setCollection('Countries');

			$results = $mongoInstance->queryColl( array( 'prefix' => 351 ), array( 'name'), '' );
			$this->assertEquals( $results[0]['name'], 'Portugal');
			

		}

		public function testCountriesAndRegionsRefs(){

			$country = PhoneGeoMatch\Country::createMongoRef( array('prefix' => 351 ) );
			$region  = PhoneGeoMatch\Region::createMongoRef( array('name' => 'Lisbon') );
			$this->assertEquals( !empty($country), true );
			$this->assertEquals( !empty($region), true );


		}

	}



?> 
