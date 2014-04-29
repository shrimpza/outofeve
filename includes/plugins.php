<?php

class Plugin {

    var $name = 'Base Plugin';
    var $_options = array();
    var $level = 0;

    function Plugin($db, $site) {
        $this->db = $db;

        $this->site = $site;
    }

    function render($template, $vars) {
        $smarty = new Smarty();

        $smarty->registerPlugin('modifier', 'eveNum', 'eveNum');
        $smarty->registerPlugin('modifier', 'eveNumInt', 'eveNumInt');
        $smarty->registerPlugin('modifier', 'eveRoman', 'eveRoman');
        $smarty->registerPlugin('modifier', 'formatTime', 'formatTime');
        $smarty->registerPlugin('modifier', 'yesNo', 'yesNo');

        $smarty->setTemplateDir($GLOBALS['config']['plugins']['directory'] . '/' . get_class($this) . '/templates');
        $smarty->setCompileDir($GLOBALS['config']['templates']['compile_dir']);

        foreach ($vars as $var => $value) {
            $smarty->assign($var, $value);
        }

        $smarty->assign('site_url', $GLOBALS['config']['site']['url']);
        $smarty->assign('url_params', $_GET);
        $smarty->assign('theme', $GLOBALS['config']['templates']['theme']);

        return $smarty->fetch($template . '.html');
    }

//      function getContent() {
//      }
//
//      function getContentJson() {
//      }
//
//      function getSideBox() {
//      } 
}

class Paginator {

    var $source;
    var $pageData;
    var $pageCount = 0;
    var $pageNum = 0;
    var $nextPage = 0;
    var $prevPage = 0;

    function Paginator($source, $pageSize, $currentPage) {
        $this->source = $source;
        $this->pageData = $source;

        if (count($this->pageData) > $pageSize) {
            $pages = array_chunk($this->pageData, $pageSize);

            $this->pageCount = count($pages);
            $this->pageNum = max((int) $currentPage, 0);
            $this->nextPage = min($this->pageNum + 1, $this->pageCount);
            $this->prevPage = max($this->pageNum - 1, 0);

            $this->pageData = $pages[$this->pageNum];
        }
    }

}

?>