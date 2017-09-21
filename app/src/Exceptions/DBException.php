<?php

namespace Exceptions;

class DBException extends \Exception
{
    const MESSAGE_DATABASE_CONNECTION= "Could not connect to the database";

    const MESSAGE_DATABASE_CONNECTION_BROKEN= "Database connection broken";

    const MESSAGE_UPDATE_ERROR = "Data could not be updated";

    const MESSAGE_SAVE_ERROR = "Data could not be saved";

    const MESSAGE_DELETE_ERROR = "Data could not be deleted";

    const MESSAGE_SAVE_MISSING_FIELD_ERROR = "Data could not be saved due to missing fields: %s";

    const MESSAGE_QUEUE_FETCH_ERROR = "Failed to fetch from message queue: %s";

    const MESSAGE_QTM_TASK_SAVE_ERROR = 'Failed to save task: %s';

    const MESSAGE_QTM_TASK_DOES_NOT_EXIST = 'Task with id %s does not exist';

    const MESSAGE_QTM_MESSAGE_DOES_NOT_EXIST_FOR_TASK = 'Message %s does not exist for task %s';
}
