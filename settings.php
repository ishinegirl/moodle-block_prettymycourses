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
 * prettymycourses block settings
 *
 * @package    block_prettymycourses
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_prettymycourses/defaultmaxcourses', new lang_string('defaultmaxcourses', 'block_prettymycourses'),
        new lang_string('defaultmaxcoursesdesc', 'block_prettymycourses'), 10, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('block_prettymycourses/forcedefaultmaxcourses', new lang_string('forcedefaultmaxcourses', 'block_prettymycourses'),
        new lang_string('forcedefaultmaxcoursesdesc', 'block_prettymycourses'), 1, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('block_prettymycourses/showchildren', new lang_string('showchildren', 'block_prettymycourses'),
        new lang_string('showchildrendesc', 'block_prettymycourses'), 1, PARAM_INT));
    $settings->add(new admin_setting_configcheckbox('block_prettymycourses/showwelcomearea', new lang_string('showwelcomearea', 'block_prettymycourses'),
        new lang_string('showwelcomeareadesc', 'block_prettymycourses'), 1, PARAM_INT));
    $showcategories = array(
        BLOCKS_COURSE_PRETTYMYCOURSES_SHOWCATEGORIES_NONE => new lang_string('none', 'block_prettymycourses'),
        BLOCKS_COURSE_PRETTYMYCOURSES_SHOWCATEGORIES_ONLY_PARENT_NAME => new lang_string('onlyparentname', 'block_prettymycourses'),
        BLOCKS_COURSE_PRETTYMYCOURSES_SHOWCATEGORIES_FULL_PATH => new lang_string('fullpath', 'block_prettymycourses')
    );
    $settings->add(new admin_setting_configselect('block_prettymycourses/showcategories', new lang_string('showcategories', 'block_prettymycourses'),
        new lang_string('showcategoriesdesc', 'block_prettymycourses'), BLOCKS_COURSE_PRETTYMYCOURSES_SHOWCATEGORIES_NONE, $showcategories));
    $settings->add(new admin_setting_configtext('block_prettymycourses/showtitle', new lang_string('showtitle', 'block_prettymycourses'),
        new lang_string('showtitledesc', 'block_prettymycourses'), 'Pretty MyCourses', PARAM_TEXT));
}
