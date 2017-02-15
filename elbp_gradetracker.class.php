<?php

/**
 * <title>
 * 
 * @copyright 2013 Bedford College
 * @package Bedford College Electronic Learning Blue Print (ELBP)
 * @version 1.0
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

namespace ELBP\Plugins;

require_once $CFG->dirroot . '/blocks/gradetracker/lib.php';


/**
 * 
 */
class elbp_gradetracker extends Plugin {
    
    /**
     * Construct the plugin object
     * @param bool $install If true, we want to send the default info to the parent constructor, to install the record into the DB
     */
    public function __construct($install = false) {
        
        if ($install){
            parent::__construct( array(
                "name" => strip_namespace(get_class($this)),
                "title" => "Grade Tracker",
                "path" => '/blocks/gradetracker/',
                "version" => \ELBP\ELBP::getBlockVersionStatic()
            ) );
        }
        else
        {
            parent::__construct( strip_namespace(get_class($this)) );
        }

    }
    
    public function getConfigPath()
    {
        $path = $this->getPath() . 'config_'.$this->getName().'.php';
        return $path;
    }
    
    
    public function install() {
        
        global $DB;
        
        $return = true;
        $this->id = $this->createPlugin();
        
        // Reporting data
        $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:quals", "getstringcomponent" => "block_gradetracker"));        
        $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:tg", "getstringcomponent" => "block_gradetracker"));        
        $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:aspg", "getstringcomponent" => "block_gradetracker"));        
        $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:avggcse", "getstringcomponent" => "block_gradetracker"));        
        
        return $return;
    }
    
    
    public function getSummaryBox(){
                
        $TPL = new \ELBP\Template();
        
        $user = new \GT\User($this->student->id);
        
        $quals = $user->getQualifications("STUDENT");
        
        usort($quals, function($A, $B){
            return ( \strnatcasecmp($A->getName(), $B->getName()) == 0 ) ? 0 : (  \strnatcasecmp($A->getName(), $B->getName()) > 0 ) ? -1 : 1;
        });
        
        $TPL->set("obj", $this);
        $TPL->set("quals", $quals);
        
        try {
            return $TPL->load($this->CFG->dirroot . $this->path . 'tpl/elbp_gradetracker/summary.html');
        }
        catch (\ELBP\ELBPException $e){
            return $e->getException();
        }
        
    }
    
    
    public function getDisplay($params = array()){
                
        $output = "";
                
        $TPL = new \ELBP\Template();
        
        $user = new \GT\User($this->student->id);
        
        $quals = $user->getQualifications("STUDENT");
        
        
        $TPL->set("obj", $this);
        $TPL->set("access", $this->access);      
        $TPL->set("params", $params);
        $TPL->set("quals", $quals);
        
        try {
            $output .= $TPL->load($this->CFG->dirroot . $this->path . 'tpl/elbp_gradetracker/expanded.html');
        } catch (\ELBP\ELBPException $e){
            $output .= $e->getException();
        }

        return $output;
        
    }
    
    
    public function upgrade() {
        
        global $DB;
        
        $return = true;
        $version = $this->version; # This is the current DB version we will be using to upgrade from     
        
        // [Upgrades here]
        if ($version < 2016093000)
        {
            
            $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:quals", "getstringcomponent" => "block_gradetracker"));        
            
            $this->version = 2016093000;
            $this->updatePlugin();
            \mtrace("## Inserted plugin_report_element data for plugin: {$this->title}");
            
        }
        
        if ($version < 2016101000)
        {
            
            $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:tg", "getstringcomponent" => "block_gradetracker"));        
            $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:aspg", "getstringcomponent" => "block_gradetracker"));        
            $DB->insert_record("lbp_plugin_report_elements", array("pluginid" => $this->id, "getstringname" => "reports:gradetracker:avggcse", "getstringcomponent" => "block_gradetracker"));        
        
            $this->version = 2016101000;
            $this->updatePlugin();
            \mtrace("## Inserted plugin_report_element data for plugin: {$this->title}");
            
        }
        
        return $return;
        
    }
    
    
    public function ajax($action, $params, $ELBP) {
        global $DB, $USER, $GT;
        
        $GT = new \GT\GradeTracker();
        
        switch($action)
        {
            
            case 'load_display_type':
                                
                // Correct params are set?
                if (!$params || !isset($params['studentID']) || !$this->loadStudent($params['studentID'])) return false;
                
                // We have the permission to do this?
                $access = $ELBP->getUserPermissions($params['studentID']);
                if (!$ELBP->anyPermissionsTrue($access)) return false;
                                
                $TPL = new \GT\Template();
                $TPL->set("obj", $this)
                    ->set("access", $access);
                                
                // Tracker
                if ($params['type'] == 'tracker'){
                    $this->loadTracker( $params['id'], $TPL );
                }
                
                try {
                    $TPL->load( $this->CFG->dirroot . $this->path . 'tpl/elbp_gradetracker/'.$params['type'].'.html' );
                    $TPL->display();
                } catch (\ELBP\ELBPException $e){
                    echo $e->getException();
                }
                exit;                
                
            break;
        }
    }
    
