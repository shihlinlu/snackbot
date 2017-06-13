<?php
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
require 'vendor/autoload.php';
use PhpSlackBot\Bot;
use PubNub\PubNub;
use PubNub\Enums\PNStatusCategory;
use PubNub\Callbacks\SubscribeCallback;
use PubNub\PNConfiguration;

class MySubscribeCallback extends SubscribeCallback {
	function status($pubnub, $status) {
		if ($status->getCategory() === PNStatusCategory::PNUnexpectedDisconnectCategory) {
			// This event happens when radio / connectivity is lost
		} else if ($status->getCategory() === PNStatusCategory::PNConnectedCategory) {
			// Connect event. You can do stuff like publish, and know you'll get it
			// Or just use the connected event to confirm you are subscribed for
			// UI / internal notifications, etc
		} else if ($status->getCategory() === PNStatusCategory::PNDecryptionErrorCategory) {
			// Handle message decryption error. Probably client configured to
			// encrypt messages and on live data feed it received plain text.
		}
	}

	function message($pubnub, $message) {
		// Handle new message stored in message.message
	}

	function presence($pubnub, $presence) {
		// handle incoming presence data
	}
}

$subscribeCallback = new MySubscribeCallback();


/**
 * Tea command
 */
class TeaCommand extends \PhpSlackBot\Command\BaseCommand {

	private $initiator;
	private $drinks = array();
	private $status = 'Tea timer has not been started.';
	private $pubNub = null;

	// Constructor to pass PubNub object
	public function __construct(PubNub $newPubNub) {
		$this->pubNub = $newPubNub;
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

			//timer event
			$loop = React\EventLoop\Factory::create();
			$loop->addTimer(10.10, function () {
				$this->send($this->getCurrentChannel(), null, "Your tea is ready.");
			 	//echo 'Your tea is ready.' . PHP_EOL;
				$this->pubNub->publish()
					->channel("tea")
					->message(['Tea Has Finished', "Finished"])
					->sync();
				$this->status = 'Tea timer has not been started.';
			});

			$this->status = 'running';
			$this->initiator = $this->getCurrentUser();
			$this->drinks = array();

			$teaPot = new stdClass();
			$teaPot->user = $this->getUserNameFromUserId($this->initiator);
			$teaPot->time = round(microtime(true) * 1000) + 418000;
			$teaPot->message = "Your tea has started brewing!";

			$result = $this->pubNub->publish()
				->channel("tea")
				->message($teaPot)
				->sync();

			print_r($result);

			//$startLoop->run();
			$loop->run();
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
 * Violent Delights
 */
class Westworld extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('Who am I?');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null, "Visit Westworld and you shall see. https://68.media.tumblr.com/bfa34642b48a1db454f020aab860b4e8/tumblr_ofpimkthf51u6p9qxo1_500.gif");

	}
}

/**
 * About Snackbot
 */
class About extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('I need some snacks');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null, "While I cannot deliver a snack to you, how about a tea, coffee, or bagel? I can let you know when it's ready. Just type `!help` to see a list of commands :tea:");

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


//variable that houses pub and sub keys
$pubNubConfig = json_decode($config["pubNub"]);

$pnconf = new PNConfiguration();
$pnconf->setSubscribeKey($pubNubConfig->subscribe);
$pnconf->setPublishKey($pubNubConfig->publish);
$pnconf->setSecure(true);

// new pubnub object
$newPubNub = new PubNub($pnconf);

$bot = new Bot();
$bot->setToken($slack);
// pubnub object is passed to TeaCommand
$bot->loadCommand(new TeaCommand($newPubNub));
$bot->loadCommand(new CoffeeCommand());
$bot->loadCommand(new BagelCommand());
$bot->loadCommand(new HelpCommand());
$bot->loadCommand(new Westworld());
$bot->loadCommand(new About());
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