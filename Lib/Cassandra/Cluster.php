<?php

namespace Alfatraining\CassandraSessionHandlerBundle\Lib\Cassandra;

use Cassandra\Cluster as ClusterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cassandra cluster builder wrapper.
 *
 * @author Daniel Lohse <daniel.lohse@alfatraining.de>
 */
class Cluster implements ClusterInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var \Cassandra\Cluster A Cassandra cluster instance
     */
    private $cluster;

    /**
     * @var \Psr\Log\LoggerInterface A logger instance (optional, will be a NullLogger by default)
     */
    private $logger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->debugMode = $this->container->getParameter('kernel.debug');
        $this->logger    = ($this->debugMode ? ($logger ?: new NullLogger()) : new NullLogger());

        $this->initializeCluster();
    }

    /**
     * Override this method in your own implementation to configure the Cassandra cluster instance.
     */
    protected function initializeCluster()
    {
        // contact points can an array or a string, optionally having multiple
        // comma-separated node host names / IP addresses
        $contactPoints = $this->container->getParameter('cassandra_cluster.contact_points');
        if (!is_array($contactPoints)) {
            $contactPoints = implode($contactPoints, ',');
            foreach ($contactPoints as &$contactPoint) {
              $contactPoint = trim($contactPoint);
            }
        }

        $cluster = \Cassandra::cluster();

        if (PHP_VERSION_ID < 50600) {
            call_user_func_array(array($cluster, "withContactPoints"), $contactPoints);
        } else { // PHP > 5.6 implements variadic parameters
            $cluster->withContactPoints(...$contactPoints);
        }

        $cluster->withPersistentSessions(true); // always use persistent connections but be explicit about it

        if ($this->container->hasParameter('cassandra_cluster.credentials.username') &&
           $this->container->hasParameter('cassandra_cluster.credentials.password')) {
            $username = $this->container->getParameter('cassandra_cluster.credentials.username');
            $password = $this->container->getParameter('cassandra_cluster.credentials.password');
            $cluster->withCredentials($username, $password);
        }

        $this->cluster = $cluster->build();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Cassandra\Session
     */
    public function connect($keyspace = null)
    {
        $this->logger->debug('Connecting this cluster instance synchronously to keyspace '.(string)$keyspace);

        return $this->cluster->connect($keyspace);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Cassandra\Future \Cassandra\Session
     */
    public function connectAsync($keyspace = null)
    {
        $this->logger->debug('Connecting this cluster instance asynchronously to keyspace '.(string)$keyspace);

        return $this->cluster->connectAsync($keyspace);
    }
}
