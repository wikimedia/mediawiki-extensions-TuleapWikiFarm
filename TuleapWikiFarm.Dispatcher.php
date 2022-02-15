<?php

require_once $GLOBALS['IP'] . '/vendor/autoload.php';

$dbLB = \MediaWiki\MediaWikiServices::getInstance()->getDBLoadBalancer();

$store = new \TuleapWikiFarm\InstanceStore( $dbLB );
$manager = new \TuleapWikiFarm\InstanceManager( $store );

$dispatcher = new \TuleapWikiFarm\Dispatcher( $_SERVER, $_REQUEST, $GLOBALS, $manager );

foreach ( $dispatcher->getFilesToRequire() as $pathname ) {
	require $pathname;
}
