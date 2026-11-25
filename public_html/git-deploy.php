<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');
http_response_code(410);
echo "This deployment endpoint is not part of the public mirror.\n";
