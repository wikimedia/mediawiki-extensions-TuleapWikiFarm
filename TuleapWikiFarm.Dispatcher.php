<?php

use MediaWiki\MediaWikiServices;
use TuleapWikiFarm\Dispatcher;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\InstanceStore;

require_once $GLOBALS['IP'] . '/vendor/autoload.php';

$dbLB = MediaWikiServices::getInstance()->getDBLoadBalancer();

$store = new InstanceStore( $dbLB );
$manager = new InstanceManager(
	$store,
	new HashConfig(
		$GLOBALS['wgTuleapFarmConfig']
	)
);

$dispatcher = new Dispatcher( $_SERVER, $_REQUEST, $GLOBALS, $manager );

foreach ( $dispatcher->getFilesToRequire() as $pathname ) {
	require $pathname;
}
