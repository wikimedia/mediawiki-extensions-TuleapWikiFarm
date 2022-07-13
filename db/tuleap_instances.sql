CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/tuleap_instances (
	`ti_id` INT UNSIGNED NOT NULL,
	`ti_name` VARCHAR(255) NOT NULL,
	`ti_status` VARCHAR(255) NOT NULL,
	`ti_created_at` VARCHAR(14) NOT NULL,
	`ti_directory` VARCHAR(255) NULL,
    `ti_database` VARCHAR(255) NULL,
    `ti_dbprefix` VARCHAR(255) NULL,
	`ti_script_path` VARCHAR(255) NULL,
    `ti_data` BLOB NULL DEFAULT '',
    PRIMARY KEY ( `ti_id` ),
    KEY idx_name ( `ti_name` ),
    KEY idx_script_path ( `ti_script_path` ),
    KEY idx_dbprefix ( `ti_dbprefix` )
);
