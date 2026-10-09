<?php
require_once '../tenant-config.php';

header('Content-Type: application/json');

session_destroy();
jsonResponse(true, 'Logged out successfully');
