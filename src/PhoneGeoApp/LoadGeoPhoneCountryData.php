<?php 

namespace PhoneGeoApp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Maps;
use PhoneGeoMatch;
use Mongo;

class LoadGeoPhoneCountryData extends Command
{

	static $progressSteps = 100;

	private static $onlyCountries = false;

	public static $mapsHandler;

	protected function configure()
	{
		$helpInfo = "Load the country phone geo data";

		$this
			->setName('phoneNumberGeo:loadCountry')
			->setDescription('Load the phone data of one or more countries, inserting the country names or prefixes')
			->addArgument(
				'countries',
				InputArgument::IS_ARRAY,
				'Insert the countries that you want to load'
			)
			->addOption(
				'europe-countries',
				null,
				InputOption::VALUE_NONE,
				'Load Europe Countries'
				)
			->addOption(
				'only-countries',
				null,
				InputOption::VALUE_NONE,
				'Load only Countries'
				)
			->addOption(
				'prefix-one-countries',
				null,
				InputOption::VALUE_NONE,
				'Load Countries that have the prefix start by one'
				)
			->addOption(
				'all-countries',
				null,
				InputOption::VALUE_NONE,
				'Load all Countries'
				)
			->setHelp($helpInfo);

	}

	/**
    * This method receive a country prefix and try with that gets all the information 
    * needed to save a country ( with the help of the API and from libforphonenumber library data)  
    *
    * @param int prefix to analyse
    * @param output to write messagens on terminal 
    * @return Object or false
    * @access public
    */
	protected function saveCountryByPrefix( $prefix, $output){

		$firstPrefixEqualFilePrefix = false;
		$prefixAsInt = (int)$prefix;
		$regionsOnFile = PhoneGeoMatch\Country::getCountryRegionsFromTables('en', $prefixAsInt);

		reset($regionsOnFile);
		$firstPrefixOnFile = key( $regionsOnFile );

		if( $firstPrefixOnFile == $prefixAsInt ){
			$firstPrefixEqualFilePrefix = true;
		}
		
	
		$regions = PhoneGeoMatch\Region::getAllRegionPrefixes( $regionsOnFile );
		
		$countryImportMess = '<fire>Importing ';
		reset( $regions );		
		//first region on file
		$firstRegionNameOnfile = key( $regions );
		//query if already exists a country with this prefix 
		$country = PhoneGeoMatch\Country::queryOne('prefix', $prefixAsInt );
		PhoneGeoMatch\Country::$prefix = $prefixAsInt;

		if( empty( $country ) ){

			$country = PhoneGeoMatch\Country::getCountryObjByPrefix( $prefixAsInt );
			if( empty( $country ) ){
				return;
			}
			elseif( !is_array( $country ) ){
				
				if( $firstPrefixEqualFilePrefix ){  
					array_shift( $regions ); 
				}
				$country = PhoneGeoMatch\Country::getCoordinatesFromMaps( $firstRegionNameOnfile, true );

			}
			
			$country['prefix'] = $prefixAsInt;
			PhoneGeoMatch\Country::save($country);
			
		}
		
		$countryImportMess .=  $firstRegionNameOnfile . ' - ' . $country['name'] . '</>';
		$output->writeln($countryImportMess);
		$countryRef = PhoneGeoMatch\Country::createMongoRef( array( 'prefix' => $prefixAsInt ) );
		
		if( !self::$onlyCountries){
			$this->loopOnFileRegionsAndPrefixes( $regions, $countryRef, strlen( $prefix ), $output );
		}

	}		

	/**
    * This method saves the regions and prefixes of a country or state 
    *
    * @param array that associate regions to prefixes
    * @param array mongo reference to countries collection. This reference will be added to the region and country document 
    * @param output to write messagens on terminal
    * 
    * @return Object or false
    * @access public
    */
	protected function loopOnFileRegionsAndPrefixes($regions, $countryRef, $prefixlen, $output ){
		
		$nbRegions = count( $regions );
		if( $nbRegions == 0 ){
			return;
		}
		$progress = new ProgressBarHandler($output, self::$progressSteps, $nbRegions);//init progress bar	
				
		foreach( $regions as $regionName => $prefixes ){
			
			$region = array('name' => $regionName, 'country' => $countryRef );
			PhoneGeoMatch\Region::save( $region );
			
			foreach( $prefixes as $prefix ){
				
				
				$prefixFilter = substr( (string)$prefix, $prefixlen);

				$prefixInfo = array(
					'prefix' => (int)$prefixFilter, 
					'region' => PhoneGeoMatch\Region::createMongoRef( array('name' => $regionName ) ),
					'country' => $countryRef
				);
				
				PhoneGeoMatch\Prefix::save($prefixInfo);
			}
			
			$progress->increment();

		}

		$progress->finish();//finish progress bar
	}


	public static function setMapsApi(){
		self::$mapsHandler = new Maps\MapsHandler();
		
	}

	public static function getMapsApi(){
		self::setMapsApi();
		return self::$mapsHandler;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
    {
        

        Maps\CountriesTables::loadAllTables();
        

        $files = Maps\CountriesTables::getAllFilesWithCountriesPrefixes('en');
        $countriesPrefixes = array();
    	
        self::$onlyCountries = $input->getOption('only-countries');

    	if ($input->getOption('europe-countries')) {
            $countriesPrefixes = Maps\CountriesTables::loadAllEuropeCountries();	
        }
        elseif( $input->getOption('prefix-one-countries') ){
        	$countriesPrefixes = Maps\CountriesTables::loadAllOnePrefixCountries();
        }
        elseif( $input->getOption('all-countries') ){
        	$countriesPrefixes = Maps\CountriesTables::loadAllCountries();
        }
        else{
        	$countries = $input->getArgument('countries');
        	foreach( $countries as $country ){
        		$countriesPrefixes[$country] = PhoneGeoMatch\Country::getPrefixByCode( PhoneGeoMatch\Country::getCodeByName( $country ) );
        	}
        }

      	self::setMapsApi();

      	$style = new OutputFormatterStyle('green');
		$output->getFormatter()->setStyle('fire', $style);
			
		foreach( $countriesPrefixes as $countryPrefix ){
			
			$countryFile = (int)$countryPrefix;
			$countryFile .= '.php';
		
			if( in_array( $countryFile, $files ) ){		
				
				$start = microtime( true );	
				$this->saveCountryByPrefix($countryPrefix, $output );
				$end = microtime( true ) - $start;
				$output->writeln( PHP_EOL . '<fg=blue>It takes ' . $end . '</>');
				sleep(0.1);
			}
			
		}

		//create index on country.$id field on prefixes collection
		PhoneGeoMatch\Prefix::createIndex();

		

    }

}