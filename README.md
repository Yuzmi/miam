A Symfony project created on December 12, 2015, 9:25 pm.
Well, that's what it says...

### Server commands

```shell
// Apache + PHP
sudo apt-get install apache2 php5 libapache2-mod-php5 php5-curl php5-gd php5-imagick php5-tidy
sudo service apache2 restart

// MySQL
sudo apt-get install mysql-server php5-mysql

// Mod rewrite if disabled
sudo a2enmod rewrite
sudo service apache2 restart

// Sass
sudo apt-get install ruby
gem install sass

// NodeJS (only for the experimental cron)
sudo apt-get install nodejs
sudo apt-get install npm
```

### Apache config

```apache
DocumentRoot /var/www/miam/web

<Directory "/var/www/miam/web">
	AllowOverride All
	#Order Allow,Deny
	#Allow from All
</Directory>
```

### Installation

```shell
// Git & Composer (https://getcomposer.org/download/)
cd /var/www
git clone https://github.com/Yuzmi/miam.git
cd miam
composer selfupdate
composer install

// ACL
sudo apt-get install acl
sudo setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX var/cache var/logs rss web/images
sudo setfacl -dR -m u:www-data:rwX -m u:`whoami`:rwX var/cache var/logs rss web/images

// Doctrine, assets, cache
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console assetic:dump
php bin/console assets:install
php app/console cache:clear
php app/console cache:warmup
```

### CRON

```
// Default cron
*/30 * * * * php /var/www/miam/bin/console miam:parse:all

//With NodeJS, faster but experimental
*/30 * * * * php /var/www/miam/bin/console miam:generate:json && nodejs /var/www/miam/get_feeds.js && php /var/www/miam/bin/console miam:parse:files
```

### TODO

- catalog remake
- try postgresql and sqlite
- order of categories and feeds
- manage if data in favicon href
- improve textContent
- subscription_item => filters
- dark theme

It's probably not up-to-date...