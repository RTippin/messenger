<?php

namespace RTippin\Messenger\Tests\Services;

use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Services\BotMatchingService;
use RTippin\Messenger\Tests\FeatureTestCase;

class BotMatchingServiceTest extends FeatureTestCase
{
    private BotMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new BotMatchingService;
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesExact
     *
     * @param  $string
     */
    public function it_matches_exact($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_EXACT, '!Exact', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesExactCaseless
     *
     * @param  $string
     */
    public function it_matches_exact_caseless($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_EXACT_CASELESS, '!Exact', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotExact
     *
     * @param  $string
     */
    public function it_doesnt_match_exact($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_EXACT, '!Exact', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotExact
     *
     * @param  $string
     */
    public function it_doesnt_match_exact_caseless($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_EXACT_CASELESS, '!Exact', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesStartsWith
     *
     * @param  $string
     */
    public function it_matches_starts_with($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_STARTS_WITH, '!Starts', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesStartsWithCaseless
     *
     * @param  $string
     */
    public function it_matches_starts_with_caseless($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_STARTS_WITH_CASELESS, '!Starts', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotStartsWith
     *
     * @param  $string
     */
    public function it_doesnt_match_starts_with($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_STARTS_WITH, '!Starts', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotStartsWith
     *
     * @param  $string
     */
    public function it_doesnt_match_starts_with_caseless($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_STARTS_WITH_CASELESS, '!Starts', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesContains
     *
     * @param  $string
     */
    public function it_matches_contains($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_CONTAINS, '!Contains', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesContainsCaseless
     *
     * @param  $string
     */
    public function it_matches_contains_caseless($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_CONTAINS_CASELESS, '!Contains', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotContains
     *
     * @param  $string
     */
    public function it_doesnt_match_contains($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_CONTAINS, '!Contains', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotContains
     *
     * @param  $string
     */
    public function it_doesnt_match_contains_caseless($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_CONTAINS_CASELESS, '!Contains', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesContains
     * @dataProvider stringMatchesContainsAny
     *
     * @param  $string
     */
    public function it_matches_contains_any($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_CONTAINS_ANY, '!Contains', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringMatchesContainsCaseless
     * @dataProvider stringMatchesContainsAnyCaseless
     *
     * @param  $string
     */
    public function it_matches_contains_any_caseless($string)
    {
        $this->assertTrue($this->service->matches(MessengerBots::MATCH_CONTAINS_ANY_CASELESS, '!Contains', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotContainsAny
     *
     * @param  $string
     */
    public function it_doesnt_match_contains_any($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_CONTAINS_ANY, '!Contains', $string));
    }

    /**
     * @test
     *
     * @dataProvider stringNotContainsAny
     *
     * @param  $string
     */
    public function it_doesnt_match_contains_any_caseless($string)
    {
        $this->assertFalse($this->service->matches(MessengerBots::MATCH_CONTAINS_ANY_CASELESS, '!Contains', $string));
    }

    public static function stringMatchesExact(): array
    {
        return [
            'Exact string' => ['!Exact'],
            'Exact with trailing space' => ['!Exact '],
            'Exact with leading space' => [' !Exact'],
            'Exact with surrounding spaces' => [' !Exact '],
        ];
    }

    public static function stringMatchesManyTriggers(): array
    {
        return [
            ['!Exact'],
            ['!e'],
            ['test'],
            ['bafOOn'],
        ];
    }

    public static function stringMatchesExactCaseless(): array
    {
        return [
            'Exact caseless string' => ['!ExAcT'],
            'Exact caseless with trailing space' => ['!exAcT '],
            'Exact caseless with leading space' => [' !ExACT'],
            'Exact caseless with surrounding spaces' => [' !EXAcT '],
        ];
    }

    public static function stringNotExact(): array
    {
        return [
            'Contains another letter' => ['!Exactt'],
            'Starts with' => ['!Exact t'],
            'Starts with another character' => ['.!Exact'],
            'Ends with another character' => ['!Exact .'],
            'Missing bang' => ['Exact'],
            'Is null' => [null],
        ];
    }

    public static function stringMatchesStartsWith(): array
    {
        return [
            'Starts-with exact string' => ['!Starts'],
            'Starts-with with trailing space' => ['!Starts '],
            'Starts-with with leading space' => [' !Starts'],
            'Starts-with with surrounding spaces' => [' !Starts '],
            'Starts-with and trailing string' => ['!Starts this test'],
            'Starts-with with leading space and trailing string' => [' !Starts this test'],
        ];
    }

    public static function stringMatchesStartsWithCaseless(): array
    {
        return [
            'Starts-with exact string' => ['!StArTs'],
            'Starts-with with trailing space' => ['!StarTs '],
            'Starts-with with leading space' => [' !StArTs'],
            'Starts-with with surrounding spaces' => [' !StArTs '],
            'Starts-with and trailing string' => ['!StArTs this test'],
            'Starts-with with leading space and trailing string' => [' !StArTs this test'],
        ];
    }

    public static function stringNotStartsWith(): array
    {
        return [
            'Contains another letter' => ['!Startss'],
            'Starts with another character' => ['.!Starts '],
            'Contained at the end' => ['This !Starts'],
            'Is null' => [null],
        ];
    }

    public static function stringMatchesContains(): array
    {
        return [
            'Contains exact string' => ['!Contains'],
            'Contains exact with trailing space' => ['!Contains '],
            'Contains exact with leading space' => [' !Contains'],
            'Contains exact with surrounding spaces' => [' !Contains '],
            'Contains with trailing string' => ['!Contains more'],
            'Contains with leading string' => ['Hello !Contains'],
            'Contains with surrounding strings' => ['Hello !Contains something'],
        ];
    }

    public static function stringMatchesContainsCaseless(): array
    {
        return [
            'Contains exact string' => ['!CoNtAiNs'],
            'Contains exact with trailing space' => ['!CoNtAiNs '],
            'Contains exact with leading space' => [' !CoNtAiNs'],
            'Contains exact with surrounding spaces' => [' !CoNtAiNs '],
            'Contains with trailing string' => ['!CoNtAiNs more'],
            'Contains with leading string' => ['Hello !CoNtAiNs'],
            'Contains with surrounding strings' => ['Hello !CoNtAiNs something'],
        ];
    }

    public static function stringNotContains(): array
    {
        return [
            'Ends with another letter' => ['!Containss something'],
            'Leads with another letter' => ['It w!Contains'],
            'Between words' => ['We T!ContainsT words'],
            'Is null' => [null],
        ];
    }

    public static function stringMatchesContainsAny(): array
    {
        return [
            'Part of a leading string' => ['testing!Contains'],
            'Has trailing string' => ['!Containsmore'],
            'Contains in the middle of a word' => ['test!Containstest'],
        ];
    }

    public static function stringMatchesContainsAnyCaseless(): array
    {
        return [
            'Part of a leading string' => ['testing!CoNtaInS'],
            'Has trailing string' => ['!CoNtaInSmore'],
            'Contains in the middle of a word' => ['test!CoNtaInStest'],
        ];
    }

    public static function stringNotContainsAny(): array
    {
        return [
            'Missing letter' => ['!Contain'],
            'Leads with another letter while missing letter' => ['It w!Contain'],
            'Between words while missing letter' => ['We T!ContainT words'],
            'Is null' => [null],
        ];
    }
}
