<?php

namespace Config;

class RouteConfig
{
    /**
     * Get routes mapped to services
     * @return array
     */
    public function getRouteMapping()
    {
        return array(
            //---------------- Crawler Service ----------------------
            array(
                'route'   => '/api/v1/crawler/start',
                'service' => 'crawler',
                'action'  => 'startCrawler',
                'methods' => array('POST')
            ),
            array(
                'route'   => '/api/v1/crawler/push',
                'service' => 'crawler',
                'action'  => 'pushUrl',
                'methods' => array('POST')
            ),
            array(
                'route'   => '/api/v1/crawler/get-scraped-emails',
                'service' => 'crawler',
                'action'  => 'getEmails',
                'methods' => array('GET')
            )
        );
    }


}
