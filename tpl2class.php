<?php

/**
 * System Tpl2Class
 * Author: Roberto Segura - Digital Disseny, S.L. - www.digitaldisseny.com
 * Copyright (c) 2012 Digital Disseny, S.L. All Rights Reserved.
 * License: GNU/GPL 2, http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Tpl2Class for Joomla 2.5
 *
 */

//no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Plugin to insert the template name as an html tag class
 * @author Roberto Segura - Digital Disseny, S.L.
 *
 */
class plgSystemTpl2class extends JPlugin {

    // constants to simplify changes
    const PLUGIN_TYPE = 'system';
    const PLUGIN_NAME = 'tpl2class';

    private $_params = null;

    // paths
    private $_pathPlugin = null;

    // urls
    private $_urlPlugin = null;
    private $_urlPluginJs = null;
    private $_urlPluginCss = null;

    function __construct( &$subject ){

        parent::__construct( $subject );

        // Load plugin parameters
        $this->_plugin = JPluginHelper::getPlugin( self::PLUGIN_TYPE, self::PLUGIN_NAME );
        $this->_params = new JRegistry( $this->_plugin->params );

        // init folder structure
        $this->_initFolders();

        // load plugin language
        $this->loadLanguage ('plg_'.$this->_plugin->type.'_'.$this->_plugin->name, JPATH_ADMINISTRATOR);

        // get the active template name
        $this->_currentTplName = $this->_getCurrentTplName();

    }


    function onAfterRender(){

        // required objects
        $app =& JFactory::getApplication();

        // only insert class on frontend
        if (!$app->isSite()) {
            return;
        }

        // actual body text
        $body = JResponse::getBody();

        // JS load
        $jsCode = $this->_genJs();
        if (!empty($jsCode)) {
            // js loads just after closing body tag
            $body = str_replace ("</body>", $jsCode . "\n</body>", $body);
        }

        JResponse::setBody($body);

        return true;
    }

    private function _initFolders() {

        // paths
        $this->_pathPlugin = JPATH_PLUGINS . DIRECTORY_SEPARATOR . "content" . DIRECTORY_SEPARATOR . self::PLUGIN_NAME;

        // urls
        $this->_urlPlugin = JURI::root() . "plugins/" . self::PLUGIN_TYPE . "/" . self::PLUGIN_NAME;
        $this->_urlPluginJs = $this->_urlPlugin . "/js";
        $this->_urlPluginCss = $this->_urlPlugin . "/css";
    }

	/**
	 * Generate the js code to insert class to html tag
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 24/04/2012
	 *
	 */
	private function _genJsCode() {

        $jsCode = null;

	    if (!empty($this->_currentTplName)) {

	        // generate the javascript
	        $jsLines = array();
	        $jsLines[] = "<script type='text/javascript'>";
	        $jsLines[] = "\twindow.addEvent('domready', function() {";
	        $jsLines[] = "\t\tvar htmlTag = document.id(document.documentElement);";
	        $jsLines[] = "\t\thtmlTag.addClass('".$this->_currentTplName."');";
	        $jsLines[] = "\t});";
	        $jsLines[] = "</script>";

	        $jsCode = implode("\n", $jsLines);
	    }
	    return $jsCode;
	}


	/**
	 * Get the name of the default themplate
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 18/04/2012
	 *
	 * @param 0/1 $client_id - 0 frontend default | 1 backend default
	 */
	private function _getDefaultTplName($client_id = 0) {
	    $result = null;
	    $db = JFactory::getDBO();
	    $query =  " SELECT template FROM #__template_styles "
	    ." WHERE client_id=".(int)$client_id." "
	    ." AND home = 1 ";
	    $db->setQuery($query);
	    try {
	        $result = $db->loadResult();
	    } catch (JDatabaseException $e) {
	        return $e;
	    }

	    return $result;
	}

	/**
	 * Get the name of the current template
	 * Works also in onAfterRender method @ plugins
	 * @author Roberto Segura - Digital Disseny, S.L.
	 * @version 23/04/2012
	 *
	 * @return string the name of the active template
	 */
	private function _getCurrentTplName() {

	    // required objects
	    $app =& JFactory::getApplication();
	    $jinput = $app->input;
	    $db = JFactory::getDBO();

	    // default values
	    $menuParams = new JRegistry();
	    $client_id = $app->isSite() ? 0 : 1;
	    $itemId = $jinput->get('Itemid',0);
	    $tplName = null;

	    // try to load custom template if assigned
	    if ($itemId) {
	        $sql = " SELECT ts.template " .
	                " FROM #__menu as m " .
	                " INNER JOIN #__template_styles as ts" .
	                " ON ts.id = m.template_style_id " .
	                " WHERE m.id=".(int)$itemId." " .
	                "";
	        $db->setQuery($sql);
	        $tplName = $db->loadResult();
	    }
	    // if no itemId or no custom template assigned load default template
	    if( !$itemId || empty($tplName)) {
	        $tplName = $this->_getDefaultTplName($client_id);
	    }

	    return $tplName;
	}

}
?>