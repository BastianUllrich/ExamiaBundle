<?php
$GLOBALS['TL_DCA']['tl_attendees_exams'] = [
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
            'fields' => ['attendee_id', 'exam_id'],
	        'format' => '%s',
            'showColumns' => true
	    ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['edit']
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['delete'],
		'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
		'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['show']
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
        'attendee_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['attendee_id'],
            'inputType' => 'select',
	        //Optionen aus Funktion getAttendee() holen
            'options_callback' => ['tl_attendees_exams', 'getAttendee'],
            'foreign_key' => 'tl_member.id',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'integer', 'default' => 0]
        ],
        'exam_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['exam_id'],
            'inputType' => 'select',
	        //Optionen aus Funktion getExam() holen
            'options_callback' => ['tl_attendees_exams', 'getExam'],
            'foreign_key' => 'tl_exams.id',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'integer', 'default' => 0]
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['status'],
            'inputType' => 'select',
            'options' => ['in_progress', 'confirmed'],
            'reference' => &$GLOBALS['TL_LANG']['tl_attendees_exams'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true],
            'sql' => ['type' => 'string', 'length' => '16', 'default' => 0]
        ],
        'rehab_devices' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['rehab_devices'],
            'inputType' => 'checkbox',
            'options' => ['pc', 'blind workspace', 'Zoomtext', 'screen magnifier', 'screen reader', 'a3 print', 'obscuration', 'writing assistance', 'high table', 'near door', 'own room', 'different'],
            'reference' => &$GLOBALS['TL_LANG']['tl_attendees_exams'],
            'eval' => ['mandatory' => false, 'multiple' => true],
            'sql' => ['type' => 'blob', 'notnull' => false, 'default' => '']
        ],
        'rehab_devices_others' => [
            'label' => $GLOBALS['TL_LANG']['tl_attendees_exams']['rehab_devices_others'],
            'inputType' => 'text',
            'eval' => ['mandatory' => false, 'maxlength' => 30],
            'sql' => ['type' => 'string', 'length' => '30', 'default' => '']
        ],
        'assistant_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['assistant_id'],
            'inputType' => 'select',
            //Optionen aus Funktion getAssistant() holen
            'options_callback' => ['tl_attendees_exams', 'getAssistant'],
            'foreign_key' => 'tl_member.id(usertype=Administrator)',
            'eval' => ['mandatory' => false, 'includeBlankOption' => true],
            'sql' => ['type' => 'integer', 'default' => 0]
        ],
        'extra_time' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['extra_time'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'maxlength' => 3, 'mandatory' => false],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
        ],
        'extra_time_minutes_percent' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['extra_time_minutes_percent'],
            'inputType' => 'select',
            'options' => ['minutes', 'percent'],
            'reference' => &$GLOBALS['TL_LANG']['tl_attendees_exams'],
            'eval' => ['mandatory' => false, 'includeBlankOption' => true],
            'sql' => ['type' => 'string', 'length' => '8', 'default' => '']
        ],
        'seat' => [
            'label' => &$GLOBALS['TL_LANG']['tl_attendees_exams']['seat'],
            'inputType' => 'select',
            'options' => ['seat1', 'seat2', 'seat3', 'seat4', 'seat5', 'seat6', 'seat7', 'seat8', 'seat9', 'seat10', 'seat11'],
            'reference' => &$GLOBALS['TL_LANG']['tl_attendees_exams'],
            'eval' => ['mandatory' => false, 'includeBlankOption' => true],
            'sql' => ['type' => 'string', 'length' => '8', 'default' => '']
        ]
    ],
    'palettes' => [
        'default' => 'attendee_id,exam_id,status,rehab_devices,rehab_devices_others,assistant_id,extra_time,extra_time_minutes_percent,seat'
    ],
];

class tl_attendees_exams extends Backend
{

	/**
	* Labels aus DB holen
     * @param array         $row
     * @param string        $label
     * @param DataContainer $dc
     * @param array         $args
	*/
/* Funktion deaktiviert
    public function getLabels($row, $label, DataContainer $dc, $args)
    {
        $this->import('Database');
        $result = Database::getInstance()->prepare("SELECT tl_member.firstname, tl_member.lastname, tl_exams.title FROM tl_member, tl_exams, tl_attendees_exams WHERE tl_member.id=tl_attendees_exams.attendee_id AND tl_exams.id=tl_attendees_exams.exam_id")->query();
        while ($result->next()) {
            //$args[$i] = $row['firstname'] . '$i' . $result->title;
            	$args[0] = sprintf('Ananas', 'Ist ok', $dc);
		return $args;
        }
    }
*/
    // Alle Infos für Select-Box "Teilnehmer" sammeln
    public function getAttendee()
    {
        $array = array();
        $this->import('Database');
        $result = Database::getInstance()->prepare("SELECT id, firstname, lastname FROM tl_member WHERE usertype='Student'")->query();
        while ($result->next()) {
            $nameset = $result->lastname;
            $nameset .= ', ';
            $nameset .= $result->firstname;
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
	    $nameset .= str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_attendees_exams'][$result->department],0,5)));
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

    // Alle Infos für Select-Box "Schreibassistenz" sammeln
    public function getAssistant()
    {
        $array = array();
        $this->import('Database');
        $result = Database::getInstance()->prepare("SELECT id, firstname, lastname FROM tl_member WHERE usertype='Aufsicht'")->query();
        while ($result->next()) {
            $nameset = $result->lastname;
            $nameset .= ', ';
            $nameset .= $result->firstname;
            $nameset .= ' (ID ';
            $nameset .= $result->id;
            $nameset .= ')';
            $array[$result->id] = $nameset;
        }
        return $array;
    }

}
?>

