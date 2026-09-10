<?php
// Mirrors the require chain in workbench/login.php (shared.php -> session.php's
// config requires -> controllers/LoginController.php), without pulling in
// session.php itself since that runs request-handling side effects
// (session_start, exception handlers, HTTPS redirects) we don't want in tests.
chdir(__DIR__ . '/../workbench');

require_once 'config/constants.php';
require_once 'config/WorkbenchConfig.php';
require_once 'shared.php';
require_once 'controllers/LoginController.php';
