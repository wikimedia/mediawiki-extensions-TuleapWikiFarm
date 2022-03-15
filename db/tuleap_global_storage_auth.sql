CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/tuleap_global_storage_auth (
	`tgsa_instance` VARCHAR(255) NOT NULL,
	`tgsa_state` VARCHAR(255) NOT NULL,
    PRIMARY KEY ( `tgsa_instance` )
);
