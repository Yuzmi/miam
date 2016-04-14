A Symfony project created on December 12, 2015, 9:25 pm.
Well, that's what it says... Work In Progress

SERVER
=====
Apache + PHP
```shell
sudo apt-get install apache2 php5 libapache2-mod-php5 php5-curl php5-gd php5-imagick php5-tidy
sudo service apache2 restart
```

MySQL
```shell
sudo apt-get install mysql-server php5-mysql
```

Mod rewrite if disabled
```shell
sudo a2enmod rewrite
sudo service apache2 restart
```

Apache config
```shell
DocumentRoot /var/www/miam/web

<Directory "/var/www/miam/web">
	AllowOverride All
	#Order Allow,Deny
	#Allow from All
</Directory>
```

Sass
```shell
sudo apt-get install ruby
gem install sass
```

Nodejs
```shell
sudo apt-get install nodejs
sudo apt-get install npm
```

INSTALL
=====

Git & Composer (https://getcomposer.org/download/)
```shell
cd /var/www
git clone https://github.com/Yuzmi/miam.git
cd miam
composer selfupdate
composer install
```

Acl
```shell
sudo apt-get install acl
sudo setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX var/cache var/logs rss web/images
sudo setfacl -dR -m u:www-data:rwX -m u:`whoami`:rwX var/cache var/logs rss web/images
```

Doctrine & assets
```shell
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console assetic:dump
php bin/console assets:install
```

Cache
```shell
php app/console cache:clear
php app/console cache:warmup
```

CRON
=====
Default
```shell
*/30 * * * * php /var/www/miam/bin/console miam:parse:all
```

With NodeJS, faster but experimental
```shell
*/30 * * * * php /var/www/miam/bin/console miam:generate:json && nodejs /var/www/miam/get_feeds.js && php /var/www/miam/bin/console miam:parse:files
```

TODO
=====
- catalog management in admin
- try postgresql and sqlite
- order of categories and feeds
- add options in context menu
- keyboard navigation
- unit tests
- manage if data in favicon href
- change the bundle name... shit controller's name too... (difficulty: hardcore)
- improve textContent