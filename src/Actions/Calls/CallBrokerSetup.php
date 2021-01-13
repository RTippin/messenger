<?php

namespace RTippin\Messenger\Actions\Calls;

use Exception;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallBrokerSetup extends BaseMessengerAction
{
    /**
     * @var VideoDriver
     */
    private VideoDriver $videoDriver;

    /**
     * CallBrokerSetup constructor.
     *
     * @param VideoDriver $videoDriver
     */
    public function __construct(VideoDriver $videoDriver)
    {
        $this->videoDriver = $videoDriver;
    }

    /**
     * Setup the call with the driver specified in our config.
     *
     * @param mixed ...$parameters
     * @return $this
     * @var Thread[0]
     * @var Call[1]
     * @throws Exception
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->setCall($parameters[1])
            ->setupCallWithProvider()
            ->updateCall();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function setupCallWithProvider(): self
    {
        if (! $this->videoDriver->create($this->getThread(), $this->getCall())) {
            $this->throwSetupFailed();
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function throwSetupFailed(): void
    {
        throw new Exception('Setup video provider failed.');
    }

    /**
     * Update the call with the information we received from our video provider.
     */
    private function updateCall(): void
    {
        $this->setData(
            $this->getCall()
                ->update([
                    'setup_complete' => true,
                    'room_id' => $this->videoDriver->getRoomId(),
                    'room_pin' => $this->videoDriver->getRoomPin(),
                    'room_secret' => $this->videoDriver->getRoomSecret(),
                    'payload' => $this->videoDriver->getExtraPayload(),
                ])
        );
    }
}
