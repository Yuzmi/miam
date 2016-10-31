# Miam

RSS agregator using [Symfony 3](https://symfony.com/), [SimplePie](https://github.com/simplepie/simplepie).  
Potentially unstable as it's still on development and not (yet) restricted to stable versions.  
Also, use a modern browser.  

### Features

- Multi-user
- Catalog & Admin
- Categories
- Item marking
- User settings
- Import/Export OPML

### Requirements

- Linux
- Apache + Mod rewrite
- PHP 5.5.9+ / Extensions: curl, iconv, imagick, json, mbstring, tidy, xml
- MySQL
- [Sass](http://sass-lang.com/install)
- [Composer](https://getcomposer.org/download/)

### Installation

- Clone the project (or download it manually)
```shell
git clone https://github.com/Yuzmi/miam.git
```

- Install dependencies
```shell
composer install # If it fails, try: composer update
```

- Check requirements
```shell
php bin/symfony_requirements
php bin/console miam:requirements:check
```

- Create the database
```shell
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

- Install assets
```shell
php bin/console assetic:dump --env=prod
php bin/console assets:install --env=prod
```

- Prepare the cache
```shell
php bin/console cache:clear --env=prod
```

- Grant write permissions to www-data on these directories : var/cache, var/logs, web/images
```shell
apt install acl
setfacl -R -m u:www-data:rwX var/cache var/logs web/images
setfacl -dR -m u:www-data:rwX var/cache var/logs web/images
# OR
chmod -R 777 var/cache var/logs web/images
```

- Configure Apache
```apache
DocumentRoot /var/www/miam/web
<Directory /var/www/miam/web>
	AllowOverride All
</Directory>
```

### CRON
```
*/30 * * * * php /var/www/miam/bin/console miam:parse:feeds used --env=prod --no-debug
```

### TODO, MAY-DO

- order management for categories and feeds
- filters => subscription_item
- postgresql & sqlite
- ico to png without imagick
- improve admin