<?php /** @noinspection PhpFieldAssignmentTypeMismatchInspection */

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\OnlineStatus;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class OnlineStatusTest extends FeatureTestCase
{
    private OnlineStatus $onlineStatus;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->onlineStatus = app(OnlineStatus::class);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function online_status_sets_away_cache()
    {
        $this->onlineStatus->withoutDispatches()->execute(true);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function online_status_sets_online_cache()
    {
        $this->onlineStatus->withoutDispatches()->execute(false);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function online_status_sets_no_cache_when_disabled_from_config()
    {
        Messenger::setOnlineStatus(false);

        $this->onlineStatus->withoutDispatches()->execute(false);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function online_status_does_not_touch_provider_when_set_to_offline()
    {
        $before = now()->subMinutes(5);

        $this->tippin->update([
            'updated_at' => $before,
        ]);

        Messenger::getProviderMessenger()->update([
            'online_status' => 0,
        ]);

        $this->onlineStatus->withoutDispatches()->execute(false);

        $this->assertSame($before->toDayDateTimeString(), $this->tippin->updated_at->toDayDateTimeString());
    }

    /** @test */
    public function online_status_touches_provider()
    {
        $before = now()->subMinutes(5);

        $this->tippin->update([
            'updated_at' => $before,
        ]);

        $this->onlineStatus->withoutDispatches()->execute(true);

        $this->assertNotSame($before->toDayDateTimeString(), $this->tippin->updated_at->toDayDateTimeString());
    }
}
