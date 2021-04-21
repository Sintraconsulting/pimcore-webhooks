#!/bin/bash
echo "running test on cicd"

cd /var/www/html

echo "working in html"

pwd
ls -l

var/www/html/src/WebHookBundle/composer validate --strict --no-check-version
      
var/www/html/src/WebHookBundle/composer install --prefer-dist --no-progress --ignore-platform-reqs

pwd
ls -l
      
vendor/bin/pimcore-install --admin-username pimcore --admin-password pimcore --mysql-username pimcore --mysql-password pimcore --mysql-database pimcore --mysql-host-socket db

cd src/WebHookBundle/tests
/var/www/html/vendor/bin/simple-phpunit --filter testB WebHookTest.php


