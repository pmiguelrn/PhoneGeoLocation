<?php 

require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;


class MatchCommandTest extends \PHPUnit_Framework_TestCase
{

    public function testPhoneGeoMatchCommand()
    {
        
        $application = new Application();
        $application->add(new PhoneGeoApp\PhoneNumbersGeoMatch());

        $command = $application->find('phoneNumberGeo:match');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'      => $command->getName(),
            'targetNumber'         => '+34955653084',
            'customNumbers' => array('+34810234546', '+351266745367', '+351253567890')
        ));

        $this->assertRegExp('/351266745367/', $commandTester->getDisplay());

        $commandTester->execute(array(
            'command'      => $command->getName(),
            'targetNumber'         => '+34955653084',
            'customNumbers' => array('+34810234546', '+351266745367', '+351253567890'),
            '--same-country-only' => true
        ));
        
        $this->assertRegExp('/34810234546/', $commandTester->getDisplay());
    }
}