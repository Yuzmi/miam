# Miam

RSS agregator using [Symfony 3](https://symfony.com/) & [SimplePie](https://github.com/simplepie/simplepie).
Potentially unstable as it's still on development and not restricted to stable versions.

### Features

- Multi-user
- Catalog & Admin (WIP)
- Categories
- Read/Starred items
- User settings
- Import/Export OPML

### Requirements

- Linux (Tested on Ubuntu & Raspbian)
- Apache + Mod rewrite
- PHP 5 + extensions (curl, gd, imagick, mbstring, tidy, zlib)
- MySQL (Never tried PostgreSQL & SQLite)
- [Sass](http://sass-lang.com/install)
- [Composer](https://getcomposer.org/download/)
- [NodeJS](https://nodejs.org/en/download/) (only for the experimental cron)

### Installation

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

- Database, assets, cache
```shell
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console assetic:dump --env=prod
php bin/console assets:install --env=prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### Apache config

I suppose the project folder is /var/www/miam
```apache
DocumentRoot /var/www/miam/web

<Directory "/var/www/miam/web">
	AllowOverride All
	#Order Allow,Deny
	#Allow from All
</Directory>
```

### CRON

```
// Default cron
*/30 * * * * php /var/www/miam/bin/console miam:parse:used

// With NodeJS, faster but experimental
*/30 * * * * php /var/www/miam/bin/console miam:generate:json && nodejs /var/www/miam/get_feeds.js && php /var/www/miam/bin/console miam:parse:files
```

### TODO

- order management for categories and feeds
- filters => subscription_item
