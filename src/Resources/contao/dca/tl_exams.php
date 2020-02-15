<?php
$GLOBALS['TL_DCA']['tl_exams'] = [
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
            'flag' => 12,
            'panelLayout' => 'search,filter;limit,sort'
        ],
        'label' => [
            'fields' => ['id', 'title', 'date', 'status'],
            'format' => '%s',
	        'showColumns' => true,
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_exams']['edit']
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_exams']['delete'],
		'attributes'  => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
		        'label' => &$GLOBALS['TL_LANG']['tl_exams']['show']
            ],
        ],
    ],

    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['title'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'mandatory' => true],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'date' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['date'],
            'search' => true,
            'inputType' => 'text',
	        'flag' => 5,
            'eval' => ['maxlength' => 10, 'mandatory' => true, 'datepicker'=>true, 'rgxp' => 'datim'],
            'sql' => ['type' => 'integer', 'length' => 10, 'default' => '0']
        ],
        'begin' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['time_begin'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 10, 'mandatory' => true],
            'sql' => ['type' => 'string', 'length' => 10, 'default' => '']
        ],
        'duration' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['exam_duration'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'maxlength' => 3, 'mandatory' => false],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
        ],
        'department' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['department'],
            'inputType' => 'select',
            'options' => ['department1', 'department2', 'department3', 'department4', 'department5', 'department6', 'department7', 'department8', 'department9', 'department10', 'department11', 'department12', 'department13', 'department14'],
	    'reference' => &$GLOBALS['TL_LANG']['tl_exams'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'string', 'length' => 80, 'default' => '']
        ],
        'tools' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['tools'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'mandatory' => false],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['status'],
            'inputType' => 'select',
            'options' => ['status1', 'status2', 'status3', 'status4', 'status5', 'status6'],
	        'reference'	=> &$GLOBALS['TL_LANG']['tl_exams'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'string', 'length' => 80, 'default' => '']
        ],
        'remarks' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['remarks'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 255],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'lecturer_title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['lecturer_title'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 255],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'lecturer_firstname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['lecturer_firstname'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'mandatory' => true],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'lecturer_lastname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['lecturer_lastname'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'mandatory' => true],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'lecturer_email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['lecturer_email'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'email', 'maxlength' => 255, 'mandatory' => false],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'lecturer_mobile' => [
            'label' => &$GLOBALS['TL_LANG']['tl_exams']['lecturer_mobile'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'phone', 'maxlength' => 15],
            'sql' => ['type' => 'string', 'length' => 15, 'default' => '']
        ]
    ],
    'palettes' => [
        'default' => '{exam_legend},title,department,date,begin,duration,status,remarks;{lecturer_legend},lecturer_title,lecturer_firstname,lecturer_lastname,lecturer_email,lecturer_mobile'
    ],
];
?>
