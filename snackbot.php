<?php
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
require 'vendor/autoload.php';
use PhpSlackBot\Bot;

/**
 * Tea command
 */
class TeaCommand extends \PhpSlackBot\Command\BaseCommand {

	private $initiator;
	private $drinks = array();
	private $status = 'free';

	protected function configure() {
		$this->setName('!tea');
	}

	protected function execute($message, $context) {
		$args = $this->getArgs($message);
		$command = isset($args[1]) ? $args[1] : '';

		switch($command) {
			case 'start':
				$this->start($args);
				break;
			//case 'status':
				//$this->status();
				//break;
			//case 'cancel':
				//$this->end();
				//break;
			default:
				$this->send($this->getCurrentChannel(), $this->getCurrentUser(), "Sorry, that is not a possible command. Please try again");
		}
	}

	private function start($args) {
		if($this->status == 'free') {
			$this->subject = isset($args[2]) ? $args[2]: null;
			if(!is_null($this->subject)) {
				$this->subject = str_replace(array('<', '>'), '', $this->subject);
			}
			//$this->status = 'running';
			$this->initiator = $this->getCurrentUser();
			$this->drinks = array();
			$this->send($this->getCurrentChannel(), null,
				"The tea kettle has been started by ".$this->getUserNameFromUserId($this->initiator)."\n".
				"Please wait!");
		}
	}

	private function getArgs($message) {
		$args = array();
		if (isset($message['text'])) {
			$args = array_values(array_filter(explode(' ', $message['text'])));
		}
		$commandName = $this->getName();
		// Remove args which are before the command name
		$finalArgs = array();
		$remove = true;
		foreach ($args as $arg) {
			if ($commandName == $arg) {
				$remove = false;
			}
			if (!$remove) {
				$finalArgs[] = $arg;
			}
		}
		return $finalArgs;
	}

}

/**
 * Coffee command
 */

class CoffeeCommand extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('!coffee');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null, "What kind of coffee do you prefer? :coffee:");
	}

}

/**
 * Bagel command
 */
class BagelCommand extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('!bagel');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null, "There are many bagels, you should go to the kitchen and find out. :bread:");
	}

}

/**
 * Help command lists all possible commands to interact with the bot
 */
class HelpCommand extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('!help');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null, "What can snackbot do for you? /n tea, coffee, bagel...");
	}
}

//grab mySQL statement
$config = readConfig("/etc/apache2/capstone-mysql/piomirrors.ini");

//variable that will house the API key for the Slack API
$slack = $config["slack"];

$bot = new Bot();
$bot->setToken($slack);
$bot->loadCommand(new TeaCommand());
$bot->loadCommand(new CoffeeCommand());
$bot->loadCommand(new BagelCommand());
$bot->loadCommand(new HelpCommand());
$bot->loadInternalCommands(); // this loads example commands

// active messaging: sends messages to users without the need for them to send one first
/**
 * temporarily disabled
 *
 * $bot->loadPushNotifier(function () {
return [
'channel' => '#pi-mirror-commands',
'username' => '@phpslackbot',
'message' => "Testing active messaging function..."
];
});

$bot->loadPushNotifier(function () {
return [
'channel' => '#pi-mirror-commands',
'username' => '@shihlin',
'message' => "Good evening, it is: " . date("D M j h:i:s A T Y")
];
});
 */

$bot->run(); // this launches the script
