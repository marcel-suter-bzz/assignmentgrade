<?php

/**
 * creates an array of the id's for all required custom fields
 */

function custom_field_ids() {
    global $DB;
    $output = [];
    $custom_field = $DB->get_record(
        'customfield_field',
        array('shortname' => get_config('local_gradeassignments', 'external_assignmentname')),
        'id,shortname,categoryid');
    $output['classroom_assignment'] = $custom_field->id;

    $custom_field = $DB->get_record(
        'user_info_field',
        array('shortname' => get_config('local_gradeassignments', 'external_username')),
        'id,shortname');
    $output['github_username'] = $custom_field->id;
    return $output;
}