<?php

namespace RTippin\Messenger\Actions\Calls;

use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Exceptions\CallBrokerException;
use RTippin\Messenger\Models\Call;

class CallBrokerTeardown extends BaseMessengerAction
{
    /**
     * @var VideoDriver
     */
    private VideoDriver $videoDriver;

    /**
     * CallBrokerTeardown constructor.
     *
     * @param  VideoDriver  $videoDriver
     */
    public function __construct(VideoDriver $videoDriver)
    {
        $this->videoDriver = $videoDriver;
    }

    /**
     * Teardown the call with the video provider.
     *
     * @param  Call  $call
     * @return $this
     *
     * @throws CallBrokerException
     */
    public function execute(Call $call): self
    {
        $this->setCall($call);

        if (! $this->getCall()->isTornDown()) {
            $this->teardownCallWithProvider()->updateCall();
        }

        return $this;
    }

    /**
     * @throws CallBrokerException
     */
    private function teardownCallWithProvider(): self
    {
        if (! $this->videoDriver->destroy($this->getCall())) {
            throw new CallBrokerException('Teardown video provider failed.');
        }

        return $this;
    }

    /**
     * Update the call so that we know teardown has been successful.
     *
     * @return void
     */
    private function updateCall(): void
    {
        $this->getCall()->update([
            'teardown_complete' => true,
        ]);
    }
}
