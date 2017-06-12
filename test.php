<?php
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
require 'vendor/autoload.php';
use PubNub\PNConfiguration;
use PubNub\PubNub;


$pnConfiguration = new PNConfiguration();

$pnConfiguration->setSubscribeKey("subscribe");
$pnConfiguration->setPublishKey("publish");

$pnConfiguration->setSecure(false);
$pubNub = new PubNub($pnConfiguration);

$result = $pubNub->publish()->channel("tea")->message(["Hello", "there"])->usePost(true)->sync();