#!/bin/bash
echo "running test on cicd"

cd /var/www/html/src/WebHookBundle

echo "working in html"

pwd
ls -l 

composer validate --strict --no-check-version --working-dir /var/www/html
      
composer install --prefer-dist --no-progress --ignore-platform-reqs --working-dir /var/www/html

ls -l 
echo "cambio dir"
cd /var/www/html
ls -l 
      
vendor/bin/pimcore-install --admin-username pimcore --admin-password pimcore --mysql-username pimcore --mysql-password pimcore --mysql-database pimcore --mysql-host-socket db

cd tests
/var/www/html/vendor/bin/simple-phpunit --filter testB WebHookTest.php


