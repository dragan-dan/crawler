<?php

namespace Services\Crawler;

use Services\Crawler\Exceptions\MissingInputException;
use Services\Crawler\DAO\EmailDAO;
use Services\QTM\QTM;

/**
 * Class Crawler
 * @package Services\Crawler
 */
class Crawler
{
    /**
     * @var EmailDAO
     */
    private $emailDAO;

    /**
     * @var int
     */
    private $maxDepth;

    /**
     * @var EmailCrawler
     */
    private $emailCrawler;

    /**
     * @var QTM
     */
    private $qtm;

    /**
     * Crawler constructor.
     * @param $emailDAO
     * @param $maxDepth
     * @param $emailCrawler
     * @param $qtm
     */
    function __construct($emailDAO, $maxDepth, $emailCrawler, $qtm)
    {
        $this->emailDAO = $emailDAO;
        $this->maxDepth = $maxDepth;
        $this->emailCrawler = $emailCrawler;
        $this->qtm = $qtm;
    }

    /**
     * start crawler action
     * @param $data
     * @return mixed
     * @throws MissingInputException
     */
    public function startCrawler($data)
    {

        if (!isset($data['url'])) {
            throw new MissingInputException(MissingInputException::MESSAGE_URL_MISSING);
        }

        $url = $data['url'];

        $dom = $this->emailCrawler->crawl($url, $this->maxDepth);

        $links = [];

        if (is_array($dom->getLinks())) {
            foreach ($dom->getLinks() as $link) {
                if ($link['visited']) {
                    $links[] = $link['url'];
                }
            }
        }

        $emails = $dom->getEmailAddresses();

        if (is_array($emails)) {
            foreach ($emails as $email => $url) {
                $emailData['email'] = $email;
                $emailData['url'] = $url['page'];
                $this->emailDAO->insertEmail($emailData);
            }
        }

        $response['links'] = $links;
        $response['emails'] = $emails;

        return $response;
    }

    /**
     * @param $data
     * @param null $callbackUrl
     * @param null $taskId
     * @throws MissingInputException
     */
    public function pushUrl($data, $callbackUrl = null, $taskId = null)
    {
        if (!isset($data['url'])) {
            throw new MissingInputException(MissingInputException::MESSAGE_URL_MISSING);
        }

        /* Queue url for crawling */
        $this->qtm->publish(
            QTM::QUEUE_URL,
            [
                'url' => $data['url']
            ],
            $callbackUrl,
            $taskId
        );
    }

    /**
     * get all scraped emails
     *
     * @return mixed
     */
    public function getEmails()
    {
        $emails = $this->emailDAO->fetchAllEmails();

        $response['emails'] = $emails;

        return $response;
    }
}
