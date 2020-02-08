<?php
$GLOBALS['TL_DCA']['tl_supervisors_exams'] = [
    'config' => [
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['date'],
            'flag' => 1,
            'panelLayout' => 'search,filter;limit,sort'
        ],
        'label' => [
            'fields' => ['id', 'supervisor_id', 'date', 'task'],
            'format' => '%s',
            'showColumns' => true,
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['edit']
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['delete'],
		'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['show']
            ],
        ],
    ],

    'fields' => [
        'id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['id'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
        ],
        'supervisor_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['supervisor_id'],
            'inputType' => 'select',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'integer', 'default' => 0]
        ],
        'date' => [
            'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['date'],
            'inputType' => 'date',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'integer', 'length' => 10, 'default' => 0]
        ],
        'time_from' => [
            'label' => $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_from'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'time', 'maxlength' => 10, 'mandatory' => true],
            'sql' => ['type' => 'string', 'length' => 10, 'default' => '']
        ],
        'time_until' => [
            'label' => $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_until'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'time', 'maxlength' => 10, 'mandatory' => true],
            'sql' => ['type' => 'string', 'length' => 10, 'default' => '']
        ],
        'task' => [
            'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['task'],
            'inputType' => 'select',
            'options' => ['Aufsicht', 'Schreibassistenz'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '']
        ],
    ],
    'palettes' => [
        'default' => 'supervisor_id,date,time_from,time_until,task'
    ],
];
?>

