<?php

namespace TuleapWikiFarm;

use DatabaseMysqlBase;
use DBConnectionError;
use IDatabase;
use MysqlInstaller as Base;
use Status;
use Wikimedia\Rdbms\DatabaseFactory;

class MySQLSSLInstaller extends Base {

	/**
	 * @inheritDoc
	 */
	public function openConnection() {
		$status = Status::newGood();

		try {
			/** @var DatabaseMysqlBase $db */
			$db = ( new DatabaseFactory() )->create( 'mysql', [
				'host' => $this->getVar( 'wgDBserver' ),
				'user' => $this->getVar( '_InstallUser' ),
				'password' => $this->getVar( '_InstallPassword' ),
				'dbname' => false,
				'tablePrefix' => $this->getVar( 'wgDBprefix' ),
				// This is deprecated. `ssl` param should be used instead, but it's not clear where.
				// Could not find it in
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
