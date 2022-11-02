<?php

namespace TuleapWikiFarm;

use Database;
use DatabaseMysqlBase;
use DBConnectionError;
use IDatabase;
use MysqlInstaller as Base;
use Status;

class MySQLSSLInstaller extends Base {

	/**
	 * @inheritDoc
	 */
	public function openConnection() {
		$status = Status::newGood();

		try {
			/** @var DatabaseMysqlBase $db */
			$db = Database::factory( 'mysql', [
				'host' => $this->getVar( 'wgDBserver' ),
				'user' => $this->getVar( '_InstallUser' ),
				'password' => $this->getVar( '_InstallPassword' ),
				'dbname' => false,
				'tablePrefix' => $this->getVar( 'wgDBprefix' ),
				'flags' => IDatabase::DBO_SSL,
			] );
			$status->value = $db;
		} catch ( DBConnectionError $e ) {
			$status->fatal( 'config-connection-error', $e->getMessage() );
		}

		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getLocalSettings() {
		$parentContent = parent::getLocalSettings();

		$content = "$parentContent
# Tuleap MySQL specific settings
\$wgDBssl = true;";

		return $content;
	}
}
