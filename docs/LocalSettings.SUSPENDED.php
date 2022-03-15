<?php

// This file is only loaded when accessing a SUSPENDED instance

// It will give permission to users in group `sysop` only...
$GLOBALS['wgGroupPermissions'] = [
	"sysop" => array_merge(
		$GLOBALS['wgGroupPermissions']['sysop'],
		[
			'read' => true,
			'edit' => true,
			'delete' => true,
			'move' => true
		]
	)
];

// ... and display a banner
$GLOBALS['wgHooks']['BeforePageDisplay'][] = function ( OutputPage $out, Skin $skin ) {
	$out->addHTML(
		Html::rawElement( 'div', [
			'style' =>
				'width: 20%; height: 40px; background-color: #d73939; ' .
				'position: fixed; top: 0; left: 40%; text-align: center;' .
				'border-radius: 0 0 10px 10px;'
		], Html::element(
			'span', [
				'style' => 'color: white; font-weight: bold; font-size: 20px;'
			], 'SUSPENDED!'
		) )
	);
};
