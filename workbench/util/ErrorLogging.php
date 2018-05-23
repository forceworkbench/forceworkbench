<?php
if (getenv('SENTRY_DSN') !== false) {
  $client = new Raven_Client(getenv('SENTRY_DSN'));
  $error_handler = new Raven_ErrorHandler($client);
  $error_handler->registerExceptionHandler();
  $error_handler->registerErrorHandler();
  $error_handler->registerShutdownFunction();
}