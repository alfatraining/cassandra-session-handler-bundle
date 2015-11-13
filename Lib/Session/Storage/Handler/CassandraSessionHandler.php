<?php

namespace Alfatraining\CassandraSessionHandlerBundle\Lib\Session\Storage\Handler;

use Cassandra\Cluster as ClusterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cassandra session handler.
 *
 * @author Daniel Lohse <daniel.lohse@alfatraining.de>
 */
class CassandraSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var \Cassandra\Cluster (it's an interface, even if the name suggests otherwise)
     */
    private $cluster;

    /**
     * @var \Cassandra\Session
     */
    private $session;

    /**
     * @var array A list of prepared statements (prepared on initialization)
     */
    private $preparedStatements;

    /**
     * @var array
     */
    private $options;

    /**
     * @var \Psr\Log\LoggerInterface A logger instance (optional, will be a NullLogger by default)
     */
    private $logger;

    /**
     * Constructor.
     *
     * List of available options:
     *  * keyspace: The name of the keyspace [required]
     *  * column_family: The name of the column family [required]
     *  * session_lifetime: The session lifetime in seconds [required]
     *  * id_field: The field name for storing the session id [default: id]
     *  * data_field: The field name for storing the session data [default: data]
     *  * time_field: The field name for storing the timestamp [default: time]
     *
     * @param \Cassandra\Cluster $cluster    A Cassandra cluster instance
     * @param array              $options    An associative array of field options
     *
     * @throws \InvalidArgumentException When "keyspace" or "column_family" options were not provided
     */
    public function __construct(ClusterInterface $cluster, array $options, LoggerInterface $logger = null)
    {
        if (!isset($options['keyspace']) || !isset($options['column_family']) || !isset($options['session_lifetime'])) {
            throw new \InvalidArgumentException('You must provide the "keyspace", "column_family" and "session_lifetime" option for CassandraSessionHandler');
        }
        $this->options['session_lifetime'] = intval($this->options['session_lifetime'], 10);

        $this->cluster = $cluster;
        $this->options = array_merge(array(
            'id_field'   => 'id',
            'data_field' => 'data',
            'time_field' => 'time',
        ), $options);
        $this->logger = $logger ?: new NullLogger();

        $this->connectToCluster();
        $this->prepareStatements();
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $blobSessionId = new \Cassandra\Blob($sessionId);
        $result = $this->getSession()->execute($this->preparedStatements['destroy'],
            new \Cassandra\ExecutionOptions(array('arguments' => array($blobSessionId)))
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $blobData = new \Cassandra\Blob($data);
        $nowTimestamp = new \Cassandra\Timestamp();
        $blobSessionId = new \Cassandra\Blob($sessionId);
        $result = $this->getSession()->execute($this->preparedStatements['write'],
            new \Cassandra\ExecutionOptions(array('arguments' => array(
                $blobData, $nowTimestamp, $blobSessionId
            )))
        );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        $blobSessionId = new \Cassandra\Blob($sessionId);
        $result = $this->getSession()->execute($this->preparedStatements['read'],
            new \Cassandra\ExecutionOptions(array('arguments' => array($blobSessionId)))
        );
        if (null !== ($sessionData = $result->first())) {
            $data = $sessionData[$this->options['data_field']];
            return ($data instanceof \Cassandra\Blob ? $data->toBinaryString() : '');
        }

        return '';
    }

    /**
     * Return a Cassandra session instance.
     *
     * @return \Cassandra\Session
     */
    protected function getSession()
    {
        return $this->session;
    }

    /**
     * Return a Cassandra cluster instance.
     *
     * @return \Cassandra\Cluster
     */
    protected function getCluster()
    {
        return $this->cluster;
    }

    /**
     * Prepare all the statements for reading, writing and destroying sessions for this cluster,
     * and do that once, on initialization.
     */
    protected function prepareStatements()
    {
        $this->preparedStatements['read'] = $this->getSession()->prepare(
            "SELECT {$this->options['data_field']}
             FROM {$this->options['keyspace']}.{$this->options['column_family']}
             WHERE {$this->options['id_field']} = ?"
        );
        $this->preparedStatements['write'] = $this->getSession()->prepare(
            "UPDATE {$this->options['keyspace']}.{$this->options['column_family']} USING TTL {$this->options['session_lifetime']}
             SET {$this->options['data_field']} = ?, {$this->options['time_field']} = ?
             WHERE {$this->options['id_field']} = ?"
        );
        $this->preparedStatements['destroy'] = $this->getSession()->prepare(
            "DELETE FROM {$this->options['keyspace']}.{$this->options['column_family']}
             WHERE {$this->options['id_field']} = ?"
        );
    }

    /**
     * Connects to the Cassandra cluster.
     * Override if you're pulling the cluster instance from somewhere else
     * but be sure to initialize the session
     */
    protected function connectToCluster()
    {
        $this->session = $this->getCluster()->connect($this->options['keyspace']);
    }
}
