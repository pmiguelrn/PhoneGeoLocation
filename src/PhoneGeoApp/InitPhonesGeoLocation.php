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

class InitPhonesGeoLocation extends Command
{

	static $progressBar = 100;

	protected function configure()
	{
		$helpInfo = "Init Offline Phone Number Geolocation";

		$this
			->setName('phoneNumberGeo:init')
			->setDescription('Create mongo database, the name is given as argument')
			->addArgument(
				'database',
				InputArgument::REQUIRED,
				'Insert the name of your database'
			)
			->setHelp($helpInfo);

	}

		
	protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $database = $input->getArgument('database');
		$collections = array('Countries', 'Regions', 'Prefixes');
		//create database
		$mongoInstance = Mongo\MongoHandler::get( $database );
		//create project collection
		foreach( $collections as $collection){
			$mongoInstance->createCollection( $collection );
		}

		$output->writeln('Your mongo database is set. You can start load data, using the command loadCountry');

    }

}