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
     * @param VideoDriver $videoDriver
     */
    public function __construct(VideoDriver $videoDriver)
    {
        $this->videoDriver = $videoDriver;
    }

    /**
     * Teardown the call with the specified driver in our config.
     *
     * @param mixed ...$parameters
     * @var Call[0]
     * @return $this
     * @throws CallBrokerException
     */
    public function execute(...$parameters): self
    {
        $this->setCall($parameters[0]->fresh())
            ->checkCallNeedsTearingDown()
            ->teardownCallWithProvider()
            ->updateCall();

        return $this;
    }

    /**
     * @throws CallBrokerException
     */
    private function teardownCallWithProvider(): self
    {
        if (! $this->videoDriver->destroy($this->getCall())) {
            $this->throwTeardownFailed('Teardown video provider failed.');
        }

        return $this;
    }

    /**
     * @return $this
     * @throws CallBrokerException
     */
    private function checkCallNeedsTearingDown(): self
    {
        if ($this->getCall()->isTornDown()) {
            $this->throwTeardownFailed('Call already torn down.');
        }

        return $this;
    }

    /**
     * @param string $message
     * @throws CallBrokerException
     */
    private function throwTeardownFailed(string $message): void
    {
        throw new CallBrokerException($message);
    }

    /**
     * Update the call so that we know teardown has been successful.
     */
    private function updateCall(): void
    {
        $this->setData(
            $this->getCall()
                ->update([
                    'teardown_complete' => true,
                ])
        );
    }
}
