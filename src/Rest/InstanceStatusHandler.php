<?php

namespace TuleapWikiFarm\Rest;

class InstanceStatusHandler extends InstanceHandler {

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertRights();
		$instance = $this->getInstance();

		return $this->getResponseFactory()->createJson( $instance->jsonSerialize() );
	}
}
