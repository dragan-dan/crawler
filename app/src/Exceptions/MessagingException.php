<?php

namespace Exceptions;

class MessagingException extends \Exception
{
  const QUEUE_NOT_DEFINED = 'Queue %s is not defined';

  const OPERATION_NOT_SUPPORTED = 'Message queue does not support operation: %s';
}
