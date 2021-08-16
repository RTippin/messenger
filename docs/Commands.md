# Commands

---

### `php artisan messenger:install`
- Installs the base messenger files. Publishes our config and service provider. This will also register the provider in your `app.php` in the providers array.

### `php artisan messenger:calls:check-activity` | `--now`
- Check active calls for active participants, end calls with none.
- `--now` flag to run immediately without dispatching jobs to queue.

### `php artisan messenger:calls:down` | `--duration=30` | `--now`
- End all active calls and disable the calling system for the specified minutes (30 default).
- `--duration=X` flag to set timeframe in minutes for calling to be disabled.
- `--now` flag to run immediately without dispatching jobs to queue.

### `php artisan messenger:calls:up`
- Put the call system back online if it is temporarily disabled.

### `php artisan messenger:invites:check-valid` | `--now`
- Check active invites for any past expiration or max use cases and invalidate them.
- `--now` flag to run immediately without dispatching jobs to queue.

### `php artisan messenger:purge:documents` | `--now` | `--days=30`
- We will purge all soft deleted document messages that were archived past the set days (30 default). We run it through our action to remove the document file from storage and message from the database.
- `--days=X` flag to set how many days in the past to start at.
- `--now` flag to run immediately without dispatching jobs to queue.

### `php artisan messenger:purge:audio` | `--now` | `--days=30`
- We will purge all soft deleted audio messages that were archived past the set days (30 default). We run it through our action to remove the audio file from storage and message from the database.
- `--days=X` flag to set how many days in the past to start at.
- `--now` flag to run immediately without dispatching jobs to queue.

### `php artisan messenger:purge:images` | `--now` | `--days=30`
- We will purge all soft deleted image messages that were archived past the set days (30 default). We run it through our action to remove the image from storage and message from the database.
- `--days=X` flag to set how many days in the past to start at.
- `--now` flag to run immediately without dispatching jobs to queue.

### `php artisan messenger:purge:messages` | `--days=30`
- We will purge all soft deleted messages that were archived past the set days (30 default). We do not need to fire any additional events or load models into memory, just remove from the table, as this is not messages that are documents or images.
- `--days=X` flag to set how many days in the past to start at.

### `php artisan messenger:purge:threads` | `--now` | `--days=30`
- We will purge all soft deleted threads that were archived past the set days (30 default). We run it through our action to remove the entire thread directory and sub files from storage and the thread from the database.
- `--days=X` flag to set how many days in the past to start at.
- `--now` flag to run immediately without dispatching jobs to queue.

### `php artisan messenger:purge:bots` | `--now` | `--days=30`
- We will purge all soft deleted bots that were archived past the set days (30 default). We run it through our action to remove the entire bot directory and sub files from storage and the bot from the database.
- `--days=X` flag to set how many days in the past to start at.
- `--now` flag to run immediately without dispatching jobs to queue.

---

## Example Kernel Scheduler using our commands
```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('messenger:calls:check-activity')->everyMinute();

        $schedule->command('messenger:invites:check-valid')->everyFifteenMinutes();

        $schedule->command('messenger:purge:threads')->dailyAt('1:00');

        $schedule->command('messenger:purge:messages')->dailyAt('2:00');

        $schedule->command('messenger:purge:images')->dailyAt('3:00');

        $schedule->command('messenger:purge:documents')->dailyAt('4:00');

        $schedule->command('messenger:purge:audio')->dailyAt('5:00');

        $schedule->command('messenger:purge:bots')->dailyAt('6:00');
    }
}
```
