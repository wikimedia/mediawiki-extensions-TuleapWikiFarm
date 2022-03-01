<?php

use MediaWiki\MediaWikiServices;
use TuleapWikiFarm\InstanceManager;
use TuleapWikiFarm\InstanceStore;

return [
	'InstanceStore' => static function ( MediaWikiServices $services ) {
		return new InstanceStore( $services->getDBLoadBalancer() );
	},
	'InstanceManager' => static function ( MediaWikiServices $services ) {
		return new InstanceManager(
			$services->getService( 'InstanceStore' ),
			new HashConfig(
				$services->getMainConfig()->get( 'TuleapFarmConfig' )
			)
		);
	}
];
