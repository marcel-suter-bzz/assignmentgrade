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
 * Plugin settings for the local_gradeassignments plugin.
 *
 * @package   local_gradeassignments
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Ensure the configurations for this site are set
if ($hassiteconfig) {

    $settings = new admin_settingpage('local_gradeassignments', 'Grade Assignments');
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_gradeassignments/external_username',
        'External Username',
        'The user profile field containing the external username',
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'local_gradeassignments/external_assignmentname',
        'External assignment name',
        'The activity field containing the name of the external assignment',
        '',
        PARAM_TEXT
    ));
}