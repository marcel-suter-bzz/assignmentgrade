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
    public static function execute($assignment_name, $user_name, $points)
    {
        global $CFG, $DB;
        $custom_fields = custom_field_ids();

        require_once('$CFG->dirroot/grade/lib.php');

        $params = self::validate_parameters(
            self::execute_parameters(),
            array(
                'assignment_name' => $assignment_name,
                'user_name' => $user_name,
                'points' => $points
            )
        );

        $user_id = self::get_user_id($params['user_name'], $custom_fields);
        $assignments = self::get_assignments_by_name(
            $assignment_name,
            $custom_fields
        );

        $courses = self::get_enrolled_courses($user_id);


        foreach ($assignments as $assignment) {
            echo "\nassignment_id:" . $assignment->assignmentid;

            if (in_array($assignment->courseid, $courses)) {
                self::update_grade(
                    $assignment->courseid,
                    $assignment->assignmentid,
                    $user_id,
                    $params['points']
                );
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
        echo "\nupdate_grade";
        echo "\ncourse_id: $course_id";
        echo "\nassignment_id: $assignment_id";
        echo "\nuser_id: $user_id";
        echo "\npoints: $points";

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

            $DB->insert_record(
                'assign_grades',
                $grade,
                false,
                false
            );
        }
    }


    /**
     * get the moodle assignment by the external name
     * @param $assignment_name
     * @param $custom_fields
     * @return array
     * @throws \dml_exception
     */

    private static function get_assignments_by_name(
        $assignment_name,
        $custom_fields
    )
    {
        global $DB, $CFG;
        echo "\nfieldid: " . $custom_fields['classroom_assignment'];
        echo "\nassignmentname: $assignment_name";

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
        echo "\nassignments=";
        print_r($assignments);

        return $assignments;
    }

    /**
     * returns an array of all courses the user is enrolled in
     * @param $user_id
     * @return array
     * @throws \dml_exception
     */
    private static function get_enrolled_courses($user_id)
    {
        global $DB;

        echo "\nuserid: $user_id";
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
        echo "\ncourses=";
        print_r($courses);
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
    private static function get_user_id($user_name, $custom_fields)
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