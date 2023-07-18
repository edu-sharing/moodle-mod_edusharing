<?php

namespace mod_edusharing;

use Exception;
use Throwable;

class EduSharingUserException extends Exception
{
    private ?string $htmlMessage;
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, ?string $htmlMessage = null) {
        $this->htmlMessage = $htmlMessage;
        parent::__construct($message, $code, $previous);
    }

    public function getHtmlMessage(): ?string {
        return $this->htmlMessage;
    }
}