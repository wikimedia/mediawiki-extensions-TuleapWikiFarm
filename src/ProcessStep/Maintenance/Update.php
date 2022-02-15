<?php

namespace TuleapWikiFarm\ProcessStep\Maintenance;

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
}
