<?php

namespace RTippin\Messenger\Commands;

use Illuminate\Console\GeneratorCommand;

class MakePackagedBotCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'messenger:make:packaged-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new packaged bot class.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Packaged Bot';

    /**
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/packaged-bot.stub';
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
