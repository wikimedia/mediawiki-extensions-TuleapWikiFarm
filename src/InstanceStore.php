<?php

namespace TuleapWikiFarm;

use Exception;
use Wikimedia\Rdbms\ILoadBalancer;

class InstanceStore {
	private const TABLE = 'tuleap_instances';
	private const FIELDS = [
		'ti_id',
		'ti_name',
		'ti_directory',
		'ti_status',
		'ti_created_at',
		'ti_script_path',
		'ti_database',
		'ti_dbprefix',
		'ti_data'
	];

	/** @var ILoadBalancer */
	private $loadBalancer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param string $name
	 * @return InstanceEntity|null
	 */
	public function getInstanceByName( $name ): ?InstanceEntity {
		if ( $name === 'w' ) {
			return new RootInstanceEntity();
		}
		$entities = $this->query( [ 'ti_name' => $name ] );
		return empty( $entities ) ? null : $entities[0];
	}

	/**
	 * @param int $id
	 * @return InstanceEntity|null
	 */
	public function getInstanceById( int $id ): ?InstanceEntity {
		$entities = $this->query( [ 'ti_id' => $id ] );
		return empty( $entities ) ? null : $entities[0];
	}

	/**
	 * @param string $name
	 * @param string $scriptPath
	 * @return bool
	 */
	public function instanceExists( $name, $scriptPath ): bool {
		return count( $this->query( [ 'ti_name' => $name ] ) ) > 0
			|| count( $this->query( [ 'ti_script_path' => $scriptPath ] ) ) > 0;
	}

	/**
	 * @param InstanceEntity $entity
	 * @return bool
	 * @throws Exception
	 */
	public function storeEntity( InstanceEntity $entity ): bool {
		if ( $entity instanceof RootInstanceEntity ) {
			throw new Exception( "Root instance not writable" );
		}
		$db = $this->loadBalancer->getConnection( DB_PRIMARY );
		if ( $this->getInstanceById( $entity->getId() ) instanceof InstanceEntity ) {
			$res = $db->update(
				static::TABLE,
				$entity->dbSerialize(),
				[ 'ti_id' => $entity->getId() ],
				__METHOD__
			);

			if ( $res ) {
				$entity->setDirty( false );
			}

			return $res;
		}

		$res = $db->insert(
			static::TABLE,
			array_merge( [ 'ti_id' => $entity->getId() ], $entity->dbSerialize() ),
			__METHOD__
		);

		if ( !$res ) {
			return false;
		}

		$entity->setDirty( false );
		return $res;
	}

	/**
	 * @param array $conditions
	 * @param bool|null $raw If true, raw db data will be returned
	 * @return array
	 */
	public function query( array $conditions, $raw = false ): array {
		$res = $this->loadBalancer->getConnection( DB_REPLICA )->select(
			static::TABLE,
			static::FIELDS,
			$conditions,
			__METHOD__
		);

		if ( !$res ) {
			return [];
		}

		$return = [];
		foreach ( $res as $row ) {
			if ( $raw ) {
				$return[] = $row;
			} else {
				$instance = $this->entityFromRow( $row );
				if ( $instance ) {
					$return[] = $instance;
				}
			}
		}

		return $return;
	}

	/**
	 * @return array
	 */
	public function getInstanceNames(): array {
		$res = $this->loadBalancer->getConnection( DB_REPLICA )->select(
			static::TABLE,
			[ 'ti_name' ],
			[],
			__METHOD__
		);

		$names = [];
		foreach ( $res as $row ) {
			$names[] = $row->ti_name;
		}

		return $names;
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function deleteInstance( int $id ) {
		return $this->loadBalancer->getConnection( DB_PRIMARY )->delete(
			static::TABLE,
			[ 'ti_id' => $id ],
			__METHOD__
		);
	}

	/**
	 * @param string $prefix
	 *
	 * @return bool
	 */
	public function dbPrefixTaken( $prefix ) {
		return (bool)$this->loadBalancer->getConnection( DB_REPLICA )->selectRow(
			static::TABLE,
			[ 'ti_dbprefix' ],
			[ 'ti_dbprefix' => $prefix ],
			__METHOD__
		);
	}

	/**
	 * @param \stdClass $row
	 * @return InstanceEntity|null
	 */
	private function entityFromRow( $row ): ?InstanceEntity {
		foreach ( static::FIELDS as $field ) {
			if ( !property_exists( $row, $field ) ) {
				return null;
			}
		}

		return new InstanceEntity(
			$row->ti_name,
			(int)$row->ti_id,
			\DateTime::createFromFormat( 'YmdHis', $row->ti_created_at ),
			$row->ti_directory,
			$row->ti_database,
			$row->ti_dbprefix,
			$row->ti_script_path,
			$row->ti_status,
			$row->ti_data ? json_decode( $row->ti_data, 1 ) : []
		);
	}
}
