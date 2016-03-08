<?php 

namespace PhoneGeoApp;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Mongo;
use PhoneGeoMatch;
use Maps;
use MongoId;

class UpdateRegionsCoordinates extends Command
{

	static $progressBar = 100;

	protected function configure()
	{
		$helpInfo = "Update Regions Coordinates";

		$this
			->setName('phoneNumberGeo:update')
			->setDescription('Update the fake coordinates that had been loaded by real ones')
			->setHelp($helpInfo);

	}

	public function updateCountries($output){

		$countries = PhoneGeoMatch\Country::query('fake', true );
		$maps = new Maps\MapsHandler();
		$nbCountriesUpdated = 0;

		

		if( !empty( $countries ) ){
			
			foreach( $countries as $country ){
				
				if( isset( $country['name'] ) ){
					
					$output->writeln('Updating ' . $country['name'] . PHP_EOL );
					$coordinates = $maps->getRegionCoordinates( $country['name'] );
					
					if( !empty( $coordinates ) ){
						PhoneGeoMatch\Country::updateCoordinates( $country['_id'], $coordinates['coordinates'] );
						++$nbCountriesUpdated;
					}
				}
			}
		}

		return $nbCountriesUpdated;

	}


	public function updateRegions($output){

		$regions = PhoneGeoMatch\Region::query( array('fake'), array(true), '$and', array('country', 'name') );
		$countryRegions = PhoneGeoMatch\Region::organizeRegionsByCountry($regions);
		
		$nbRegionsUpdated = 0;
		$maps = new Maps\MapsHandler();
		

		if( !empty( $countryRegions ) ){
			
			foreach( $countryRegions as $countryId => $regions ){
				
				$countryIdObj = new MongoId( $countryId );
				$nbCountryRegions = count( $regions );
				$country = PhoneGeoMatch\Country::queryOne( '_id', $countryIdObj, array('name') );
				
				$countryUpdateMessage = '<fire>Updating ' . $country['name'] . '</>';
				
				$output->writeln($countryUpdateMessage);
				
				$progressBar = new ProgressBarHandler($output, self::$progressBar, $nbCountryRegions );	
				$start = microtime( true );
				
				$nbOfFailedCallsToMaps = 0;
				foreach( $regions as $region ){
					
					$regionCoordinates = $maps->getRegionCoordinates( $region['name'] . ', ' . $country['name'], false, false );
				
					if( !empty( $regionCoordinates ) ){
						
						PhoneGeoMatch\Region::updateCoordinates( $region['_id'], $regionCoordinates['coordinates'] );
						++$nbRegionsUpdated;
						$progressBar->increment();
					
					}
					else{

						++$nbOfFailedCallsToMaps;
						if( $nbOfFailedCallsToMaps == 20 ){
							return $nbRegionsUpdated;
						}
					}
					sleep(0.1);
				}
				
				$progressBar->finish();
				$end = microtime( true ) - $start;
				$output->writeln( PHP_EOL . '<fg=blue>It takes ' . $end . '</>');
				
				
			}
		}

		return $nbRegionsUpdated; 

	}

		
	protected function execute(InputInterface $input, OutputInterface $output)
    {
    	$style = new OutputFormatterStyle('green');
		$output->getFormatter()->setStyle('fire', $style);
			

		$nbCountriesUpdated = $this->updateCountries($output);
		$nbRegionsUpdated   = $this->updateRegions($output);			
		
		$mongoInstance = Mongo\MongoHandler::get();
		$nbCountriesMissing = $mongoInstance->count(array('fake' => true), 'Countries' );
		$nbRegionsMissing = $mongoInstance->count(array('fake' => true), 'Regions' );
		
		$nbRegionsTotal = $mongoInstance->count(array(), 'Regions' );
		$nbCountriesTotal = $mongoInstance->count(array(), 'Countries' );

		
		$countriesUpdatedPercentage = 100 - ( ( $nbCountriesMissing / $nbCountriesTotal ) * 100 );
		$regionsUpdatedPercentage = 100 - ( ( $nbRegionsMissing / $nbRegionsTotal ) * 100 );
		
		$message = PHP_EOL;
		
		if( $countriesUpdatedPercentage == 0 && $regionsUpdatedPercentage == 0){
			$message .= 'All Countries and regions are updated.';
		}
		else{
			
			$message .= $countriesUpdatedPercentage . ' of countries are updated.' . PHP_EOL;
			$message .= $regionsUpdatedPercentage . ' of regions are updated.' . PHP_EOL;
			
		}

		$output->writeln($message);

    }

}