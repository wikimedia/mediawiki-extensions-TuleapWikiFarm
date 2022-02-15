<?php

namespace TuleapWikiFarm\ProcessStep\Maintenance;

class RunJobs extends MaintenanceScript {
	/**
	 * @inheritDoc
	 */
	protected function getFormattedArgs(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	protected function getScriptPath(): string {
		return 'maintenance/runJobs.php';
	}
}
