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
 * Library class for the blog submission plugin.
 *
 * @package assignsubmission_blog
 * @copyright 2012 Department of Computer and System Sciences,
 *					Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/deprecatedlib.php');

defined('MOODLE_INTERNAL') || die();

define('ASSIGNSUBMISSION_BLOG_MAXENTRIES', 10);
define('ASSIGNSUBMISSION_BLOG_MAXCOMMENTS', 20);

require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');

class assign_submission_blog extends assign_submission_plugin {

    /**
     * Get the name of the submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('blog', 'assignsubmission_blog');
    }

    /**
     * Get the default settings for the blog submission plugin.
     *
     * @param MoodleQuickForm $mform The form to append the elements to.
     */
    public function get_settings(MoodleQuickForm $mform) {
        $defaultrequiredentries = $this->get_config('requiredentries');
        $defaultrequiredcomments = $this->get_config('requiredcomments');

        $entriesoptions = array();
        for ($i = 1; $i <= ASSIGNSUBMISSION_BLOG_MAXENTRIES; $i++) {
            $entriesoptions[$i] = $i;
        }

        $mform->addElement('select', 'assignsubmission_blog_requiredentries',
                get_string('requiredentries', 'assignsubmission_blog'), $entriesoptions);
        $mform->setDefault('assignsubmission_blog_requiredentries', $defaultrequiredentries);
        $mform->addHelpButton('assignsubmission_blog_requiredentries', 'requiredentries', 'assignsubmission_blog');
        $mform->disabledIf('assignsubmission_blog_requiredentries', 'assignsubmission_blog_enabled', 'notchecked');

        $commentsoptions = array();
        for ($i = 0; $i <= ASSIGNSUBMISSION_BLOG_MAXCOMMENTS; $i++) {
            $commentsoptions[$i] = $i;
        }

        $mform->addElement('select', 'assignsubmission_blog_requiredcomments',
                get_string('requiredcomments', 'assignsubmission_blog'), $commentsoptions);
        $mform->setDefault('assignsubmission_blog_requiredcomments', $defaultrequiredcomments);
        $mform->addHelpButton('assignsubmission_blog_requiredcomments', 'requiredcomments', 'assignsubmission_blog');
        $mform->disabledIf('assignsubmission_blog_requiredcomments', 'assignsubmission_blog_enabled', 'notchecked');
    }

    /**
     * Save the settings for the plugin
     * 
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('requiredentries', $data->assignsubmission_blog_requiredentries);
        $this->set_config('requiredcomments', $data->assignsubmission_blog_requiredcomments);
        return true;
    }

    /**
     * Displays the number of associated blog entries from a student in the submissions table
     * together with a link that will display the entries in question. 
     * If the student meets the required number of entries, the submission status will be given a green background color.
     *
     * @param stdClass $submission The submission to show a summary of
     * @param bool $showviewlink Will be set to true to enable the view link
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $DB;

        $showviewlink = true;

        list($entriescount, $commentscount) = $this->get_entries_and_comments($submission->userid, true);

        $studentmeetsrequirements = $entriescount >= $this->get_config('required_entries')
                && $commentscount >= $this->get_config('required_comments');

        if ($studentmeetsrequirements) {
            $divclass = 'submissionstatussubmitted';
        } else {
            $divclass = 'submissionstatus';
        }

        $result = html_writer::start_tag('div', array('class' => $divclass));
        $result .= get_string($entriescount == 1 ? 'numentry' : 'numentries', 'assignsubmission_blog', $entriescount);
        $result .= html_writer::empty_tag('br');
        $result .= get_string($commentscount == 1 ? 'numcomment' : 'numcomments', 'assignsubmission_blog', $commentscount);
        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Displays all submitted entries for this assignment from a specified student.
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $CFG, $DB, $OUTPUT, $PAGE;

        require_once('../../blog/locallib.php');
        require_once('../../comment/lib.php');

        // This line prepares the comment subsystem. For example it adds a couple of language strings to js.
        comment::init();

        list($entries, $comments) = $this->get_entries_and_comments($submission->userid);

        $output = $PAGE->get_renderer('blog');

        $result = '';
        $result .= $OUTPUT->heading(get_string('blogentries', 'blog'), 2);
        foreach ($entries as $entry) {
            $blogentry = new blog_entry($entry->id);
            $blogentry->prepare_render();
            $result .= $output->render($blogentry);
        }

        if (count($comments) > 0) {
            $result .= $OUTPUT->heading(get_string('comments'), 2);
            foreach ($comments as $entry) {
                $blogentry = new blog_entry($entry->id);
                $blogentry->prepare_render();
                $result .= $output->render($blogentry);
            }
        }
        return $result;
    }

    /**
     * Handles the action from the "add/edit submission" button. If a student have no submissions and blogsubmission is the only
     * active submission type then he/she will be directed to the new entry form. If other submission types are active then a
     * button "Add new entry" will be displayed alongside the forms for the other submission types.
     * If a student have previous submission then the students submissions will be displayed together with links to edit them.
     * 
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $CFG;

        $addnewentryurl = $CFG->wwwroot.'/blog/edit.php?action=add&modid='.$this->assignment->get_course_module()->id;

        if (isset($submission->status) && $submission->status <> 'new') {
            $mform->addElement('html', html_writer::tag('a', get_string('addnewentry', 'blog'), array(
                'href' => $addnewentryurl
            )));
            $mform->addElement('html', $this->view($submission));
        } else {
            $activesubmissionplugincount = 0;
            foreach ($this->assignment->get_submission_plugins() as $plugin) {
                if ($plugin->is_enabled()) {
                    if ($plugin->get_type() == 'comments') {
                        continue;
                    } 
                    $activesubmissionplugincount++;
                }
            }

            if ($activesubmissionplugincount == 1) {
                redirect($addnewentryurl);
            } else {
                $mform->addElement('html', html_writer::tag('a', get_string('addnewentry', 'blog'), array(
                    'href' => $addnewentryurl
                )));
            }
        }
        return true;
    }

    /**
     * Fetches or counts (depending on the value of the parameter $countentries) all entries and comments that a specified user 
     * have submitted to this assignment.
     * 
     * @param int $userid
     * @param bool $countentries If true, the method returns a count of the number of entries and comments by the user. If false
     *     the method returns the entries and comments. Default value is false.
     * @return void
     */
    private function get_entries_and_comments($userid, $countentries = false) {
        global $DB;
        if ($countentries) {
            $selectstatement = 'SELECT COUNT(p.id) ';
        } else {
            $selectstatement = 'SELECT p.id ';
        }

        $entriesquery = $selectstatement.'FROM {post} p JOIN {blog_association} ba ON ba.blogid = p.id WHERE p.userid = ? '.
                        'AND ba.contextid = ?';
        $commentsquery = $selectstatement."FROM {post} p JOIN {blog_association} ba ON ba.blogid = p.id ".
                         "WHERE p.id IN (SELECT itemid FROM {comments} c WHERE userid = ? AND c.itemid = p.id ".
                         "AND c.commentarea = 'format_blog') AND ba.contextid = ?";

        if (!empty($this->assignment->get_instance()->preventlatesubmissions)) {
            $daterestriction = ' AND p.created BETWEEN '.$this->assignment->get_instance()->allowsubmissionsfromdate.
                               ' AND '.$this->assignment->get_instance()->duedate;
            $entriesquery  .= $daterestriction;
            $commentsquery .= $daterestriction;
        }

        if ($countentries) {
            $entries = $DB->count_records_sql($entriesquery, array($userid, $this->assignment->get_context()->id));
            $comments = $DB->count_records_sql($commentsquery, array($userid, $this->assignment->get_context()->id));
        } else {
            $entries = $DB->get_records_sql($entriesquery, array($userid, $this->assignment->get_context()->id));
            $comments = $DB->get_records_sql($commentsquery, array($userid, $this->assignment->get_context()->id));
        }

        return array($entries, $comments);
    }

