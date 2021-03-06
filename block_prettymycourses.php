<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course overview block
 *
 * @package    block_prettymycourses
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/blocks/prettymycourses/locallib.php');

/**
 * Course overview block
 *
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_prettymycourses extends block_base {
    /**
     * If this is passed as mynumber then showallcourses, irrespective of limit by user.
     */
    const SHOW_ALL_COURSES = -2;

    /**
     * Block initialization
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_prettymycourses');
    }

    /**
     * Return contents of prettymycourses block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        if($this->content !== NULL) {
            return $this->content;
        }

        $config = get_config('block_prettymycourses');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';
        $this->title = $config->showtitle;



        //get prereg courses and redo array indexes
        $hiddencourses = $config->hiddencourses;
        $preregistrations = \block_prettymycourses\preregmanager::fetch_preregistrations($hiddencourses);


        $preregcourses = array();
        if($preregistrations){
            foreach($preregistrations as $preregistration){
                $thecourse= get_course($preregistration->courseid);
                $preregcourses[] = $thecourse;
            }
        }

        //get enrolled courses
        //$courses = enrol_get_my_courses();
        $courses = enrol_get_users_courses($USER->id);
        $usecourses= array();

        //remove hidden courses
        if(!empty($hiddencourses)){
            $hiddencourseids = explode(',',$hiddencourses);
            foreach($courses as $course){
               if(!in_array($course->id,$hiddencourseids)){
                   $usecourses[] = $course;
               }
            }
        }else{
            $usecourses = $courses;
        }

        //to make processing trial/full logic a bit easier
        $shortnames = [];
        foreach($usecourses as $usecourse){
            $usecourse->fullcourse=false;
            $usecourse->alreadypurchased=false;
            $shortnames[]=$usecourse->shortname;
        }

        //add trial course / full course pair info
        if(!empty($config->trialcoursepairs) ){
            $trialfullpairs = explode(',',$config->trialcoursepairs);
            foreach($trialfullpairs as $pairstring){
                $pair=explode('|',$pairstring);
                if(count($pair)==2){
                    foreach($usecourses as $usecourse){

                        if($usecourse->shortname==$pair[0]){
                            //set the trial course's full course pair (shortname)
                            $usecourse->fullcourse=$pair[0];
                            //check if already enrolled in full course
                            if(in_array($pair[1],$shortnames)){
                                $usecourse->alreadypurchased=true;
                            }
                        }
                    }
                }
            }
        }


        $renderer = $this->page->get_renderer('block_prettymycourses');
        if (!empty($config->showwelcomearea)) {
            require_once($CFG->dirroot.'/message/lib.php');
            $msgcount = message_count_unread_messages();
            $this->content->text = $renderer->welcome_area($msgcount);
        }


        if (empty($courses)) {
            $this->content->text .= get_string('nocourses','my');
        } else {
            // For each course, build category cache.
            $this->content->text .= $renderer->prettymycourses($usecourses, $preregistrations,$preregcourses, $config->showcoursenames);
        }

        return $this->content;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my' => true);
    }

    /**
     * Sets block header to be hidden or visible
     *
     * @return bool if true then header will be visible.
     */
    public function hide_header() {
        // Hide header if welcome area is show.
        $config = get_config('block_prettymycourses');
        return !empty($config->showwelcomearea);
    }
}
