<?php
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
require 'vendor/autoload.php';
use PhpSlackBot\Bot;

// custom command
class MyCommand extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('mycommand');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null,'Hello !');
	}

}

//grab mySQL statement
$config = readConfig("/etc/apache2/capstone-mysql/piomirrors.ini");

//variable that will house the API key for the Slack API
$slack = $config["slack"];

$bot = new Bot();
$bot->setToken($slack);
$bot->loadCommand(new MyCommand());
$bot->loadInternalCommands(); // this loads example commands
$bot->run();
