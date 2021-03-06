<?php
/**
 * 
 * @copyright 2017 Bedford College
 * @package Bedford College Dashboard (BCDB)
 * @version 2.0
 * @author Conn Warwicker <cwarwicker@bedford.ac.uk> <conn@cmrwarwicker.com>
 * 
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 */

namespace BCDB;

abstract class View {
    
    protected $Controller;
    protected $vars = array();
    protected $forcePage = false;
    
    protected $breadcrumbs = array();
    protected $context;
    
    public function __construct() {
        
        global $CFG, $bcdb, $OUTPUT;
                
        $this->set("CFG", $CFG);
        
        $string = get_string_manager()->load_component_strings('block_bc_dashboard', $CFG->lang, true);
        $this->set("string", $string);
        
        // Some default values we will want, such as site name
        $siteName = get_site()->fullname;
        $this->set("siteName", $siteName);
        $this->set("pageTitle", $string['pluginname']);
        
        // All permissions are checked against the system context, not a course
        $this->context = $bcdb['context'];
        $this->set("context", $this->context);
        
        // BCDB config array
        $this->set("bcdb", $bcdb);
        
        // Some globals
        global $OUTPUT;
        $this->set("OUTPUT", $OUTPUT);
        
    }
    
    public function setController($obj){
        $this->Controller = $obj;
        return $this;
    }
    
    public function getController(){
        return $this->Controller;
    }
    
    public function getAction(){
        if ($this->Controller){
            return $this->Controller->getAction();
        }
    }
    
    public function getParams(){
        if ($this->Controller){
            return $this->Controller->getParams();
        }
    }
    
    public function get($name){
        return (array_key_exists($name, $this->vars)) ? $this->vars[$name] : null;
    }
    
    public function set($name, $value){
                
        $this->vars[$name] = $value;
        return $this;
        
    }
    
    public function force($page){
        $this->forcePage = $page;
        return $this;
    }
    
    public function addBreadcrumb(array $crumb){
        $this->breadcrumbs[] = $crumb;
        return $this;
    }
    
    public function getBreadcrumbs(){
        return $this->breadcrumbs;
    }
    
    public function render(){
        
        global $CFG, $USER;
        
        // Define constant so we can load the template files - to stop them being loaded directly
        if (!defined('BCDB')){
            define('BCDB', true);
        }
                                
        $action = $this->getAction();
        $params = $this->getParams();
                        
        // If action call that here as well
        if ($action && method_exists($this, $action)){
            call_user_func( array($this, $action), $params );
        }
        
        // Extract variables from array to be used in html/css/etc...
        if (!empty($this->vars)){
            extract($this->vars);
        }
        
        // Include the header template
        include_once $CFG->dirroot . '/blocks/bc_dashboard/tpl/header.html';
        
        // Content
        $files = array();
        
        if ($this->forcePage){
            $files[] = $this->forcePage;
        } else {
        
            // Strip the "action_" from the action name for the template file
            $action = str_replace("action_", "", $action);

             // First see if there is a file for this action & param within this plugin
            if ($action && $params){
                $files[] = $this->Controller->getPath() . 'tpl/' . $action . '/' . (implode("/", $params)) . '.html';
            }

            if ($action && $params){
                $files[] = $this->Controller->getPath() . 'tpl/' . $action . '/' . $params[0] . '.html';
            }

            // Next look for a template for this action
            if ($action)
            {
                $files[] = $this->Controller->getPath() . 'tpl/' . $action . '.html';
            }

            // Next just look for a main file
            $defaultMethod = (isset($this->defaultMethod)) ? $this->defaultMethod : 'main';
            $files[] = $this->Controller->getPath() . 'tpl/' . $defaultMethod . '.html';

        }
        
        // Lastly if we find none of those, give them a 404 error
        $files[] = $CFG->dirroot . '/blocks/bc_dashboard/tpl/404.html';
                
        foreach($files as $file){
            if (file_exists($file)){
                include_once $file;
                break;
            }
        }
        
        // Footer
        include_once $CFG->dirroot . '/blocks/bc_dashboard/tpl/footer.html';

        
        // Flush content
        \bcdb_stop();
        
    }
    
    /**
     * Only render the forced page, not the header, footer, nor call any actions
     */
    public function renderForcedPageOnly(){
                
        // Define constant so we can load the template files - to stop them being loaded directly
        if (!defined('BCDB')){
            define('BCDB', true);
        }
        
        // Extract variables from array to be used in html/css/etc...
        if (!empty($this->vars)){
            extract($this->vars);
        }
       
        // Include forced page
        if ($this->forcePage){
            include_once $this->forcePage;
        }
        
        // Flush content
        \bcdb_stop();        
        
    }
    
        
}
