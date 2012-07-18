<?php
/**
 * Library class for the blog submission plugin.
 *
 * @package assignsubmission_blog
 * @copyright 2012 Department of Computer and System Sciences,
 *					Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        $mform->disabledIf('assignsubmission_blog_requiredentries', 'assignsubmission_blog_enabled', 'eq', 0);

        $commentsoptions = array();
        for ($i = 0; $i <= ASSIGNSUBMISSION_BLOG_MAXCOMMENTS; $i++) {
            $commentsoptions[$i] = $i;
        }

        $mform->addElement('select', 'assignsubmission_blog_requiredcomments',
                get_string('requiredcomments', 'assignsubmission_blog'), $commentsoptions);
        $mform->setDefault('assignsubmission_blog_requiredcomments', $defaultrequiredcomments);
        $mform->addHelpButton('assignsubmission_blog_requiredcomments', 'requiredcomments', 'assignsubmission_blog');
        $mform->disabledIf('assignsubmission_blog_requiredcomments', 'assignsubmission_blog_enabled', 'eq', 0);
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
        $entries = $DB->count_records_sql('SELECT COUNT(ba.id) FROM {blog_association} ba JOIN {post} p ON ba.blogid = p.id'
                .' WHERE ba.contextid = ? AND p.userid = ?', array(
                    $this->assignment->get_context()->id,
                    $submission->userid
                ));

        $comments = $DB->count_records_sql('SELECT COUNT(ba.blogid) FROM {blog_association} ba '
                . 'WHERE ba.blogid IN (SELECT itemid FROM {comments} WHERE userid = ?) AND contextid = ?', array(
                    $submission->userid,
                    $this->assignment->get_context()->id
                ));

        $studentmeetsrequirements = $entries >= $this->get_config('required_entries') 
                && $comments >= $this->get_config('required_comments');

        if ($studentmeetsrequirements) {
            $divclass = 'submissionstatussubmitted';
        } else {
            $divclass = 'submissionstatus';
        }

        $result = html_writer::start_tag('div', array('class' => $divclass));
        $result .= get_string($entries == 1 ? 'numentry' : 'numentries', 'assignsubmission_blog', $entries);
        $result .= html_writer::start_tag('br');
        $result .= get_string($comments == 1 ? 'numcomment' : 'numcomments', 'assignsubmission_blog', $comments);
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
        global $CFG, $DB, $OUTPUT;

        require_once('../../blog/locallib.php');
        require_once('../../comment/lib.php');

        // This line prepares the comment subsystem. For example it adds a couple of language strings to js.
        comment::init();

        $bloglisting = new blog_listing(array(
            'user' => $submission->userid,
            'module' => $this->assignment->get_course_module()->id
        ));

        $comments = $DB->get_records_sql('SELECT ba.blogid FROM {blog_association} ba '
                .'WHERE ba.blogid IN (SELECT itemid FROM {comments} WHERE userid = ?) AND contextid = ?', array(
                    $submission->userid,
                    $this->assignment->get_context()->id
                ));

        ob_start();
        echo $OUTPUT->heading(get_string('blogentries', 'blog'), 2);
        foreach ($bloglisting->get_entries() as $entry) {
            $blogentry = new blog_entry(null, $entry);
            $blogentry->print_html();
        }

        if (count($comments) > 0) {
            echo $OUTPUT->heading(get_string('comments'), 2);
            foreach ($comments as $entry) {
                $blogentry = new blog_entry($entry->blogid);
                $blogentry->print_html();
            }
        }
        return ob_get_clean();
    }

    /**
     * Handles the action from the "add/edit submission" button. If a student have no submissions
     * then it will be directed to the new entry form. Else the method will display the students submissions
     * together with links to edit them.
     * 
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $CFG;

        $addnewentryurl = $CFG->wwwroot . '/blog/edit.php?action=add&modid='
                 . $this->assignment->get_course_module()->id;

        if ($submission) {
            $mform->addElement('html', html_writer::tag('a', get_string('addnewentry', 'blog'), array(
                'href' => $addnewentryurl
            )));
            $mform->addElement('html', $this->view($submission));
        } else {
            redirect($addnewentryurl);
        }
        return true;
    }
}
