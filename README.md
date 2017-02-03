# Miam

RSS agregator using [Symfony 3](https://symfony.com/) and [SimplePie](https://github.com/simplepie/simplepie).  

### Features

* Multi-user
* Categories
* Item marking
* User settings
* OPML import/export
* Localization (en/fr)

### Requirements

* Linux
* Apache & Mod rewrite enabled
* PHP 5.5.9+ or PHP 7  
* MySQL or PostgreSQL
* [Sass](http://sass-lang.com/install)
* [Composer](https://getcomposer.org/download/)

### Installation

#### Clone the project (or [download it manually](https://github.com/Yuzmi/miam/archive/master.zip))

```shell
git clone https://github.com/Yuzmi/miam.git
```

#### Dependencies and requirements

```shell
# Check Symfony requirements
php bin/symfony_requirements

# Install vendor libraries
composer install # If it fails, you can try: composer update

# Check Miam requirements
php bin/console miam:requirements
```

#### Set parameters

```
# Database parameters
database_driver: 	pdo_mysql 	# 'pdo_pgsql' for PostgreSQL 
database_host: 		localhost
database_port: 		null
database_name: 		miam
database_user: 		root
database_password: 	~
database_charset: 	utf8mb4 	# 'utf8' for PostgreSQL

# Skip the mailer_* parameters

# Localization
locale: 	en 					# 'fr' for french

# Secret value for security
secret: 	YourSecret

# Path to the SCSS binary
scss_path: 	/usr/local/bin/scss
```

#### Create the database

```shell
php bin/console doctrine:database:create # if not already created
php bin/console doctrine:schema:create
``` 

#### Install assets

```shell
php bin/console assetic:dump --env=prod
php bin/console assets:install --env=prod
```

#### Prepare the cache

```shell
php bin/console cache:clear --env=prod
```

#### Grant write permissions on var/cache, var/logs and web/images

```shell
apt install acl
setfacl -R -m u:www-data:rwX var/cache var/logs web/images
setfacl -dR -m u:www-data:rwX var/cache var/logs web/images
# OR
chmod -R 777 var/cache var/logs web/images # not recommended
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

#### Add an admin (optional)
```
php bin/console miam:admin:add YOUR_USERNAME
```

### Note about the stability

It's potentially unstable as it's still on development and not (yet) restricted to stable versions.  
The default config is set for MySQL. Change parameters for PostgreSQL.  
Other engines may or may not work.  
Also, use a modern browser.  

### May-Do

- order management for categories and feeds
- filters => subscription_item
- ico to png without imagick
- improve admin