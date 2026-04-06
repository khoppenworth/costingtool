<?php
return [
    'states' => ['draft', 'submitted', 'reviewed', 'returned', 'approved', 'locked'],
    'transitions' => [
        'draft' => ['submitted'],
        'submitted' => ['reviewed', 'returned', 'approved'],
        'reviewed' => ['returned', 'approved'],
        'returned' => ['draft', 'submitted'],
        'approved' => ['locked', 'returned'],
        'locked' => [],
    ],
];
