<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeBotCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'messenger:make:bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new bot handler class.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Bot Handler';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/bot.stub';
    }

    /**
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Bots';
    }
}
