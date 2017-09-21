<?php
namespace Library\Messaging\Providers;

use Exceptions\DBException;
use Library\DAO\Postgre\Json\Query\PostgresJSONQuery;
use Library\DAO\Postgre\BaseDAO;
use Library\DAO\Query\QueryFactory;
use Services\Factory;
use Uecode\Bundle\QPushBundle\Provider\AbstractProvider;
use Uecode\Bundle\QPushBundle\Message\Message;
use Doctrine\Common\Cache\Cache;
use Library\Logger\Logger;

/**
 * Class DatabaseClient
 * @package Library\Messaging\Providers
 */
class DatabaseClient extends AbstractProvider
{
    const DEFAULT_DELAY              = 0;
    const DEFAULT_RECEIVE_LIMIT      = 10;
    const DEFAULT_VISIBILITY_TIMEOUT = 30;

    const QUEUE_SELECTOR = 'name';
    const QUEUE_TABLE    = 'queue';

    const MESSAGE_ID                 = 'message_id';
    const MESSAGE_BODY               = 'message_body';
    const MESSAGE_VISIBILITY_TIMEOUT = 'visibility_timeout';

    /**
     * @var resource
     */
    protected $db;

    /**
     * @param string $name
     * @param array $options
     * @param mixed $client
     * @param Cache $cache
     * @param \Monolog\Logger $logger
     */
    public function __construct($name, array $options, $client, Cache $cache, \Monolog\Logger $logger)
    {
        $this->name     = $name;
        $this->options  = $options;
        $this->db       = $client;
        $this->cache    = $cache;
        $this->logger   = $logger;
    }

    public function getProvider()
    {
        return "Database";
    }

    public function create()
    {
        return true;
    }

    /**
     * @param array $message
     * @param array $options
     * @return mixed
     * @throws DBException
     */
    public function publish(array $message, array $options = [])
    {
        // Merge method, instance and default options
        $options = array_merge(['message_delay' => self::DEFAULT_DELAY], $options, $this->options);

        $name = $this->getNameWithPrefix();
        $publishStart = microtime(true);

        $query = QueryFactory::create(self::QUEUE_TABLE, QueryFactory::TYPE_RELATIONAL)
            ->setInsertData([
                self::QUEUE_SELECTOR                => $name,
                self::MESSAGE_BODY                  => json_encode($message),
                self::MESSAGE_VISIBILITY_TIMEOUT    =>
                    $options['message_delay'] > 0 ?
                        date('c', strtotime(sprintf('+%d seconds', $options['message_delay']))) :
                        null
            ]);

        // Empty named message queue
        $result = pg_query($this->db, $query->getInsertQuery());

        if (!$result) {
            $dbError = pg_last_error($this->db);
            throw new DBException(DBException::MESSAGE_UPDATE_ERROR . ": " . $dbError);
        }

        // PG row OID
        $id = pg_fetch_array(pg_query($this->db, 'SELECT LASTVAL() AS ' . self::MESSAGE_ID), 0);

        $context = [
            'Name'          => $name,
            'PublishTime'   => microtime(true) - $publishStart,
            'MessageId'     => $id[self::MESSAGE_ID],
            'MessageDelay'  => $options['message_delay']
        ];
        Logger::logInfo("Message published to DB Queue", $context);

        return $context['MessageId'];
    }

