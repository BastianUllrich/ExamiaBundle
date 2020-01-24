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
        $this->loadLanguageFile('tl_attendees_exams');
        $this->loadLanguageFile('miscellaneous');

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;

        $this->Template->showConfirmationQuestion = false;

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
        $this->Template->examParticipationList = $examParticipationList;
        $this->Template->examsUnsubscribe = $GLOBALS['TL_LANG']['miscellaneous']['examsUnsubscribe'];
        $this->Template->registeredExamsExplanation = $GLOBALS['TL_LANG']['miscellaneous']['registeredExamsExplanation'];
        $this->Template->registeredExamsNone = $GLOBALS['TL_LANG']['miscellaneous']['registeredExamsNone'];

        if ($_GET["unsubscribe"] == "success") {
            $this->Template->unsubscribtionSuccessful = $GLOBALS['TL_LANG']['miscellaneous']['unsubscribtionSuccessful'];
        }

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
            $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['confirmationQuestion'];
            $this->Template->examDescription = $examDescription;
            $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['confirmationYes'];
            $this->Template->confirmationNo = $GLOBALS['TL_LANG']['miscellaneous']['confirmationNo'];

            if (($_GET["confirmed"] == "yes")) {
                if ($unsuscribeFromExam = $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE exam_id=$exam_id AND attendee_id=$userID")->execute()->affectedRows) {

                    // Überprüfen, ob noch Mitglieder zu der Klausur angemeldet sind, um leere Datensätze zu vermeiden
                    $result = Database::getInstance()->prepare("SELECT COUNT(*) FROM tl_attendees_exams WHERE exam_id=$exam_id")->query();

                    // Klausur aus Datenbank löschen, falls niemand mehr dafür angemeldet ist
                    if ($result == 0) {
                        $this->Database->prepare("DELETE FROM tl_exams WHERE id=$exam_id")->execute()->affectedRows;
                    }

                    \Controller::redirect('klausurverwaltung/von-klausur-abmelden.html?unsubscribe=success&result=$result');
                }
            }
            elseif ($_GET["confirmed"] == "no") {
                \Controller::redirect('klausurverwaltung/von-klausur-abmelden.html');
            }
        }

    }
}
