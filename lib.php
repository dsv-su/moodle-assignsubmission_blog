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

    // This is a workaround for MDL-27629.
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
    global $CFG, $DB, $OUTPUT, $USER;

    if (($cm = entry_is_relevant($entry))) {
        require_once($CFG->dirroot.'/mod/assign/locallib.php');
        $existingsubmission = user_have_registred_submission($entry->userid, $cm->instance);
        $assign = new assign(context::instance_by_id($entry->modassoc), $cm, null);
        if ($assign->submissions_open($entry->userid)) {
            // The following two if-statements are stolen from the assignment class.
            if ($assign->get_instance()->teamsubmission) {
                $submission = $assign->get_group_submission($USER->id, 0, true);
            } else {
                $submission = $assign->get_user_submission($USER->id, true);
            }
            if ($assign->get_instance()->submissiondrafts) {
                $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
            } else {
                $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
            }

            // Get the grade to check if it is locked.
            $grade = $assign->get_user_grade($USER->id, false);
            if ($grade && $grade->locked) {
                echo $OUTPUT->notification(get_string('submissionslocked', 'assign'));
                return true;
            }

            if ($existingsubmission) {
                $submission->timemodified = time();
                $DB->update_record('assign_submission', $submission);
                add_to_log($cm->course, 'assign', 'submit', 'view.php?id='.$cm->id,
                        'Assignment blog submission: Associated blog entry submitted to assignment', $cm->id, $entry->userid);
            } else {
                $DB->update_record('assign_submission', $submission);
                add_to_log($cm->course, 'assign', 'update', 'view.php?id='.$cm->id,
                        'Assignment blog submission: Submission updated', $cm->id, $entry->userid);
            }
        }
    }

    return true;
}