    /**
     * Receive messages from queue. Visibility of the messages is automatically updated
     * so that we are the exclusive receiver.
     *
     * @param array $options
     * @return array|Message $messages
     * @throws DBException
     */
    public function receive(array $options = [])
    {
        //checking postgres connection and try to reset it
        if (pg_connection_status($this->db) !== PGSQL_CONNECTION_OK) {
            if (!pg_connection_reset($this->db)) {
                throw new DBException(
                    sprintf(DBException::MESSAGE_QUEUE_FETCH_ERROR, "lost database connection")
                );
            }
        }

        $name       = $this->getNameWithPrefix();
        // Merge method, instance and default options
        $options = array_merge(
            ['message_delay' => self::DEFAULT_DELAY, 'message_visibility_timeout' => self::DEFAULT_VISIBILITY_TIMEOUT],
            $options,
            $this->options
        );

        // Start transaction
        pg_query($this->db, 'BEGIN');

        try {
            // Fetch messages
            $result = pg_query_params(
                $this->db,
                'SELECT ' . self::MESSAGE_ID . ', ' . self::MESSAGE_BODY
                . '  FROM ' . self::QUEUE_TABLE
                . ' WHERE ' . self::QUEUE_SELECTOR . ' = $1'
                . '   AND ('
                . self::MESSAGE_VISIBILITY_TIMEOUT . ' IS NULL OR '
                . self::MESSAGE_VISIBILITY_TIMEOUT . ' <= $2'
                . ')'
                . ' LIMIT $3'
                . ' FOR UPDATE NOWAIT',
                [$name, date('c'), $options['messages_to_receive']]
            );

            if ($result === false) {
                throw new DBException(
                    sprintf(DBException::MESSAGE_QUEUE_FETCH_ERROR, pg_last_error($this->db))
                );
            }

            if (pg_num_rows($result) == 0) {
                // Stop transaction
                pg_query($this->db, 'ROLLBACK');

                $context = [ 'Name' => $name , 'Options' => $options ];
                Logger::logInfo("No messages in DB Queue", $context);
                return [];
            }

            // Fetch database result
            $messages = pg_fetch_all($result);

            // Convert to Message Class
            foreach ($messages as &$message) {
                $id = $message[self::MESSAGE_ID];
                $metadata = [];

                // Json decode message body
                if (is_array($body = json_decode($message[self::MESSAGE_BODY], true))
                    && isset($body[self::MESSAGE_BODY])
                ) {
                    $body = json_decode($body[self::MESSAGE_BODY], true);
                }

                // Construct Message object with meta data
                $message = new Message($id, $body, $metadata);

                // Bump visibility timeout
                $result = pg_query_params(
                    $this->db,
                    "UPDATE " . self::QUEUE_TABLE
                    . "   SET " . self::MESSAGE_VISIBILITY_TIMEOUT . ' = $1'
                    . " WHERE " . self::MESSAGE_ID . ' = ' . (int)$id,
                    [
                        date('c', strtotime(sprintf('+%s seconds', $options['message_visibility_timeout'])))
                    ]
                );

                // Verify update result
                if (!$result || pg_affected_rows($result) <> 1) {
                    throw new DBException(DBException::MESSAGE_UPDATE_ERROR);
                }

                $context = ['Name' => $name, 'MessageId' => $id];
                Logger::logInfo("Message fetched from DB Queue", $context);
            }

            // Commit visibility changes
            pg_query($this->db, 'COMMIT');
        } catch (DBException $exception) {
            // Roll back visibility changes
            pg_query($this->db, 'ROLLBACK');

            $dbError = pg_last_error($this->db);
            throw new DBException($exception->getMessage() . ": " . $dbError);
        }

        return $messages;
    }

    /**
     * @param mixed $id
     * @return bool
     * @throws DBException
     */
    public function delete($id)
    {
        $name = $this->getNameWithPrefix();

        // Empty named message queue
        $result = pg_query_params(
            $this->db,
            'DELETE FROM ' . self::QUEUE_TABLE . ' '
            .   'WHERE ' . self::QUEUE_SELECTOR . ' = $1 AND ' . self::MESSAGE_ID . ' = $2',
            [$name, $id]
        );

        if (pg_affected_rows($result) <> 1) {
            $dbError = pg_last_error($this->db);
            throw new DBException(DBException::MESSAGE_DELETE_ERROR . ": " . $dbError);
        }

        $context = ['MessageId' => $id];
        Logger::logInfo("Message deleted from DB Queue", $context);

        return true;

    }

    /**
     * Destroy message queue
     * @return bool
     * @throws DBException
     */
    public function destroy()
    {
        $name = $this->getNameWithPrefix();

        // Empty named message queue
        $result = pg_query_params(
            $this->db,
            'DELETE FROM ' . self::QUEUE_TABLE . ' WHERE ' . self::QUEUE_SELECTOR . ' = $1',
            [$name]
        );

        if (!$result) {
            $dbError = pg_last_error($this->db);
            throw new DBException(DBException::MESSAGE_DELETE_ERROR . ": " . $dbError);
        }

        $context = ['Name' => $name];
        Logger::logInfo("DB Queue destroyed", $context);

        return true;
    }
}
