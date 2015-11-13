# Cassandra Session Handler bundle #

This Symfony 2 bundle provides a session handler for saving sessions in Cassandra.

## Installation

1. Add the repository and requirement to `composer.json`:
```yml
    {
        "repositories": [
            {
                "type": "vcs",
                "url": "git@github.com:alfatraining/cassandra-session-handler-bundle.git"
            }
        ],
        "require": {
            "alfatraining/cassandra-session-handler-bundle": "dev-master"
        }
    }
```

2. Install the bundle via `composer update` or `composer update nothing` if you don't want updates.

3. Register the bundle in `app/AppKernel.php`:
```php
    new Alfatraining\CassandraSessionHandlerBundle\AlfatrainingCassandraSessionHandlerBundle(),
```

4. Add configuration parameters to your `app/config/config.yml` (the following shows the bundle defaults) and activate the session handler:
```yml
    framework:
        session:
            handler_id:  alfatraining_cassandra_session_handler.cassandra_session_handler

    alfatraining_cassandra_session_handler:
        keyspace:         symfony2_sessions    # Cassandra keyspace to use
        column_family:    sessions             # Cassandra table/column family to use
        session_lifetime: 84600                # session lifetime in seconds
```

5. Add parameters to `app/config/parameters.yml.dist` to configure the Cassandra cluster instance:
```yml
    parameters:
        # contact points can be 1) a single node (not advisable for Cassandra)
        cassandra_cluster.contact_points:       localhost
        # or 2) a comma-separated list of nodes (mixed host names / IP addresses possible)
        cassandra_cluster.contact_points:       localhost, 127.0.0.1, 172.18.1.10
        # or 3) a native YAML array
        cassandra_cluster.contact_points:
        - localhost
        - 127.0.0.1
        # of course, you may also write this using the inline style
        cassandra_cluster.contact_points:       [localhost, 127.0.0.1]

        # optionally set the credentials to be used for connecting to the Cassandra cluster
        cassandra_cluster.credentials.username: cassandra
        cassandra_cluster.credentials.password: cassandra
```
Then run `composer install` again or update your `parameters.yml` by hand.

6. Don't forget to actually create the keyspace and the column family. An example of the CQL that's needed can be found in `Resources/doc/create_session_keyspace_and_table.sql` inside this bundle.


As you can see in the `Resources/config/services.yml` file you need to pass a `Cassandra\Cluster` (it's an interface even if it doesn't like one) instance to the session handler upon instantiation. Because the DefaultCluster has a lot of configuration options (for SSL, authentication and a whole lot more – see http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/ for more information) you may replace this class entirely and just instantiate your own custom cluster class instance. Here's how:

1. Replace the class name to use in your `app/config/config.yml` file:
```yml
    parameters:
        cassandra_cluster.class: Namespace/To/Your/Own/Class
```

2. Take a look at how the bundle's implementation works in `Lib/Cassandra/Cluster.php`.
