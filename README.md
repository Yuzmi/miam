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

Read instructions in INSTALL.md.

### Notes

This project is potentially unstable as it's still in development.  
Moreover it uses FOSJsRoutingBundle 2.0 which is still in its alpha version.  

The default config is set for MySQL. Change parameters for PostgreSQL.  
Other engines may or may not work.  

Also, use a modern browser.  

### May-Do (or not)

- Order management for categories and feeds
- Filters => subscription_item
- Ico to png without imagick
- Improve admin
- PubSubHubBub