    /**
     * Produce a list of files suitable for export that represents this submission
     * 
     * @param stdClass $submission
     * @return array an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        global $DB, $CFG;
        require_once('../../blog/locallib.php');

        $files = array();
        list($entries, $comments) = $this->get_entries_and_comments($submission->userid);

        if ($entries) {
            $user = $DB->get_record('user', array(
                'id' => $submission->userid
            ), 'id, username, firstname, lastname', MUST_EXIST);

            $finaltext  = html_writer::start_tag('html');
            $finaltext .= html_writer::start_tag('head');
            $finaltext .= html_writer::start_tag('title');
            $finaltext .= 'Blog entries by '.fullname($user).' on '.$this->assignment->get_instance()->name;
            $finaltext .= html_writer::end_tag('title');
            $finaltext .= html_writer::empty_tag('meta', array(
                'http-equiv' => 'Content-Type',
                'content' => 'text/html; charset=utf-8'
            ));
            $finaltext .= html_writer::end_tag('head');
            $finaltext .= html_writer::start_tag('body');

            foreach ($entries as $entryid) {
                $entry = new blog_entry($entryid->id);
                ob_start();
                $entry->print_html();
                $finaltext .= ob_get_contents();
                ob_end_clean();
            }

            $finaltext .= html_writer::end_tag('body');
            $finaltext .= html_writer::end_tag('html');
            $files[get_string('blogfilename', 'assignsubmission_blog')] = array($finaltext);
        }

        return $files;
    }

    /**
     * Are there any associated entries for this submission?
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        list($entriescount, $commentedentriescount) = $this->get_entries_and_comments($submission->userid, true);
        return $entriescount + $commentedentriescount == 0;
    }

    /**
     * If blind marking is enabled then disable this plugin (it shows names)
     *
     * @return bool
     */
    public function is_enabled() {
        // Ensure that capabilities are added: associatemodule for this assignment, associatecourse for this course
        $roles = get_roles_used_in_context($this->assignment->get_course_context());
        foreach ($roles as $id => $role) {
            //assign_capability('moodle/blog:associatemodule', 1, $id, $this->assignment->get_context()->id);
            //assign_capability('moodle/blog:associatecourse', 1, $id, $this->assignment->get_course_context()->id);
        }

        if ($this->assignment->has_instance() && $this->assignment->is_blind_marking()) {
            return false;
        }
        return parent::is_enabled();
    }
}
