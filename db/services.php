<?php
$functions = [
    // The name of your web service function, as discussed above.
    'local_gradeassignments_update_grade' => [
        // The name of the namespaced class that the function is located in.
        'classname'   => 'local_gradeassignments\external\update_grade',

        // A brief, human-readable, description of the web service function.
        'description' => 'Updates the grade of an assignment from an external source',

        // Options include read, and write.
        'type'        => 'write',

        // Whether the service is available for use in AJAX calls from the web.
        'ajax'        => true,

        // An optional list of services where the function will be included.
        'services' => []
    ],
];