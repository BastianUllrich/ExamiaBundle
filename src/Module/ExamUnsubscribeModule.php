<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;
use Baul\ExamiaBundle\Model\ExamsModel;


class ExamUnsubscribeModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_examUnsubscribe';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['examUnsubscribe'][0]) . ' ###';
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
        // Sprachdatei einbinden
        $this->loadLanguageFile('tl_exams');
        $this->loadLanguageFile('tl_attendees_exams');
        $this->loadLanguageFile('miscellaneous');

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;

        $this->Template->showConfirmationQuestion = false;
        $this->Template->unsuscribtionSuccessful = false;

        // Auflistung der angemeldeten Klausuren
        // Aktueller Timestamp muss via PHP geholt werden, da SQL nur Datumformat "2020-02-02" erstellen kann
        $this->import('Database');
        $examParticipationList = array();
        $i = 0;
        $currentTime = time();
        $result = Database::getInstance()->prepare(
            "SELECT tl_exams.id, tl_exams.date, tl_exams.begin, tl_exams.title, tl_exams.lecturer_title, tl_exams.lecturer_prename, tl_exams.lecturer_lastname, tl_attendees_exams.status 
             FROM tl_exams, tl_attendees_exams 
             WHERE tl_exams.id=tl_attendees_exams.exam_id 
             AND tl_attendees_exams.attendee_id=$userID
             AND tl_exams.date >= $currentTime
             ORDER BY tl_exams.date
            ")->query();
        while ($result->next()) {
            $examParticipationList[$i]['date'] = date("d.m.Y", $result->date);
            $examParticipationList[$i]['time'] = $result->begin;
            $examParticipationList[$i]['title'] = $result->title;

            $examParticipationList[$i]['lecturer_name'] = $result->lecturer_title;
            $examParticipationList[$i]['lecturer_name'] .= ' ';
            $examParticipationList[$i]['lecturer_name'] .= $result->lecturer_prename;
            $examParticipationList[$i]['lecturer_name'] .= ' ';
            $examParticipationList[$i]['lecturer_name'] .= $result->lecturer_lastname;
            $examParticipationList[$i]['examsID'] = $result->id;

            $i++;
        }

        $this->Template->unsubscribe = $GLOBALS['TL_LANG']['miscellaneous']['unsubscribe'];
        $this->Template->unsubscribeThis = $GLOBALS['TL_LANG']['miscellaneous']['examThisUnsubscribe'];
        $this->Template->examParticipationList = $examParticipationList;
        $this->Template->examsUnsubscribe = $GLOBALS['TL_LANG']['miscellaneous']['examsUnsubscribe'];
        $this->Template->registeredExamsExplanation = $GLOBALS['TL_LANG']['miscellaneous']['registeredExamsExplanation'];
        $this->Template->registeredExamsNone = $GLOBALS['TL_LANG']['miscellaneous']['registeredExamsNone'];

        $this->Template->langDate = $GLOBALS['TL_LANG']['tl_exams']['date'][0];
        $this->Template->langTimeBegin = $GLOBALS['TL_LANG']['tl_exams']['time_begin'][0];
        $this->Template->langTitle = $GLOBALS['TL_LANG']['tl_exams']['title_short'];
        $this->Template->langLecturer = $GLOBALS['TL_LANG']['tl_exams']['lecturer'];
        $this->Template->langUnsubscribe = $GLOBALS['TL_LANG']['miscellaneous']['unsubscribe'];

        // Von Klausur abmelden
        if ($_GET["do"] == "unsubscribe") {
            $exam_id = $_GET["exam"];

            $examData = ExamsModel::findBy('id', $exam_id);
            $examDescription = $examData->title;
            $examDescription .= ' '. $GLOBALS['TL_LANG']['miscellaneous']['dateAt'] . ' ';
            $examDescription .= date("d.m.Y", $examData->date);
            $examDescription .= ' ' . $GLOBALS['TL_LANG']['miscellaneous']['timeAt'] . ' ';
            $examDescription .= $examData->begin;
            $examDescription .= ' ' . $GLOBALS['TL_LANG']['miscellaneous']['timeHour'] . ' ' . $GLOBALS['TL_LANG']['miscellaneous']['lecturerAt'];
            $examDescription .= $examData->lecturer_title;
            $examDescription .= ' ';
            $examDescription .= $examData->lecturer_prename;
            $examDescription .= ' ';
            $examDescription .= $examData->lecturer_lastname;

            $this->Template->showConfirmationQuestion = true;
            $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['examConfirmationQuestion'];
            $this->Template->examDescription = $examDescription;
            $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['examConfirmationYes'];
            $this->Template->unsuscribeConfirmation = $GLOBALS['TL_LANG']['miscellaneous']['unsuscribeConfirmation'];
            $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['examConfirmationYes'];
            $this->Template->cancel = $GLOBALS['TL_LANG']['miscellaneous']['cancel'];

            if (($_GET["confirmed"] == "yes")) {
                if ($unsuscribeFromExam = $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE exam_id=$exam_id AND attendee_id=$userID")->execute()->affectedRows) {

                    // Überprüfen, ob noch Mitglieder zu der Klausur angemeldet sind, um leere Datensätze zu vermeiden
                    $getExamRegistration = Database::getInstance()->prepare("SELECT * FROM tl_attendees_exams WHERE exam_id=$exam_id")->query();

                    // Klausur aus Datenbank löschen, falls niemand mehr dafür angemeldet ist
                    if (empty($getExamRegistration->attendee_id)) {
                        $this->Database->prepare("DELETE FROM tl_exams WHERE id=$exam_id")->execute()->affectedRows;
                    }

                    // Mailversand aufrufen
                    $this->sendMail($examDescription, $examData->department);

                    $this->Template->unsuscribtionSuccessful = true;
                    $this->Template->unsuscribtionSuccessfulMessage = $GLOBALS['TL_LANG']['miscellaneous']['unsubscribtionSuccessful'];
                    $this->Template->linkBackToUnsuscribeText = $GLOBALS['TL_LANG']['miscellaneous']['linkBackToUnsuscribeText'];
                }
            }
            elseif ($_GET["confirmed"] == "no") {
                \Controller::redirect('klausurverwaltung/von-klausur-abmelden.html');
            }
        }

    }

    // Mailversand
    public function sendMail($examDescription, $department) {
        $objUser = FrontendUser::getInstance();
        $examDescription .= " (";
        $examDescription .= $department;
        $examDescription .= ")";
        $this->sendMailMember($objUser, $examDescription);
        $this->sendMailBliZ($objUser, $examDescription);
    }

    public function sendMailMember($objUser, $examData) {
        $objMailSuscribe = new \Email();
        $objMailSuscribe->fromName = $GLOBALS['TL_LANG']['miscellaneous']['emailFromName'];
        $objMailSuscribe->from = 'beratung@bliz.thm.de';
        $objMailSuscribe->subject = $GLOBALS['TL_LANG']['miscellaneous']['emailSubjectUnsuscribe'];
        $objMailSuscribe->text = $GLOBALS['TL_LANG']['miscellaneous']['emailTextUnsuscribeMember'];
        $objMailSuscribe->text .= "\n";
        $objMailSuscribe->text .= $examData;
        $objMailSuscribe->sendTo($objUser->email);
        unset($objMailSuscribe);
    }
    public function sendMailBliZ($objUser, $examData) {
        $objMailSuscribe = new \Email();
        $objMailSuscribe->fromName = $GLOBALS['TL_LANG']['miscellaneous']['emailFromName'];
        $objMailSuscribe->from = 'beratung@bliz.thm.de';
        $objMailSuscribe->subject = $GLOBALS['TL_LANG']['miscellaneous']['emailSubjectUnuscribe'];
        $objMailSuscribe->text = $GLOBALS['TL_LANG']['miscellaneous']['emailTextUnsuscribeBliZ'];
        $objMailSuscribe->text .= "\n";
        $objMailSuscribe->text .= $objUser->username;
        $objMailSuscribe->text .= " (ID ";
        $objMailSuscribe->text .= $objUser->username;
        $objMailSuscribe->text .= "), ";
        $objMailSuscribe->text .= $GLOBALS['TL_LANG']['miscellaneous']['exam'];
        $objMailSuscribe->text .= ": ";
        $objMailSuscribe->text .= $examData;
        $objMailSuscribe->sendTo($objUser->email);
        unset($objMailSuscribe);
    }
}
