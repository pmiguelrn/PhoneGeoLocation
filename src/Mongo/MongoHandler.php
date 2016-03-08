<?php

namespace Mongo;
use MongoClient;

class MongoHandler{

	public $collection;
	
	private $dbConnection;
	
	private $mongoInstance;

	private static $host;

	private static $port;

	private static $user;

	private static $password;

	private static $dbName;

	private function __construct( $database ){
		 
		$connection = self::$host . ':' . self::$port;
		$connectionOptions = array();
		
		if( !empty( self::$user ) && !empty( self::$password) ){
			$connectionOptions['username'] = self::$user;
			$connectionOptions['password'] = self::$password;
		}

		try{
			$this->mongoInstance = new MongoClient($connection, $connectionOptions);
		}
		catch(MongoConnectionException $e ){
			echo 'It has not possible to made a connection with mongo';
		}

		if( empty( $database ) ){
			$database = self::$dbName;
		}

		$this->setDatabaseConnection( $database );
	}

	public static function mongoConfig( $config = array() ){

		self::$host = $config['host'];
		self::$port = $config['port'];
		self::$user = $config['user'];
		self::$password = $config['password'];
		self::$dbName = $config['dbname'];
	}


	public static function get( $database = ''){
		return new MongoHandler( $database );
	}

	private function setDatabaseConnection($database){
		
		try{
			$this->dbConnection = $this->mongoInstance->selectDB( $database );
		}
		catch(MongoConnectionException $e ){
			echo 'It has not possible to made a connection with the database ' . $database;
		}

	}

	public static function setArgsArrayToQuery( $fields, $values, $operator){

		$nbFields = count( $fields);
		$nbValues = count( $values);

		if( $nbFields != $nbValues ){
			return false;
		}
		
		$args = array();
		
		if( $nbFields > 1){
			
			foreach( $fields as $key => $field ){
				array_push( $args, array($field => $values[$key] ) );
			}
			$args = array( $operator => $args );
		
		}
		elseif( $nbFields == 1 ){
			$args[$fields[0]] = $values[0];
		}

		return $args;
	}
	
	public function getDatabaseConnection(){
		return $this->dbConnection;

	}
	
	public function getMongoInstance(){
		return $this->mongoInstance;
	}
	
	public function createCollection($collectionName){
		$this->dbConnection->createCollection($collectionName);
	}

	public function getCollection(){
		return $this->collection;
	}

	/**
    * Method that sets a connection to a collection
    *
    * @param string collection name
    * @access public
    */
	public function setCollection( $collectionName ){
		if( !empty( $collectionName ) ){
			$this->collection = $this->dbConnection->selectCollection( $collectionName );
		}
	}	

	/**
    * Method that inserts data into a collection
    *
    * @param string collection name
    * @access public
    */
	public function insertData($data = array(), $collectionName = '' ){
		
		if( empty( $data ) ){
			return false;
		}
		
		$insertedStatus = $this->collection->insert($data);
		return $insertedStatus;
	}

	public function queryOne( $queryArgs = array(), $fieldsToReturn =  array() ){
		
		$result = $this->collection->findOne( $queryArgs );
		return $result;
	
	}

	public function queryColl( $queryArgs = array(), $fieldsToReturn =  array() ){
		
		$results = array();
		$filter  = array();
		
		foreach( $fieldsToReturn as $field ){
			$filter[$field] = true;
		}

		$collectionFind = $this->collection->find( $queryArgs,  $filter );
		
		foreach ($collectionFind as $key => $result) {
			array_push($results, $result);
		}

		$results =  empty($results) ? false : $results;

		return $results;
	}

	public function createRef($documentToRef, $collectionName = false){
		
		$ref = $this->collection->createDBRef($documentToRef); 
		return $ref;
	}

	public function createIndex( $key = '', $collectionName = ''){
		if( !empty($collectionName) ){
			$this->setCollection($collectionName);
		}

		$this->collection->createIndex( array($key => 1 ) );
	}

	public function updateColl( $query, $dataToUpdate){

		$this->collection->update($query, $dataToUpdate);
	}


	public function count( $query, $collectionName ){
		if( !empty($collectionName) ){
			$this->setCollection($collectionName);
		}

		return $this->collection->count($query);

	}


	public static function setConnectionServerData(){
		
		$handle = fopen(__DIR__ . "/../../.env", "r");
		$mongo = array();

		if ($handle) {
			
		    while (($line = fgets($handle)) !== false) {
		       	$line = explode('=', $line);
		       	if( $line[0] == 'DB_HOST'){
		       		$mongo['host']=trim(preg_replace('/\s\s+/', ' ', $line[1])); 
		       	}
		       	if( $line[0] == 'DB_PORT'){
		       		$mongo['port']= trim(preg_replace('/\s\s+/', ' ', $line[1])); 
		       	}
		       	if( $line[0] == 'DB_USER'){
		       		$mongo['user'] = trim(preg_replace('/\s\s+/', ' ', $line[1]));
		       	}
		       	if( $line[0] == 'DB_PASSWORD'){
		       		$mongo['password'] = trim(preg_replace('/\s\s+/', ' ', $line[1]));
		       	}
		       	if( $line[0] == 'DB_NAME'){
		       		$mongo['dbname'] = trim(preg_replace('/\s\s+/', ' ', $line[1]));
		       	}
		       	
		    }
			fclose($handle);
			self::mongoConfig($mongo);
		}
		else{
			echo 'Configuration file not set';
			die();
		} 
	}


}

?>