<?php
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
