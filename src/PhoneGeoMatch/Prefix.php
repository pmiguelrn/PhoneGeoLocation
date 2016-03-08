<?php 

namespace PhoneGeoMatch;
use Mongo;

class Prefix{

	public static $collectionName = 'Prefixes';

	public static $collectionConn = Mongo\MongoHandler::get()->setCollection( self::$collectionName );

	public function save($prefix){
		self::$collectionConn->insertData( $prefix );
	}
	
	public function query( $field, $value, $return = array() ){

		
		$arg = array( $field => $value );
		
		$query = self::$collectionConn->queryColl( $arg, $return );
		
		if( empty( $query ) ){ return false; }
		
		return $query;
	}

	public function createIndex(){
		self::$collectionConn->createIndex('country.$id');
	}


}