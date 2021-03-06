# Miam

RSS agregator using [Symfony 3](https://symfony.com/) and [SimplePie](https://github.com/simplepie/simplepie).  

### Features

* Multi-user
* Categories
* Read/Starred items
* User settings
* OPML import/export
* Localization (en/fr)

### Requirements

* Linux
* Apache
* PHP 5.5.9+ or PHP 7  
* MySQL or PostgreSQL
* [Composer](https://getcomposer.org/download/)

### Installation

Read instructions in [INSTALL.md](https://github.com/Yuzmi/miam/blob/master/INSTALL.md).

### Commands

```
# Add an admin
php bin/console miam:admin:add USERNAME

# Remove old items
php bin/console miam:items:remove-old

# See all commands
php bin/console
```

### Notes

This project is potentially unstable as it's still in development.  
Moreover it uses FOSJsRoutingBundle 2.0 which is still in its alpha version.  

The default config is set for MySQL. Change parameters for PostgreSQL.  
Other engines may work but i don't support them.  

CSS files are generated using [Sass](http://sass-lang.com).  
Originals files are found in the src/MiamBundle/Resources/public/scss folder.  

The admin section is not polished, don't be surprised.  

Use a modern browser.  

### May-Do (or not)

- Order management for categories and feeds
- Filters => subscription_item
- ICO to PNG without imagick