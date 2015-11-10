#/bin/sh

curl -sS https://getcomposer.org/installer | php
php composer.phar  global require "fxp/composer-asset-plugin:~1.0.3"
php composer.phar update

php yii mongodb-migrate
php yii fill-users

vendor/bin/codecept run