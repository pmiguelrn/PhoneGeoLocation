#!/usr/bin/env php
<?php
// application.php

require __DIR__.  '/../vendor/autoload.php';
use Symfony\Component\Console\Application;


Mongo\MongoHandler::setConnectionServerData();
Maps\MapsHandler::setApiConfig();

$application = new Application();
$application->add(new PhoneGeoApp\PhoneNumbersGeoMatch());
$application->add(new PhoneGeoApp\LoadGeoPhoneCountryData());
$application->add(new PhoneGeoApp\InitPhonesGeoLocation());
$application->add(new PhoneGeoApp\UpdateRegionsCoordinates());
$application->run();


