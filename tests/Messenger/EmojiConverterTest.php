<?php

namespace RTippin\Messenger\Tests\Messenger;

use JoyPixels\Client;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Support\EmojiConverter;

class EmojiConverterTest extends TestCase
{
    private EmojiConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converter = new EmojiConverter(new Client);
    }

    /**
     * @test
     * @dataProvider hasEmojiStrings
     * @param $string
     * @param $string2
     */
    public function it_verifies_emoji_exist($string, $string2)
    {
        $this->assertTrue($this->converter->verifyHasEmoji($string));
        $this->assertTrue($this->converter->verifyHasEmoji($string2));
    }

    /**
     * @test
     * @dataProvider stringInputs
     * @param $string
     * @param $expected
     */
    public function converter_swaps_emojis_with_shortcode($string, $expected)
    {
        $this->assertSame($expected, $this->converter->toShort($string));
    }

    public function stringInputs(): array
    {
        return [
            ['Test string. No emoji to see here.', 'Test string. No emoji to see here.'],
            ['', ''],
            ["This may be a long sentence. We're quite excited! 123 %$^", "This may be a long sentence. We're quite excited! 123 %$^"],
            ['We are 😀', 'We are :grinning:'],
            ['Poop. 💩💩💩💩', 'Poop. :poop::poop::poop::poop:'],
            ['👍👎👍👎Yes👍', ':thumbsup::thumbsdown::thumbsup::thumbsdown:Yes:thumbsup:'],
            ['Spacing 💀 is 💀 preserved.💀', 'Spacing :skull: is :skull: preserved.:skull:'],
            ["\u{1F480}", ':skull:'],
        ];
    }

    public function hasEmojiStrings(): array
    {
        return [
            ['We are 😀', 'We are :grinning:'],
            ['Poop. 💩💩💩💩', 'Poop. :poop::poop::poop::poop:'],
            ['👍👎👍👎Yes👍', ':thumbsup::thumbsdown::thumbsup::thumbsdown:Yes:thumbsup:'],
            ['Spacing 💀 is 💀 preserved.💀', 'Spacing :skull: is :skull: preserved.:skull:'],
            ["\u{1F480}", ':skull:'],
        ];
    }
}
