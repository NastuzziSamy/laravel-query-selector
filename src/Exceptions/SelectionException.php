<?php

namespace NastuzziSamy\Laravel\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Allow the developer to distinct the Exception and now that error happened during the selection
 */
class SelectionException extends HttpException {
    public function __construct(string $message = null, int $statusCode = 400, \Exception $previous = null, array $headers = array(), ?int $code = 0) {
		return parent::__construct($statusCode, $message, $previous, $headers, $code);
	}
}
