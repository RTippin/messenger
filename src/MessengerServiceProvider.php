<?php

namespace RTippin\Messenger;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RTippin\Messenger\Commands\CallsActivityCheck;
use RTippin\Messenger\Commands\InvitesCheck;
use RTippin\Messenger\Commands\ProvidersCache;
use RTippin\Messenger\Commands\ProvidersClear;
use RTippin\Messenger\Commands\PurgeDocuments;
use RTippin\Messenger\Commands\PurgeImages;
use RTippin\Messenger\Commands\PurgeMessages;
use RTippin\Messenger\Commands\PurgeThreads;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\PushNotificationDriver;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Policies\CallParticipantPolicy;
use RTippin\Messenger\Policies\CallPolicy;
use RTippin\Messenger\Policies\FriendPolicy;
use RTippin\Messenger\Policies\InvitePolicy;
use RTippin\Messenger\Policies\MessagePolicy;
use RTippin\Messenger\Policies\ParticipantPolicy;
use RTippin\Messenger\Policies\PendingFriendPolicy;
use RTippin\Messenger\Policies\SentFriendPolicy;
use RTippin\Messenger\Policies\ThreadPolicy;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for messenger models.
     *
     * @var array
     */
    protected array $policies = [
        Call::class => CallPolicy::class,
        CallParticipant::class => CallParticipantPolicy::class,
        Thread::class => ThreadPolicy::class,
        Participant::class => ParticipantPolicy::class,
        Message::class => MessagePolicy::class,
        Invite::class => InvitePolicy::class,
        Friend::class => FriendPolicy::class,
        PendingFriend::class => PendingFriendPolicy::class,
        SentFriend::class => SentFriendPolicy::class
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'rtippin');
         $this->loadViewsFrom(__DIR__.'/../resources/views', 'messenger');
         $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        $this->registerPolicies();

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/messenger.php', 'messenger');

        $this->app->singleton(
            Messenger::class,
            Messenger::class
        );

        $this->app->alias(
            Messenger::class,
            'messenger'
        );

        $this->app->singleton(
            BroadcastDriver::class,
            $this->getBroadcastImplementation()
        );

        $this->app->singleton(
            PushNotificationDriver::class,
            $this->getPushNotificationImplementation()
        );

        $this->app->singleton(
            VideoDriver::class,
            $this->getVideoImplementation()
        );
    }

    /**
     * Get the driver set in config for our services broadcasting feature
     *
     * @return string
     */
    private function getBroadcastImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.broadcasting.driver');

        return $this->app['config']->get('messenger.drivers.broadcasting')[$alias ?? 'null'];
    }

    /**
     * Get the driver set in config for our services push notifications
     *
     * @return string
     */
    private function getPushNotificationImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.push_notifications.driver');

        return $this->app['config']->get('messenger.drivers.push_notifications')[$alias ?? 'null'];
    }

    /**
     * Get the driver set in config for our services video feature
     *
     * @return string
     */
    private function getVideoImplementation(): string
    {
        $alias = $this->app['config']->get('messenger.calling.driver');

        return $this->app['config']->get('messenger.drivers.calling')[$alias ?? 'null'];
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['messenger'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/messenger.php' => config_path('messenger.php'),
        ], 'messenger.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/rtippin'),
        ], 'messenger.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/rtippin'),
        ], 'messenger.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/rtippin'),
        ], 'messenger.views');*/

        // Registering package commands.
         $this->commands([
             CallsActivityCheck::class,
             InvitesCheck::class,
             ProvidersCache::class,
             ProvidersClear::class,
             PurgeDocuments::class,
             PurgeImages::class,
             PurgeMessages::class,
             PurgeThreads::class
         ]);
    }
    /**
     * Register the application's policies.
     *
     * @return void
     */
    private function registerPolicies()
    {
        foreach ($this->policies() as $key => $value) {
            Gate::policy($key, $value);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    private function policies()
    {
        return $this->policies;
    }

}
