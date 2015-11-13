--DROP KEYSPACE IF EXISTS alfacon2_sessions;

CREATE KEYSPACE IF NOT EXISTS symfony2_sessions WITH replication = {'class': 'SimpleStrategy', 'replication_factor': '2'}  AND durable_writes = true;

CREATE TABLE symfony2_sessions.sessions (
  id blob,
  data blob,
  time timestamp,
  PRIMARY KEY ((id))
);
