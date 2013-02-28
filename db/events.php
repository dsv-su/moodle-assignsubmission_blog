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
 * Defines events to catch for this module.
 *
 * @package assignsubmission_blog
 * @copyright 2012 Department of Computer and System Sciences,
 *					Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array(
    'blog_entry_added' => array(
        'handlerfile'     => '/mod/assign/submission/blog/lib.php',
        'handlerfunction' => 'entry_added_handler',
        'schedule'        => 'instant'
    ),

    'blog_entry_edited' => array(
        'handlerfile'     => '/mod/assign/submission/blog/lib.php',
        'handlerfunction' => 'entry_edited_handler',
        'schedule'        => 'instant'
    ),

    'blog_entry_deleted' => array(
        'handlerfile'     => '/mod/assign/submission/blog/lib.php',
        'handlerfunction' => 'entry_deleted_handler',
        'schedule'        => 'instant'
    )
);
