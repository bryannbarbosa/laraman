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
    protected $signature = 'laraman:export {--name=laraman-export} {--match=} {--port=8000}';
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
        $name = $this->option('name');
        $port = $this->option('port');
        $search = $this->option('match');
        $request = null;
        // Set the base data.
        $routes = [
            'variables' => [],
            'info' => [
                'name' => $name,
                '_postman_id' => Uuid::uuid4(),
                'description' => '',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ]
        ];
        foreach ($this->router->getRoutes() as $route) {
            foreach ($route->methods as $method) {
                if ($method == 'HEAD') {
                    continue;
                }

                $alias = $route->uri();

                if ($route->uri() == '/') {
                    $uri = null;
                } else {
                    $uri = $route->uri();
                }

                $server = url()->current();
                $url = "${server}:${port}/${uri}";

                if ($search) {
                    if (in_array($search, $route->middleware())) {
                        if ($route->getActionName() == 'Closure' && $method != 'get') {
                            if (in_array('api', $route->middleware())) {
                                $code = file_get_contents(base_path('routes/api.php'));
                            }
                            if (in_array('web', $route->middleware())) {
                                $code = file_get_contents(base_path('routes/web.php'));
                            }
                            $function = $this->getFunction($code, $route->uri(), $route->getActionName());
                            $request = $this->getRequest($function, null, $route->getActionName());
                            $params = $this->getParams($function, $request);
                            if (count($params) == 1) {
                                for ($i = 0; $i < count($params); $i++) {
                                    $params[$i] = "    \"" . $params[$i] . "\": \"\"";
                                }
                            }
                            if (count($params) > 1) {
                                for ($i = 0; $i < count($params); $i++) {
                                    $params[$i] = "    \"" . $params[$i];
                                }
                                $length = count($params);
                                $params[$length - 1] = $params[$length - 1] . "\": \"\"";
                            }

                            $paramsList = implode("\": \"\",\n", $params);
                        } else {
                            $functionName = substr($route->getActionName(), strpos($route->getActionName(), "@") + 1);
                            $path = substr($route->getActionName(), 0, strpos($route->getActionName(), "@")) . '.php';
                            $code = file_get_contents(base_path($path));
                            if ($code != null) {
                                $request = $this->getRequest($code, $functionName, $route->getActionName());
                                $params = $this->getParams($code, $request);
                                if (count($params) == 1) {
                                    for ($i = 0; $i < count($params); $i++) {
                                        $params[$i] = "    \"" . $params[$i] . "\": \"\"";
                                    }
                                }
                                if (count($params) > 1) {
                                    for ($i = 0; $i < count($params); $i++) {
                                        $params[$i] = "    \"" . $params[$i];
                                    }
                                    $length = count($params);
                                    $params[$length - 1] = $params[$length - 1] . "\": \"\"";
                                }
                                $paramsList = implode("\": \"\",\n", $params);
                            }
                        }
                        $routes['item'][] = [
                        'name' => "${method} ${alias}",
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
                                'raw' => "{\n    \n}"
                            ],
                            'description' => '',
                        ],
                        'response' => [],
                    ];
                    }
                }
                if (!$search) {
                    if ($route->getActionName() == 'Closure' && $method != 'get') {
                        if (in_array('api', $route->middleware())) {
                            $code = file_get_contents(base_path('routes/api.php'));
                        }
                        if (in_array('web', $route->middleware())) {
                            $code = file_get_contents(base_path('routes/web.php'));
                        }
                        $function = $this->getFunction($code, $route->uri(), $route->getActionName());
                        $request = $this->getRequest($function, null, $route->getActionName());
                        $params = $this->getParams($function, $request);
                        if (count($params) == 1) {
                            for ($i = 0; $i < count($params); $i++) {
                                $params[$i] = "    \"" . $params[$i] . "\": \"\"";
                            }
                        }
                        if (count($params) > 1) {
                            for ($i = 0; $i < count($params); $i++) {
                                $params[$i] = "    \"" . $params[$i];
                            }
                            $length = count($params);
                            $params[$length - 1] = $params[$length - 1] . "\": \"\"";
                        }

                        $paramsList = implode("\": \"\",\n", $params);
                    } else {
                        $functionName = substr($route->getActionName(), strpos($route->getActionName(), "@") + 1);
                        $path = substr($route->getActionName(), 0, strpos($route->getActionName(), "@")) . '.php';
                        $condition = true;
                        try {
                            $code = file_get_contents(base_path($path));
                        } catch(\Exception $e) {
                            $this->info('Error in reading file (route ' . $alias .'): ' . $e->getMessage());
                            $condition = false;
                        }
                        
                        if ($condition) {
                            $request = $this->getRequest($code, $functionName, $route->getActionName());
                            $params = $this->getParams($code, $request);
                            if (count($params) == 1) {
                                for ($i = 0; $i < count($params); $i++) {
                                    $params[$i] = "    \"" . $params[$i] . "\": \"\"";
                                }
                            }
                            if (count($params) > 1) {
                                for ($i = 0; $i < count($params); $i++) {
                                    $params[$i] = "    \"" . $params[$i];
                                }
                                $length = count($params);
                                $params[$length - 1] = $params[$length - 1] . "\": \"\"";
                            }
                            $paramsList = implode("\": \"\",\n", $params);
                        } else {
                            $paramsList = '';
                        }
                    }
                    $routes['item'][] = [
                      'name' => "${method} ${alias}",
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
                              'raw' => "{\n" . $paramsList . "\n}"
                          ],
                          'description' => '',
                      ],
                      'response' => [],
                  ];
                }
            }
        }
        if (!$this->files->put($name.'.json', json_encode($routes))) {
            $this->error('Export failed');
        } else {
            $this->info('Routes exported!');
        }
    }
    public function getFunction($code, $match = null, $type)
    {
        if ($match == '/') {
            preg_match('/\/\'(.*?)\)/', $code, $matches);
            if ($matches) {
                return $matches[0];
            }
        } else {
            $match = str_replace('api/', '', $match);
            preg_match("/\/${match}(.*?)}/s", $code, $matches);
            if ($matches) {
                return $matches[0];
            }
        }
    }

    public function getRequest($code, $match, $type)
    {
        if ($type == 'Closure') {
            preg_match_all('/(?<=Request.)((\$\w+))/', $code, $request);
            return $request[0][0];
        }
        if ($type != 'Closure') {
            preg_match_all('/(?<='. $match . '\(Request.)\$(\w+)/', $code, $request);
            return $request[0][0];
        }
    }

    public function getParams($code, $request)
    {
        $request = substr($request, 1);
        $params = [];
        preg_match_all('/(?<=\$'. $request . '\-\>(input|query)\(\')(\w+)(\'?\,?\s?\'?\w+)/s', $code, $input_query);
        preg_match_all('/(?<=\$'. $request . '\-\>(only)\(\')(\w+)(\'?\,?\s?\'?\w+)/s', $code, $only);
        foreach ($input_query[0] as $param) {
            if ($param != null) {
                if (strpos($param, ',') !== false) {
                    $param_filter = preg_replace('/\s+/', '', $param);
                    $param_filter = preg_replace('/\'+/', '', $param_filter);
                    $words = explode(',', $param_filter);
                    foreach ($words as $value) {
                        array_push($params, $value);
                    }
                } else {
                    array_push($params, $param);
                }
            }
        }
        foreach ($only[0] as $param) {
            if ($param != null) {
                if (strpos($param, ',') !== false) {
                    $param_filter = preg_replace('/\s+/', '', $param);
                    $words = explode(',', $param_filter);
                    foreach ($words as $value) {
                        array_push($params, $value);
                    }
                } else {
                    array_push($params, $param);
                }
            }
        }
        return $params;
    }
}
