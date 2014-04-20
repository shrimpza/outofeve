Out of Eve
==========

**Out of Eve** is a web application which allows you to keep complete track of your Eve characters and corporations, when you're not in Eve. Because it's web-based, all you need is a browser and internet access to keep tabs on your market orders, transactions, assets, manufacturing jobs, training, starbases, etc.

Out of Eve supports multiple characters across as many Eve accounts as you'd like, supports a full range of Eve personal and corporate API data, full item and ship reference, as well as a number of useful out-of-game utilities.


Installation
============

System Requirements
-------------------
* PHP 5 with curl module enabled
* MySQL

Prerequesits
------------
* MySQL Eve static data dump 
    (https://forums.eveonline.com/default.aspx?g=posts&t=252031)
* Icons and Types images from the Eve Community Toolkit 
    (http://community.eveonline.com/community/fansites/toolkit/)

----------

Installation is quite straight-forward. Extract and upload the entire contents of the `outofeve-x.x` source package to your web server.

Extract the `ExpansionName_x.x_Types` and `ExpansionName_x.x_Icons` packages from the community toolkit into the "`eveimages`" directory.

When you have the MySQL database dump imported, you will need to run the included "`sql/additional-tables.sql`" SQL script on that database, which will create tables for the missing Manufacturing completion status descriptions, corporation roles and icons converted from YAML format.

In addition to importing CCP's database dump, you will need to create an additional database for Out of Eve's users and Eve account details. Once you have a database created and ready, execute the contents of "`sql/install-db.sql`" on that database to create the required tables.


Before you can begin configuring settings, create a copy of "`includes/config.default.php`" named "`includes/config.php`".

Whip open your new "`includes/config.php`", and make sure the settings are all suitable and match your environment. Most things should not need changing. Just make sure the "`$config['site']['url']`" option is correct; it should be the relative path to your Out of Eve site. For example, if you're running it from:

> http://mywebsite.com/outofeve/

Then "`$config['site']['url']`" should be set to "`/outofeve`".


If you want to encrypt your Eve API keys (**highly recommended!**), you will need to create an encryption key file. This is a simple plain text file containing nothing but a keyword which will be used to encrypt API keys stored in the Out of Eve accounts database. This file should be kept well away from any www-published paths. An example for creating a key file on *nix:

    $ echo "mykey9876" > /root/ooekeypass

You can then configure `$config['site']['keypass']` as follows:

```
$config['site']['keypass'] = '/root/ooekeypass';
```

If you do not want to encrypt API keys (**not recommended!**), leave the keypass option empty:

```
$config['site']['keypass'] = '';
```

Another option you may wish to change would be "`$config['eve']['cache_dir']`". This option controls where cached API XML files are stored. I'd recommend you store these outside of any www-available paths, for example:

```
$config['eve']['cache_dir'] = '/var/cache/outofeve/'
```

Just make sure the path is writable by the web server (either `chmod` or `chown` the directory with relevant options). By default this directory is just "`cache/`" where you uploaded Out of Eve.

Storing these cache files outside of the website path improves security and reduces the chances of someone getting hold of your character data (no passwords, API keys, user IDs etc are stored here, though).


Once you are happy with `config.php`, the Eve data has been imported, your Out of Eve database has been created, and the `eveimages` directory has been filled, you should be ready to go!

Should you be feeling generous, ISK donations to "Azazel Mordred" are greatly appreciated!
