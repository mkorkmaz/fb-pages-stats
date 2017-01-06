<?php

return [
    'elasticsearch' => [
        'hosts'     => ['127.0.0.1:9200'],
        'db_name'   => 'sm_stats',
        'options'   => []
    ],
    'root' => 'https://www.socialbakers.com/statistics/facebook/pages/total',
    'debug_file'    => dirname(__DIR__) . '/logs/debug.log',
    'red_file'      => dirname(__DIR__) . '/logs/important.log',
    'blue_file'     => dirname(__DIR__) . '/logs/app.log'
];