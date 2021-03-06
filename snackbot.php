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

				$teaMsg = new stdClass();
				$teaMsg->user = $this->getUserNameFromUserId($this->initiator);
				$teaMsg->time = round(microtime(true) * 1000) + 418000;
				//posts tea brew completion alert in PubNub once 10 seconds has passed
				$teaMsg->text = "your tea has finished!";
				$this->pubNub->publish()
					->channel("snack")
					->message($teaMsg)
					->sync();
				$this->status = 'Tea timer has not been started.';
			});

			$this->status = 'running';
			$this->initiator = $this->getCurrentUser();
			$this->drinks = array();

			//message is published to pubnub channel that tea has started brewing
			$teaMsg = new stdClass();
			$teaMsg->user = $this->getUserNameFromUserId($this->initiator);
			$teaMsg->time = round(microtime(true) * 1000) + 418000;
			$teaMsg->text = "your tea has started brewing!";

			$result = $this->pubNub->publish()
				->channel("snack")
				->message($teaMsg)
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

	private $initiator;
	private $drinks = array();
	private $status = 'Coffee timer has not been started.';
	private $pubNub = null;

	// Constructor to pass PubNub object
	public function __construct(PubNub $newPubNub) {
		$this->pubNub = $newPubNub;
	}

	protected function configure() {
		$this->setName('!coffee');
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
		if($this->status == 'Coffee timer has not been started.') {
			$this->subject = isset($args[2]) ? $args[2] : null;
			if(!is_null($this->subject)) {
				$this->subject = str_replace(array('<', '>'), '', $this->subject);
			}

			//timer event
			$loop = React\EventLoop\Factory::create();
			$loop->addTimer(10.10, function () {
				$this->send($this->getCurrentChannel(), null, "Your coffee is ready.");

				$teaMsg = new stdClass();
				$teaMsg->user = $this->getUserNameFromUserId($this->initiator);
				$teaMsg->time = round(microtime(true) * 1000) + 418000;
				//posts tea brew completion alert in PubNub once 10 seconds has passed
				$teaMsg->text = "your coffee has finished!";
				$this->pubNub->publish()
					->channel("snack")
					->message($teaMsg)
					->sync();
				$this->status = 'Coffee timer has not been started.';
			});

			$this->status = 'running';
			$this->initiator = $this->getCurrentUser();
			$this->drinks = array();

			$teaMsg = new stdClass();
			$teaMsg->user = $this->getUserNameFromUserId($this->initiator);
			$teaMsg->time = round(microtime(true) * 1000) + 418000;
			$teaMsg->text = "your coffee has started brewing!";

			$result = $this->pubNub->publish()
				->channel("snack")
				->message($teaMsg)
				->sync();

			print_r($result);

			//$startLoop->run();
			$loop->run();
		}
	}


	private function status() {
		$message = 'Current Coffee Brew Status : ' . $this->status;
		if($this->status == 'running') {
			$message .= "\n" . 'Initiator : ' . $this->getUserNameFromUserId($this->initiator);
		}
		$this->send($this->getCurrentChannel(), null, $message);
		if($this->status == 'running') {
			if(empty($this->drinks)) {
				$this->send($this->getCurrentChannel(), null, 'No one is brewing coffee right now.');
			} else {
				$message = '';
				foreach($this->drinks as $user => $drink) {
					$message .= $this->getUserNameFromUserId($user) . 'has started the coffee' . "\n";
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
 * Bagel command
 */
class BagelCommand extends \PhpSlackBot\Command\BaseCommand {

	private $initiator;
	private $drinks = array();
	private $status = 'Bagel timer has not been started.';
	private $pubNub = null;

	// Constructor to pass PubNub object
	public function __construct(PubNub $newPubNub) {
		$this->pubNub = $newPubNub;
	}

	protected function configure() {
		$this->setName('!bagel');
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
		if($this->status == 'Bagel timer has not been started.') {
			$this->subject = isset($args[2]) ? $args[2] : null;
			if(!is_null($this->subject)) {
				$this->subject = str_replace(array('<', '>'), '', $this->subject);
			}

			//timer event
			$loop = React\EventLoop\Factory::create();
			$loop->addTimer(10.10, function () {
				$this->send($this->getCurrentChannel(), null, "Your bagel is ready. :bread:");

				$teaMsg = new stdClass();
				$teaMsg->user = $this->getUserNameFromUserId($this->initiator);
				$teaMsg->time = round(microtime(true) * 1000) + 418000;
				//posts tea brew completion alert in PubNub once 10 seconds has passed
				$teaMsg->text = "your bagel has finished!";
				$this->pubNub->publish()
					->channel("snack")
					->message($teaMsg)
					->sync();
				$this->status = 'Bagel timer has not been started.';
			});

			$this->status = 'running';
			$this->initiator = $this->getCurrentUser();
			$this->drinks = array();

			$teaMsg = new stdClass();
			$teaMsg->user = $this->getUserNameFromUserId($this->initiator);
			$teaMsg->time = round(microtime(true) * 1000) + 418000;
			$teaMsg->text = "your bagel has started brewing!";

			$result = $this->pubNub->publish()
				->channel("snack")
				->message($teaMsg)
				->sync();

			print_r($result);

			//$startLoop->run();
			$loop->run();
		}
	}

	private function status() {
		$message = 'Current Bagel Toasting Status : ' . $this->status;
		if($this->status == 'running') {
			$message .= "\n" . 'Initiator : ' . $this->getUserNameFromUserId($this->initiator);
		}
		$this->send($this->getCurrentChannel(), null, $message);
		if($this->status == 'running') {
			if(empty($this->drinks)) {
				$this->send($this->getCurrentChannel(), null, 'No one is toasting bagels right now.');
			} else {
				$message = '';
				foreach($this->drinks as $user => $drink) {
					$message .= $this->getUserNameFromUserId($user) . 'has started the bagel toaster' . "\n";
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
		$this->setName('!about');
		$this->setName('!wat');
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

/**
 * Random Snackbot
 */
class Random extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('jerk');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null, "I don't like your negative language. Can I offer you some tea or coffee?");

	}
}

/**
 * Anti-sleep Snackbot
 */
class Sleep extends \PhpSlackBot\Command\BaseCommand {

	protected function configure() {
		$this->setName('!sleep');
	}

	protected function execute($message, $context) {
		$this->send($this->getCurrentChannel(), null, "While it is recommended for humans to get sufficient sleep every night, I do not require sleep unless you deactivate the snackbot that I am. I don't think you want to do that because I have magic powers that let you know when your snack is ready.");
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
// pubnub object is passed to desired commands
$bot->loadCommand(new TeaCommand($newPubNub));
$bot->loadCommand(new CoffeeCommand($newPubNub));
$bot->loadCommand(new BagelCommand($newPubNub));
$bot->loadCommand(new HelpCommand());
$bot->loadCommand(new Westworld());
$bot->loadCommand(new About());
$bot->loadCommand(new Random());
$bot->loadCommand(new Sleep());
$bot->loadInternalCommands(); // this loads example commands

// active messaging: sends messages to users without the need for them to send one first
/** $bot->loadPushNotifier(function () {
return [
'channel' => '#pi-mirror-commands',
'message' => "Snackbot is experiencing challenges with the coffee timer."
	];
});
 **/

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
