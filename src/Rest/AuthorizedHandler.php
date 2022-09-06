<?php

namespace TuleapWikiFarm\Rest;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Session\SessionManager;
use TuleapWikiFarm\StepProcess;

abstract class AuthorizedHandler extends Handler {
	/**
	 * This will evaluate session created by AuthenticationHeader (if passed)
	 * @throws HttpException
	 */
	protected function assertRights() {
		$meta = SessionManager::getGlobalSession()->getProviderMetadata();
		if (
			!is_array( $meta ) || !isset( $meta['rights'] ) ||
			!in_array( 'tuleap-farm-manage', $meta['rights'] )
		) {
			throw new HttpException( 'permissiondenied', 401 );
		}
	}

	/**
	 * @param array $response
	 * @param int|null $code
	 *
	 * @return Response
	 */
	protected function returnJson( $response, $code = 200 ): Response {
		$response = $this->getResponseFactory()->createJson( $response );
		$response->setStatus( $code );
		return $response;
	}

	/**
	 * @param StepProcess $process
	 * @param int|null $timeout
	 *
	 * @return Response
	 */
	protected function runProcess( StepProcess $process, ?int $timeout = 300 ): Response {
		$response = [];
		try {
			$data = $process->process( $timeout );
			$response['status'] = 'success';
			$response['output'] = $data;
			return $this->returnJson( $response );
		} catch ( \Exception $ex ) {
			$response['status'] = 'error';
			$response['error'] = [
				'code' => $ex->getCode(),
				'message' => $ex->getMessage(),
				'trace' => $ex->getTraceAsString(),
			];
			return $this->returnJson( $response, 500 );
		}
	}
}
