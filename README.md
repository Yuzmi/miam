# Miam

RSS agregator using [Symfony 3](https://symfony.com/) & [SimplePie](https://github.com/simplepie/simplepie).
Potentially unstable as it's still on development and not restricted to stable versions.

#### Features

- Multi-user
- Catalog & Admin
- Categories
- Read/Starred items
- User settings
- Import/Export OPML

#### Requirements

- Linux (Tested on Ubuntu & Raspbian)

- Apache + Mod rewrite
```shell
sudo apt-get install apache2
sudo a2enmod rewrite
```

- PHP 5 + extensions (Curl, GD, Imagick, Tidy)
```shell
sudo apt-get install php5 libapache2-mod-php5 php5-curl php5-gd php5-imagick php5-tidy
```

- MySQL (Never tried PostgreSQL & SQLite)
```shell
sudo apt-get install mysql-server php5-mysql
```

- Sass
```shell
sudo apt-get install ruby
gem install sass
```

- NodeJS (only for the experimental cron)
```shell
sudo apt-get install nodejs
sudo apt-get install npm
```

- [Composer](https://getcomposer.org/download/)

#### Installation

- Clone the project (or install it manually)
```shell
git clone https://github.com/Yuzmi/miam.git
```

- Dependencies
```shell
composer install
```

- ACL
```shell
sudo apt-get install acl
sudo setfacl -R -m u:www-data:rwX -m u:`whoami`:rwX var/cache var/logs rss web/images
sudo setfacl -dR -m u:www-data:rwX -m u:`whoami`:rwX var/cache var/logs rss web/images
```

- Doctrine, assets, cache
```shell
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console assetic:dump
php bin/console assets:install
php app/console cache:clear
php app/console cache:warmup
```

#### Apache config

I suppose the project folder is /var/www/miam
```apache
DocumentRoot /var/www/miam/web

<Directory "/var/www/miam/web">
	AllowOverride All
	#Order Allow,Deny
	#Allow from All
</Directory>
```

#### CRON

```
// Default cron
*/30 * * * * php /var/www/miam/bin/console miam:parse:all

// With NodeJS, faster but experimental
*/30 * * * * php /var/www/miam/bin/console miam:generate:json && nodejs /var/www/miam/get_feeds.js && php /var/www/miam/bin/console miam:parse:files
```

#### TODO

- order management for categories and feeds
- dark theme
- filters => subscription_item
