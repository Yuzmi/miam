Soon...
==========

A Symfony project created on December 12, 2015, 9:25 pm.
Well, that's what it says...

ACL
=====
```shell
HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/logs web/images/feeds
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/logs web/images/feeds
```

TODO
=====
- Categories/Lists/Groups in catalog
- Improve OPML import