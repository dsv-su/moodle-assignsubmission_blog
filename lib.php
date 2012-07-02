<?php
/**
 * This file contains the event hooks for the submission blog plugin.
 *
 * @package assignsubmission_blog
 * @copyright 2012 Department of Computer and System Sciences, 
 *					Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Handles a new entry in the blog. 
 * Will determine if the entry is associated with a blog assignment and,
 * if so, add a new submission. 
 *
 * @param mixed $entry 
 * @return bool
 */
function entry_added_handler($entry) {
	global $CFG, $USER, $DB;
	if (isset($entry->modassoc)) {
		$context_data = $DB->get_record('context', 
				array('id' => $entry->modassoc));
		
		$cm = get_coursemodule_from_id('assign', $context_data->instanceid);
		if ($cm->modname == 'assign') {
			$course = $DB->get_record('course', array('id' => $cm->course));

			// Replace this variable with a function call?
			$context = get_context_instance_by_id($entry->modassoc);
			$blogsubmission_active = $DB->get_record('assign_plugin_config', 
					array('assignment' => $cm->instance, 
					'plugin' => 'blog', 
					'subtype' => 'assignsubmission',
					'name' => 'enabled'));
			
			//This is a workaround for MDL-27629
			if ($blogsubmission_active && has_capability('mod/assign:submit', $context)) {
				require_sesskey();
				
				// Since assign::get_user_submission is private, we need to replicate it's
				// functionallity
				
				$existing_submission = $DB->get_record('assign_submission', 
						array('assignment' => $cm->instance, 'userid' => $USER->id));
				if ($existing_submission) {
					$existing_submission->timemodified = time();
					$DB->update_record('assign_submission', $existing_submission);
				} else {
					$new_submission = new stdClass();
					$new_submission->assignment = $cm->instance;
					$new_submission->userid = $USER->id;
					$new_submission->timecreated = time();
					$new_submission->timemodified = $new_submission->timecreated;
					$new_submission->status = 'submitted';
					$DB->insert_record('assign_submission', $new_submission);
				}	
				// Here be logging!
			}
		}
	}
	return true;
}

/**
 * Handles an edited entry in the blog. 
 * Currenty not implemented. 
 *
 * @param mixed $entry 
 * @return bool
 */
function entry_edited_handler($entry) {
	return true;
}

/**
 * Handles a removed entry in the blog.
 * Currently not implemented. 
 *
 * @param mixed $entry
 * @return bool
 */
function entry_deleted_handler($entry) {
	return true;
}
