<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateMessengerSettingsTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        Messenger::setProvider($this->tippin);
    }
}
