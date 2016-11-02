# Miam

RSS agregator using [Symfony 3](https://symfony.com/) & [SimplePie](https://github.com/simplepie/simplepie).  

### Features

- Multi-user
- Catalog & Admin
- Categories
- Item marking
- User settings
- Import/Export OPML

### Requirements

- Linux
- Apache & Mod rewrite enabled
- PHP 5.5.9+  
Extensions: curl, iconv, imagick, json, mbstring, tidy, xml
- MySQL, PostgreSQL or SQLite  
The default config is set for MySQL, see the bottom section for other engines.
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
If you use SQLite, the app/data directory must be writable.

- Install assets
```shell
php bin/console assetic:dump --env=prod
php bin/console assets:install --env=prod
```

- Prepare the cache
```shell
php bin/console cache:clear --env=prod
```

- Grant write permissions on app/data, var/cache, var/logs & web/images
```shell
apt install acl
setfacl -R -m u:www-data:rwX app/data var/cache var/logs web/images
setfacl -dR -m u:www-data:rwX app/data var/cache var/logs web/images
# OR
chmod -R 777 app/data var/cache var/logs web/images # not recommended
```

- Configure Apache
```apache
DocumentRoot /var/www/miam/web
<Directory /var/www/miam/web>
	AllowOverride All
</Directory>
```

- Add a cron job
```
*/30 * * * * php /var/www/miam/bin/console miam:parse:feeds used --env=prod --no-debug
```

### Other database engines

If you use PostgreSQL or SQLite, you need to make a few changes.
```
# app/config/config.yml
doctrine:
	dbal:
		driver:	pdo_mysql # Change to 'pdo_pgsql' or 'pdo_sqlite'
		charset: utf8mb4 # Change to 'utf8' if you use PostgreSQL
		path: "%kernel.root_dir%/data/data.db3" # Uncomment if you use SQLite
```
I didn't try other engines, it may or may not work with them.

### Note about the stability

It's potentially unstable as it's still on development and not (yet) restricted to stable versions.  
I mainly use MySQL, unknown issues may occur with other database engines.  
Also, use a modern browser.  

### May-Do

- order management for categories and feeds
- filters => subscription_item
- ico to png without imagick
- improve admin