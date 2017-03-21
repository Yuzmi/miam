# Installation

Following commands are based on my system (PHP 7 and Ubuntu 16.04).  
They may change depending on your own system. 

### Requirements  

#### Apache

```shell
apt install -y apache2
a2enmod rewrite # Enable the mod_rewrite module
service apache2 restart
```

#### PHP

```shell
apt install -y php libapache2-mod-php7.0 php7.0-curl php-imagick php7.0-mbstring php7.0-tidy php7.0-xml
```

#### MySQL or PostgreSQL

```shell
# MySQL
apt install -y mysql-server php7.0-mysql

# PostgreSQL
apt install -y postgresql php7.0-pgsql
```

#### [Composer](https://getcomposer.org/download/)

```shell
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

#### Git (Only if you clone the project)

```shell
apt install -y git
```

#### Timezones

Make sure your server timezone and your PHP timezone are identical.  

```shell
# Server
cat /etc/timezone # Show
dpkg-reconfigure tzdata # Edit

# PHP
nano /etc/php/7.0/apache2/php.ini
# Check the 'date.timezone' setting in the "Date" section
```

### Miam

#### Clone the project (or [download it manually](https://github.com/Yuzmi/miam/archive/master.zip))

```shell
cd /var/www
git clone https://github.com/Yuzmi/miam.git
cd miam
```

#### Dependencies & parameters

The PHP-DOM extension must be enabled.  

```shell
composer install
``` 

During this part, you'll be asked to valid or edit some parameters.  
Change them depending on your own installation.  

```
# Database parameters
database_driver: 	pdo_mysql 	# 'pdo_pgsql' for PostgreSQL 
database_host: 		127.0.0.1
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
```

#### PHP requirements

```shell
php bin/symfony_requirements
php bin/console miam:requirements
```

Make sure all required extensions are installed.  

#### Database user (PostgreSQL only)

Here is an example to create a PostgreSQL user if you don't have one.  

```shell
sudo -i -u postgres
psql

# Create the user
CREATE USER miam;

# Create the password
ALTER USER miam WITH ENCRYPTED PASSWORD 'your_password';

# Grant permission to create databases
ALTER ROLE miam WITH CREATEDB;

\q
exit
```

#### Database

```shell
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

#### Cache

```shell
php bin/console cache:clear --env=prod
```

#### Permissions

Grant write permissions to Apache for var/cache, var/logs and web/images folders.  

```shell
apt install acl
setfacl -R -m u:www-data:rwX var/cache var/logs web/images
setfacl -dR -m u:www-data:rwX var/cache var/logs web/images
# OR
chmod -R 777 var/cache var/logs web/images # not recommended
```

#### Apache configuration

Edit /etc/apache2/sites-available/000-default.conf and add these settings:

```apache
DocumentRoot /var/www/miam/web
<Directory /var/www/miam/web>
	AllowOverride All
</Directory>
```

Reload the Apache configuration  

```shell
service apache2 reload
```

#### Cron job

Open the crontab.  

```shell
crontab -e
```

And add one of these lines (not both):  

```
# Normal
*/30 * * * * php /var/www/miam/bin/console miam:parse:feeds subscribed -e=prod --no-debug
# Do not forget the flags !

# Faster, require Python 3
*/30 * * * * python3 /var/www/miam/parse.py --feeds subscribed
# You can also add the option "--threads 2" and change the number of threads (2 on default)
```

It will parse your feeds automatically every 30 minutes.  
The "subscribed" argument will only parse the feeds you're subscribed to.  
