<?php

namespace App\Commands;

use GhostZero\Maid\Maid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use function RingCentral\Psr7\parse_query;

class LoginCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'login {--console-only}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Authenticate with maid.sh';

    public function handle(): int
    {
        $authorizeUrl = sprintf('%s/oauth/authorize?%s', rtrim(Maid::$authBaseUrl, '/'), http_build_query([
            'client_id' => '96325d9f-5c72-4adf-a9a1-97197c36268a',
            'redirect_uri' => 'http://localhost:1337/cli',
            'response_type' => 'token',
            'scope' => '*',
            'state' => $state = Str::random(),
        ]));

        $loop = Loop::get();
        $socket = new SocketServer('127.0.0.1:1337', [], $loop);

        $server = new HttpServer(function (ServerRequestInterface $request) use ($loop, $socket, $state) {
            if ($request->getUri()->getPath() === '/callback') {
                $query = parse_query(Str::replaceFirst('#', '', $request->getBody()->getContents()));
                if (empty($query['state']) || $query['state'] !== $state) {
                    $this->info(sprintf('State %s is not the expected state %s.', $query['state'], $state));
                    return Response::plaintext('Invalid state.');
                }
                $query['expires_at'] = Carbon::now()->addSeconds($query['expires_in']);
                Storage::put('credentials.json', json_encode($query, JSON_PRETTY_PRINT));
                $this->info('Login successful, exiting...');
                $loop->addPeriodicTimer(1, fn() => exit(self::SUCCESS));
                return Response::json($query);
            }

            return Response::html(file_get_contents(base_path('resources/views/cli.html')));
        });

        $server->listen($socket);

        $this->info('Please follow the instructions in the browser...');

        if ($this->option('console-only') || strncasecmp(PHP_OS, 'WIN', 3) !== 0) {
            $this->info($authorizeUrl);
        } else {
            shell_exec(sprintf('start "" "%s"', $authorizeUrl));
        }

        $loop->run();

        return self::SUCCESS;
    }
}
