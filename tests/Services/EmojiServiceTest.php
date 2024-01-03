<?php

namespace RTippin\Messenger\Tests\Services;

use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Tests\MessengerTestCase;

class EmojiServiceTest extends MessengerTestCase
{
    private EmojiInterface $emoji;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emoji = app(EmojiInterface::class);
    }

    /**
     * @test
     *
     * @dataProvider hasEmojiStrings
     *
     * @param  $string
     * @param  $string2
     */
    public function it_verifies_emoji_exist($string, $string2)
    {
        $this->assertTrue($this->emoji->verifyHasEmoji($string));
        $this->assertTrue($this->emoji->verifyHasEmoji($string2));
    }

    /**
     * @test
     *
     * @dataProvider doesntHaveEmojiStrings
     *
     * @param  $string
     */
    public function it_verifies_emoji_doesnt_exist($string)
    {
        $this->assertFalse($this->emoji->verifyHasEmoji($string));
    }

    /**
     * @test
     *
     * @dataProvider hasShortcodeResponse
     *
     * @param  $string
     * @param  $expected
     */
    public function it_returns_valid_emojis_as_shortcode_array($string, $expected)
    {
        $this->assertSame($expected, $this->emoji->getValidEmojiShortcodes($string));
    }

    /**
     * @test
     *
     * @dataProvider doesntHaveEmojiStrings
     *
     * @param  $string
     */
    public function it_returns_empty_shortcode_array_if_no_valid_emojis($string)
    {
        $this->assertCount(0, $this->emoji->getValidEmojiShortcodes($string));
    }

    /**
     * @test
     *
     * @dataProvider hasSingleShortcodeResponse
     *
     * @param  $string
     * @param  $expected
     */
    public function it_returns_first_valid_emojis_as_shortcode_or_null($string, $expected)
    {
        $this->assertSame($expected, $this->emoji->getFirstValidEmojiShortcode($string));
    }

    /**
     * @test
     *
     * @dataProvider stringInputs
     *
     * @param  $string
     * @param  $expected
     */
    public function converter_swaps_emojis_with_shortcode($string, $expected)
    {
        $this->assertSame($expected, $this->emoji->toShort($string));
    }

    public static function stringInputs(): array
    {
        return [
            ['Test string. No emoji to see here.', 'Test string. No emoji to see here.'],
            ['', ''],
            ["This may be a long sentence. We're quite excited! 123 %$^", "This may be a long sentence. We're quite excited! 123 %$^"],
            ['We are 😀', 'We are :grinning:'],
            ['Poop. 💩💩💩💩', 'Poop. :poop::poop::poop::poop:'],
            ['👍👎👍👎Yes👍', ':thumbsup::thumbsdown::thumbsup::thumbsdown:Yes:thumbsup:'],
            ['Spacing 💀 is 💀 preserved.💀', 'Spacing :skull: is :skull: preserved.:skull:'],
            ['Spacing :) is still preserved >.<', 'Spacing :slight_smile: is still preserved :persevere:'],
            ["\u{1F480}", ':skull:'],
            ['testing :) ascii', 'testing :slight_smile: ascii'],
        ];
    }

    public static function hasEmojiStrings(): array
    {
        return [
            ['We are 😀', 'We are :grinning:'],
            ['Poop. 💩💩💩💩', 'Poop. :poop::poop::poop::poop:'],
            ['👍👎👍👎Yes👍', ':thumbsup::thumbsdown::thumbsup::thumbsdown:Yes:thumbsup:'],
            ['Spacing 💀 is 💀 preserved.💀', 'Spacing :skull: is :skull: preserved.:skull:'],
            ['Spacing :) is still preserved >.<', 'Spacing :slight_smile: is still preserved :persevere:'],
            ["\u{1F480}", ':skull:'],
            [':)', ':slight_smile:'],
        ];
    }

    public static function doesntHaveEmojiStrings(): array
    {
        return [
            ['Test string. No emoji to see here.'],
            [''],
            [null],
            ["This may be a long sentence. We're quite excited! 123 %$^"],
            [':fake: :4040404: :notfound:'],
        ];
    }

    public static function hasShortcodeResponse(): array
    {
        return [
            ['We are 😀', [':grinning:']],
            ['Poop. 💩💩💩💩', [':poop:', ':poop:', ':poop:', ':poop:']],
            ['Spacing 💀 is 💀 preserved.💀 :notfound::undefined::poop:', [':skull:', ':skull:', ':skull:', ':poop:']],
            [':)', [':slight_smile:']],
            ['>.< lol :)', [':persevere:', ':slight_smile:']],
        ];
    }

    public static function hasSingleShortcodeResponse(): array
    {
        return [
            ['We are 😀', ':grinning:'],
            ['Poop. 💩💩💩💩', ':poop:'],
            ['Spacing 💀 is 💀 preserved.💀 :notfound::undefined::poop:', ':skull:'],
            [':)', ':slight_smile:'],
            ['>.< lol :)', ':persevere:'],
            [':unknown:', null],
            ['< 3', null],
        ];
    }
}
