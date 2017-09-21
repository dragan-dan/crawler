<?php

namespace Services\Crawler\Test;

use Services\Crawler\Crawler;
use Services\Crawler\Exceptions\MissingInputException;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{
    public $maxDepth = 10;

    public function testPushUrl()
    {
        $data = [
            'url' => 'www.aaaa.com',
        ];

        $qtm = $this->getMockQtmService(['publish']);

        $qtm->expects($this->once())
            ->method('publish')
            ->with('url',$data);

        $emailDAO = $this->getMockEmailDAO([]);

        $emailCrawler = $this->getMockEmailCrawler([]);

        $crawlerService = new Crawler($emailDAO, $this->maxDepth, $emailCrawler, $qtm);

        $crawlerService->pushUrl($data);
    }

    public function testPushUrlWithEmptyUrl()
    {
        $data = [
            'url' => null,
        ];

        $this->setExpectedException(
            '\Services\Crawler\Exceptions\MissingInputException',
            MissingInputException::MESSAGE_URL_MISSING
        );

        $qtm = $this->getMockQtmService(['publish']);

        $qtm->expects($this->never())
            ->method('publish')
            ->with('url',$data);

        $emailDAO = $this->getMockEmailDAO([]);

        $emailCrawler = $this->getMockEmailCrawler([]);

        $crawlerService = new Crawler($emailDAO, $this->maxDepth, $emailCrawler, $qtm);

        $crawlerService->pushUrl($data);
    }

    public function testStartCrawler()
    {
        $data = [
            'url' => 'www.test.com',
        ];

        $qtm = $this->getMockQtmService([]);

        $emailDAO = $this->getMockEmailDAO([]);

        $emailCrawler = $this->getMockEmailCrawler(['crawl', 'getLinks', 'getEmailAddresses']);

        $emailCrawler->expects($this->once())
            ->method('crawl')
            ->with($data['url'], $this->maxDepth)
            ->willReturn($emailCrawler);

        $emailCrawler->expects($this->any())
            ->method('getLinks');

        $emailCrawler->expects($this->once())
            ->method('getEmailAddresses');

        $crawlerService = new Crawler($emailDAO, $this->maxDepth, $emailCrawler, $qtm);

        $crawlerService->startCrawler($data);
    }

    public function testStartCrawlerWithInvalidUrl()
    {
        $data = [
            'url' => null,
        ];

        $this->setExpectedException(
            '\Services\Crawler\Exceptions\MissingInputException',
            MissingInputException::MESSAGE_URL_MISSING
        );

        $qtm = $this->getMockQtmService([]);

        $emailDAO = $this->getMockEmailDAO([]);

        $emailCrawler = $this->getMockEmailCrawler(['crawl', 'getLinks', 'getEmailAddresses']);

        $emailCrawler->expects($this->never())
            ->method('crawl')
            ->with($data['url'], $this->maxDepth)
            ->willReturn($emailCrawler);

        $emailCrawler->expects($this->never())
            ->method('getLinks');

        $emailCrawler->expects($this->never())
            ->method('getEmailAddresses');

        $crawlerService = new Crawler($emailDAO, $this->maxDepth, $emailCrawler, $qtm);

        $crawlerService->startCrawler($data);
    }


    private function getMockEmailDAO($methods)
    {
        return $this->getMockBuilder('Services\Crawler\DAO\EmailDAO')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    private function getMockEmailCrawler($methods)
    {
        return $this->getMockBuilder('Services\Crawler\EmailCrawler')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    private function getMockQtmService($methods)
    {
        return $this->getMockBuilder('Services\QTM\QTM')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
