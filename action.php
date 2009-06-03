<?php
/**
 * Plugin for a nicer Admin main page with some layout
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Håkan Sandell <hakan.sandell@home.se>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC'))
    die();

if (!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_adminhomepage extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo() {
    return array (
            'author' => 'H&aring;kan Sandell',
            'email'  => 'hakan.sandell@home.se',
            'date'   => @file_get_contents(DOKU_PLUGIN.'adminhomepage/VERSION'),
            'name'   => 'AdminHomePage',
            'desc'   => 'Replacement for "Admin" page with better usability',
            'url'    => 'http://www.dokuwiki.org/plugin:adminhomepage'
        );
    }

    /**
     * register the eventhandlers
     */
    function register(& $controller) {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_act_preprocess');
        $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'handle_act_unknown');
    }

    /**
     * Looks for admin action, if found the name is changed so TPL_ACT_UNKNOWN is raised
     */
    function handle_act_preprocess(& $event, $param) {
        if (($event->data == 'admin') && empty($_REQUEST['page']) && (act_permcheck($event->data) == 'admin')) {
            $event->data = 'adminhomepage';
            $event->stopPropagation();
            $event->preventDefault();
        }
    }

    /**
     * Catches the "unknown" event "adminhomepage" and outputs the alternative admin main page
     */
    function handle_act_unknown(& $event, $param) {
        if ($event->data == 'adminhomepage') {
            $this->_html_admin();
            $event->stopPropagation();
            $event->preventDefault();
        }
    }

    function _html_admin(){
        global $ID;
        global $INFO;
        global $lang;
        global $conf;
        global $auth;

        // build menu of admin functions from the plugins that handle them
        $pluginlist = plugin_list('admin');
        $menu = array();
        foreach ($pluginlist as $p) {
            if($obj =& plugin_load('admin',$p) === NULL) continue;

            // check permissions
            if($obj->forAdminOnly() && !$INFO['isadmin']) continue;

            $menu[$p] = array('plugin' => $p,
                                'prompt' => $obj->getMenuText($conf['lang']),
                                'sort' => $obj->getMenuSort()
                            );
        }

        // check if UserManager available
        $usermanageravailable = true;
        if (!isset($auth)) {
          $usermanageravailable = false;
        } else if (!$auth->canDo('getUsers')) {
          $usermanageravailable = false;
        }

        // output main tasks
        ptln('<h1>'.$this->getLang('pageheader').'</h1>');
        ptln('<div id="admin__maintable">');
        ptln('  <div id="admin__tasks">');
        if ($INFO['isadmin']) {
            if ($usermanageravailable) {
                ptln('    <div id="admin__usermanager"><a href="'.wl($ID, 'do=admin&amp;page=usermanager').'">'.$menu[usermanager]['prompt'].'</a></div>');
            }
            ptln('    <div id="admin__acl"><a href="'.wl($ID, 'do=admin&amp;page=acl').'">'.$menu['acl']['prompt'].'</a></div>');
            ptln('    <div id="admin__plugin"><a href="'.wl($ID, 'do=admin&amp;page=plugin').'">'.$menu['plugin']['prompt'].'</a></div>');
            ptln('    <div id="admin__config"><a href="'.wl($ID, 'do=admin&amp;page=config').'">'.$menu['config']['prompt'].'</a></div>');
        }else{
            ptln('&nbsp');
        }
        ptln('  </div>');
        ptln('  <div id="admin__version">');
        ptln('    <div><b>'.$this->getLang('wiki_version').'</b><br/>'.getVersion().'</div>');
        ptln('    <div><b>'.$this->getLang('php_version').'</b><br/>'.phpversion().'</div>');
        ptln('  </div>');
        ptln('</div>');

        // remove the four main plugins
        unset($menu['acl']);
        if ($usermanageravailable) unset($menu['usermanager']);
        unset($menu['config']);
        unset($menu['plugin']);
  
        // output the remaining menu
        usort($menu, 'p_sort_modes');
        ptln('<h2>'.$this->getLang('more_adminheader').'</h2>');
        ptln('<div class="level2">');
        echo $this->render($this->getLang('more_admintext'));
        ptln('<ul id="admin__pluginlist">');
        foreach ($menu as $item) {
          if (!$item['prompt']) continue;
          ptln('  <li class="level1"><div class="li"><a href="'.wl($ID, 'do=admin&amp;page='.$item['plugin']).'">'.$item['prompt'].'</a></div></li>');
        }
        ptln('</ul></div>');
    }

}
