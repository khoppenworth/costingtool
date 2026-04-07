<?php
return [
    'states' => ['draft', 'submitted', 'reviewed', 'returned', 'approved', 'locked'],
    'module_statuses' => ['Not Started', 'In Progress', 'Complete', 'Complete with Warnings', 'Validation Errors'],
    'transitions' => [
        'draft' => ['submitted'],
        'submitted' => ['reviewed', 'returned', 'approved'],
        'reviewed' => ['returned', 'approved'],
        'returned' => ['draft', 'submitted'],
        'approved' => ['locked', 'returned'],
        'locked' => [],
    ],
];
