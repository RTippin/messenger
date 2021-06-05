<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Models\Action;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\BotService;
use RTippin\Messenger\Tests\FeatureTestCase;

class BotServiceTest extends FeatureTestCase
{
    private BotService $botService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->botService = new BotService;
    }

    /**
     * @test
     * @dataProvider stringMatchesExact
     * @param $string
     */
    public function it_matches_exact($string)
    {
        $action = Action::factory()
            ->for(Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->trigger('!exact')
            ->match('exact')
            ->create();

        $this->assertTrue($this->botService->matches($action, $string));
    }

    /**
     * @test
     * @dataProvider stringMatchesStartsWith
     * @param $string
     */
    public function it_matches_starts_with($string)
    {
        $action = Action::factory()
            ->for(Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->trigger('!starts')
            ->match('starts-with')
            ->create();

        $this->assertTrue($this->botService->matches($action, $string));
    }

    /**
     * @test
     * @dataProvider stringMatchesContains
     * @param $string
     */
    public function it_matches_contains($string)
    {
        $action = Action::factory()
            ->for(Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->trigger('!contains')
            ->match('contains')
            ->create();

        $this->assertTrue($this->botService->matches($action, $string));
    }

    public function stringMatchesExact(): array
    {
        return [
            'Exact string' => ['!exact'],
            'Exact with trailing space' => ['!exact '],
            'Exact with leading space' => [' !exact'],
            'Exact with surrounding spaces' => [' !exact '],
        ];
    }

    public function stringMatchesStartsWith(): array
    {
        return [
            'Starts-with exact string' => ['!starts'],
            'Starts-with with trailing space' => ['!starts '],
            'Starts-with with leading space' => [' !starts'],
            'Starts-with with surrounding spaces' => [' !starts '],
            'Starts-with and trailing string' => ['!starts this test'],
            'Starts-with with leading space and trailing string' => [' !starts this test'],
        ];
    }

    public function stringMatchesContains(): array
    {
        return [
            'Contains exact string' => ['!contains'],
            'Contains exact with trailing space' => ['!contains '],
            'Contains exact with leading space' => [' !contains'],
            'Contains exact with surrounding spaces' => [' !contains '],
            'Contains with trailing string' => ['!contains more'],
            'Contains with leading string' => ['Hello !contains'],
            'Contains with surrounding strings' => ['Hello !contains something'],
        ];
    }
}
