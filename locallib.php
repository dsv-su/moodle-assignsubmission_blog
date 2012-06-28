<?php
	defined('MOODLE_INTERNAL') || die();

	require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');

class assign_submission_blog extends assign_submission_plugin {
	public function get_name() {
        return get_string('blog', 'assignsubmission_blog');
    }
}
