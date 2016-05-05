Out of Eve
==========

**Out of Eve** is a web application which allows you to keep complete track of your EVE characters and corporations, when you're not in EVE. Because it's web-based, all you need is a browser and internet access to keep tabs on your market orders, transactions, assets, manufacturing jobs, training, starbases, etc.

Out of Eve supports multiple characters across as many EVE accounts as you'd like, supports a full range of EVE personal and corporate API data, full item and ship reference, as well as a number of useful out-of-game utilities.

Last updated for: *Citadel* (May 2016)


Installation
============

### System Requirements
* Web server with PHP 5 (with curl module enabled)
* MySQL


### Prerequesits
* [MySQL EVE static data dump](https://www.fuzzwork.co.uk/dump/)
* [Icons and Types images from EVE Developer Resources Toolkit](https://developers.eveonline.com/resource/resources)


### Files

Installation is quite straight-forward. Extract and upload the entire contents of the `outofeve-x.x` source package to your web server.

Extract the `ExpansionName_x.x_Types` and `ExpansionName_x.x_Icons` packages from the community toolkit into the `eveimages` directory.


### Database

In addition to importing CCP's database dump, you will need to create an additional database for Out of Eve's users and EVE account details. Once you have a database created and ready, execute the contents of `sql/install-db.sql` on that database to create the required tables.


### Initial Configuration

Before you can begin configuring settings, create a copy of `includes/config.default.php` named `includes/config.php`.

Whip open your new `includes/config.php` file, and make sure the settings are all suitable and match your environment. Most things should not need changing. Make sure the `$config['site']['url']` option is correct; it should be the relative path to your Out of Eve site. For example, if you're running it from:

> http://mywebsite.com/outofeve/

Then `$config['site']['url']` should be set to `/outofeve`.


### API Key Security

Before writing to the database, OOE encrypts API keys for an added layer of security - should your database be compromised, your API keys should remain secure, as long as the attackers do not have access to the key used to encrypt the keys -- the encryption is reversible by necessity since we still need to attach them to API requests.

The `$config['site']['keypass']` option may be a random string of any length, which is used as the encryption key.


### Cache Paths

Another option you may wish to change would be `$config['eve']['cache_dir']`. This option controls where cached API XML files are stored. It is recommended that you store these outside of any www-available paths, for example:

```php
$config['eve']['cache_dir'] = '/var/cache/outofeve/'
```

Make sure the path is writable by the web server (either `chmod` or `chown` the directory with relevant options). By default this directory is just `cache/` where you uploaded Out of Eve.

Storing these cache files outside of the website path improves security and reduces the chances of someone getting hold of your character data (no passwords, API keys, user IDs etc are stored in the cache, though).


### Memcached

To reduce load on your database, Out of Eve may optionally make use of one or more `memcached` instances to cache entities retrieved from the SDE database - significantly reducing the number of queries per page load (which can be hundreds or thousands on some pages).

All `memcached` related options are available in the `$config['memcached']` option set.

Configuration of a `memcached` instance and related PHP modules is beyond the scope of this document.


### Conclusion

Review the other available options, and once you are happy with `config.php`, the EVE SDE data has been imported, your Out of Eve database has been created, and the `eveimages` directory has been filled, you should be ready to go!


--

If you find Out of Eve useful, in-game ISK donations to "Azazel Mordred" are greatly appreciated!
