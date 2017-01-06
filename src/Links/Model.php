<?php

declare(strict_types = 1);

namespace Links;

use ServiceProvider;
use Soupmix\ElasticSearch;
use Monolog;

class Model
{
    private $db;
    private $logger;

    public function __construct(ElasticSearch $db, Monolog\Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public static function factory(ServiceProvider $provider)
    {
        return new Model($provider->get(ElasticSearch::class), $provider->get(Monolog\Logger::class));
    }

    public function add(
        string $industry,
        string $industry_slug,
        string $country,
        string $country_slug,
        string $link
    ) {
        $is_exists = $this->db->find('links', ['link'=>$link], ['_id'], null, 0, 1);
        if ($is_exists['total'] === 0) {
            $doc = [
                'industry'      => $industry,
                'industry_slug' => $industry_slug,
                'country'       => $country,
                'country_slug'  => $country_slug,
                'link'          => $link,
                'created_at'    => time() * 100, // epoch_millis,
                'is_active'     => 1,
                'is_deleted'    => 0
            ];
            $user_id =  $this->db->insert('links', $doc);
            return $user_id;
        }
        return 'exists';
    }
}
