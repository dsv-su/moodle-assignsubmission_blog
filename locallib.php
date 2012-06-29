<?php
	defined('MOODLE_INTERNAL') || die();
	
	define('ASSIGNSUBMISSION_BLOG_MAXENTRIES', 10);
	define('ASSIGNSUBMISSION_BLOG_MAXCOMMENTS', 20);

	require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');

class assign_submission_blog extends assign_submission_plugin {
	public function get_name() {
        return get_string('blog', 'assignsubmission_blog');
    }
    
    public function get_settings(MoodleQuickForm $mform) {
    	$default_required_entries = $this->get_config('required_entries');
    	$default_required_comments = $this->get_config('required_comments');
    	
    	
    	$entries_options = array();
    	for ($i = 1; $i <= ASSIGNSUBMISSION_BLOG_MAXENTRIES; $i++) {
    		$entries_options[$i] = $i;
    	}
    
    	$mform->addElement('select', 'assignsubmission_blog_required_entries', 
    			get_string('required_entries', 'assignsubmission_blog'), $entries_options);
    	$mform->setDefault('assignsubmission_blog_required_entries', $default_required_entries);
    	$mform->addHelpButton('assignsubmission_blog_required_entries', 'required_entries', 'assignsubmission_blog');
    	$mform->disabledIf('assignsubmission_blog_required_entries', 'assignsubmission_blog_enabled', 'eq', 0);
    	
    	$comments_options = array();
    	for ($i = 0; $i <= ASSIGNSUBMISSION_BLOG_MAXCOMMENTS; $i++) {
    		$comments_options[$i] = $i;
    	}
    	
    	$mform->addElement('select', 'assignsubmission_blog_required_comments', 
    			get_string('required_comments', 'assignsubmission_blog'), $comments_options);
    	$mform->setDefault('assignsubmission_blog_required_comments', $default_required_comments);
    	$mform->addHelpButton('assignsubmission_blog_required_comments', 'required_comments', 'assignsubmission_blog');
    	$mform->disabledIf('assignsubmission_blog_required_comments', 'assignsubmission_blog_enabled', 'eq', 0);
    }
    
	public function save_settings(stdClass $data) {
		$this->set_config('required_entries', $data->assignsubmission_blog_required_entries);
		$this->set_config('required_comments', $data->assignsubmission_blog_required_comments);
		return true;
	}
	
	public function view_summary(stdClass $submission, & $showviewlink) {
		global $DB;
		
		$showviewlink = true;
		
		$entries = $DB->count_records_sql('SELECT COUNT(ba.id) FROM {blog_association} ba JOIN {post} p ON ba.blogid = p.id'
				.' WHERE ba.contextid = ? AND p.userid = ?', 
				array($this->assignment->get_context()->id, $submission->userid));
				
		return get_string('num_entries', 'assignsubmission_blog', $entries);
	}
	
	public function view(stdClass $submission) {
		global $CFG;
		
		require_once('../../blog/locallib.php');
		$bloglisting = new blog_listing(array('user' => $submission->userid, 
				'module' => $this->assignment->get_course_module()->id));

		ob_start();
		foreach ($bloglisting->get_entries() as $entry) {
			$blogentry = new blog_entry(null, $entry);
			$blogentry->print_html();
		}
		
		return ob_get_clean();		
	}
}
