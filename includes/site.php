<?php

require_once(dirname(__FILE__) . '/config.php');
require_once('smarty3/Smarty.class.php');
require_once('api.php');
require_once(dirname(__FILE__) . '/functions.php');
require_once(dirname(__FILE__) . '/database.php');
require_once(dirname(__FILE__) . '/plugins.php');

class Site {

    var $plugins = array();
    var $activePlugin = '';
    var $tplVars = array();

    function Site() {
        $this->incPath = dirname(__FILE__) . '/libs/';

        $this->db = new DBManager($GLOBALS['config']['database']);

        if (!isset($_GET['module'])) {
            $_GET['module'] = $GLOBALS['config']['plugins']['default'];
        }

        $this->activePlugin = $_GET['module'];

        $this->loadPlugins();
    }

    function loadPlugins() {
        foreach ($GLOBALS['config']['plugins']['enabled'] as $plugin) {
            include_once($GLOBALS['config']['plugins']['directory'] . $plugin . '/plugin.php');
            $newPlug = new $plugin($this->db, $this);
            $this->plugins[$plugin] = $newPlug;
        }
    }

    function output() {
        $pluginTitle = '';
        $content = '';
        $sideBlocks = array();
        $pluginCSS = false;

        if (file_exists($GLOBALS['config']['plugins']['directory'] . $this->activePlugin . '/plugin.css')) {
            $pluginCSS = $this->activePlugin . '/plugin.css';
        }

        foreach ($this->plugins as $name => $plugin) {
            if ($name == $this->activePlugin) {
                if (($this->user->id > 0) && ($plugin->level <= $this->user->level) || ($plugin->level <= 0)) {
                    if (method_exists($plugin, 'getContent')) {
                        $content = $plugin->getContent();
                    }
                } else {
                    $content = '<h1>DENIED!</h1>';
                }
                $pluginTitle = $plugin->name;
            }

            if (($this->user->id > 0) && ($plugin->level <= $this->user->level) || ($plugin->level <= 0)) {
                if (method_exists($plugin, 'getSideBox')) {
                    $newBlock = array('title' => $plugin->name,
                        'content' => $plugin->getSideBox());
                    $sideBlocks[] = $newBlock;
                }
            }
        }

        $smarty = new Smarty();
        $smarty->setTemplateDir($GLOBALS['config']['templates']['theme_dir'] . '/' . $GLOBALS['config']['templates']['theme']);
        $smarty->setCompileDir($GLOBALS['config']['templates']['compile_dir']);

        $smarty->assign('pluginTitle', $pluginTitle);
        $smarty->assign('content', $content);
        $smarty->assign('sideBlocks', $sideBlocks);
        $smarty->assign('pluginCSS', $pluginCSS);

        $this->tplVars['title'] = $GLOBALS['config']['site']['title'];
        $this->tplVars['site_url'] = $GLOBALS['config']['site']['url'];
        $this->tplVars['theme'] = $GLOBALS['config']['templates']['theme'];
        if (!empty(apiStats::$errors)) {
            $this->tplVars['errors'] = objectToArray(apiStats::$errors);
        }

        foreach ($this->tplVars as $var => $val) {
            $smarty->assign($var, $val);
        }

        if (isset($_GET['popup'])) {
            $smarty->display('popup.html');
        } else {
            $smarty->display('index.html');
        }
    }

    function outputJson() {
        $content = '';

        foreach ($this->plugins as $name => $plugin) {
            if ($name == $this->activePlugin) {
                if (($this->user->id > 0) && ($plugin->level <= $this->user->level) || ($plugin->level <= 0)) {
                    if (method_exists($plugin, 'getContentJson')) {
                        $content = $plugin->getContentJson();
                    }
                } else {
                    $content = '{}';
                }
            }
        }

        header('Content-type: text/plain');
        echo $content;
    }

}

?>