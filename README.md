A Symfony project created on December 12, 2015, 9:25 pm.
Well, that's what it says...

SERVER
=====
Packages
```shell
sudo apt-get install apache2
sudo apt-get install mysql-server
sudo apt-get install php5 libapache2-mod-php5 php5-mysql php5-gd php5-imagick
sudo service apache2 restart
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
	Order Allow,Deny
	Allow from All
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
composer selfupdate
composer install
```

Acl
```shell
HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/logs rss web/images/feeds
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/logs rss web/images/feeds
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

TODO
=====
- Categories/Lists/Groups in catalog
- change headers
- unread for all articles
- try postgresql and sqlite
- login/register page