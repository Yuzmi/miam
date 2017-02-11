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
* [Composer](https://getcomposer.org/download/)

### Installation

Read instructions in INSTALL.md.

### Notes

This project is potentially unstable as it's still in development.  
Moreover it uses FOSJsRoutingBundle 2.0 which is still in its alpha version.  

The default config is set for MySQL. Change parameters for PostgreSQL.  
Other engines may work but i don't support them at the moment.  

CSS files are generated using [Sass](http://sass-lang.com).  
Originals files are found in the src/MiamBundle/Resources/public/scss folder.  

Also, use a modern browser.  

### May-Do (or not)

- Order management for categories and feeds
- Filters => subscription_item
- ICO to PNG without imagick
- Improve admin
- PubSubHubBub