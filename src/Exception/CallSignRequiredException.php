<?php


namespace OpenPublicMedia\PbsTvSchedulesService\Exception;

use BadMethodCallException;
use Throwable;

/**
 * Class CallSignRequiredException
 *
 * @package OpenPublicMedia\PbsTvSchedulesService\Exception
 */
class CallSignRequiredException extends BadMethodCallException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (empty($message)) {
            $message = "A call sign is required but not configured.";
        }
        parent::__construct($message, $code, $previous);
    }
}
