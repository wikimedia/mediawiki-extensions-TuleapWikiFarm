<?php

namespace TuleapWikiFarm;

use DateTime;

class RootInstanceEntity extends InstanceEntity {
	public function __construct() {
		parent::__construct(
			'w', 0, new DateTime(), '/w', '', '/mediawiki/w', InstanceEntity::STATE_READY
		);
	}
}
