
#Offline Phone Number Geolocation

This project has the objective to build a command line app that receive a phone number to call, and a list of the customer's available numbers, returning then the closest geographic match to the number being called.

To reach this objective I used PHP as programming language and MongoDB to store data. To collect countries and regions geographic coordinates I experiment to only use the geocode Google Maps http API. 

However Google Maps API has a free limit of 2500 requests per day, so I decided to support also the MapQuest API that has a higher limit. If, in the middle of the data loading process, the api fails, random coordinates are generated, and a flag indicating that, is added to the respective Mongo document. Then you have the possibility, when the api limits refresh, for instance, to successively run a command that updates the coordinates, until you have all the correct coordinates.

These are the steps that you have to follow to set all you need to start choose the closest geographic contact offline:
 
 1. Clone the project.
 
 2. Check if you have Mongo and intl extensions installed ay your php installation 

 3. Install composer and type composer install at your terminal to install dependencies and generate autoloading classes
 
 4. Choose an Api ( Google Maps or Map Quest) and generate a key:
 	* https://developers.google.com/maps/documentation/javascript/get-api-key
 	* https://developer.mapquest.com/plan_purchase/steps/business_edition/business_edition_free/register
 
 5. Fill the .env file with following information:
 	* MongoDB server connection info : **host**, **port**, **user** ( optional ) and **password** ( optional )
 	* Api name **(GoogleMaps or MapQuest )** and key.
 
 6. Create the database and the necessary collections with the command: **php application.php phoneNumberGeo:init <databaseName>** Don´t forget to update, if necessary, the name of the database at the .env file.
 
 7. Load the database with the command **phoneNumberGeo:loadCountry**. You can add some options to the command:
 	* Indicate the countries that you want to load: **phoneNumberGeo:loadCountry Portugal Spain**
 	* Load all europe Countries with option --europe-countries
 	* Load One Prefix Countries with option --prefix-one-countries
 	* Load all countries ( this option could take some hours ) --all-countries
 	* You have also the option to load only the countries collection --only-countries. The use of this option is recommended if you want to load all contries at once. In this case run first the command **phoneNumberGeo:loadCountry --all-countries --only-countries** and then again the command  **phoneNumberGeo:loadCountry --all-countries**

 8. After the database is loaded, you can run the command **php application.php phoneNumberGeo:update** to update the countries and regions with correct coordinates returned by the chosen api. If you possess a maps api paid licence this should not be necessary.  	
 
 9. After you have the database loaded you can start use the main functionality of this app: **php application.php phoneNumberGeo:match <targetNumber> <customNumberList>**. You have also the --same-country-only option if you want to choose same country phone numbers.

All the commands have to be run under the PhonesGeoLocation folder.

The loading of the database is carried out with the data provided by the libphonenumber-for-php library. In most cases there is a file to each country, that associate regions with the respective prefixes. However countries that have the international prefix 1 ( like United States) or 86 ( China ) have files to each state, and so, the chosen geocode API is used to infer the country to which that file refers to.  

The database has three collections: Countries, Regions and Prefixes. The Countries collection keeps all the information about countries or states (around 700 ). As a region can have many prefixes associated, to avoid data replication it is created a collection to regions ( 40 thousand regions ) and another one to prefixes. So the Prefixes collection contains all those who are provided by libphonenumber-for-php library ( around 216 thousand ), and each one has a mongo document reference to the country and region to which they belong. 

The reference to country inside prefix collection is indexed.

Examples:

# Call to Seville: +34955653084
# Available numbers:
# - Madrid: +34810234546
# - Évora:  +351266745367
# - Braga:  +351253567890

$ php application.php phoneNumberGeo:match +34955653084 +34810234546 +351266745367 +351253567890 
# Output: Évora:  +351266745367

$ php application.php phoneNumberGeo:match +34955653084 +34810234546 +351266745367 +351253567890 --same-country-only
# Output: Madrid:  +34810234546


The performance of the algorithm to find the coordinates of a phone number is approximately 0.007s at the first request and then 0.002 to the following. To a list of three custom numbers, for example, it takes around 0.02s to match a target to a custom number.  
