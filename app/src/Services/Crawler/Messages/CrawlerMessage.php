<?php

namespace Services\Crawler\Messages;

/**
 * @property string $url
 * @property int    $messageId
 */
class CrawlerMessage extends AbstractMessage
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var int
     */
    protected $messageId;

    /**
     * @var int
     */
    protected $taskId;
    
    /**
     * @var string
     */
    protected $messageHandle;

    /**
     * @param $url
     * @param $messageId
     * @param $taskId
     * @param $messageHandle
     */
    public function __construct($url, $messageId, $taskId, $messageHandle = null)
    {
        $this->url            = $url;
        $this->messageId      = $messageId;
        $this->taskId         = $taskId;
        $this->messageHandle  = $messageHandle;
    }

    /**
     * Convert the object to an array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            $this->url,
            $this->messageId,
            $this->taskId,
            $this->messageHandle
        ];
    }
}
