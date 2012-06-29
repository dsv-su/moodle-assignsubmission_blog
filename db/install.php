<?php

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
