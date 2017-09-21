<?php

namespace Console\Command\Crawler\Actions;

use Services\Crawler\Crawler;

class CrawlerConsumerAction implements ConsumerActionInterface
{
    /**
     * @var Crawler
     */
    protected $crawlerService;

    /**
     * CrawlerConsumerAction constructor.
     *
     * @param Crawler $crawlerService
     */
    public function __construct(Crawler $crawlerService)
    {
        $this->crawlerService = $crawlerService;
    }


    /**
     * @param array $params
     */
    public function crawl($params)
    {
        $this->crawlerService->startCrawler($params);
    }

}
