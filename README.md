# Miam

RSS agregator using [Symfony 3](https://symfony.com/) & [SimplePie](https://github.com/simplepie/simplepie).  

### Features

- Multi-user
- Catalog & Admin
- Categories
- Item marking
- User settings
- OPML import/export
- Localization (english/french)

### Requirements

- Linux
- Apache & Mod rewrite enabled
- PHP 5.5.9+ or PHP 7
Extensions: curl, iconv, imagick, json, mbstring, tidy, xml
- MySQL, PostgreSQL or SQLite
- [Sass](http://sass-lang.com/install)
- [Composer](https://getcomposer.org/download/)

### Installation

#### Clone the project (or [download it manually](https://github.com/Yuzmi/miam/archive/master.zip))

```shell
git clone https://github.com/Yuzmi/miam.git
```

#### Install dependencies

```shell
composer install # If it fails, try: composer update
```

#### Set parameters

```
# Database parameters
database_driver: 	pdo_mysql 	# 'pdo_pgsql' for PostgreSQL, 'pdo_sqlite' for SQLite  
database_host: 		localhost
database_port: 		null
database_name: 		miam
database_user: 		root
database_password: 	~
database_charset: 	utf8mb4 	# 'utf8' for PostgreSQL

# Localization
locale: 	en 	# 'fr' for french

# Secret value for security
secret: 	YourSecret

# Path to the SCSS binary
scss_path: 	/usr/local/bin/scss
```

#### Check requirements

```shell
php bin/symfony_requirements
php bin/console miam:requirements:check
```

#### Create the database

```shell
php bin/console doctrine:database:create # if not already created
php bin/console doctrine:schema:create
``` 
If you use SQLite, the app/data directory must be writable.

#### Install assets

```shell
php bin/console assetic:dump --env=prod
php bin/console assets:install --env=prod
```

#### Prepare the cache

```shell
php bin/console cache:clear --env=prod
```

#### Grant write permissions on app/data, var/cache, var/logs & web/images

```shell
apt install acl
setfacl -R -m u:www-data:rwX app/data var/cache var/logs web/images
setfacl -dR -m u:www-data:rwX app/data var/cache var/logs web/images
# OR
chmod -R 777 app/data var/cache var/logs web/images # not recommended
```

#### Configure Apache

```apache
DocumentRoot /var/www/miam/web
<Directory /var/www/miam/web>
	AllowOverride All
</Directory>
```

#### Add a cron job

```
*/30 * * * * php /var/www/miam/bin/console miam:parse:feeds used --env=prod --no-debug
```

### Note about the stability

It's potentially unstable as it's still on development and not (yet) restricted to stable versions.  
The default config is set for MySQL. PostgreSQL and SQLite should be fine if you set parameters correctly. Other engines may or may not work.  
Also, use a modern browser.  

### May-Do

- order management for categories and feeds
- filters => subscription_item
- ico to png without imagick
- improve admin