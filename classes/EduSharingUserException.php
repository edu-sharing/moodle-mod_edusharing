<?php declare(strict_types=1);

namespace mod_edusharing;

use Exception;
use Throwable;

/**
 * Class EduSharingUserException
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 */
class EduSharingUserException extends Exception
{
    private ?string $htmlMessage;

    /**
     * Function __construct
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param string|null $htmlMessage
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, ?string $htmlMessage = null) {
        $this->htmlMessage = $htmlMessage;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Function getHtmlMessage
     *
     * @return string|null
     */
    public function getHtmlMessage(): ?string {
        return $this->htmlMessage;
    }
}
