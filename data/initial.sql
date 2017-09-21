/****************************************************************************************
 * SET UP
 */

-- Init JSONB data tables
CREATE TABLE IF NOT EXISTS emails(data jsonb);
CREATE TABLE IF NOT EXISTS tasks(data jsonb);
CREATE TABLE IF NOT EXISTS sub_tasks(data jsonb);
-- Init JSONB message queue table
CREATE TABLE IF NOT EXISTS queue(
  name                VARCHAR(64) NOT NULL,
  message_id          SERIAL,
  message_body        JSONB,
  visibility_timeout  TIMESTAMP WITH TIME ZONE DEFAULT NULL,
  PRIMARY KEY(message_id)
);

-- Grant privileges
GRANT ALL PRIVILEGES ON TABLE emails TO root;
GRANT ALL PRIVILEGES ON TABLE queue TO root;
GRANT ALL PRIVILEGES ON TABLE tasks TO root;
GRANT ALL PRIVILEGES ON TABLE sub_tasks TO root;

DO $$
BEGIN

 /* Message queue indexes */
  IF (
    SELECT to_regclass('public.i_queue_name') ISNULL
  ) THEN
    CREATE INDEX i_queue_name ON queue (name, visibility_timeout);
  END IF;

  IF (
    SELECT NOT to_regclass('public.i_tasks_taskid') ISNULL
  ) THEN
    DROP INDEX i_tasks_taskid;
  END IF;

  -- INDEX on task_id for tasks
  IF (
    SELECT to_regclass('public.i_tasks_task_id') ISNULL
  ) THEN
    CREATE UNIQUE INDEX i_tasks_task_id ON tasks (
        (data->>'task_id')
    );
  END IF;

  -- UNIQUE INDEX on task_id for tasks
  IF (
    SELECT to_regclass('public.i_subtasks_taskid') ISNULL
  ) THEN
    CREATE UNIQUE INDEX i_subtasks_taskid ON sub_tasks (
      (data->>'task_id'),  (data->>'message_id')
    );
  END IF;

END$$;
