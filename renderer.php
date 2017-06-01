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
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Course_overview block rendrer
 *
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_prettymycourses_renderer extends plugin_renderer_base {


    public function prettymycourses($courses, $overviews) {
        $html='';
        foreach ($courses as $key => $course) {
            $html .= $this->render_one_course($course);
        }
        // Wrap course list in a div and return.
        return html_writer::tag('div', $html, array('class' => 'course_list'));

    }

    /**
     * Coustuct activities overview for a course
     *
     * @param int $cid course id
     * @param array $overview overview of activities in course
     * @return string html of activities overview
     */
    protected function activity_display($cid, $overview) {
        $output = html_writer::start_tag('div', array('class' => 'activity_info'));
        foreach (array_keys($overview) as $module) {
            $output .= html_writer::start_tag('div', array('class' => 'activity_overview'));
            $url = new moodle_url("/mod/$module/index.php", array('id' => $cid));
            $modulename = get_string('modulename', $module);
            $icontext = html_writer::link($url, $this->output->pix_icon('icon', $modulename, 'mod_'.$module, array('class'=>'iconlarge')));
            if (get_string_manager()->string_exists("activityoverview", $module)) {
                $icontext .= get_string("activityoverview", $module);
            } else {
                $icontext .= get_string("activityoverview", 'block_prettymycourses', $modulename);
            }

            // Add collapsible region with overview text in it.
            $output .= $this->collapsible_region($overview[$module], '', 'region_'.$cid.'_'.$module, $icontext, '', true);

            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Constructs header in editing mode
     *
     * @param int $max maximum number of courses
     * @return string html of header bar.
     */
    public function editing_bar_head($max = 0) {
        $output = $this->output->box_start('notice');

        $options = array('0' => get_string('alwaysshowall', 'block_prettymycourses'));
        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = $i;
        }
        $url = new moodle_url('/my/index.php');
        $select = new single_select($url, 'mynumber', $options, block_prettymycourses_get_max_user_courses(), array());
        $select->set_label(get_string('numtodisplay', 'block_prettymycourses'));
        $output .= $this->output->render($select);

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Show hidden courses count
     *
     * @param int $total count of hidden courses
     * @return string html
     */
    public function hidden_courses($total) {
        if ($total <= 0) {
            return;
        }
        $output = $this->output->box_start('notice');
        $plural = $total > 1 ? 'plural' : '';
        $config = get_config('block_prettymycourses');
        // Show view all course link to user if forcedefaultmaxcourses is not empty.
        if (!empty($config->forcedefaultmaxcourses)) {
            $output .= get_string('hiddencoursecount'.$plural, 'block_prettymycourses', $total);
        } else {
            $a = new stdClass();
            $a->coursecount = $total;
            $a->showalllink = html_writer::link(new moodle_url('/my/index.php', array('mynumber' => block_prettymycourses::SHOW_ALL_COURSES)),
                    get_string('showallcourses'));
            $output .= get_string('hiddencoursecountwithshowall'.$plural, 'block_prettymycourses', $a);
        }

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Creates collapsable region
     *
     * @param string $contents existing contents
     * @param string $classes class names added to the div that is output.
     * @param string $id id added to the div that is output. Must not be blank.
     * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
     * @param string $userpref the name of the user preference that stores the user's preferred default state.
     *      (May be blank if you do not wish the state to be persisted.
     * @param bool $default Initial collapsed state to use if the user_preference it not set.
     * @return bool if true, return the HTML as a string, rather than printing it.
     */
    protected function collapsible_region($contents, $classes, $id, $caption, $userpref = '', $default = false) {
            $output  = $this->collapsible_region_start($classes, $id, $caption, $userpref, $default);
            $output .= $contents;
            $output .= $this->collapsible_region_end();

            return $output;
        }

    /**
     * Print (or return) the start of a collapsible region, that has a caption that can
     * be clicked to expand or collapse the region. If JavaScript is off, then the region
     * will always be expanded.
     *
     * @param string $classes class names added to the div that is output.
     * @param string $id id added to the div that is output. Must not be blank.
     * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
     * @param string $userpref the name of the user preference that stores the user's preferred default state.
     *      (May be blank if you do not wish the state to be persisted.
     * @param bool $default Initial collapsed state to use if the user_preference it not set.
     * @return bool if true, return the HTML as a string, rather than printing it.
     */
    protected function collapsible_region_start($classes, $id, $caption, $userpref = '', $default = false) {
        // Work out the initial state.
        if (!empty($userpref) and is_string($userpref)) {
            user_preference_allow_ajax_update($userpref, PARAM_BOOL);
            $collapsed = get_user_preferences($userpref, $default);
        } else {
            $collapsed = $default;
            $userpref = false;
        }

        if ($collapsed) {
            $classes .= ' collapsed';
        }

        $output = '';
        $output .= '<div id="' . $id . '" class="collapsibleregion ' . $classes . '">';
        $output .= '<div id="' . $id . '_sizer">';
        $output .= '<div id="' . $id . '_caption" class="collapsibleregioncaption">';
        $output .= $caption . ' ';
        $output .= '</div><div id="' . $id . '_inner" class="collapsibleregioninner">';
        $this->page->requires->js_init_call('M.block_prettymycourses.collapsible', array($id, $userpref, get_string('clicktohideshow')));

        return $output;
    }

    /**
     * Close a region started with print_collapsible_region_start.
     *
     * @return string return the HTML as a string, rather than printing it.
     */
    protected function collapsible_region_end() {
        $output = '</div></div></div>';
        return $output;
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

    protected function render_one_course($course)
    {
        global $CFG;
        require_once($CFG->dirroot . '/course/renderer.php');
        require_once($CFG->libdir. '/coursecatlib.php');
        $chelper = new coursecat_helper();
        $course = new course_in_list($course);

        $content = '';
        $coursename = $chelper->get_course_formatted_name($course);
        $coursenamelink = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
            $coursename, array('class' => $course->visible ? '' : 'dimmed'));
        $content .= html_writer::tag('div', $coursenamelink, array('class' => 'coursename'));

        /*
        if ($course->has_summary()) {
            $content .= html_writer::start_tag('div', array('class' => 'summary'));
            $content .= $chelper->get_course_formatted_summary($course,
                array('overflowdiv' => true, 'noclean' => true, 'para' => false));
            $content .= html_writer::end_tag('div');
        }
        */

        // Display course overview files.
        $contentimages = $contentfiles = '';
        foreach ($course->get_course_overviewfiles() as $file) {
            $isimage = $file->is_valid_image();
            $url = file_encode_url("{$CFG->wwwroot}/pluginfile.php",
                '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                $file->get_filearea() . $file->get_filepath() . $file->get_filename(), !$isimage);
            if ($isimage) {
                $contentimages .= html_writer::tag('div',
                    html_writer::empty_tag('img', array('src' => $url, 'style' => 'max-height: 150px')),
                    array('class' => 'courseimage'));

                //we only want one image
                break;
            } else {
                $image = $this->output->pix_icon(file_file_icon($file, 24), $file->get_filename(), 'moodle');
                $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')) .
                    html_writer::tag('span', $file->get_filename(), array('class' => 'fp-filename'));
                $contentfiles .= html_writer::tag('span',
                    html_writer::link($url, $filename),
                    array('class' => 'coursefile fp-filename-icon'));
                //we only want one image
                break;
            }
        }
        if (!empty($contentimages)) {
            $courseimagelink = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
                $contentimages, array('class' => $course->visible ? '' : 'dimmed'));
            $content .= $courseimagelink;
        }elseif(!empty($contentfiles)) {
            $courseimagelink = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)),
                $contentfiles, array('class' => $course->visible ? '' : 'dimmed'));
            $content .= $courseimagelink;
        }
        //add course dates
        $startdate = get_string('startdate','block_prettymycourses','04/11/2017');;
        $enddate = get_string('enddate','block_prettymycourses','04/11/2017');
        $content .=  html_writer::tag('div', $startdate, array('class' => 'block_prettymycourses_startdate'));
        $content .=  html_writer::tag('div', $enddate, array('class' => 'block_prettymycourses_enddate'));

        //add progress bar
        $modinfo = get_fast_modinfo($course);
        $sect_compl_info = \availability_sectioncompleted\condition::get_section_completion_info(
                            \availability_sectioncompleted\condition::ALL_SECTIONS,
                            $course,
                            $modinfo) ;
        $progresspercent= ($sect_compl_info->activitycount && $sect_compl_info->activitycompletedcount)?
                floor(($sect_compl_info->activitycompletedcount  /  $sect_compl_info->activitycount)*100) :
                0;
        $progressbar=html_writer::tag('div', $progresspercent .'%',
                        array('class' => 'progress-bar','role'=>'progressbar','aria-valuemin'=>"0",
                        'aria-valuemax'=>"100",'aria-valuenow'=>"$progresspercent",'style'=>"width: $progresspercent%"));
        $progressbarlabel =  html_writer::tag('div', get_string('progress','block_prettymycourses'), array('class' => 'progressbarlabel'));
        $progresscontainer =  html_writer::tag('div',$progressbarlabel . $progressbar, array('class' => 'progress'));

    $content .=  html_writer::tag('div', $progresscontainer, array('class' => 'block_prettymycourses_progressbar'));

        return html_writer::tag('div', $content, array('class' => 'block_prettymycourses_course'));
        return $content;
    }
}
