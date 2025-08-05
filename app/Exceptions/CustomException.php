<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    private $exceptionData;

    public function __construct($message, $code = 0, Exception $previous = null, $exceptionData = [])
    {
        parent::__construct($message, $code, $previous);
        $this->exceptionData = $exceptionData;
    }

    public function getExceptionData()
    {
        return $this->exceptionData;
    }
}
