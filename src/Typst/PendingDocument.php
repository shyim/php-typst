<?php

declare(strict_types=1);

namespace Typst;

use FFI;
use FFI\CData;
use Typst\Exception\LogicException;
use Typst\Exception\RuntimeException;

final class PendingDocument
{
    /** @internal */
    public function __construct(
        private CData $handle,
    ) {
    }

    public function __destruct()
    {
        if (!FFI::isNull($this->handle)) {
            Native::lib()->typst_pending_free($this->handle);
        }
    }

    public function __clone(): void
    {
        throw new LogicException('PendingDocument cannot be cloned');
    }

    public function isReady(): bool
    {
        return ((int) Native::lib()->typst_pending_is_ready($this->handle)) === 1;
    }

    /**
     * @return resource
     */
    public function getNotificationStream()
    {
        $fd = (int) Native::lib()->typst_pending_notification_fd($this->handle);
        if ($fd < 0) {
            Native::throwLastError();
        }

        $stream = fopen('php://fd/' . $fd, 'r');
        if ($stream === false) {
            throw new RuntimeException(
                'Failed to open notification stream',
                RuntimeException::COMPILATION_FAILED,
            );
        }

        return $stream;
    }

    public function join(): Document
    {
        $ptr = Native::lib()->typst_pending_join($this->handle);

        return new Document(Native::requirePtr($ptr, 'join'));
    }
}
