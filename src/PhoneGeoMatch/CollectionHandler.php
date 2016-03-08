<?php 
abstract class Collection{

	private $connection;

	public function __construct(){
		$this->mongoConnection = Mongo\MongoHandler::get();
		$this->setConnection();
	}

	private function setConnection(){
		$this->connection =$this->mongoConnection->setCollection( $this->collectionName );
	}

	public function fetch( $fields = array(), $values = array(), $operator = 'AND', $return = array() ){

		$args = static::setArgsArrayToQuery($fields, $values, $operator);
		$results = $this->mongoConnection->queryColl( $args, $return );

		return $results;

	}

	public function fetchOne( $fields, $values, $operator){

		$args   = static::setArgsArrayToQuery($fields, $values, $operator, $return = array() );
		$result = $this->mongoConnection->queryOne( $args );
		return $result;

	}

	public function insert( $object, $fieldToQuery = null, $valueToQuery = null){

		$objNotExists = true;
		if( isset($fieldToQuery) && isset($valueToQuery ) ){
			if( !empty( $this->fetchOne( array($fieldToQuery),  array($valueToQuery) ) ) ){
				$objNotExists = false;
			}	
		}

		if( $objNotExists ){
			$this->connection->insertData($object);
		}

	};

	public function update(){}

	public function createRef(){}

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
} 