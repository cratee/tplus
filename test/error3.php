<?php
ini_set('display_errors', 1);
ini_set('display_startup_errrors', 1);
error_reporting(E_ALL);

include_once '../dist/Tpl-dist.php';

echo Tpl::get('error3.html', []);