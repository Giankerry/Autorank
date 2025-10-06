<?php

namespace App\Exceptions;

use Exception;

class GoogleAccountDisconnectedException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string|null $message
     */
    public function __construct($message = null)
    {
        // Use the provided message or a default if none is given.
        parent::__construct($message ?? 'The user\'s Google account is not linked or has been disconnected. Please reconnect it on the settings page.');
    }
}
