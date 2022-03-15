<?php

namespace TuleapWikiFarm;

use Wikimedia\Rdbms\ILoadBalancer;

class GlobalStorage {
	/** @var ILoadBalancer */
	private $lb;

	/**
	 * @param ILoadBalancer $lb
	 */
	public function __construct( ILoadBalancer $lb ) {
		$this->lb = $lb;
	}

	/**
	 * @param string $state
	 * @return string|null
	 */
	public function getAuthInstanceFromState( $state ): ?string {
		$row = $this->lb->getConnection( DB_REPLICA )->selectRow(
			'tuleap_global_storage_auth',
			[
				'tgsa_instance'
			],
			[
				'tgsa_state' => $state,
			],
			__METHOD__
		);
		if ( !$row ) {
			return null;
		}
		return $row->tgsa_instance;
	}

	/**
	 * @param string $state
	 * @param string $instance
	 * @return bool
	 */
	public function setAuthRecord( $state, $instance ) {
		$this->destroyAuthRecordForInstance( $instance );
		return $this->lb->getConnection( DB_PRIMARY )->insert(
			'tuleap_global_storage_auth',
			[
				'tgsa_state' => $state,
				'tgsa_instance' => $instance
			],
			__METHOD__
		);
	}

	/**
	 * @param string $instance
	 * @return bool
	 */
	public function destroyAuthRecordForInstance( $instance ) {
		return $this->lb->getConnection( DB_PRIMARY )->delete(
			'tuleap_global_storage_auth',
			[
				'tgsa_instance' => $instance
			]
		);
	}
}
