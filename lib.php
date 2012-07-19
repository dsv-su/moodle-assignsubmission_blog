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

    $context = get_context_instance_by_id($entry->modassoc);
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
 * Checks if a user have submitted blog entries that is associated with an assignment, identified by it's contexid.
 *
 * @param int $userid
 * @param int $contextid
 * @return bool
 */
function user_have_associated_entries($userid, $contextid) {
    global $DB;

    $usersentries = $DB->count_records_sql('SELECT COUNT(p.id) FROM {post} p, {blog_association} ba '
            .'WHERE p.id = ba.blogid AND p.userid = ? AND ba.contextid = ?', array($userid, $contextid));
    return $usersentries > 0;
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

/**
 * This function removes a submission from the assign_submission table if the user have no associated entries to this assignment.
 *
 * @param int $userid
 * @param int $contextid
 * @param int $instanceid
 * @return void
 */
function remove_submission_if_no_associated_entries($userid, $contextid, $instanceid, $cm = null) {
    global $DB;

    if (!user_have_associated_entries($userid, $contextid)
            && user_have_registred_submission($userid, $instanceid)) {
        $DB->delete_records('assign_submission', array(
            'assignment' => $instanceid, 
            'userid' => $userid));
        if ($cm == null) {
            $context = get_context_instance_by_id($contextid);
            $cm = get_coursemodule_from_id('assign', $context->instanceid);
        }
        add_to_log($cm->course, 'assign', 'delete', 'view.php?id='.$cm->id,
                'Assignment blog submission: Submission removed', $cm->id, $userid);
    }
}

/**
 * Handles an edited entry in the blog.
 * If the modassoc value of the entry is 0, then the module association for this entry have been removed.
 * If the module association have been removed, check if there are any assignments where the user have an registred submission
 * but not associated blog entries. If so, remove the registred submission.
 *
 * @param mixed $entry
 * @return bool
 */
function entry_edited_handler($entry) {
    global $DB;

    if (isset($entry->modassoc) && $entry->modassoc === '0') {
        $usersubmissions = $DB->get_records('assign_submission', array(
            'userid' => $entry->userid
        ));
        foreach ($usersubmissions as $index => $submission) {
            if (blogsubmission_is_active($submission->assignment)) {
                // Get the contextid for the assignment that the submission is submitted to
                $contextid = $DB->get_field_sql("SELECT c.id FROM {context} c JOIN {course_modules} cm ON c.instanceid = cm.id"
                        ." WHERE c.contextlevel = ? AND cm.instance = ?", array(CONTEXT_MODULE, $submission->assignment));

                remove_submission_if_no_associated_entries($entry->userid, $contextid, $submission->assignment);
            }
        }
    }
    return true;
}

/**
 * Handles a removed entry in the blog.
 * If the user doesn't have any associated entries left, the assignment submission will be removed.
 *
 * @param mixed $entry
 * @return bool True to indicate that the event was handled successfully.
 */
function entry_deleted_handler($entry) {
    global $DB;

    if (($cm = entry_is_relevant($entry))) {
        remove_submission_if_no_associated_entries($entry->userid, $entry->modassoc, $cm->instance, $cm);
    }
    return true;
}
