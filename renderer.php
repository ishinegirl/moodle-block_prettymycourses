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
 * prettymycourses block rendrer
 *
 * @package    block_prettymycourses
 * @copyright  2017 Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Pretty MyCourses block renderer
 *
 * @copyright  2017 Justin Hunt <justin@@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_prettymycourses_renderer extends plugin_renderer_base {


    public function prettymycourses($courses,$preregistrations,$preregcourses, $showcoursenames)
    {


        $html = '';
        usort($courses,function($a,$b){
            if($a->idnumber == $b->idnumber){
                return 0;
            }elseif($a->idnumber < $b->idnumber) {
                return -1;
            }else{
                return 1;
            }
        });

        foreach ($courses as $course) {
            $html .= $this->render_one_course($course, $showcoursenames);
        }

        if ($preregistrations) {
            for ($i = 0; $i < count($preregistrations); $i++) {
                if (array_key_exists($i, $preregcourses)) {
                    $html .= $this->render_one_preregistration($preregistrations[$i], $preregcourses[$i], $showcoursenames);
                }
            }
        }

        // Wrap course list in a div and return.
        return html_writer::tag('div', $html, array('class' => 'course_list'));

    }

    protected function sortArrayByArra(array $array, array $orderArray) {
        $ordered = array();
        foreach ($orderArray as $key) {
            if (array_key_exists($key, $array)) {
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }
        return $ordered + $array;
    }



    /**
     * Cretes html for welcome area
     *
     * @param int $msgcount number of messages
     * @return string html string for welcome area.
     */
    public function welcome_area($msgcount) {
        global $CFG, $USER;
        $output = $this->output->box_start('welcome_area');

        $picture = $this->output->user_picture($USER, array('size' => 75, 'class' => 'welcome_userpicture'));
        $output .= html_writer::tag('div', $picture, array('class' => 'profilepicture'));

        $output .= $this->output->box_start('welcome_message');
        $output .= $this->output->heading(get_string('welcome', 'block_prettymycourses', $USER->firstname));

        if (!empty($CFG->messaging)) {
            $plural = 's';
            if ($msgcount > 0) {
                $output .= get_string('youhavemessages', 'block_prettymycourses', $msgcount);
                if ($msgcount == 1) {
                    $plural = '';
                }
            } else {
                $output .= get_string('youhavenomessages', 'block_prettymycourses');
            }
            $output .= html_writer::link(new moodle_url('/message/index.php'),
                    get_string('message'.$plural, 'block_prettymycourses'));
        }
        $output .= $this->output->box_end();
        $output .= $this->output->container('', 'flush');
        $output .= $this->output->box_end();

        return $output;
    }

    //TO DO
    //This needs a little work. We are doing a db call here, that really is not
    //supposed to be in the renderer. But whatever .. J 20170602
    protected function render_one_course($thecourse, $showcoursename)
    {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/course/renderer.php');
        require_once($CFG->libdir . '/coursecatlib.php');
        $chelper = new coursecat_helper();
        $course = new course_in_list($thecourse);

        //init content
        $content = '';

        //get course dates
        $startdate = '--/--/--';
        $enddate = '--/--/--';
        $SQL = "SELECT mue.timestart, mue.timeend FROM mdl_user_enrolments mue  
          INNER JOIN mdl_enrol me ON me.id = mue.enrolid
          WHERE me.courseid = ? AND mue.userid =?";//AND me.enrol='ishinemanual'";

        $results = $DB->get_records_sql($SQL, array($course->id, $USER->id));
        if ($results) {
            $result = array_shift($results);
            if ($result->timestart > 0) {
                $startdate = date('Y年m月d日', $result->timestart);
            }
            if ($result->timeend > 0) {
                $enddate = date('Y年m月d日', $result->timeend);
            }
        }

        //if we are preregistered and not starting yet
        if($result->timestart > time()){return;}

        //determine expiry
        $expired = ($result->timeend > 0) && ($result->timeend < time());

        //if we are showing coursenames, do that.
        if ($showcoursename) {
            $coursename = $chelper->get_course_formatted_name($course);
            if ($expired) {
                $coursenamelink = html_writer::tag('span', $coursename, array('class' => 'dimmed'));
            } else {
                $coursenamelink = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
                    $coursename, array('class' => $course->visible ? '' : 'dimmed'));
            }

            $content .= html_writer::tag('div', $coursenamelink, array('class' => 'coursename'));
        }

        //get course image
        $config = get_config('block_prettymycourses');


        //link course image and add to content
        if ($expired) {
            $url = $config->courseimagesbase . '/icon/png/gray/icottl_' . $course->shortname . '_g.png';
            $courseimage = html_writer::tag('div',
                html_writer::empty_tag('img', array('src' => $url, 'style' => 'max-height: 150px')),
                array('class' => 'courseimage'));

            $courseimagelink = html_writer::tag('span',
                $courseimage, array('class' => 'dimmed'));
        } else {
            $url = $config->courseimagesbase . '/icon/png/icottl_' . $course->shortname . '.png';
            $courseimage = html_writer::tag('div',
                html_writer::empty_tag('img', array('src' => $url, 'style' => 'max-height: 150px')),
                array('class' => 'courseimage'));

            $courseimagelink = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
                $courseimage, array('class' => $course->visible ? '' : 'dimmed'));
        }
        $content .= $courseimagelink;


        $startdate = get_string('startdate', 'block_prettymycourses', $startdate);;
        $enddate = get_string('enddate', 'block_prettymycourses', $enddate);
        $content .= html_writer::tag('div', $startdate, array('class' => 'block_prettymycourses_startdate'));
        $content .= html_writer::tag('div', $enddate, array('class' => 'block_prettymycourses_enddate'));


        //Add Buy the course button
        if ($course->fullcourse) {
            $buybuttonattr = array();
            $buybuttonattr['type'] = 'button';
            $buybuttonattr['src']='https://ispc.jp/tuition/before';
            $buybuttonattr['style'] = 'margin: auto; display: grid;';
            if ($course->alreadypurchased) {
                $buybuttonattr['disabled'] = 'disabled';
                $buybuttonattr['class'] = 'btn ishine-buybutton';
                $buybuttontext = get_string('boughtbuttontext', 'block_prettymycourses');
            }else{
                $buybuttonattr['class'] = 'btn btn-success　ishine-buybutton';
                $buybuttontext = get_string('buybuttontext', 'block_prettymycourses');
            }

            $buybutton = html_writer::tag('button', $buybuttontext,
                $buybuttonattr);
            $content .= $buybutton;

        //add progress bar
        }else{
            $modinfo = get_fast_modinfo($thecourse);
            $sect_compl_info = \availability_sectioncompleted\condition::get_section_completion_info(
                \availability_sectioncompleted\condition::ALL_SECTIONS,
                $course,
                $modinfo);
            $progresspercent = ($sect_compl_info->activitycount && $sect_compl_info->activitycompletedcount) ?
                floor(($sect_compl_info->activitycompletedcount / $sect_compl_info->activitycount) * 100) :
                0;
            $progressbar = html_writer::tag('div', $progresspercent . '%',
                array('class' => 'progress-bar', 'role' => 'progressbar', 'aria-valuemin' => "0",
                    'aria-valuemax' => "100", 'aria-valuenow' => "$progresspercent", 'style' => "width: $progresspercent%"));
            $progressbarlabel = html_writer::tag('div', get_string('progress', 'block_prettymycourses'), array('class' => 'progressbarlabel'));
            $progresscontainer = html_writer::tag('div', $progressbar, array('class' => 'progress'));
            $content .= html_writer::tag('div', $progressbarlabel . $progresscontainer, array('class' => 'block_prettymycourses_progressbar'));
        }


        return html_writer::tag('div', $content, array('class' => 'block_prettymycourses_course'));

    }


    protected function render_one_preregistration($prereg, $course, $showcoursename)
    {
        global $CFG;
        require_once($CFG->dirroot . '/course/renderer.php');
        require_once($CFG->libdir . '/coursecatlib.php');
        $chelper = new coursecat_helper();
        $course = new course_in_list($course);

        //init content
        $content = '';

        //if we are not beyond the course enrolstartdate return blank
        if(time()>$prereg->startdate){
            return $content;
        }

        //if we are showing coursenames, do that.
        if ($showcoursename) {
            $coursename = $chelper->get_course_formatted_name($course);
            $content .= html_writer::tag('div', $coursename, array('class' => 'coursename'));
        }


        //grey image
        $config = get_config('block_prettymycourses');
        $url = $config->courseimagesbase . '/icon/png/gray/icottl_' . $course->shortname . '_g.png';
        $content .= html_writer::tag('div',
            html_writer::empty_tag('img', array('src' => $url, 'style' => 'max-height: 150px')),
            array('class' => 'courseimage'));



        //add course dates

        $startdate=date('Y年m月d日',$prereg->startdate);
        $startdate = get_string('startdate','block_prettymycourses',$startdate);
        $enddate=date('Y年m月d日',$prereg->enddate);
        $enddate = get_string('enddate','block_prettymycourses',$enddate);
        $content .=  html_writer::tag('div', $startdate, array('class' => 'block_prettymycourses_startdate prereg'));
        $content .=  html_writer::tag('div', $enddate, array('class' => 'block_prettymycourses_enddate prereg'));

        return html_writer::tag('div', $content, array('class' => 'block_prettymycourses_course'));

    }

}
