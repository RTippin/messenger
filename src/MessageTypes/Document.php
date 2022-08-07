<?php

namespace RTippin\Messenger\MessageTypes;

use RTippin\Messenger\Models\Message;

class Document extends Base
{
    protected int $code = 2;

    protected string $verbose = 'DOCUMENT_MESSAGE';

    public function getResourceData(Message $message): ?array
    {
        return ['document' => $message->getDocumentDownloadRoute()];
    }
}
