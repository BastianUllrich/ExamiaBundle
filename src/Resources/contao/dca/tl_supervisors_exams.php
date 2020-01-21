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
            'fields' => ['exam_id'],
            'flag' => 1,
            'panelLayout' => 'search,filter;limit,sort'
        ],
        'label' => [
            'fields' => ['supervisor_id:tl_member.lastname', 'exam_id:tl_exams.title', 'task'],
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
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
        ],
        'supervisor_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['supervisor_id'],
            'inputType' => 'select',
	        //Optionen aus Funktion getAttendee() holen
            'options_callback' => ['tl_supervisors_exams', 'getSupervisor'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'integer', 'default' => 0]
        ],
        'exam_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_supervisors_exams']['exam_id'],
            'inputType' => 'select',
	        //Optionen aus Funktion getExam() holen
            'options_callback' => ['tl_supervisors_exams', 'getExam'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'integer', 'default' => 0]
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
        'default' => 'supervisor_id,exam_id,status,time_from,time_until,task'
    ],
];

class tl_supervisors_exams extends Backend
{

    // Alle Infos für Select-Box "Aufsicht" sammeln
    public function getSupervisor()
    {
        $array = array();
        $this->import('Database');
        $result = Database::getInstance()->prepare("SELECT id, firstname, lastname FROM tl_member WHERE usertype='Aufsicht'")->query();
        while ($result->next()) {
            $nameset = $result->firstname;
            $nameset .= ' ';
            $nameset .= $result->lastname;
            $array[$result->id] = $nameset;
        }
        return $array;
    }

    // Alle Infos für Select-Box "Klausur" sammeln
    public function getExam()
    {
        $array = array();
        $this->import('Database');
        $result = Database::getInstance()->prepare("SELECT id, title, date, department, begin FROM tl_exams")->query();
        while ($result->next()) {
            $nameset = $result->title;
            $nameset .= ' (';
	    //Verkürzte Schreibweise für die Fachbereiche
	    $nameset .= str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_supervisors_exams'][$result->department],0,5)));
	    $nameset .= ', ';
            $nameset .= date("d.m.Y", $result->date);
            $nameset .= ' um ';
            $nameset .= date("H:i", $result->begin);
            $nameset .= ' Uhr';
            $nameset .= ')';
            $array[$result->id] = $nameset;
        }
        return $array;
    }
}
?>

