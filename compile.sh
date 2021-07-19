#!/bin/bash
echo "running test on cicd"

cd /var/www/html/

echo "working in html"

COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/skeleton:^6 tmp
mv tmp/.[!.]* .
mv tmp/* .
rmdir tmp

composer validate --strict --no-check-version 
      
composer install --prefer-dist --no-progress --ignore-platform-reqs

vendor/bin/pimcore-install --admin-username pimcore --admin-password pimcore --mysql-username pimcore --mysql-password pimcore --mysql-database pimcore --mysql-host-socket db

bin/console pimcore:bundle:enable WebHookBundle
bin/console pimcore:bundle:install WebHookBundle

cd src/WebHookBundle/

composer validate --strict --no-check-version 

cd tests

/var/www/html/vendor/bin/phpunit WebHookTest.php