    private function loadTracker($qualID, $TPL){
        
        $UserQualification = new \GT\Qualification\UserQualification($qualID);
        if ($UserQualification->isValid())
        {
            
            $params = array(
                'student' => new \GT\User($this->student->id),
                'TPL' => $TPL,
                'courseID' => ($this->course) ? $this->course->id : 0,
                'access' => 'v'
            );
            
            $TPL->set("params", $params);
            $TPL->set("UserQualification", $UserQualification);
            
        }
        
    }
    
    public function loadJavascript($simple = false) {
        
        $this->js = array(
            '/blocks/gradetracker/elbp_gradetracker.js'
        );
        
        parent::loadJavascript($simple);
    }
    
    
    /**
     * Get all the student's target grades
     * @param type $simple
     * @param type $courseID
     * @return type
     */
    public function getUserTargetGrades($simple = false, $courseID = -1)
    {
        return $this->getUserGrades('target', $simple, $courseID);
    }
    
    /**
     * Get all the student's weighted target grades
     * @param type $simple
     * @param type $courseID
     * @return type
     */
    public function getUserWeightedTargetGrades($simple = false, $courseID = -1)
    {
        return $this->getUserGrades('weighted_target', $simple, $courseID);
    }
    
    /**
     * Get all the student's ____ grades and return as array
     * @param type $simple
     * @param type $courseID
     * @return boolean|string
     */
    public function getUserGrades($type, $simple = false, $courseID = -1){
                
        if (!$this->student) return false;
        
        $array = array();
        
        $Student = new \GT\User($this->student->id);
        $records = $Student->getAllUserGrades($type, array('courseID' => $courseID));
        
        if ($records)
        {
            
            foreach($records as $record)
            {
                
                $qual = (isset($record['record']->qualid)) 
                                ? new \GT\Qualification($record['record']->qualid)
                                : false;
                
                // Make sure the qualification is valid and the student is still on the qual
                if ($qual && $qual->isValid() && !$qual->isDeleted() && $Student->isOnQual($qual->getID(), "STUDENT"))
                {
                
                    $qualName = $qual->getDisplayName();
                    if ($simple){
                        $array[$qualName] = $record['grade']->getName();
                    } else {
                        $array[] = "<span title='".\gt_html($qualName)."' style='cursor:help;'>{$record['grade']->getName()}</span>";
                    }
                
                }
                
            }
            
        }
        
        // Return array
        if ($simple){
            return $array;
        } else {
            return ($array) ? implode(", ", $array) : get_string('na', 'block_gradetracker');
        }
        
    }
    
    
    
