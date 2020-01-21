<?php

//Palettes

$GLOBALS['TL_DCA']['tl_member']['palettes']['__selector__'][] = 'usertype';

$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] =
   '{groups_legend},groups;{login_legend},username,password;usertype;';

$GLOBALS['TL_DCA']['tl_member']['subpalettes']['usertype_Student'] =
  '{personal_legend},firstname,lastname,usertype;{personal_legend},handicaps,handicaps_others;{contact_legend},email,phone,mobile;{study_legend},department,study_course,chipcard_nr,contact_person;{exam_legend},extra_time,extra_time_minutes_percent,rehab_devices,rehab_devices_others,comments;';

$GLOBALS['TL_DCA']['tl_member']['subpalettes']['usertype_Aufsicht'] =
  '{personal_legend},firstname,lastname,usertype;{contact_legend},email,phone,mobile;';

$GLOBALS['TL_DCA']['tl_member']['subpalettes']['usertype_Administrator'] =
  '{personal_legend},firstname,lastname,usertype;{contact_legend},email;';



//Fields

$GLOBALS['TL_DCA']['tl_member']['fields']['usertype'] = array
		(
			'label'		=> $GLOBALS['TL_LANG']['tl_member']['usertype'],
			'inputType'	=> 'select',
			'options'	=> array('Student', 'Aufsicht', 'Administrator'),
			'eval'		=> array(
						'includeBlankOption'	=> true,
						'mandatory'		=> true,
                        'feEditable'		=> true,
                        'feViewable'		=> true,
			),
			'sql'		=> "varchar(20) NOT NULL default ''"
		);


$GLOBALS['TL_DCA']['tl_member']['fields']['department'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['department'],
                        'inputType'     => 'select',
                        'search'	=> true,
                        'options'       => array('department1', 'department2', 'department3', 'department4', 'department5', 'department6', 'department7', 'department8', 'department9', 'department10', 'department11', 'department12', 'department13', 'department14'),
                        'reference'	=> &$GLOBALS['TL_LANG']['tl_member'],
			'eval'          => array(
                                                'includeBlankOption'    => true,
						'feGroup'		=> 'study',
						'feEditable'		=> true,
						'feViewable'		=> true,
                                                'mandatory'             => true
                        ),
                        'sql'           => "varchar(80) NOT NULL default ''"
                );

$GLOBALS['TL_DCA']['tl_member']['fields']['study_course'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['study_course'],
             		'inputType'	=> 'text',
                        'eval'          => array(
                                                'feGroup'               => 'study',
                                                'feEditable'            => true,
                                                'feViewable'            => true,
                                                'mandatory'             => true,
                		       		'maxlength'		=> '30'
			),
                        'sql'           => "varchar(30) NOT NULL default ''"
                );


$GLOBALS['TL_DCA']['tl_member']['fields']['chipcard_nr'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['chipcard_nr'],
                        'inputType'     => 'text',
                        'eval'          => array(
                                                'feGroup'               => 'study',
                                                'feEditable'            => true,
                                                'feViewable'            => true,
                                                'mandatory'             => false,
                                                'maxlength'             => '20'
                        ),
                        'sql'           => "varchar(20) NOT NULL default ''"
                );



$GLOBALS['TL_DCA']['tl_member']['fields']['handicaps'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['handicaps'],
                        'inputType'     => 'checkbox',
			'options'	=> array(
						'blind',
						'visually impaired',
						'deaf',
						'motorically restricted',
						'autism',
						'mental disorder',
						'chronic disorder',
						'acute illness',
						'different'
					),
			'reference'	=> &$GLOBALS['TL_LANG']['tl_member'],
                        'eval'          => array(
                                                'feGroup'               => 'personal',
                                                'feEditable'            => true,
                                                'feViewable'            => true,
                                                'mandatory'             => false,
						'multiple'		=> true
                        ),
                        'sql'           => "blob NULL"
                );

$GLOBALS['TL_DCA']['tl_member']['fields']['handicaps_others'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['handicaps_others'],
                        'inputType'     => 'text',
                        'eval'          => array(
                                                'feGroup'               => 'personal',
                                                'feEditable'            => true,
                                                'feViewable'            => true,
                                                'mandatory'             => false,
                                                'maxlength'             => '64'
                        ),
                        'sql'           => "varchar(64) NOT NULL default ''"
                );

$GLOBALS['TL_DCA']['tl_member']['fields']['rehab_devices'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['rehab_devices'],
                        'inputType'     => 'checkbox',
                        'options'       => array(
                                                'pc',
                                                'blind workspace',
                                                'Zoomtext',
                                                'screen magnifier',
                                                'screen reader',
                                                'a3 print',
                                                'obscuration',
                                                'writing assistance',
                                                'high table',
						'near door',
						'different'
                                        ),
                        'reference'     => &$GLOBALS['TL_LANG']['tl_member'],
                        'eval'          => array(
                                                'feGroup'               => 'exam',
                                                'feEditable'            => true,
                                                'feViewable'            => true,
                                                'mandatory'             => false,
                                                'multiple'              => true
                        ),
                        'sql'           => "blob NULL"
                );

$GLOBALS['TL_DCA']['tl_member']['fields']['rehab_devices_others'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['rehab_devices_others'],
                        'inputType'     => 'text',
			'eval'		=> array(
						'feGroup'	=> 'exam',
						'feEditable'	=> true,
						'feViewable'	=> true,
						'mandatory'	=> false,
						'maxlength'	=> 30
			),
			'sql'		=> "varchar(30) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['extra_time'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['extra_time'],
                        'inputType'     => 'text',
                        'eval'          => array(
                                                'feGroup'       => 'exam',
                                                'feEditable'    => true,
                                                'feViewable'    => true,
                                                'mandatory'     => false,
                                                'maxlength'     => 8
                        ),
                        'sql'           => "varchar(8) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['extra_time_minutes_percent'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['extra_time_minutes_percent'],
                        'inputType'     => 'select',
			'sorting'	=> true,
			'flag'		=> 1,
			'options'	=> array(
						'minutes',
						'percent'
					),
			'reference'	=> &$GLOBALS['TL_LANG']['tl_member'],
                        'eval'          => array(
						'includeBlankOption'	=> true,
                                                'feGroup'       	=> 'exam',
                                                'feEditable'    	=> true,
                                                'feViewable'    	=> true,
                                                'mandatory'    		=> false
                        ),
                        'sql'           => "varchar(10) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['contact_person'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['contact_person'],
                        'inputType'     => 'select',
                        'sorting'       => true,
                        'flag'          => 1,
                        'options'       => array(
                                                'contact1',
                                                'contact2'
                                        ),
                        'reference'     => &$GLOBALS['TL_LANG']['tl_member'],
                        'eval'          => array(
                                                'includeBlankOption'    => true,
                                                'feGroup'               => 'study',
                                                'feEditable'            => true,
                                                'feViewable'            => true,
                                                'mandatory'             => true
                        ),
                        'sql'           => "varchar(10) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['comments'] = array
                (
                        'label'         => $GLOBALS['TL_LANG']['tl_member']['comments'],
                        'inputType'     => 'textarea',
                        'eval'          => array(
                                                'includeBlankOption'    => true,
                                                'feGroup'               => 'exam',
                                                'feEditable'            => true,
                                                'feViewable'            => true,
                                                'mandatory'             => false,
						'maxlength'		=> 80
                        ),
                        'sql'           => "varchar(80) NOT NULL default ''"
);

?>

