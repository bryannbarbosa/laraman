<?php

namespace RLStudio\Laraman;

use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Ramsey\Uuid\Uuid;

class ExportRoutesToPostman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laraman:export {--name=laraman-export} {--api} {--web} {--port=8000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all routes to a json file that can be imported in Postman';

    /**
     * The Laravel router.
     *
     * @var \Illuminate\Routing\Router
     */
    private $router;

    /**
     * The filesystem implementation.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $files;

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Routing\Router $router
     * @param \Illuminate\Contracts\Filesystem\Filesystem $files
     */
    public function __construct(Router $router, Filesystem $files)
    {
        $this->router = $router;
        $this->files = $files;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $api = $this->option('api');
        $web = $this->option('web');
        if (!$api && !$web) {
            $api = $web = true;
        }
        $name = $this->option('name');
        $port = $this->option('port');
        // Set the base data.
        $routes = [
              'variables' => [],
              'info' => [
                  'name' => $name,
                  '_postman_id' => Uuid::uuid4(),
                  'description' => '',
                  'schema' => 'https://schema.getpostman.com/json/collection/v2.0.0/collection.json',
              ]
          ];

        foreach ($this->router->getRoutes() as $route) {
            foreach ($route->methods as $method) {
                if ($method == 'HEAD') {
                    continue;
                }
                if ($api && $route->middleware()[0] == "api") {
                    $url = url('');
                    $url .= ':' . $port . '/' . $route->uri();
                    $routes['item'][] = [
                        'name' => $method.' '.$route->uri(),
                        'request' => [
                            'url' => $url,
                            'method' => strtoupper($method),
                            'header' => [
                                [
                                    'key' => 'Content-Type',
                                    'value' => 'application/json',
                                    'description' => ''
                                ]
                            ],
                            'body' => [
                                'mode' => 'raw',
                                'raw' => '{\n    \n}'
                            ],
                            'description' => '',
                        ],
                        'response' => [],
                    ];
                }
                if ($web && $route->middleware()[0] == "web") {
                    $url = url('');
                    $uri = $route->uri();
                    if ($route->uri() == '/') {
                        $uri = null;
                    }
                    $url .= ':' . $port . '/' . $uri;
                    $routes['item'][] = [
                        'name' => $method.' '.$route->uri(),
                        'request' => [
                            'url' => $url,
                            'method' => strtoupper($method),
                            'header' => [
                                [
                                    'key' => 'Content-Type',
                                    'value' => 'text/html',
                                    'description' => ''
                                ]
                            ],
                            'body' => [
                                'mode' => 'raw',
                                'raw' => '{\n    \n}'
                            ],
                            'description' => '',
                        ],
                        'response' => [],
                    ];
                }
            }
        }
        if (! $this->files->put($name.'.json', json_encode($routes))) {
            $this->error('Export failed');
        } else {
            $this->info('Routes exported!');
        }
    }
}
