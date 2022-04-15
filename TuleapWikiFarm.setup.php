<?php

require_once __DIR__ . '/TuleapWikiFarm.Dispatcher.php';
if ( FARMER_IS_ROOT_WIKI_CALL ) {
	wfLoadExtension( 'TuleapWikiFarm' );
}
