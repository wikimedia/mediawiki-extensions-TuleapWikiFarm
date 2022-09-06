<?php

namespace TuleapWikiFarm\Rest;

class InstanceStatusHandler extends InstanceHandler {

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->assertRights();

		$params = $this->getValidatedParams();
		if ( $params['name'] === '_list' ) {
			return $this->getResponseFactory()->createJson(
				$this->getManager()->getStore()->getInstanceNames()
			);
		}
		$instance = $this->getInstance();
		return $this->returnJson( $instance->jsonSerialize() );
	}
}
