<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;
use Baul\ExamiaBundle\Model\ExamsModel;
use Baul\ExamiaBundle\Model\AttendeesExamsModel;


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
        $this->Template->unsubscribtionSuccessful = false;

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
        $this->Template->registeredExamsNone = $GLOBALS['TL_LANG']['miscellaneous']['registeredExamsNoneFuture'];

        $this->Template->langDate = $GLOBALS['TL_LANG']['tl_exams']['date'][0];
        $this->Template->langTimeBegin = $GLOBALS['TL_LANG']['tl_exams']['time'];
        $this->Template->langTitle = $GLOBALS['TL_LANG']['tl_exams']['title_short'];
        $this->Template->langLecturer = $GLOBALS['TL_LANG']['tl_exams']['lecturer'];
        $this->Template->langUnsubscribe = $GLOBALS['TL_LANG']['miscellaneous']['unsubscribe'];

        $this->Template->linkBackToUnsubscribeText = $GLOBALS['TL_LANG']['miscellaneous']['linkBackToUnsubscribeText'];
        $this->Template->linkTitleBackToUnsubscribe = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleBackToUnsubscribe'];

        // Von Klausur abmelden
        if (\Input::get("do") == "unsubscribe") {
            $exam_id = \Input::get("exam");

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
            $examFullTimestamp = $examData->date;

            // Doppeltes Absenden überprüfen
            $checkForDoubleSending = AttendeesExamsModel::findBy(['attendee_id = ?', 'exam_id = ?'], [$userID, $exam_id]);
            if (!empty($checkForDoubleSending->exam_id)) {
                $this->Template->showConfirmationQuestion = true;
                $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['examConfirmationQuestion'];
                $this->Template->examDescription = $examDescription;
                $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['examConfirmationYes'];
                $this->Template->unsubscribeConfirmation = $GLOBALS['TL_LANG']['miscellaneous']['unsubscribeConfirmation'];
                $this->Template->confirmationNo = $GLOBALS['TL_LANG']['miscellaneous']['examConfirmationNo'];
                $this->Template->cancel = $GLOBALS['TL_LANG']['miscellaneous']['cancel'];
            }
            else {
                $this->Template->showConfirmationQuestion = true;
                $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['noExamFound'];
                $this->Template->confirmationNo = $GLOBALS['TL_LANG']['miscellaneous']['linkBackToUnsubscribeText'];
            }

            if ((\Input::get("confirmed") == "yes")) {

                // Doppeltes Absenden überprüfen
                $attendeeExamData = AttendeesExamsModel::findBy(['attendee_id = ?', 'exam_id = ?'], [$userID, $exam_id]);
                if (!empty($attendeeExamData->exam_id)) {

                    // Schreibassistenz löschen
                    $writingAssistanceID = $attendeeExamData->assistant_id;
                    if ($writingAssistanceID > 0) {
                        $this->Database->prepare("DELETE FROM tl_supervisors_exams WHERE id=$writingAssistanceID")->execute()->affectedRows;
                    }


                    // Klausurteilnahme löschen
                    if ($unsubscribeFromExam = $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE exam_id=$exam_id AND attendee_id=$userID")->execute()->affectedRows) {

                        // Überprüfen, ob noch Mitglieder zu der Klausur angemeldet sind, um leere Datensätze zu vermeiden
                        $getExamRegistration = AttendeesExamsModel::findBy('exam_id', $exam_id);

                        // Klausur aus Datenbank löschen, falls niemand mehr dafür angemeldet ist
                        if (empty($getExamRegistration->attendee_id)) {
                            $this->Database->prepare("DELETE FROM tl_exams WHERE id=$exam_id")->execute()->affectedRows;

                            /* Werden noch Klausuren am gleichen Tag geschrieben? Falls nein, Aufsichten entfernen */
                            // Klausurdatum auf "Datum 0 Uhr" umwandeln, anschließend Timestamp von "Datum 23:59:59 Uhr" berechnen
                            $examDateReadable = date("d.m.Y", $examFullTimestamp);
                            $examDayStartTimestamp = strtotime($examDateReadable);
                            $examDayEndTimestamp = $examDayStartTimestamp+86399;
                            // Anzahl Klausuren im Zeitraum heraussuchen
                            $numberOfExamsTimePeriod = Database::getInstance()->prepare("SELECT COUNT(*) AS numberOfExams FROM tl_exams WHERE date BETWEEN $examDayStartTimestamp AND $examDayEndTimestamp")->query();
                            // Wenn Anzahl = 0, Aufsichten entfernen
                            if ($numberOfExamsTimePeriod->numberOfExams == 0) {
                                $this->Database->prepare("DELETE FROM tl_supervisors_exams WHERE date BETWEEN $examDayStartTimestamp AND $examDayEndTimestamp")->execute()->affectedRows;
                            }
                        }

                        // Mailversand aufrufen
                        $this->sendMail($examDescription, $examData->department);

                        // Rückmeldung geben, dass die Abmeldung erfolgreich war
                        $this->Template->unsubscribtionSuccessful = true;
                        $this->Template->unsubscribtionSuccessfulMessage = $GLOBALS['TL_LANG']['miscellaneous']['unsubscribtionSuccessful'];
                    }
                }
                else {
                    $this->Template->unsubscribtionSuccessful = true;
                    $this->Template->unsubscribtionSuccessfulMessage = $GLOBALS['TL_LANG']['miscellaneous']['noExamFound'];
                }
            }
            elseif (\Input::get("confirmed") == "no") {
                \Controller::redirect('klausurverwaltung/von-klausur-abmelden.html');
            }
        }

    }

    // Mailversand
    public function sendMail($examDescription, $department) {
        $objUser = FrontendUser::getInstance();
        $examDescription .= " (";
        $examDescription .= $GLOBALS['TL_LANG']['tl_exams'][$department];
        $examDescription .= ")";
        $this->sendMailMember($objUser, $examDescription);
        $this->sendMailBliZ($objUser, $examDescription);
    }

    public function sendMailMember($objUser, $examData) {
        $objMailUnsubscribe = new \Email();
        $objMailUnsubscribe->fromName = $GLOBALS['TL_LANG']['miscellaneous']['emailFromName'];
        $objMailUnsubscribe->from = 'beratung@bliz.thm.de';
        $objMailUnsubscribe->subject = $GLOBALS['TL_LANG']['miscellaneous']['emailSubjectUnsubscribe'];
        $objMailUnsubscribe->text = $GLOBALS['TL_LANG']['miscellaneous']['emailTextUnsubscribeMember'];
        $objMailUnsubscribe->text .= "\n";
        $objMailUnsubscribe->text .= $examData;
        $objMailUnsubscribe->text .= "\n\n";
        $objMailUnsubscribe->text .= $GLOBALS['TL_LANG']['miscellaneous']['emailTextAutoMail'];
        $objMailUnsubscribe->sendTo($objUser->email);
        unset($objMailUnsubscribe);
    }
    public function sendMailBliZ($objUser, $examData) {
        $objMailUnsubscribe = new \Email();
        $objMailUnsubscribe->fromName = $GLOBALS['TL_LANG']['miscellaneous']['emailFromName'];
        $objMailUnsubscribe->from = 'beratung@bliz.thm.de';
        $objMailUnsubscribe->subject = $GLOBALS['TL_LANG']['miscellaneous']['emailSubjectUnsubscribe'];
        $objMailUnsubscribe->text = $GLOBALS['TL_LANG']['miscellaneous']['emailTextUnsubscribeBliZ'];
        $objMailUnsubscribe->text .= "\n";
        $objMailUnsubscribe->text .= $objUser->username;
        $objMailUnsubscribe->text .= " (ID ";
        $objMailUnsubscribe->text .= $objUser->id;
        $objMailUnsubscribe->text .= "), ";
        $objMailUnsubscribe->text .= $GLOBALS['TL_LANG']['miscellaneous']['exam'];
        $objMailUnsubscribe->text .= ": ";
        $objMailUnsubscribe->text .= $examData;
        $objMailUnsubscribe->text .= "\n\n";
        $objMailUnsubscribe->text .= $GLOBALS['TL_LANG']['miscellaneous']['emailTextAutoMail'];
        $objMailUnsubscribe->sendTo($objUser->email);
        unset($objMailUnsubscribe);
    }
}