     /**
     * For the bc_dashboard reporting wizard - get all the data we can about Targets for these students,
     * then return the elements that we want.
     * @param type $students
     * @param type $elements
     * @param type $filters
     * @param $obj - If a qual level is being used in the report then this is an arry of quals
     * @param $obj2 - If a qual level is being used in the report then this ia an array of courses that was used to find the students
     */
    public function getAllReportingData($students, $elements, $filters = false, $obj = false, $obj2 = false)
    {
        
        global $DB;
                        
        if (!$students || !$elements) return false;
                
        $now = time();
        
        $qualIDsArray = array();
        $courseIDsArray = array();
        
        if (is_array($obj))
        {
            foreach($obj as $o)
            {
                $qualIDsArray[] = $o->get_id();
            }
        }
        elseif (is_object($obj))
        {
            $qualIDsArray[] = $obj->get_id();
        }
        
        if (!$qualIDsArray) $qualIDsArray = false;
            
        
        
        if (is_array($obj2)){
            foreach($obj2 as $o){
                $courseIDsArray[] = $o->id;
            }
        } elseif (is_object($obj2)){
            $courseIDsArray[] = $obj2->id;
        }
        
        if (!$courseIDsArray) $courseIDsArray = false;
        
                
        // Check which elements are ticked and add to array
        $elementNames = array();
        foreach($elements as $elementID){
            $elementNames[] = $this->getReportingElementName($elementID);
        }
        
        
        // Overall vars
        $totalStudents = count($students);
        $qualifications = '-';
        $targetGrades = '-';
        $aspGrades = '-';
        $avgGCSE = '-';
        
        
         // Loop students and find all their targets
        foreach($students as $student)
        {
            
            $this->loadStudent($student->id);
            $user = new \GT\User($student->id);
            $quals = $user->getQualifications("STUDENT");
            
            // Qual names
            if ($totalStudents == 1 && in_array('reports:gradetracker:quals', $elementNames))
            {
                // Quals
                $q = array();
                $tg = array();
                $ag = array();
                if ($quals)
                {
                    foreach($quals as $qual)
                    {
                        
                        // Name of qual
                        $q[] = $qual->getShortDisplayName();
                        
                        // Target grade
                        $tg[] = $qual->getShortDisplayName() . ': (' . $user->getUserGrade('target', array('qualID' => $qual->getID())) . ')';
                        
                        // Asp grade
                        $ag[] = $qual->getShortDisplayName() . ': (' . $user->getUserGrade('aspirational', array('qualID' => $qual->getID())) . ')';
                        
                    }
                }

                $qualifications = implode("\n\n", $q);
                $targetGrades = implode("\n\n ", $tg);
                $aspGrades = implode("\n\n", $ag);
                                                
                // Avg GCSE
                $avgGCSE = $user->getAverageGCSEScore();
                
            }

            
        }

        $data = array();
        $data['reports:gradetracker:quals'] = $qualifications;
        $data['reports:gradetracker:tg'] = $targetGrades;
        $data['reports:gradetracker:aspg'] = $aspGrades;
        $data['reports:gradetracker:avggcse'] = $avgGCSE;
        
        $names = array();
        $els = array();
        
        foreach($elements as $element)
        {
            $record = $DB->get_record("lbp_plugin_report_elements", array("id" => $element));
            $names[] = $record->getstringname;
            $els[$record->getstringname] = $record->getstringcomponent;
        }
        
        $return = array();
        foreach($names as $name)
        {
            if (isset($data[$name])){
                $newname = \get_string($name, $els[$name]);
                $return["{$newname}"] = $data[$name];
            }
        }
        
        return $return;
        
        
    }
    
    public function getSummaryElements(){
        return array(
            array('name' => 'mintargetgrade', 'component' => 'block_gradetracker'),
            array('name' => 'aspirationalgrade', 'component' => 'block_gradetracker'),
        );
    }
    
     /**
     * Get the little bit of info we want to display in the Student Profile summary section
     * @return mixed
     */
    public function getSummaryInfo(){
                
        if (!$this->student) return false;
        
        $return = array();
        
        if ($this->isSummaryElementEnabled('mintargetgrade', 'block_gradetracker')){
            $return[] = array(
                'name' => get_string('mintargetgrade', 'block_gradetracker'),
                'value' => $this->getUserGrades("target")
            );
        }
        
        if ($this->isSummaryElementEnabled('aspirationalgrade', 'block_gradetracker')){
            $return[] = array(
                'name' => get_string('aspirationalgrade', 'block_gradetracker'),
                'value' => $this->getUserGrades("aspirational")
            );
        }
        
        return $return;
        
    }
    
    public function getSimpleQualsTargets(){
        
        if (!$this->student) return false;

        $Student = new \GT\User($this->student->id);
                
        $quals = $Student->getQualifications("STUDENT");
                
        return array(
            'block' => 'gradetracker',
            'student' => $Student,
            'quals' => $quals
        );
        
    }
    
}