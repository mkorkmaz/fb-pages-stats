<?php
declare(strict_types = 1);

namespace Stats;

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

    public function get($id)
    {
        return $this->db->get('links', $id);
    }
    public function getAllData()
    {
        return $this->db->find(
            'links',
            ['is_active' => 1, 'is_deleted' => 0],
            ['_id'],
            ['country_slug'=>'asc'],
            0,
            10000
        );
    }

    public function update(array $link_info, array $item)
    {
        $filter = [

            'industry_slug' => $link_info['industry_slug'],
            'country_slug'  => $link_info['country_slug'],
            'order'         => $item[2]
        ];


        $doc = [
            'industry'      => $link_info['industry'],
            'industry_slug' => $link_info['industry_slug'],
            'country'       => $link_info['country'],
            'country_slug'  => $link_info['country_slug'],
            'order'         => $item[2],
            'page'          => $item[3],
            'facebook_id'   => preg_replace('/[\D]/', '', $item[0]),
            'profile_pic'   => $item[1],
            'total_fans'    => $item[4],
            'updated_at'    => time() * 100 // epoch millis
        ];

        $is_exists = $this->db->find('stats', $filter, ['_id'], null, 0, 1);

        if ($is_exists['total'] === 0) {
            return $this->db->insert('stats', $doc);
            return 'I';
        }
        return $this->db->update('stats', ['_id' => $is_exists['data']['_id']], $doc);
    }
}
