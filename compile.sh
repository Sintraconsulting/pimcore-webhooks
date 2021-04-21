#!/bin/bash
echo "running test on cicd"


composer validate --strict --no-check-version
      
composer install --prefer-dist --no-progress --ignore-platform-reqs
      
/vendor/bin/pimcore-install --admin-username pimcore --admin-password pimcore --mysql-username pimcore --mysql-password pimcore --mysql-database pimcore --mysql-host-socket db

/var/www/html/vendor/bin/simple-phpunit --filter testB WebHookTest.php


