<?php

namespace TuleapWikiFarm\ProcessStep\Maintenance;

use TuleapWikiFarm\InstanceEntity;

class TerminateSessions extends MaintenanceScript {
	/**
	 * @inheritDoc
	 */
	protected function getFormattedArgs(): array {
		return [ '-u', $this->args['user'] ?? null ];
	}

	/**
	 * @inheritDoc
	 */
	protected function getScriptPath(): string {
		return 'extensions/TuleapWikiFarm/maintenance/terminateUserSession.php';
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function execute( $data = [] ): array {
		$user = $this->args['user'] ?? null;
		if ( $user ) {
			// If a user is specified, we need to execute a maintenance script of the instance
			// itself, or on all instances
			return parent::execute( $data );
		}
		// If user is NOT specified, just insert a instance date item to change the salt
		// for the session cookie => https://www.mediawiki.org/wiki/Manual:%24wgAuthenticationTokenVersion
		// This will be evaluated in the Dispatcher later on
		if ( !$this->instanceId && isset( $data['id'] ) ) {
			$this->instanceId = $data['id'];
		}
		$result = [];
		$newToken = md5( random_bytes( 32 ) );
		if ( $this->instanceId === -1 ) {
			$instances = $this->manager->getStore()->getInstanceNames();
			foreach ( $instances as $instanceName ) {
				$instance = $this->manager->getStore()->getInstanceByName( $instanceName );
				if ( !$instance ) {
					$result[$instanceName] = 'Instance not found';
					continue;
				}
				$result[$instanceName] = $this->terminateForInstance( $instance, $newToken );
			}
		} else {
			$instance = $this->manager->getStore()->getInstanceById( $this->instanceId );
			$result = $this->terminateForInstance( $instance, $newToken );
		}

		return [
			'id' => $this->instanceId,
			'stdout' => $result,
		];
	}

	/**
	 * @param InstanceEntity $instance
	 * @param string $newToken
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function terminateForInstance( InstanceEntity $instance, string $newToken ): bool {
		$instance->setDataItem( 'auth_token_version', $newToken );
		return $this->manager->getStore()->storeEntity( $instance );
	}
}
