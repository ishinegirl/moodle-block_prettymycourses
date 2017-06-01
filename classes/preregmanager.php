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
 * Block search forums renderer.
 *
 * @package     block_prettymycourses
 * @copyright  2017 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_prettymycourses;
defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;

/**
 * Pre registrations manager
 *
 * @package    block_prettymycourses
 * @copyright  2017 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preregmanager {

    /**
     * fetch preregistrations
     *
     * @param String $user_email
     * @return string
     */
    public static function fetch_preregistrations($user_email) {
            global $DB;
            $preregistrations =  $DB->get_records('block_prettymycourses_prereg',array('email'=>$user_email));
            return $preregistrations;
    }

}
