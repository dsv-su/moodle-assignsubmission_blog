<?php
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

    // do the install

    require_once($CFG->dirroot . '/mod/assign/adminlib.php');
    // set the correct initial order for the plugins
    $pluginmanager = new assign_plugin_manager('assignsubmission');

    $pluginmanager->move_plugin('blog', 'down');
    $pluginmanager->move_plugin('blog', 'down');

    // do the upgrades
    return true;


}
