
<?php

putenv("PIMCORE_PROJECT_ROOT=/var/www/html/");



include "../../../vendor/autoload.php";


$_ENV['PIMCORE_PROJECT_ROOT']  ="/var/www/html/";

\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();
