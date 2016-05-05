<?php

/*************************************************************************
General site config
*************************************************************************/
// relative URL to your Out of Eve site
$config['site']['url'] = '/outofeve';
$config['site']['title'] = 'Out of Eve';

// Change to false to disable new user registrations
// (useful for personal use)
$config['site']['registration'] = true;

// Secret key for encrypting API keys before storing.
// This can just be a random string of characters, and assuming nobody
// gains access to this config file, API keys stored in the database
// should remain reasonably secure.
$config['site']['keypass'] = 'weak secret';

// if enabled, load times, DB usage and API access info will be printed
// in the footer of each page
$config['site']['showstats'] = false;


/*************************************************************************
Database for OOE (users, API details, etc)
*************************************************************************/
$config['database']['dsn'] = 'mysql:host=localhost;dbname=ooe';
$config['database']['user'] = 'ooe';
$config['database']['pass'] = '';

/*************************************************************************
Database generated from EVE SDE database dump
*************************************************************************/
$config['evedatabase']['dsn'] = 'mysql:host=localhost;dbname=evedump';
$config['evedatabase']['user'] = 'ooe';
$config['evedatabase']['pass'] = '';

/*************************************************************************
Memcached options for caching static database entities
Requires PHP's memcached module
*************************************************************************/
$config['memcached']['enable'] = false;
$config['memcached']['persistent_id'] = 'ooe';
$config['memcached']['expiration'] = 60*60*24*5;  // 5 days
$config['memcached']['servers'] = array(
    array('localhost', 11211),
);

/*************************************************************************
Templates and themes
The compile_dir should be writable
*************************************************************************/
$config['templates']['theme'] = 'dark'; // (dark || light)

$config['templates']['compile_dir'] = dirname(__FILE__).'/../templates/compiled';
$config['templates']['theme_dir'] = dirname(__FILE__).'/../templates';

/*************************************************************************
EVE API and paths
The cache_dir should be writable
*************************************************************************/
$config['eve']['cache_dir'] = dirname(__FILE__).'/../cache/';
$config['eve']['api_url'] = 'https://api.eveonline.com';
$config['eve']['method'] = 'POST';

// Add this many seconds to the cachedUntil time
// May prove useful for potential slight overlaps on some re-requests
$config['eve']['cache_time_add'] = 120;

$config['eve']['journal_records'] = 2560;
$config['eve']['transaction_records'] = 2560;

$config['images']['types'] = 'eveimages/Types/%1$d_%2$d.png';
$config['images']['icons'] = 'eveimages/Icons/items/%1$d_%3$d_%2$d.png';

/*************************************************************************
No need to modify anything beyond this point, unless you've writing
additional modules and need to enable them.
*************************************************************************/
$config['plugins']['enabled'] = array(
    'users',
    'mainmenu',
    'showinfo',
    'character',
    'corporation',
    'standings',
    'mail',
    'assets',
    'transactions',
    'journal',
    'orders',
    'manufacture',
    'kills',
    'starbases',
    'util_prices',
    'util_production',
    'util_prodprofit',
    'util_mining',
    //'ajaxtest',
);
$config['plugins']['directory'] = dirname(__FILE__).'/../plugins/';
$config['plugins']['default'] = 'users';

$GLOBALS['config'] = $config;

ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__).'/libs'.PATH_SEPARATOR.dirname(__FILE__).'/eve');

?>
