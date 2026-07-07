<?php
/**
 * dashboard.php — legacy URL → role dashboard redirect
 */
require_once 'config.php';
requireLogin();
header('Location: ' . getDashboardUrl());
exit;