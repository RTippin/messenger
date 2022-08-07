<?php

namespace RTippin\Messenger\MessageTypes;

class System extends Base
{
    protected bool $isSystem = true;

    public function __construct(
        int $code = null,
        string $verbose = null,
    )
    {
        $this->code = $code ?? $this->code;
        $this->verbose = $verbose ?? $this->verbose;
    }

}
