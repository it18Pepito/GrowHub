<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

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
        Mail::extend('smtp', function (array $config) {
            $factory = new EsmtpTransportFactory;

            $scheme = $config['scheme'] ?? null;
            if (! $scheme) {
                $scheme = ((int) ($config['port'] ?? 0) === 465) ? 'smtps' : 'smtp';
            }

            // By default, allow corporate internal certificates if verify_peer is not explicitly true
            $verifyPeer = filter_var($config['verify_peer'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $config['verify_peer'] = $verifyPeer ? 'true' : 'false';

            $transport = $factory->create(new Dsn(
                $scheme,
                $config['host'],
                $config['username'] ?? null,
                $config['password'] ?? null,
                $config['port'] ?? null,
                $config
            ));

            $stream = $transport->getStream();
            if ($stream instanceof SocketStream) {
                if (isset($config['source_ip'])) {
                    $stream->setSourceIp($config['source_ip']);
                }

                if (isset($config['timeout'])) {
                    $stream->setTimeout($config['timeout']);
                }

                $sslOptions = $config['stream']['ssl'] ?? [
                    'verify_peer' => $verifyPeer,
                    'verify_peer_name' => $verifyPeer,
                    'allow_self_signed' => ! $verifyPeer,
                ];

                $stream->setStreamOptions(array_merge($stream->getStreamOptions(), [
                    'ssl' => $sslOptions,
                ]));
            }

            return $transport;
        });
    }
}
