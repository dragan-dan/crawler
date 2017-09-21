<?php

namespace Services\Crawler;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class EmailCrawler
{
    protected $url;
    protected $links;
    protected $emailAddresses;
    protected $maxDepth;

    public function __construct()
    {
        $this->baseUrl = '';
        $this->links = [];
        $this->depth = 0;
    }

    /**
     * Crawling of the url
     *
     * @param $url
     * @param int $maxDepth
     * @return $this
     */
    public function crawl($url, $maxDepth = 10)
    {
        $this->baseUrl = $url;
        $this->depth = $maxDepth;

        $this->spider($this->baseUrl, $maxDepth);

        return $this;
    }

    /**
     * Get scraped links
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Get scraped email addresses
     *
     * @return mixed
     */
    public function getEmailAddresses()
    {
        return $this->emailAddresses;
    }

    /**
     * Crawl the page , get the content and mark it as visited
     *
     * @param $url
     * @param $maxDepth
     */
    private function spider($url, $maxDepth)
    {
        try {

            $this->links[$url] = [
                'status_code' => 0,
                'url' => $url,
                'visited' => false,
                'is_external' => false,
            ];

            // Create a client and send out a request to a url
            $client = new Client();
            $crawler = $client->request('GET', $url);

            // get the content of the request result
            $html = $crawler->getBody()->getContents();
            // lets also get the status code
            $statusCode = $crawler->getStatusCode();

            // Set the status code
            $this->links[$url]['status_code'] = $statusCode;
            if ($statusCode == 200) {

                // Make sure the page is html
                $contentType = $crawler->getHeader('Content-Type');
                if (strpos($contentType[0], 'text/html') !== false) {

                    // collect the links within the page
                    $pageLinks = [];
                    if (@$this->links[$url]['is_external'] == false) {
                        $pageLinks = $this->extractLinks($html, $url);
                        $emails = $this->extractEmails($html);
                        foreach ($emails as $email) {
                            if (! isset($this->emailAddresses[$email])) {
                                $this->emailAddresses[$email]['page'] = $url;
                            }
                        }
                    }

                    // mark current url as visited
                    $this->links[$url]['visited'] = true;
                    // spawn spiders for the child links, marking the depth as decreasing, or send out the soldiers
                    $this->spawn($pageLinks, $maxDepth - 1);
                }
            }
        } catch (\Exception $ex) {
            $this->links[$url]['status_code'] = '404';
        }
    }

    /**
     * Spawn more spiders
     *
     * @param $links
     * @param $maxDepth
     */
    private function spawn($links, $maxDepth)
    {
        // if we hit the max - then its the end of the rope
        if ($maxDepth == 0) {
            return;
        }

        foreach ($links as $url => $info) {
            // only pay attention to those we do not know
            if (! isset($this->links[$url])) {
                $this->links[$url] = $info;
                // we really only care about links which belong to this domain
                if (! empty($url) && ! $this->links[$url]['visited'] && ! $this->links[$url]['is_external']) {
                    // restart the process by sending out more soldiers!
                    $this->spider($this->links[$url]['url'], $maxDepth);
                }
            }
        }
    }

    /**
     * Check if url is external
     *
     * @param $url
     * @return bool
     */
    private function checkIfExternal($url)
    {
        $baseUrl = str_replace(['http://', 'https://'], '', $this->baseUrl);
        // if the url fits then keep going!
        if (preg_match("@http(s)?\://$baseUrl@", $url)) {
            return false;
        }

        return true;
    }

    /**
     * Extract urls from html
     *
     * @param $html
     * @param $url
     * @return array
     */
    private function extractLinks($html, $url)
    {
        $dom = new DomCrawler($html);
        $currentLinks = [];

        // get the links
        $dom->filter('a')->each(function(DomCrawler $node, $i) use (&$currentLinks) {
            // get the href
            $nodeUrl = $node->attr('href');

            // If we don't have it lets collect it
            if (! isset($this->links[$nodeUrl])) {
                // set the basics
                $currentLinks[$nodeUrl]['is_external'] = false;
                $currentLinks[$nodeUrl]['url'] = $nodeUrl;
                $currentLinks[$nodeUrl]['visited'] = false;

                // check if the link is external
                if ($this->checkIfExternal($currentLinks[$nodeUrl]['url'])) {
                    $currentLinks[$nodeUrl]['is_external'] = true;
                }
            }
        });

        // if page is linked to itself, ex. homepage
        if (isset($currentLinks[$url])) {
            // let's avoid endless cycles
            $currentLinks[$url]['visited'] = true;
        }

        // Send back the reports
        return $currentLinks;
    }

    /**
     * Extract email addresses from html
     *
     * @param $html
     * @return mixed
     */
    private function extractEmails($html)
    {
        $matches = array(); //create array
        $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i'; //regex for pattern of e-mail address
        preg_match_all($pattern, $html, $matches); //find matching pattern

        return $matches[0];
    }

}
