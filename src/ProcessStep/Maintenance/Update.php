<?php

namespace TuleapWikiFarm\ProcessStep\Maintenance;

use Symfony\Component\Process\Process;

class Update extends MaintenanceScript {
	/**
	 * @inheritDoc
	 */
	protected function getFormattedArgs(): array {
		return [ '--quick' ];
	}

	/**
	 * @inheritDoc
	 */
	protected function getScriptPath(): string {
		return 'maintenance/update.php';
	}

	/**
	 * @inheritDoc
	 */
	protected function shouldSetMaintenanceMode(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyProcess( Process $process ) {
		$process->setTimeout( null );
	}
}
