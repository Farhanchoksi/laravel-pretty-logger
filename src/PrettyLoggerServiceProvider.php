<?php

namespace Farhanchoksi\PrettyLogger;

use Farhanchoksi\PrettyLogger\Http\Middleware\PrettyLoggerMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class PrettyLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pretty-logger.php', 'pretty-logger');

        // Raw output only — no channel name, timestamp, level or context dump.
        $formatter = [
            'format' => "%message%\n",
            'dateFormat' => null,
            'allowInlineLineBreaks' => true,
            'ignoreContextNormalization' => true,
        ];

        // Register the two log channels the middleware writes to, without the
        // user having to touch config/logging.php.
        config()->set('logging.channels.pretty_logger_terminal', [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => StreamHandler::class,
            'handler_with' => ['stream' => 'php://stderr'],
            'formatter' => LineFormatter::class,
            'formatter_with' => $formatter,
        ]);

        config()->set('logging.channels.pretty_logger_file', [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => StreamHandler::class,
            'handler_with' => ['stream' => config('pretty-logger.file_path')],
            'formatter' => LineFormatter::class,
            'formatter_with' => $formatter,
        ]);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pretty-logger.php' => $this->app->configPath('pretty-logger.php'),
            ], 'pretty-logger-config');
        }

        // Push the middleware onto the global stack so every HTTP request is logged.
        // Pushing the class name keeps the middleware resolved lazily by the container.
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(PrettyLoggerMiddleware::class);
    }
}
