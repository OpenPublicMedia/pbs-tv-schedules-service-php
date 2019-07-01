<?php


namespace OpenPublicMedia\PbsTvSchedulesService\Exception;

use BadMethodCallException;
use Throwable;

/**
 * Class ApiKeyRequiredException
 *
 * @package OpenPublicMedia\PbsTvSchedulesService\Exception
 */
class ApiKeyRequiredException extends BadMethodCallException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (empty($message)) {
            $message = "An API key is required but not configured.";
        }
        parent::__construct($message, $code, $previous);
    }
}
