<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/integrations.php';
$app = require __DIR__ . '/../config/app.php';
date_default_timezone_set($app['timezone']);
