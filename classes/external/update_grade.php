<?php


namespace local_gradeassignments\external;
global $CFG;
require_once("$CFG->dirroot/lib/externallib.php");
require_once("$CFG->dirroot/mod/assign/externallib.php");
require_once("customfields.php");

use external_function_parameters;
use external_single_structure;
use external_value;
use mod_assign_external;

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
                    self::update_mod_grade(
                        $assignment->assignmentid,
                        $user_id,
                        $params['points'],
                        $feedback
                    );
                }
            }
            if (empty($assignments)) {
                echo 'WARNING: no assignment ' . $assignment_name . ' found';
            }
        } else {
            echo 'WARNING: no username ' . $params['user_name'] . ' found';
        }


        return array();
    }

    private static function update_mod_grade(
        $assignment_id,
        $user_id,
        $points,
        $feedback
    ) {
        if ($feedback !== '[]') {
            $commenttext = self::feedback_table($feedback);
        } else {
            $commenttext = '';
        }
        $plugindata = array(
            'assignfeedbackcomments_editor' => array('text'=> $commenttext, 'format' =>'1')
        );
        $mod_assign_external = new mod_assign_external();
        $mod_assign_external->save_grade(
            $assignment_id,
            $user_id,
            $points,
            -1,
            0,
            'graded',
            '1',
            $plugindata
        );
    }


    /**
     * convert feedback from json to html table
     * @param $feedback json-string
     */
    static function feedback_table($feedback)
    {
        $messages = json_decode(stripslashes($feedback), true);
        if (count($messages) > 0) {
            $thead = '<table border="1px solid black"><thead><tr>';
            $first_row = true;
            $tbody = '<tbody>';

            foreach ($messages as $message) {
                $tbody .= '<tr>';
                foreach ($message as $key => $value) {
                    if ($first_row) {
                        $thead .= '<th>' . $key . '</th>';
                        $first_row = false;
                    }
                    $tbody .= "<td>" . $value . "</td>";
                }
                $tbody .= '</tr>';
            }
        }
        $thead .= '</thead><tbody>';
        $html = $thead . $tbody . '</tbody></table>';
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
     * @return int
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
        if (property_exists($user->userid)) return $user->userid;
        else return null;
    }
}
