<?php


class ServiceProvider
{
    private static $instance;

    private static $registry = [];

    private $provides = [
        'config' => 'array',
        GuzzleClient::class => GuzzleClient::class,
        Monolog\Logger::class => Monolog\Logger::class,
        Soupmix\ElasticSearch ::class => Soupmix\ElasticSearch ::class,
    ];

    protected function __construct() { }

    protected function __clone() { }

    public static function getInstance()
    {
        if (!(self::$instance instanceof ServiceProvider)) {
            self::$instance = new ServiceProvider();
        }
        return self::$instance;
    }

    public function set(string $key, $value)
    {
        if (!array_key_exists($key, $this->provides)) {
            throw new InvalidArgumentException(sprintf('%s is not valid provides', $key));
        }
        $keyType = $this->provides[$key];
        $valueType = gettype($value);
        if ($valueType === 'object' && (!$value instanceof $keyType)) {
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $value));
        }
        if ($valueType !== 'object' && $valueType !== $keyType) {
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $value));
        }
        self::$registry[$key] = $value;
    }

    public function has(string $key)
    {
        return array_key_exists($key, self::$registry);
    }

    public function get(string $key)
    {
        $value =  self::$registry[$key] ?? null;
        $keyType = $this->provides[$key];
        $valueType = gettype($value);
        if ($valueType === 'object' && (!$value instanceof $keyType)) {
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $value));
        }
        if ($valueType !== 'object' && $valueType !== $keyType) {
            throw new InvalidArgumentException(sprintf('%s is not valid type of %s', $key, $valueType));
        }
        return $value;
    }
}
