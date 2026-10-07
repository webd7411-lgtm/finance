<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Events\CommandStarting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hard technical safeguard: Permanently prohibit destructive database commands
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $destructiveCommands = ['migrate:fresh', 'db:wipe', 'migrate:reset'];
            if (in_array($event->command, $destructiveCommands)) {
                $event->output->writeln('<error>==============================================================</error>');
                $event->output->writeln('<error>[SAFETY BLOCKED] Destructive command "' . $event->command . '" is PERMANENTLY DISABLED!</error>');
                $event->output->writeln('<error>To protect real database records, fresh/wipe commands are prohibited.</error>');
                $event->output->writeln('<error>==============================================================</error>');
                exit(1);
            }
        });
    }
}
