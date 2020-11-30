<?php

namespace RTippin\Messenger\Actions\Calls;

use Exception;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Contracts\VideoDriver;
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
     * Teardown the call with the specified driver in our config
     *
     * @param mixed ...$parameters
     * @var Call $call $parameters[0]
     * @return $this
     * @throws Exception
     */
    public function execute(...$parameters): self
    {
        $this->setCall($parameters[0])
            ->teardownCallWithProvider();

        return $this;
    }

    /**
     * @throws Exception
     */
    private function teardownCallWithProvider(): void
    {
        if( ! $this->videoDriver->destroy($this->getCall()))
        {
            $this->throwTeardownError();
        }
    }

    /**
     * @throws Exception
     */
    private function throwTeardownError(): void
    {
        throw new Exception('Teardown video provider failed.');
    }
}