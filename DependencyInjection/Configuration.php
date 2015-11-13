<?php

namespace Alfatraining\CassandraSessionHandlerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('alfatraining_cassandra_session_handler');

        $rootNode->canBeUnset()->children()
            ->scalarNode('keyspace')->defaultValue("symfony2_sessions")->end()
            ->scalarNode('column_family')->defaultValue("sessions")->end()
            ->scalarNode('session_lifetime')->defaultValue(86400)->end();

        return $treeBuilder;
    }
}
