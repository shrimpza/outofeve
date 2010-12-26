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
            $tpl = new Smarty();

            $tpl->register_modifier('eveNum', 'eveNum');
            $tpl->register_modifier('eveNumInt', 'eveNumInt');
            $tpl->register_modifier('eveRoman', 'eveRoman');
            $tpl->register_modifier('formatTime', 'formatTime');
            $tpl->register_modifier('yesNo', 'yesNo');

            $tpl->template_dir = $GLOBALS['config']['plugins']['directory'].'/'.get_class($this).'/templates';
            $tpl->compile_dir = $GLOBALS['config']['templates']['compile_dir'];

            foreach ($vars as $var => $value)
                $tpl->assign($var, $value);
   
            $tpl->assign('site_url', $GLOBALS['config']['site']['url']);
            $tpl->assign('url_params', $_GET);
            $tpl->assign('theme', $GLOBALS['config']['templates']['theme']);

            return $tpl->fetch($template . '.html');
        }

        /*function getContent() {
        }

        function getContentJson() {
        }

        function getSideBox() {
        }*/

    }

?>