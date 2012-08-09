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
 * Determines if this entry is relevant for the blog submission type.
 *
 * @param mixed $entry
 * @return mixed if relevant: the associated coursemodule, else false.
 */
function entry_is_relevant($entry) {
    global $DB;

    if (!isset($entry->modassoc)) {
        return false;
    }

    $context = context::instance_by_id($entry->modassoc);
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    $cm = get_coursemodule_from_id('assign', $context->instanceid);
    if (!$cm) {
        return false;
    }

    if (!blogsubmission_is_active($cm->instance)) {
        return false;
    }

    if (!has_capability('mod/assign:submit', $context)) {
        return false;
    }

    require_sesskey();
    return $cm;
}

/**
 * This function determines if an assignment, specified by its id, is using the blogsubmission plugin.
 *
 * @param int $assign_instance
 * @return bool
 */
function blogsubmission_is_active($assigninstance) {
    global $DB;

    $blogsubmissionactive = $DB->get_record('assign_plugin_config', array(
            'assignment' => $assigninstance,
            'plugin' => 'blog',
            'subtype' => 'assignsubmission',
            'name' => 'enabled'
    ));

    //This is a workaround for MDL-27629
    return $blogsubmissionactive->value == "1";
}

/**
 * Check if a user have a registered submission to an assignment.
 *
 * @param mixed $userid
 * @param mixed $assignment_instance
 * @return mixed False if no submission, else the submission record.
 */
function user_have_registred_submission($userid, $assignment_instance) {
     global $DB;

     $submission = $DB->get_record('assign_submission', array(
        'assignment' => $assignment_instance,
        'userid' => $userid
    ));

    return $submission;
}

/**
 * Handles a new entry in the blog.
 * Will determine if the entry is associated with a blog assignment and,
 * if so, add a new submission.
 *
 * @param mixed $entry
 * @return bool True to indicate that the event was handled successfully.
 */
function entry_added_handler($entry) {
    global $CFG, $USER, $DB;

    if (($cm = entry_is_relevant($entry))) {
        $assignmentinstance = $DB->get_record('assign', array(
            'id' => $cm->instance
        ));

        $withinassignmentlimits = time() >= $assignmentinstance->allowsubmissionsfromdate && time() <= $assignmentinstance->duedate;

        if (!$assignmentinstance->preventlatesubmissions || $withinassignmentlimits) {
            // Since assign::get_user_submission is private, we need to replicate it's functionality
            if (($existingsubmission = user_have_registred_submission($entry->userid, $cm->instance))) {
                $existingsubmission->timemodified = time();
                $DB->update_record('assign_submission', $existingsubmission);
                add_to_log($cm->course, 'assign', 'update', 'view.php?id='.$cm->id,
                        'Assignment blog submission: Submission updated', $cm->id, $entry->userid);
            } else {
                $newsubmission = new stdClass();
                $newsubmission->assignment = $cm->instance;
                $newsubmission->userid = $entry->userid;
                $newsubmission->timecreated = time();
                $newsubmission->timemodified = $newsubmission->timecreated;
                $newsubmission->status = 'submitted';
                $DB->insert_record('assign_submission', $newsubmission);
                add_to_log($cm->course, 'assign', 'submit', 'view.php?id='.$cm->id, 
                        'Assignment blog submission: Associated blog entry submitted to assignment', $cm->id, $entry->userid);
            }
        }
    }

    return true;
}
