<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin-layout.php';
require_https(); // druhá poistka popri .htaccess
auth_start_session();
