<?php

namespace Baul\ExamiaBundle\Module;
use Baul\ExamiaBundle\Model\ExamsModel;
use Baul\ExamiaBundle\Model\MemberModel;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;


class ExamRegistrationModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_examRegistrationForm';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['examRegistration'][0]) . ' ###';
            $template->title = $this->headline;
            $template->id = $this->id;
            $template->link = $this->name;
            $template->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $template->parse();
        }

        return parent::generate();
    }

    /**
     * Generates the module.
     */
    protected function compile()
    {
        // Sprachdateien einbinden
        $this->loadLanguageFile('tl_member');
        $this->loadLanguageFile('tl_exams');


        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;

        // Daten des Mitglieds aus der Datenbank laden
        $userdata = MemberModel::findBy('id', $userID);

        // Variablen für das Template setzen
        $this->Template->title_label = $GLOBALS['TL_LANG']['tl_exams']['title'];
        $this->Template->lecturer_legend = $GLOBALS['TL_LANG']['tl_exams']['lecturer_legend'];
        $this->Template->lecturer_title_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_title'];
        $this->Template->lecturer_firstname_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_prename'];
        $this->Template->lecturer_lastname_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_lastname'];
        $this->Template->lecturer_email_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_email'];
        $this->Template->lecturer_mobile_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_mobile'];
        $this->Template->lecturer_department_label = $GLOBALS['TL_LANG']['tl_exams']['department'];

        $this->Template->department1 = $GLOBALS['TL_LANG']['tl_exams']['department1'];
        $this->Template->department2 = $GLOBALS['TL_LANG']['tl_exams']['department2'];
        $this->Template->department3 = $GLOBALS['TL_LANG']['tl_exams']['department3'];
        $this->Template->department4 = $GLOBALS['TL_LANG']['tl_exams']['department4'];
        $this->Template->department5 = $GLOBALS['TL_LANG']['tl_exams']['department5'];
        $this->Template->department6 = $GLOBALS['TL_LANG']['tl_exams']['department6'];
        $this->Template->department7 = $GLOBALS['TL_LANG']['tl_exams']['department7'];
        $this->Template->department8 = $GLOBALS['TL_LANG']['tl_exams']['department8'];
        $this->Template->department9 = $GLOBALS['TL_LANG']['tl_exams']['department9'];
        $this->Template->department10 = $GLOBALS['TL_LANG']['tl_exams']['department10'];
        $this->Template->department11 = $GLOBALS['TL_LANG']['tl_exams']['department11'];
        $this->Template->department12 = $GLOBALS['TL_LANG']['tl_exams']['department12'];
        $this->Template->department13 = $GLOBALS['TL_LANG']['tl_exams']['department13'];
        $this->Template->department14 = $GLOBALS['TL_LANG']['tl_exams']['department14'];
        $this->Template->usr_department = $userdata->department;

        $this->Template->exam_date_label = $GLOBALS['TL_LANG']['tl_exams']['date'];
        $this->Template->exam_begin_label = $GLOBALS['TL_LANG']['tl_exams']['time_begin'];
        $this->Template->exam_duration_label = $GLOBALS['TL_LANG']['tl_exams']['exam_duration'];
        $this->Template->tools_label = $GLOBALS['TL_LANG']['tl_exams']['tools'];
        $this->Template->remarks_label = $GLOBALS['TL_LANG']['tl_exams']['remarks'];

    }
}
