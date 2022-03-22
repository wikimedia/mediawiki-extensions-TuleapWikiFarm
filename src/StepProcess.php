<?php

namespace TuleapWikiFarm;

use Exception;

class StepProcess {
	/** @var array */
	private $steps;

	/**
	 * @param array $steps
	 */
	public function __construct( $steps ) {
		$this->steps = $steps;
	}

	/**
	 * @param array|null $data
	 * @return array
	 * @throws Exception
	 */
	public function process( $data = [] ) {
		$of = \MediaWiki\MediaWikiServices::getInstance()->getObjectFactory();
		foreach ( $this->steps as $name => $spec ) {
			try {
				$object = $of->createObject( $spec );
				if ( !( $object instanceof IProcessStep ) ) {
					throw new Exception(
						"Specification of step \"$name\" does not produce object of type " .
						IProcessStep::class
					);
				}

				$data = $object->execute( $data );
			} catch ( Exception $ex ) {
				throw new Exception(
					"Step \"$name\" failed: " . $ex->getMessage(),
					$ex->getCode(),
					$ex
				);
			}
		}
		return $data;
	}
}
