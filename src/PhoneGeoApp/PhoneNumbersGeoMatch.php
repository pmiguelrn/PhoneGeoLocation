<?php 

namespace PhoneGeoApp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \libphonenumber\PhoneNumberUtil;
use PhoneGeoMatch;

class PhoneNumbersGeoMatch extends Command
{

	public static $R = 6371; 
	public static $sameCountry;
	public static $targetCountryCode;

	protected function configure()
	{
		$helpInfo = "Find the phone number that you should use to dial to a certain client!
Usage:  
Insert first the client number, then, separated by a space, all your phone numbers that you want to match";

		$this
			->setName('phoneNumberGeo:match')
			->setDescription('Find your best phone number to dial to this client')
			->addArgument(
				'targetNumber',
				InputArgument::REQUIRED,
				'What is your target number?'
			)
			->addArgument(
				'customNumbers',
				InputArgument::IS_ARRAY,
				'Which are your custom numbers ( separate multiple numbers with a space )'
			)
			->addOption(
				'same-country-only',
				null,
				InputOption::VALUE_NONE,
				'If set, search only for numbers that are from the same country than the target one'
				)
			->setHelp($helpInfo);

	}	

	protected function parsePhoneNumber( $phoneUtil, $phoneNumber) {
		
		try {
		    $targetNumberParsed = $phoneUtil->parse($phoneNumber, "US");
		} catch (\libphonenumber\NumberParseException $e) {
		    var_dump($e);
		}

		return $targetNumberParsed;
	}


	/**
    * Method that contains the algorithm that match a target number with the custom numbers
    *
    * @param string target number
     *@param array that contains all the custom numbers
    * @return array with number and region name of the matched custom number
    * @access public
    */
	protected function matchPhoneNumbersGeo( $targetNumber, $customNumbers ){

		$start = microtime(true);
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		
		$targetNumber = $this->parsePhoneNumber( $phoneUtil, $targetNumber);
		self::$targetCountryCode = ( self::$sameCountry ) ? $targetNumber->getCountryCode() : false;
		$targetNumberCoordinates = $this->getNumberPhoneCoordinates($targetNumber);
		
		if( empty($targetNumberCoordinates) ){
			return false;
		}

		$targetPoint = array( $targetNumberCoordinates['coordinates']['lat'], $targetNumberCoordinates['coordinates']['lng'] );
		
		$distances = array();
		$names = array();
		$phoneNumberDistances = array();

		foreach( $customNumbers as $customNumber ){
			
			$customNumberObj = $this->parsePhoneNumber( $phoneUtil, $customNumber);
			$customNumberCoordinates = $this->getNumberPhoneCoordinates( $customNumberObj );
			
			if( empty( $customNumberCoordinates ) ){
				continue;
			}
			
			if( self::$sameCountry ){
				if( self::$targetCountryCode != $customNumberObj->getCountryCode() ){
					continue;
				}
			}
			
			$customPoint = array( $customNumberCoordinates['coordinates']['lat'], $customNumberCoordinates['coordinates']['lng'] );
			$distance = $this->calculateDistanceBetweenTwoPoints( $targetPoint, $customPoint );
			
			if( $distance > 0 ){
				array_push( $names,$customNumberCoordinates['name'] );
				array_push( $distances, $distance );
				$phoneNumberDistances[$customNumber] = $distance;
			}
			
			
		}
		
		$closerRegion = array();
		
		if( count($distances) ){

			asort( $distances );
			reset( $distances );
		
			$closerPhoneNumber = array_search( $distances[key($distances)], $phoneNumberDistances );
			$closerRegion = array( 'number' => $closerPhoneNumber, 'name' => $names[key( $distances )]);
		}
		
		//echo microtime(true) - $start . PHP_EOL;
		return $closerRegion;
	}

	/**
    * Method to validate phone number and filter the country and region prefixes
    *
    * @param string number
    * @return array with region and country prefixes
	*   
    * @access public
    */
	public function getCountryAndRegionPrefix( $number ){

		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		$prefixes = array();
		
		if( $phoneUtil->isValidNumber( $number ) ){

			$countryPrefix = $number->getCountryCode();
			$numberFormat = $phoneUtil->format($number, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
			$numberFormatSplit = explode( ' ', $numberFormat);

			if( $countryPrefix == 1 || $countryPrefix == 86){
				$splitNumber = explode('-', $numberFormatSplit[1] );
				$countryPrefix = (int)( $countryPrefix . $splitNumber[0] );
				$regionPrefix = (int)$splitNumber[1];
			}
			else{
				$regionPrefix = (int)$numberFormatSplit[1];
			}

			$prefixes['country'] = $countryPrefix;
			$prefixes['region']  = $regionPrefix;

		}

		return $prefixes;
	}

	/**
    * Method to collect phone coordinates. From the phone number it is possible to get the country and region prefix. With this info we can query the database and find the phone coordinates
    *
    * @param string number
    * @return array with region/country name and coordinates
	*   
    * @access public
    */
	public function getNumberPhoneCoordinates( $number ){

		
		$regionCoordinates = false;
		$countryAndRegionPrefixes = $this->getCountryAndRegionPrefix($number);	
		$start = microtime( true );
		if( !empty( $countryAndRegionPrefixes ) ){

			$countryPrefix = $countryAndRegionPrefixes['country'];
			$regionPrefix = $countryAndRegionPrefixes['region'];
			//query Mongo by the phone number country
			$country  = PhoneGeoMatch\Country::queryOne('prefix', $countryPrefix, array( '_id', 'name', 'coordinates' ) );
			//get all prefixes from the country found
			$prefixes = PhoneGeoMatch\Prefix::query('country.$id', $country['_id'] , array( 'region', 'prefix') );
			//loop on country prefixes to find the number prefix
			if( !empty( $prefixes ) ){
				
				foreach( $prefixes as $prefix){
					if( $prefix['prefix'] == $regionPrefix  ){
						//get region name and coordinates
						$coordinates = PhoneGeoMatch\Region::query(array('_id'), array($prefix['region']['$id']) , array('name', 'coordinates') );
						$regionCoordinates = $coordinates[0];
						break;
					}
				}
			}
			//it wasn´t possibily to find a region coordinates so the fallback are the country coordinates
			if( empty( $regionCoordinates ) ){
				$regionCoordinates = $country;
			}

		}
		
		return $regionCoordinates;
	}

	public function calculateDistanceBetweenTwoPoints( $point1 = array(), $point2 = array() ){

		$lat1 = $point1[0];
		$lat2 = $point2[0];

		$lng1 = $point1[1];
		$lng2 = $point2[1];

		$dLat = deg2rad($lat2-$lat1);
		$dLon = deg2rad($lng2-$lng1);
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);

		$a = sin($dLat/2) * sin($dLat/2) +
	        sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2); 
		$c = 2 * atan2( sqrt($a), sqrt(1-$a)); 
		$d = self::$R * $c;
		
		return $d;
	}



	protected function execute(InputInterface $input, OutputInterface $output)
    {
        self::$sameCountry = ( $input->getOption('same-country-only' ) ) ? true : false;
		
		$targetName = $input->getArgument('targetNumber');
        $customNumbers = $input->getArgument('customNumbers');
		$closerDistance = $this->matchPhoneNumbersGeo($targetName, $customNumbers);
		
		$message = !empty( $closerDistance ) ? $closerDistance['name'] . ' : ' . $closerDistance['number'] : 'no match found';
		$output->writeln( $message );
		
    }


}