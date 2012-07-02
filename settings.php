<?php
/**
 * This file defines the admin settings for the blog submission plugin.
 *
 * @package assignsubmission_blog
 * @copyright 2012 Department of Computer and System Sciences, 
 *					Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox('assignsubmission_blog/default',
                   new lang_string('default', 'assignsubmission_blog'),
                   new lang_string('default_help', 'assignsubmission_blog'), 0));

