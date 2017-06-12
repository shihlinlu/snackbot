<?php
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
require 'vendor/autoload.php';
use PhpSlackBot\Bot;
use PubNub\PubNub;
use PubNub\PNConfiguration;

/**
 * Tea command
 */
class TeaCommand extends \PhpSlackBot\Command\BaseCommand {

	private $initiator;
	private $drinks = array();
	private $status = 'Tea timer has not been started.';
	private $pubNub = null;

	public function __construct() {
		//grab mySQL statement
		$config = readConfig("/etc/apache2/capstone-mysql/piomirrors.ini");

		//variable that houses pub and sub keys
		$pubNubConfig = json_decode($config["pubNub"]);

		$pnconf = new PNConfiguration();
		$this->pubNub = new PubNub($pnconf);

		$pnconf->setSubscribeKey($pubNubConfig->subscribe);
		$pnconf->setPublishKey($pubNubConfig->publish);

	}


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
			case 'status':
				$this->status();
				break;
			//case 'cancel':
			//$this->end();
			//break;
			default:
				$this->send($this->getCurrentChannel(), $this->getCurrentUser(), "Sorry, that is not a possible command. Please try again");
		}
	}

	private function start($args) {
		if($this->status == 'Tea timer has not been started.') {
			$this->subject = isset($args[2]) ? $args[2] : null;
			if(!is_null($this->subject)) {
				$this->subject = str_replace(array('<', '>'), '', $this->subject);
			}
			// timer event
			//$loop = React\EventLoop\Factory::create();
			//$loop->addTimer(4.18, function () {
			//$this->send($this->getCurrentChannel(), null, "Your tea is ready.");
			// echo 'Your tea is ready.' . PHP_EOL;
			//});

			// created a start loop for 0.001s
			//$startLoop = React\EventLoop\Factory::create();
			//$startLoop->addTimer(0.001, function () {


			$this->status = 'running';
			$this->initiator = $this->getCurrentUser();
			$this->drinks = array();
			$this->send($this->getCurrentChannel(), null,
				"The tea timer has started " . $this->getUserNameFromUserId($this->initiator) . "\n" .
				"Please wait!");

			$teaPot = new stdClass();
			$teaPot->user = $this->getUserNameFromUserId($this->initiator);
			$teaPot->time = round(microtime(true) * 1000);


			$result = $this->pubNub->publish()
				->channel("tea")
				->message($teaPot)
				->sync();

			print_r($result);

			//});
			//$startLoop->run();
			//$loop->run();
		}
	}

	private function status() {
		$message = 'Current Tea Brew Status : ' . $this->status;
		if($this->status == 'running') {
			$message .= "\n" . 'Initiator : ' . $this->getUserNameFromUserId($this->initiator);
		}
		$this->send($this->getCurrentChannel(), null, $message);
		if($this->status == 'running') {
			if(empty($this->drinks)) {
				$this->send($this->getCurrentChannel(), null, 'No one is brewing tea right now.');
			} else {
				$message = '';
				foreach($this->drinks as $user => $drink) {
					$message .= $this->getUserNameFromUserId($user) . 'has started the tea' . "\n";
				}
				$this->send($this->getCurrentChannel(), null, $message);
			}
		}
	}

	private function getArgs($message) {
		$args = array();
		if(isset($message['text'])) {
			$args = array_values(array_filter(explode(' ', $message['text'])));
		}
		$commandName = $this->getName();
		// Remove args which are before the command name
		$finalArgs = array();
		$remove = true;
		foreach($args as $arg) {
			if($commandName == $arg) {
				$remove = false;
			}
			if(!$remove) {
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
		$this->send($this->getCurrentChannel(), null, "Currently, Snackbot can notify you when your snack is ready. 
		Options: !tea, !coffee, !bagel.
		If you are indecisive, !lucky allows me to choose a snack for you :smiley:
		`!tea start` - start snack preparation 
		`!tea status` - status of snack 
		`!tea cancel` - cancel the timer
		`!help` - access the list of commands");
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
 *$bot->loadPushNotifier(function () {
 * return [
 * 'channel' => '#pi-mirror-commands',
 * 'username' => '@phpslackbot',
 * 'message' => "Snackbot needs some snacks"
 * ];
 * });
 */


/**
 * temporarily disabled
 *
 *
 * $bot->loadPushNotifier(function () {
 * return [
 * 'channel' => '#pi-mirror-commands',
 * 'username' => '@shihlin',
 * 'message' => "Good evening, it is: " . date("D M j h:i:s A T Y")
 * ];
 * });
 */

$bot->run(); // this launches the script


/**
 *
 */