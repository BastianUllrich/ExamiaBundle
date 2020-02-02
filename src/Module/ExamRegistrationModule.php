<?php

namespace Baul\ExamiaBundle\Module;
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
        $this->loadLanguageFile('miscellaneous');


        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;
        $this->Template->address = $objUser->email;

        // Daten des Mitglieds aus der Datenbank laden
        $userdata = MemberModel::findBy('id', $userID);

        /**
        * Variablen für das Template setzen *
        */

        // Formular nur angezeigen, wenn es nicht abgesandt wurde
        $this->Template->formIsSubmitted = false;

        // Sprach-Variablen setzen

        $this->Template->mandatory = $GLOBALS['TL_LANG']['miscellaneous']['mandatory'];
        $this->Template->examRegistration = $GLOBALS['TL_LANG']['miscellaneous']['examRegistration'];
        $this->Template->examRegistrationExplanation = $GLOBALS['TL_LANG']['miscellaneous']['examRegistrationExplanation'];
        $this->Template->title_label = $GLOBALS['TL_LANG']['tl_exams']['title'][0];
        $this->Template->lecturer_legend = $GLOBALS['TL_LANG']['tl_exams']['lecturer_legend'];
        $this->Template->lecturer_title_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_title'][0];
        $this->Template->lecturer_firstname_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_prename'][0];
        $this->Template->lecturer_lastname_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_lastname'][0];
        $this->Template->lecturer_email_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_email'][0];
        $this->Template->lecturer_mobile_label = $GLOBALS['TL_LANG']['tl_exams']['lecturer_mobile'][0];
        $this->Template->department_label = $GLOBALS['TL_LANG']['tl_exams']['department'][0];
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
        $this->Template->exam_date_label = $GLOBALS['TL_LANG']['tl_exams']['date'][0];
        $this->Template->exam_begin_label = $GLOBALS['TL_LANG']['tl_exams']['time_begin'][0];
        $this->Template->exam_duration_label = $GLOBALS['TL_LANG']['tl_exams']['exam_duration'][0];
        $this->Template->tools_label = $GLOBALS['TL_LANG']['tl_exams']['tools'][0];
        $this->Template->remarks_label = $GLOBALS['TL_LANG']['tl_exams']['remarks'][0];

        // Aktionen nach Absenden des Formulars
        if (\Contao\Input::post('FORM_SUBMIT') == 'examRegistration') {
            // Funktion zum Eintrag in die Datenbank aufrufen, mit Entitäten des Mitglieds für die Tabelle "tl_attendees_exams" (Zuweisung Klausur - Mitglied)
            $this->registerExam($userID, $userdata->rehab_devices, $userdata->rehab_devices_others, $userdata->extra_time, $userdata->extra_time_minutes_percent);
        }
    }

    public function registerExam($userID, $rehab_devices, $rehab_devices_others, $extra_time, $extra_time_minutes_percent) {

        // Felder aus Formular auslesen
        $exam_title = \Input::post('exam_title');
        $lecturer_title = \Input::post('lecturer_title');
        $lecturer_firstname = \Input::post('lecturer_firstname');
        $lecturer_lastname = \Input::post('lecturer_lastname');
        $lecturer_email = \Input::post('lecturer_email');
        $lecturer_mobile = \Input::post('lecturer_mobile');
        $department = \Input::post('department');
        $exam_date = \Input::post('exam_date');
        $exam_begin = \Input::post('exam_begin');
        $exam_duration = \Input::post('exam_duration');
        $tools = \Input::post('tools');
        $remarks = \Input::post('remarks');

        // Datum um Beginn verknüpfen und in Variable für Datenbank schreiben
        $exam_datetime = $exam_date;
        $exam_datetime .= " ";
        $exam_datetime .= $exam_begin;
        $exam_datetime = strtotime($exam_datetime);

        //Status der Anmeldung auf "status1" (Noch nicht angefordert) setzen
        $status = 'status1';

        // Datenbank importieren, Insertions für Tabelle "tl_exams" definieren
        $this->import('Database');
        $set = array('tstamp' => time(), 'title' => $exam_title, 'lecturer_title' => $lecturer_title, 'lecturer_prename' => $lecturer_firstname, 'lecturer_lastname' => $lecturer_lastname,
                    'lecturer_email' => $lecturer_email, 'lecturer_mobile' => $lecturer_mobile, 'department' => $department, 'date' => $exam_datetime, 'begin' => $exam_begin,
                    'duration' => $exam_duration, 'tools' => $tools, 'remarks' => $remarks, 'status' => $status);

        // Eintrag in Tabelle "tl_exams" vornehmen
        if ($objInsert = $this->Database->prepare("INSERT INTO tl_exams %s")->set($set)->execute()) {
            if (empty($extra_time)) $extra_time = 0;

            // Insertions für Tabelle "tl_attendees_exams" definieren
            $newset = array('tstamp' => time(), 'attendee_id' => $userID, 'exam_id' => $objInsert->insertId, 'status' => 'in_progress', 'rehab_devices' => $rehab_devices,
                            'rehab_devices_others' => $rehab_devices_others, 'extra_time' => $extra_time, 'extra_time_minutes_percent' => $extra_time_minutes_percent);

            // Eintrag in Tabelle "tl_attendees_exams" vornehmen, anschließend eine E-Mail versenden und die Funktion submitSuccess() aufrufen
            if ($newObjInsert = $this->Database->prepare("INSERT INTO tl_attendees_exams %s")->set($newset)->execute()) {
                $this->sendMail();
                $this->submitSuccess();
            }
        }
    }

    // Funktion gibt Erfolgsmeldung aus, wenn Formular abgesandt wurde
    public function submitSuccess() {
        $this->Template->formIsSubmitted = true;
        $this->Template->submittedMessage = $GLOBALS['TL_LANG']['miscellaneous']['examRegistrationSuccess'];
    }

    // Mailversand
    public function sendMail() {
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;
        $memberdata = MemberModel::findBy('id', $userID);

        $objMailSuscribe = new \Email();
        $objMailSuscribe->fromName = $GLOBALS['TL_ADMIN_NAME'];
        $objMailSuscribe->from = $GLOBALS['TL_ADMIN_EMAIL'];
        $objMailSuscribe->subject = 'Anmeldung zu einer Klausur im BliZ';
        $objMailSuscribe->text = 'Eine Anmeldung zu einer Klausur im BliZ ist erfolgt';
        $objMailSuscribe->sendTo($memberdata->email);
        unset($objMailSuscribe);
    }
}
