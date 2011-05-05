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

    // path to API key encryption key file. 
    // Set to blank to disable APi key encryption
    $config['site']['keypass'] = '/root/ooekeypass';

    // enable the precache.php script to pre-load API data
    $config['site']['precache'] = false;


    /*************************************************************************
        Database for OOE (users, API details, etc)
    *************************************************************************/
    $config['database']['dsn'] = 'mysql:host=localhost;dbname=ooe';
    $config['database']['user'] = 'ooe';
    $config['database']['pass'] = '';

    /*************************************************************************
        Database generated from Eve database dump
    *************************************************************************/
    $config['evedatabase']['dsn'] = 'mysql:host=localhost;dbname=evedump';
    $config['evedatabase']['user'] = 'ooe';
    $config['evedatabase']['pass'] = '';

    /*************************************************************************
        Default site theme (clean, eve, maroon)
    *************************************************************************/
    $config['templates']['theme'] = 'clean';

    /*************************************************************************
        URLs for RSS feeds displayed on the home page
    *************************************************************************/
    $config['rss'] = array();
    $config['rss'][] = 'http://myeve.eve-online.com/feed/rdfdevblog.asp';
    $config['rss'][] = 'http://myeve.eve-online.com/feed/rdfnews.asp?tid=1';

    /*************************************************************************
        EVE API and paths, for caching, themes, etc.
    *************************************************************************/
    $config['eve']['cache_dir'] = dirname(__FILE__).'/../cache/';
    $config['eve']['api_url'] = 'http://api.eve-online.com';
    $config['eve']['method'] = 'POST';

    $config['templates']['compile_dir'] = dirname(__FILE__).'/../templates/compiled';
    $config['templates']['theme_dir'] = dirname(__FILE__).'/../templates';

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
