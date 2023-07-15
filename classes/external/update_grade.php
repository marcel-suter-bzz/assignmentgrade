<?php


namespace local_gradeassignments\external;
global $CFG;
require_once("$CFG->dirroot/lib/externallib.php");
require_once("customfields.php");

use external_function_parameters;
use external_single_structure;
use external_value;

class update_grade extends \external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters()
    {
        return new external_function_parameters(
            array(
                'assignment_name' => new external_value(
                    PARAM_TEXT,
                    'name of the assignment'
                ),
                'user_name' => new external_value(
                    PARAM_TEXT,
                    'username of the github user'
                ),
                'points' => new external_value(
                    PARAM_FLOAT,
                    'the points for grading'
                ),
                'feedback' => new external_value(
                    PARAM_TEXT,
                    'the feedback for this grade',
                    0,
                    '[]'
                )
            )
        );
    }

    /**
     * creates the return structure
     * @return external_single_structure
     */
    public static function execute_returns()
    {
        return new external_single_structure(array());
    }

    /**
     * Update grades from an external system
     * @param $assignment_name  the name of the external assignment
     * @param $user_name  the external username
     * @param $points the number of points
     * @return array
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute($assignment_name, $user_name, $points, $feedback)
    {
        $params = self::validate_parameters(
            self::execute_parameters(),
            array(
                'assignment_name' => $assignment_name,
                'user_name' => $user_name,
                'points' => $points
            )
        );
        $custom_fields = custom_field_ids();
        $user_id = self::get_user_id($params['user_name'], $custom_fields);
        if (!empty($user_id)) {
            $courses = self::get_enrolled_courses($user_id);

            $assignments = self::get_assignments_by_name(
                $assignment_name,
                $custom_fields
            );

            foreach ($assignments as $assignment) {

                if (in_array($assignment->courseid, $courses)) {
                    $gradeid = self::update_grade(
                        $assignment->courseid,
                        $assignment->assignmentid,
                        $user_id,
                        $params['points']
                    );

                    if ($feedback !== '[]') {
                        self::update_feedback(
                            $assignment->assignmentid,
                            $gradeid,
                            $feedback
                        );
                    }
                }
            }
        }


        return array();
    }

    /**
     * update the user's grade for the assignment
     * @param $course_id
     * @param $assignment_id
     * @param $user_id
     * @param $points
     * @return void
     * @throws \dml_exception
     */
    private static function update_grade(
        $course_id,
        $assignment_id,
        $user_id,
        $points
    )
    {
        global $DB;

        $gradeid = $DB->get_field(
            'assign_grades',
            'id',
            array(
                'assignment' => $assignment_id,
                'userid' => $user_id
            )
        );
        $grade = new \stdClass;
        $grade->assignment = $assignment_id;
        $grade->userid = $user_id;
        $grade->grade = $points;
        $grade->grader = 2;  // FIXME
        $grade->timecreated = time();
        $grade->timemodified = time();

        if ($gradeid) {
            $grade->id = $gradeid;
            $DB->update_record(
                'assign_grades',
                $grade,
                false
            );
        } else {
            $gradeid = $DB->insert_record(
                'assign_grades',
                $grade,
                true,
                false
            );
        }
        return $gradeid;
    }

    private static function update_feedback(
        $assignment_id,
        $grade_id,
        $feedback
    )
    {
        global $DB;
        $feedbackid = $DB->get_field(
            'assignfeedback_comments',
            'id',
            array(
                'assignment' => $assignment_id,
                'grade' => $grade_id
            )
        );
        $text = new \stdClass;
        $text->assignment = $assignment_id;
        $text->grade = $grade_id;
        $text->commenttext = 'foobar';
        $text->commentformat = 1;
        if ($feedbackid) {
            $text->id = $feedbackid;
            $DB->update_record(
                'assignfeedback_comments',
                $text,
                false
            );
        } else {
            $DB->insert_record(
                'assignfeedback_comments',
                $text,
                false,
                false
            );
        }
    }

    /**
     * convert feedback from json to html table
     */
    static function feedback_table($feedback)
    {
        $fields = ['message', 'expected', 'actual'];
        $html = '';
        $messages = json_decode($feedback);
        if (count($messages) > 0) {
            $html = '<table><thead><tr><th>Testfall</th><th>Meldung</th><th>Erwartetes Resultat</th><th>Tatsächliches Resultat</th></tr></thead><tbody>';
            foreach ($messages as $message) {
                $html .= '<tr>';
                foreach ($fields as $key) {
                    $html .= "<td>$message[$key]</td>";
                }
                $html .= '</tr>';
}
        }
        $html .= '</tbody></table>';
        echo "table: $html";
        return $html;
    }

    /**
     * get the moodle assignment by the external name
     * @param $assignment_name
     * @param $custom_fields
     * @return array
     * @throws \dml_exception
     */

    private
    static function get_assignments_by_name(
        $assignment_name,
        $custom_fields
    )
    {
        global $DB, $CFG;

        $query = 'SELECT a.id AS assignmentid, a.name, a.course AS courseid' .
            '  FROM mdl_customfield_data AS cfd' .
            '  JOIN mdl_course_modules AS cm ON (cm.id=cfd.instanceid)' .
            '  JOIN mdl_assign AS a ON (cm.instance=a.id)' .
            ' WHERE cfd.fieldid=:fieldid' .
            '  AND cfd.value=:assignmentname';


        $assignments = $DB->get_records_sql(
            $query,
            [
                'fieldid' => $custom_fields['classroom_assignment'],
                'assignmentname' => $assignment_name
            ]
        );
        print_r($assignments);

        return $assignments;
    }

    /**
     * returns an array of all courses the user is enrolled in
     * @param $user_id
     * @return array
     * @throws \dml_exception
     */
    private
    static function get_enrolled_courses($user_id)
    {
        global $DB;

        $query = 'SELECT e.id, e.courseid' .
            '  FROM mdl_user_enrolments AS ue' .
            '  JOIN mdl_enrol AS e ON (ue.enrolid=e.id)' .
            ' WHERE ue.userid=:userid';
        $courses = $DB->get_records_sql(
            $query,
            [
                'userid' => $user_id
            ]
        );

        $course_ids = array();
        foreach ($courses as $course) {
            $course_ids[] = $course->courseid;
        }
        return $course_ids;
    }

    /**
     * returns the moodle userid by the external username
     * @param $user_name
     * @param $custom_fields
     * @return void
     * @throws \dml_exception
     */
    private
    static function get_user_id($user_name, $custom_fields)
    {
        global $DB;
        $query = 'SELECT userid' .
            '  FROM {user_info_data}' .
            ' WHERE fieldid=:fieldid' .
            '   AND data=:ghusername';
        $user = $DB->get_record_sql(
            $query,
            [
                'fieldid' => $custom_fields['github_username'],
                'ghusername' => $user_name
            ]
        );
        return $user->userid;
    }
}