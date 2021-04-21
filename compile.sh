#!/bin/bash
echo "running test on cicd"
cd /var/www/html/tests
pwd
ls -l
/var/www/html/vendor/bin/simple-phpunit --filter testB WebHookTest.php


