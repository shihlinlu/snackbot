<?php
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
require 'vendor/autoload.php';
use PhpSlackBot\Bot;

// custom command
class MyCommand extends \PhpSlackBot\Command\BaseCommand {

	//private $count = 0;
	private $initiator;
	private $scores = array();
	private $status = 'free';

	protected function configure() {
		$this->setName('!snack');
	}

	protected function execute($message, $context) {
		$args = $this->getArgs($message);
		$command = isset($args[1]) ? $args[1] : '';

		switch($command) {
			case 'tea':
				$this->tea($args);
				break;
			case 'status':
				$this->status();
				break;
			default:
				$this->send($this->getCurrentChannel(), $this->getCurrentUser(),'Error!!!. Use "'.$this->getName().' tea" or "'.$this->getName().' coffee"');

		}
	}

	/**
	 * Tea function
	 */
	private function tea($args) {
		if ($this->status == 'free') {
			$this->subject = isset($args[2]) ? $args[2] : null;
			if (!is_null($this->subject)) {
				$this->subject = str_replace(array('<', '>'), '', $this->subject);
			}
			$this->status = 'running';
			$this->initiator = $this->getCurrentUser();
			$this->scores = array();
			$this->send($this->getCurrentChannel(), null,
				'The snack timer has been started by '.$this->getUserNameFromUserId($this->initiator)."\n".
				'Please vote'.(!is_null($this->subject) ? ' for '.$this->subject : ''));
			$this->send($this->getCurrentChannel(), $this->getCurrentUser(), 'Use "'.$this->getName().' done" to end the session');
		}
		else {
			$this->send($this->getCurrentChannel(), $this->getCurrentUser(), 'A tea session is still active');
		}
	}

	private function status() {
		$message = 'Current status : '.$this->status;
		if ($this->status == 'running') {
			$message .= "\n".'Initiator : '.$this->getUserNameFromUserId($this->initiator);
		}
		$this->send($this->getCurrentChannel(), null, $message);
		if ($this->status == 'running') {
			if (empty($this->scores)) {
				$this->send($this->getCurrentChannel(), null, 'Your snack is not ready yet!');
			}
			else {
				$message = '';
				foreach ($this->scores as $user => $score) {
					$message .= $this->getUserNameFromUserId($user).' has voted'."\n";
				}
				$this->send($this->getCurrentChannel(), null, $message);
			}
		}
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
