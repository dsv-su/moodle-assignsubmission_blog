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
 * Installs the module on a Moodle instance.
 *
 * @package assignsubmission_blog
 * @copyright 2012 Department of Computer and System Sciences,
 *					Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_assignsubmission_blog_install() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/assign/adminlib.php');
    // Set the correct initial order for the plugins.
    $pluginmanager = new assign_plugin_manager('assignsubmission');

    $pluginmanager->move_plugin('blog', 'down');
    $pluginmanager->move_plugin('blog', 'down');

    return true;
}
