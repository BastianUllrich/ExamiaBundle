<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;
use Baul\ExamiaBundle\Model\ExamsModel;
use Baul\ExamiaBundle\Model\MemberModel;
use Baul\ExamiaBundle\Model\AttendeesExamsModel;
use Baul\ExamiaBundle\Model\SupervisorsExamsModel;


class ExamAdministrationModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_examAdministration';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['exanAdministration'][0]) . ' ###';
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
        $this->loadLanguageFile('miscellaneous');
        $this->loadLanguageFile('tl_member');
        $this->loadLanguageFile('tl_exams');
        $this->loadLanguageFile('tl_attendees_exams');

        $this->Template->showConfirmationQuestion = false;
        $this->Template->showDetails = false;
        $this->Template->showEditForm = false;
        $this->Template->changesSaved = false;
        $this->Template->attendeeChangesSaved = false;
        $this->Template->editAttendees = false;
        $this->Template->showAttendeeDetails = false;
        $this->Template->showCombineForm = false;
        $this->Template->combinationSaved = false;

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $this->Template->userID = $objUser->id;

        // Sprachvariablen setzen
        $this->Template->manage_exams = $GLOBALS['TL_LANG']['miscellaneous']['manage_exams'];

        $this->Template->langNoDataAvailable = $GLOBALS['TL_LANG']['miscellaneous']['noDataAvailable'];

        $this->Template->headerDate = $GLOBALS['TL_LANG']['tl_exams']['date'][0];
        $this->Template->headerBegin = $GLOBALS['TL_LANG']['tl_exams']['time_begin'][0];
        $this->Template->headerExamTitle = $GLOBALS['TL_LANG']['tl_exams']['title_short'];
        $this->Template->headerDepartment = $GLOBALS['TL_LANG']['tl_exams']['department_short'];
        $this->Template->headerAction = $GLOBALS['TL_LANG']['miscellaneous']['action'];
        $this->Template->orderAltText = $GLOBALS['TL_LANG']['miscellaneous']['orderAltText'];

        $this->Template->imgAltViewExamDetails = $GLOBALS['TL_LANG']['miscellaneous']['imgAltViewExamDetails'];
        $this->Template->imgAltEditExamDetails = $GLOBALS['TL_LANG']['miscellaneous']['imgAltEditExamDetails'];
        $this->Template->imgAltEditAttendees = $GLOBALS['TL_LANG']['miscellaneous']['imgAltEditAttendees'];
        $this->Template->imgAltCombineExams = $GLOBALS['TL_LANG']['miscellaneous']['imgAltCombineExams'];
        $this->Template->imgAltDeleteExam = $GLOBALS['TL_LANG']['miscellaneous']['imgAltDeleteExam'];

        $this->Template->linkTitleViewExamDetails = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleViewExamDetails'];
        $this->Template->linkTitleEditExamDetails = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleEditExamDetails'];
        $this->Template->linkTitleEditAttendees = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleEditAttendees'];
        $this->Template->linkTitleCombineExams = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleCombineExams'];
        $this->Template->linkTitleDeleteExam = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleDeleteExam'];

        $this->Template->linkTitleDeleteExamConfirmYes = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleDeleteExamConfirmYes'];
        $this->Template->linkTitleDeleteExamConfirmNo = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleDeleteExamConfirmNo'];

        $this->Template->linktextBackToExamsAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linktextBackToExamsAdministration'];
        $this->Template->linkTitleBackToExamsAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleBackToExamsAdministration'];

        $this->Template->showRoomPlan = $GLOBALS['TL_LANG']['miscellaneous']['showRoomPlan'];
        $this->Template->showRoomPlanLinkTitle = $GLOBALS['TL_LANG']['miscellaneous']['showRoomPlanLinkTitle'];

        if ($_GET["orderBy"] == "dateDESC") {
            $options = [
                'order' => 'date DESC'
            ];
            $this->Template->isOrderedBy = "dateDESC";
            $this->Template->orderByDateText = $GLOBALS['TL_LANG']['miscellaneous']['orderByDateASC'];

        } else {
            $options = [
                'order' => 'date ASC'
            ];
            $this->Template->isOrderedBy = "dateASC";
            $this->Template->orderByDateText = $GLOBALS['TL_LANG']['miscellaneous']['orderByDateDESC'];
        }

        $results = ExamsModel::findAll($options);

        $i = 0;
        $examData = array();
        foreach ($results as $result) {
            // Variablen für das Template setzen
            $examData[$i]['date'] = date("d.m.Y", $result->date);
            $examData[$i]['begin'] = $result->begin;
            $examData[$i]['title'] = $result->title;
            // Verkürzte Schreibweise für den Fachbereich
            $examData[$i]['department'] = str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_exams'][$result->department], 0, 5)));
            $examData[$i]['id'] = $result->id;
            $i++;
        }
        $this->Template->examDataList = $examData;

        // Klausurdetails anzeigen
        if ($_GET["do"] == "viewDetails") {
            $this->Template->showDetails = true;
            $exam = $_GET["exam"];
            $examDetails = ExamsModel::findBy('id', $exam);
            $this->setLangValuesViewEditDetails();
            $this->setExamValuesViewDetails($examDetails);
        }

        // Klausurdetails bearbeiten
        if ($_GET["do"] == "editDetails") {
            $this->Template->showEditForm = true;
            $exam = $_GET["exam"];
            $examDetails = ExamsModel::findBy('id', $exam);
            $this->setLangValuesViewEditDetails();
            $this->setExamValuesEdit($examDetails);
        }

        // Formular wurde abgesendet -> Zur Unterscheidung, welches Formular abgesendet wurde, wird das hidden-Feld "formIdentity" ausgelesen
        if (\Contao\Input::post('FORM_SUBMIT') == 'editExam') {
            $examID = $_GET["exam"];
            $attendeeID = $_GET["editAttendee"];
            $formIdentity = \Input::post('formIdentity');

            if ($formIdentity == "editExamData") {
                $this->saveExamChanges($examID);
            }
            elseif ($formIdentity == "editAttendeeData") {
                $this->saveAttendeeChanges($examID, $attendeeID);
            }
            elseif ($formIdentity == "combineExams") {
                $this->saveCombineChanges($examID);
            }
        }

        // Teilnehmer bearbeiten
        if ($_GET["do"] == "editAttendees") {
            $this->Template->editAttendees = true;
            $this->Template->showAttendeeDetails = false;
            $this->Template->showEditAttendeeForm = false;
            $this->Template->showDeleteAttendeeConfirmation = false;
            $exam = $_GET["exam"];

            // Teilnehmer löschen
            if (array_key_exists('deleteAttendee', $_GET)) {
                $this->deleteAttendee($exam);
            }
            // Teilnehmerdaten bearbeiten
            elseif (array_key_exists('editAttendee', $_GET)) {
                $this->editAttendee($exam);
            }
            // Teilnehmerdaten anzeigen
            elseif (array_key_exists('showAttendee', $_GET)) {
                $this->showAttendee($exam);
            }
            // Alle Teilnehmer anzeigen
            else {
                $this->showAttendeeList($exam);
            }
        }

        // Klausuren zusammenlegen
        if ($_GET["do"] == "combine") {
            $exam = $_GET["exam"];
            $this->Template->showCombineForm = true;
            $examDetails = ExamsModel::findBy('id', $exam);
            $this->setLangValuesCombineExams();
            $this->setValuesCombineExams($examDetails);
        }

        // Klausur löschen
        if ($_GET["do"] == "delete") {
            $exam = $_GET["exam"];
            $this->Template->showConfirmationQuestion = true;
            $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['deleteExamConfirmationQuestion'];
            $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['deleteExamConfirmationYes'];
            $this->Template->confirmationNo = $GLOBALS['TL_LANG']['miscellaneous']['deleteExamConfirmationNo'];

            // Klausur erst nach Bestätigung löschen
            if (($_GET["confirmed"] == "yes")) {
                $this->import('Database');

                // Klausurteilnehmer entfernen
                $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE exam_id=$exam")->execute()->affectedRows;

                // Aufsichten vom Klausurtag entfernen, falls am Tag der Klausur keine Klausuren mehr vorhanden sind
                $getExamDate = ExamsModel::findBy('id', $exam);
                // Umwandeln der Time-Angabe von Tag + Uhrzeit auf Tag
                $examDateString = date("d.m.Y", $getExamDate->date);
                $examDateFrom = strtotime($examDateString);
                $examDateTo = $examDateFrom + 86399;
                // Anzahl der Datensätze zählen & ggf. Aufsichten entfernen
                $resultCount = $this->Database->prepare("SELECT count(*) FROM tl_exams WHERE 'date' BETWEEN $examDateFrom AND $examDateTo")->query();
                if ($resultCount != 0) {
                    $this->Database->prepare("DELETE FROM tl_supervisors_exams WHERE 'date' BETWEEN $examDateFrom AND $examDateTo")->execute()->affectedRows;
                }

                // Klausur aus Datenbank löschen und zur Seite "Klausurverwaltung" zurückkehren
                if ($deleteExam = $this->Database->prepare("DELETE FROM tl_exams WHERE id=$exam")->execute()->affectedRows) {
                    \Controller::redirect('klausurverwaltung/klausurverwaltung.html');
                }

            } elseif ($_GET["confirmed"] == "no") {
                \Controller::redirect('klausurverwaltung/klausurverwaltung.html');
            }
        }
    }

    public function setLangValuesCombineExams() {
        $this->Template->langCombineExams = $GLOBALS['TL_LANG']['miscellaneous']['combineExams'];
        $this->Template->langCombineExamsExplanation = $GLOBALS['TL_LANG']['miscellaneous']['combineExamsExplanation'];
        $this->Template->langCombineExamsTo = $GLOBALS['TL_LANG']['miscellaneous']['combineExamsTo'];
        $this->Template->langCombineExamsFrom = $GLOBALS['TL_LANG']['miscellaneous']['combineExamsFrom'];
        $this->Template->langCombineDoCombine = $GLOBALS['TL_LANG']['miscellaneous']['combineDoCombine'];
        $this->Template->langExam = $GLOBALS['TL_LANG']['miscellaneous']['exam'];
        $this->Template->langExamTimeAt = $GLOBALS['TL_LANG']['miscellaneous']['timeAt'];
        $this->Template->langExamTimeHour = $GLOBALS['TL_LANG']['miscellaneous']['timeHour'];
    }

    public function setValuesCombineExams($examDetails) {
        $this->Template->examToId = $examDetails->id;
        $this->Template->examToTitle = $examDetails->title;
        $this->Template->examToDate = date("d.m.Y", $examDetails->date);
        $this->Template->examToTime = $examDetails->begin;
        // Verkürzte Schreibweise für den Fachbereich
        $this->Template->examToDepartment = str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_exams'][$examDetails->department], 0, 5)));

        // Andere Klausuren aus Datenbank holen (nur aktuelle)
        $timeNow = time();
        $id = $examDetails->id;

        $results = ExamsModel::findBy(['id <> ?', 'date > ?'], [$id, $timeNow]);
        $i = 0;
        $examFromData = array();
        foreach ($results as $result) {
            // Variablen für das Template setzen
            $examFromData[$i]['id'] = $result->id;
            $examFromData[$i]['title'] = $result->title;
            $examFromData[$i]['date'] = date("d.m.Y", $result->date);
            $examFromData[$i]['begin'] = $result->begin;
            $examFromData[$i]['department'] = str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_exams'][$result->department], 0, 5)));
            $i++;
        }
        $this->Template->examFromDataList = $examFromData;
    }

    public function saveCombineChanges($examID) {
        $combineToId = $examID;
        $combineFromId = \Input::post('examFrom');

        /* Verhindern, dass ein Teilnehmer doppelt eingetragen wird */
        // Alle Teilnehmer der "alten" Klausur heraussuchen
        $getAttendeesFromExam = AttendeesExamsModel::findBy(['exam_id = ?'], [$combineFromId]);
        foreach ($getAttendeesFromExam as $getAttendeeFromExam) {
            $attendeeIdFromExam = $getAttendeeFromExam->attendee_id;
            // Ist dieser Teilnehmer in der "neuen" Klausur vorhanden?
            $getAttendeesToExam = AttendeesExamsModel::findBy(['exam_id = ?', 'attendee_id = ?'], [$combineToId, $attendeeIdFromExam]);
            $attendeeIdToExam = $getAttendeesToExam->attendee_id;
            // Wenn der Teilnehmer schon vorhanden ist, wird er aus der "alten" Klausur gelöscht
            if (!empty($attendeeIdToExam)) {
                $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE attendee_id=$attendeeIdToExam AND exam_id=$combineFromId")->execute()->affectedRows;
            }
        }

        // Alle Teilnehmer der "alten" Klausur in die "neue" Klausur übertragen (falls noch nicht drin)
        $getAttendeesFromExam->exam_id = $combineToId;
        if ($getAttendeesFromExam->save()) {
            // "Alte" Klausur löschen
            $this->Database->prepare("DELETE FROM tl_exams WHERE id=$combineFromId")->execute()->affectedRows;
            $this->Template->combinationSaved = true;
            $this->Template->combinationSavedMessage = $GLOBALS['TL_LANG']['miscellaneous']['combinationSavedSavedMessage'];
        }

    }

    public function setExamValuesViewDetails($examDetails)
    {
        $this->Template->detailExamTitel = $examDetails->title;
        $detailDate = date("d.m.Y", $examDetails->date);
        $this->Template->detailDate = $detailDate;
        $this->Template->detailTimeStart = $examDetails->begin;

        // Reguläre Dauer zusammensetzen
        $this->Template->detailRegularDuration = $examDetails->duration;
        $this->Template->detailRegularDuration .= " ";
        $this->Template->detailRegularDuration .= $GLOBALS['TL_LANG']['tl_attendees_exams']['minutes'];

        /* Späteste Endzeit berechnen */

        // Maximale Dauer in Minuten berechnen
        $results = AttendeesExamsModel::findBy('exam_id', $examDetails->id);
        $i = 0;
        $maxDuration = $examDetails->duration;
        foreach ($results as $result) {
            if ($result->extra_time_minutes_percent == "percent") {
                $multiplicator = 1 + ($result->extra_time / 100);
                $duration = ($examDetails->duration) * $multiplicator;
            } elseif ($result->extra_time_minutes_percent == "minutes") {
                $duration = ($examDetails->duration) + $result->extra_time;
            }
            if ($duration > $maxDuration) {
                $maxDuration = $duration;
            }
        }
        // Späteste Endzeit berechnen
        $maxEndTime = ($examDetails->date) + ($maxDuration * 60);
        $maxEndTimeReadable = date("H:i", $maxEndTime);
        $this->Template->detailMaxEndtime = $maxEndTimeReadable;

        // Dozentendaten zusammensetzen
        $this->Template->detailLecturer = $examDetails->lecturer_title;
        $this->Template->detailLecturer .= " ";
        $this->Template->detailLecturer .= $examDetails->lecturer_prename;
        $this->Template->detailLecturer .= " ";
        $this->Template->detailLecturer .= $examDetails->lecturer_lastname;
        $this->Template->detailLecturer .= " (";
        $this->Template->detailLecturer .= $examDetails->lecturer_email;
        if (!empty($examDetails->lecturer_mobile)) {
            $this->Template->detailLecturer .= ", ";
            $this->Template->detailLecturer .= $examDetails->lecturer_mobile;
        }
        $this->Template->detailLecturer .= ")";

        $this->Template->detailDepartment = $GLOBALS['TL_LANG']['tl_exams'][$examDetails->department];
        $this->Template->detailTools = $examDetails->tools;
        $this->Template->detailStatus = $GLOBALS['TL_LANG']['tl_exams'][$examDetails->status];

        /* Aufsichten / Schreibassistenten heraussuchen */
        // Tag der Klausur in timestamp umwandeln (0:00 Uhr und 23:59:59)
        $dayExamMidnightTimeStamp = strtotime($detailDate);
        $dayExamLastSecond = $dayExamMidnightTimeStamp + 86399;

        $result = Database::getInstance()->prepare("SELECT tl_member.firstname, tl_member.lastname, tl_supervisors_exams.time_from, tl_supervisors_exams.time_until, tl_supervisors_exams.task
                                                    FROM tl_member, tl_supervisors_exams
                                                    WHERE tl_supervisors_exams.supervisor_id=tl_member.id
                                                    AND tl_supervisors_exams.date
                                                    BETWEEN $dayExamMidnightTimeStamp
                                                    AND $dayExamLastSecond
                                                    ")->query();
        $i = 0;
        $supervisorsData = array();
        while ($result->next()) {
            // Variablen für das Template setzen
            $supervisorsData[$i]['firstname'] = $result->firstname;
            $supervisorsData[$i]['lastname'] = $result->lastname;
            $supervisorsData[$i]['time_from'] = $result->time_from;
            $supervisorsData[$i]['time_until'] = $result->time_until;
            $supervisorsData[$i]['task'] = $result->task;
            $i++;
        }
        $this->Template->supervisorsDataList = $supervisorsData;

        // Teilnehmer heraussuchen
        $result = Database::getInstance()->prepare("SELECT tl_member.firstname, tl_member.lastname, tl_member.id, tl_attendees_exams.extra_time, tl_attendees_exams.extra_time_minutes_percent
                                                    FROM tl_member, tl_attendees_exams
                                                    WHERE tl_attendees_exams.exam_id=$examDetails->id
                                                    AND tl_attendees_exams.attendee_id=tl_member.id
                                                    ")->query();
        $i = 0;
        $attendeesData = array();
        while ($result->next()) {
            // Variablen für das Template setzen
            $attendeesData[$i]['firstname'] = $result->firstname;
            $attendeesData[$i]['lastname'] = $result->lastname;
            if (!empty($result->extra_time) && !empty($result->extra_time_minutes_percent)) {
                $attendeesData[$i]['extra_time'] = $result->extra_time;
                $attendeesData[$i]['extra_time_minutes_percent'] = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->extra_time_minutes_percent];
            }
            else {
                $attendeesData[$i]['extra_time'] = $GLOBALS['TL_LANG']['miscellaneous']['noExtraTime'];
            }
            $i++;
        }
        $this->Template->attendeesDataList = $attendeesData;

        $this->Template->detailRemarks = $examDetails->remarks;
    }

    public function setLangValuesViewEditDetails()
    {
        $this->Template->langExamDetails = $GLOBALS['TL_LANG']['miscellaneous']['examDetails'];
        $this->Template->langEditExamDetails = $GLOBALS['TL_LANG']['miscellaneous']['editExamDetails'];
        $this->Template->langExamTitel = $GLOBALS['TL_LANG']['tl_exams']['title_short'];
        $this->Template->langDate = $GLOBALS['TL_LANG']['tl_exams']['date'][0];
        $this->Template->langTimeStart = $GLOBALS['TL_LANG']['tl_exams']['time_begin'][0];
        $this->Template->langRegularDuration = $GLOBALS['TL_LANG']['tl_exams']['exam_reg_duration'];
        $this->Template->langRegularDurationLong = $GLOBALS['TL_LANG']['tl_exams']['exam_reg_duration_long'];
        $this->Template->langMaxEndtime = $GLOBALS['TL_LANG']['tl_exams']['max_ending'];
        $this->Template->langLecturer = $GLOBALS['TL_LANG']['tl_exams']['lecturer'];
        $this->Template->langLecturerLong = $GLOBALS['TL_LANG']['tl_exams']['lecturer_legend'];
        $this->Template->langLecturerTitle = $GLOBALS['TL_LANG']['tl_exams']['lecturer_title'][0];
        $this->Template->langLecturerFirstname = $GLOBALS['TL_LANG']['tl_exams']['lecturer_prename'][0];
        $this->Template->langLecturerLastname = $GLOBALS['TL_LANG']['tl_exams']['lecturer_lastname'][0];
        $this->Template->langLecturerEmail = $GLOBALS['TL_LANG']['tl_exams']['lecturer_email'][0];
        $this->Template->langLecturerMobile = $GLOBALS['TL_LANG']['tl_exams']['lecturer_mobile'][0];
        $this->Template->langDepartment = $GLOBALS['TL_LANG']['tl_exams']['department_short'];
        $this->Template->langDepartmentLong = $GLOBALS['TL_LANG']['tl_exams']['department'];
        $this->Template->langDepartment1 = $GLOBALS['TL_LANG']['tl_exams']['department1'];
        $this->Template->langDepartment2 = $GLOBALS['TL_LANG']['tl_exams']['department2'];
        $this->Template->langDepartment3 = $GLOBALS['TL_LANG']['tl_exams']['department3'];
        $this->Template->langDepartment4 = $GLOBALS['TL_LANG']['tl_exams']['department4'];
        $this->Template->langDepartment5 = $GLOBALS['TL_LANG']['tl_exams']['department5'];
        $this->Template->langDepartment6 = $GLOBALS['TL_LANG']['tl_exams']['department6'];
        $this->Template->langDepartment7 = $GLOBALS['TL_LANG']['tl_exams']['department7'];
        $this->Template->langDepartment8 = $GLOBALS['TL_LANG']['tl_exams']['department8'];
        $this->Template->langDepartment9 = $GLOBALS['TL_LANG']['tl_exams']['department9'];
        $this->Template->langDepartment10 = $GLOBALS['TL_LANG']['tl_exams']['department10'];
        $this->Template->langDepartment11 = $GLOBALS['TL_LANG']['tl_exams']['department11'];
        $this->Template->langDepartment12 = $GLOBALS['TL_LANG']['tl_exams']['department12'];
        $this->Template->langDepartment13 = $GLOBALS['TL_LANG']['tl_exams']['department13'];
        $this->Template->langDepartment14 = $GLOBALS['TL_LANG']['tl_exams']['department14'];
        $this->Template->langTools = $GLOBALS['TL_LANG']['tl_exams']['tools'][0];
        $this->Template->langStatus = $GLOBALS['TL_LANG']['tl_exams']['status'][0];
        $this->Template->langStatus1 = $GLOBALS['TL_LANG']['tl_exams']['status1'];
        $this->Template->langStatus2 = $GLOBALS['TL_LANG']['tl_exams']['status2'];
        $this->Template->langStatus3 = $GLOBALS['TL_LANG']['tl_exams']['status3'];
        $this->Template->langStatus4 = $GLOBALS['TL_LANG']['tl_exams']['status4'];
        $this->Template->langStatus5 = $GLOBALS['TL_LANG']['tl_exams']['status5'];
        $this->Template->langStatus6 = $GLOBALS['TL_LANG']['tl_exams']['status6'];
        $this->Template->langSupervisors = $GLOBALS['TL_LANG']['tl_exams']['supervisors'];
        $this->Template->langAttendees = $GLOBALS['TL_LANG']['tl_exams']['attendees'];
        $this->Template->langRemarks = $GLOBALS['TL_LANG']['tl_exams']['remarks'][0];
        $this->Template->langHour = $GLOBALS['TL_LANG']['miscellaneous']['timeHour'];

        $this->Template->langSaveChanges = $GLOBALS['TL_LANG']['miscellaneous']['saveChanges'];
    }

    public function setLangValuesEditAttendees()
    {
        $this->Template->langShowAttendees = $GLOBALS['TL_LANG']['miscellaneous']['show_Attendees'];
        $this->Template->langExam = $GLOBALS['TL_LANG']['miscellaneous']['exam'];
        $this->Template->headerFirstname = $GLOBALS['TL_LANG']['tl_member']['firstname'][0];
        $this->Template->headerLastname = $GLOBALS['TL_LANG']['tl_member']['lastname'][0];
        $this->Template->headerSeat = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat'][0];
        $this->Template->headerWritingAssistance = $GLOBALS['TL_LANG']['tl_attendees_exams']['assistant'];
        $this->Template->headerStatus = $GLOBALS['TL_LANG']['tl_attendees_exams']['status'][0];
        $this->Template->noAttendeeExam = $GLOBALS['TL_LANG']['miscellaneous']['noAttendeeExam'];

        $this->Template->imgAltViewAttendeeDetails = $GLOBALS['TL_LANG']['miscellaneous']['imgAltViewAttendeeDetails'];
        $this->Template->imgAltEditAttendeeDetails = $GLOBALS['TL_LANG']['miscellaneous']['imgAltEditAttendeeDetails'];
        $this->Template->imgAltDeleteAttendee = $GLOBALS['TL_LANG']['miscellaneous']['imgAltDeleteAttendee'];

        $this->Template->linkTitleViewAttendeeDetails = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleViewAttendeeDetails'];
        $this->Template->linkTitleEditAttendeeDetails = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleEditAttendeeDetails'];
        $this->Template->linkTitleDeleteAttendee = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleDeleteAttendee'];
    }

    public function setExamValuesEdit($examData)
    {
        $this->Template->title = $examData->title;
        $this->Template->date = date("Y-m-d", $examData->date);
        $this->Template->begin = date("H:i", $examData->date);
        $this->Template->regularDuration = $examData->duration;
        $this->Template->examDepartment = $examData->department;
        $this->Template->status = $examData->status;
        $this->Template->tools = $examData->tools;
        $this->Template->remarks = $examData->remarks;
        $this->Template->lecturerTitle = $examData->lecturer_title;
        $this->Template->lecturerFirstname = $examData->lecturer_prename;
        $this->Template->lecturerLastname = $examData->lecturer_lastname;
        $this->Template->lecturerEmail = $examData->lecturer_email;
        $this->Template->lecturerMobile = $examData->lecturer_mobile;
    }

    public function saveExamChanges($examID)
    {
        // Wird über Model gelöst
        $exam = ExamsModel::findBy('id', $examID);
        $id = $exam->id;
        // set the values
        $exam->title = \Input::post('title');

        $exam_date = \Input::post('date');
        $exam_begin = \Input::post('begin');
        // Datum um Beginn verknüpfen und in Variable für Datenbank schreiben
        $exam_datetime = $exam_date;
        $exam_datetime .= " ";
        $exam_datetime .= $exam_begin;
        $exam_datetime = strtotime($exam_datetime);
        $exam->date = $exam_datetime;
        $exam->begin = $exam_begin;
        $exam->duration = \Input::post('regularDuration');
        $exam->department = \Input::post('department');
        $exam->status = \Input::post('status');
        $exam->tools = \Input::post('tools');
        $exam->remarks = \Input::post('remarks');
        $exam->lecturer_title = \Input::post('lecturerTitle');
        $exam->lecturer_prename = \Input::post('lecturerFirstname');
        $exam->lecturer_lastname = \Input::post('lecturerLastname');
        $exam->lecturer_email = \Input::post('lecturerEmail');
        $exam->lecturer_mobile = \Input::post('lecturerMobile');

        // update the record in the database
        if ($exam->save()) {
            $this->Template->changesSaved = true;
            $this->Template->changesSavedMessage = $GLOBALS['TL_LANG']['miscellaneous']['changesSavedMessage'];
        }
    }

    // Alle Klausurteilnehmer anzeigen
    public function showAttendeeList($exam)
    {
        $examData = ExamsModel::findBy('id', $exam);
        $this->Template->examTitle = $examData->title;
        $this->Template->examID = $exam;
        $result = Database::getInstance()->prepare("SELECT
                                                        tl_member.firstname, tl_member.lastname, tl_member.id,
                                                        tl_attendees_exams.seat, tl_attendees_exams.status, tl_attendees_exams.rehab_devices,
                                                        tl_attendees_exams.assistant_id
                                                        FROM tl_member, tl_exams, tl_attendees_exams
                                                        WHERE tl_member.id=tl_attendees_exams.attendee_id
                                                        AND tl_attendees_exams.exam_id = tl_exams.id
                                                        AND tl_exams.id = $exam
                                                        ")->query();
        $i = 0;
        $attendeeData = array();
        while ($result->next()) {
            // Variablen für das Template setzen
            $attendeeData[$i]['id'] = $result->id;
            $attendeeData[$i]['firstname'] = $result->firstname;
            $attendeeData[$i]['lastname'] = $result->lastname;
            // Sitzplatz ausgeben  -> Default: Kein Sitzplatz
            $attendeeData[$i]['seat'] = $GLOBALS['TL_LANG']['tl_attendees_exams']['no_seat'];
            if (!empty($result->seat)) {
                $attendeeData[$i]['seat'] = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->seat];
            }

            $attendeeData[$i]['status'] = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->status][0];

            // Überprüfen, ob eine Schreibassistenz benötigt wird -> Default: Keine Schreibassistenz
            $attendeeData[$i]['writingAssistance'] = $GLOBALS['TL_LANG']['miscellaneous']['writingAssistanceNotRequired'];
            if (!empty($result->assistant_id) && ($result->assistant_id != 0)) {

                $supervisorsExamData = SupervisorsExamsModel::findBy('id', $result->assistant_id);
                $assistantData = MemberModel::findBy('id', $supervisorsExamData->supervisor_id);
                $assistant = $assistantData->firstname;
                $assistant .= " ";
                $assistant .= $assistantData->lastname;
                $attendeeData[$i]['writingAssistance'] = $assistant;
            }
        }
        $this->Template->attendeeDataList = $attendeeData;
        $this->setLangValuesEditAttendees();
    }

    // Teilnehmerdetails anzeigen
    public function showAttendee($exam)
    {
        $this->Template->showAttendeeDetails = true;
        $examID = $exam;
        $attendeeID = $_GET['showAttendee'];
        $this->setShowEditAttendeeLangValues();
        $this->setShowEditAttendeeValues($examID, $attendeeID);
    }

    // Teilnehmer löschen
    public function deleteAttendee($exam)
    {
        $this->Template->showDeleteAttendeeConfirmation = true;
        $examID = $exam;
        $attendeeID = $_GET['deleteAttendee'];
        $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['deleteAttendeeConfirmationQuestion'];
        $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['deleteAttendeeConfirmationYes'];
        $this->Template->confirmationNo = $GLOBALS['TL_LANG']['miscellaneous']['deleteAttendeeConfirmationNo'];

        if ($_GET['confirmed'] == "yes") {
            if ($deleteAttendee = $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE exam_id=$examID AND attendee_id=$attendeeID")->execute()->affectedRows) {
                \Controller::redirect('klausurverwaltung/klausurverwaltung.html?do=editAttendees&exam=' . $examID);
            }
        } elseif ($_GET["confirmed"] == "no") {
            \Controller::redirect('klausurverwaltung/klausurverwaltung.html?do=editAttendees&exam=' . $examID);
        }
    }

    // Teilnehmer bearbeiten
    public function editAttendee($exam)
    {
        $this->Template->editAttendees = true;
        $this->Template->showEditAttendeeForm = true;
        $examID = $exam;
        $attendeeID = $_GET['editAttendee'];
        $this->setShowEditAttendeeLangValues();
        $this->setShowEditAttendeeValues($examID, $attendeeID);

    }

    public function saveAttendeeChanges($examID, $attendeeID) {
        $attendeeExam = AttendeesExamsModel::findBy(['exam_id = ?', 'attendee_id = ?'], [$examID, $attendeeID]);
        $id = $attendeeExam->id;
        $ae_id = $id;

        $addSupervisor = false;
        $deleteSupervisor = false;
        $updateSupervisor = false;

        $attendeeExam->seat = \Input::post('seat');
        $rehab_devices = \Input::post('rehab_devices');
        $attendeeExam->rehab_devices = serialize($rehab_devices);
        $attendeeExam->rehab_devices_others = \Input::post('rehab_devices_others');
        $extraTime = \Input::post('extra_time');
        if (empty($extraTime) || !is_numeric($extraTime)) $extraTime = 0;
        $attendeeExam->extra_time = $extraTime;
        $extraTimeValue = \Input::post('extra_time_minutes_percent');
        $attendeeExam->extra_time_minutes_percent = $extraTimeValue;
        $attendeeExam->status = \Input::post('status');

        // Werte von Select-Feld "Schreibassistenz" trennen
        $writingAssistance = \Input::post('writingAssistance');
        $writingAssistance_explode = explode('|', $writingAssistance);
        $assistantID = $writingAssistance_explode[0];
        $supervisors_exams_ID = $writingAssistance_explode[1];

        // Die ID der Schreibassistenz in einer Variable speichern, um sie aktualisieren oder aus der Tabelle löschen zu können
        $supervisorData = SupervisorsExamsModel::findBy('id', $attendeeExam->assistant_id);
        $actualSupervisorID = $supervisorData->id;

        if ($actualSupervisorID == 0 && $assistantID <> 0) {
            $addSupervisor = true;
        }
        elseif ($actualSupervisorID > 0 && $assistantID == 0) {
            $deleteSupervisor = true;
        }
        elseif ($actualSupervisorID > 0 && $assistantID <> 0) {
            $updateSupervisor = true;
        }

        // Eintrag in der Datenbank aktualisieren
        if ($attendeeExam->save()) {

            /* Schreibassistenz in die Datenbank hinzufügen, aktualisieren oder löschen */
            $this->import('Database');
            // Klausurdatum & Uhrzeit holen
            $examData = ExamsModel::findBy('id', $examID);
            $fullTimestampExam = $examData->date;
            $dateStringExam = date("d.m.Y", $fullTimestampExam);
            $dateTimestampExam = strtotime($dateStringExam);
            $timeStringExamBegin = date("H:i", $fullTimestampExam);
            $examDuration = $examData->duration;
            // "Zeit bis" aus Klausurdauer + Zeitverlängerung berechnen
            if ($extraTimeValue == "percent") {
                $examDurationExtraTime = $examDuration + ($examDuration*($extraTime/100));
            }
            elseif ($extraTimeValue == "minutes") {
                $examDurationExtraTime = $examDuration + $extraTime;
            }
            else {
                $examDurationExtraTime = $examDuration;
            }
            $examDurationExtraTimeInSeconds = $examDurationExtraTime*60;
            $timeExamEnd = $fullTimestampExam + $examDurationExtraTimeInSeconds;
            $timeStringExamEnd = date("G:i", $timeExamEnd);

            $text = "";

            // Schreibassistenz hinzufügen
            if ($addSupervisor === true) {
                $set = array('tstamp' => time(), 'supervisor_id' => $assistantID, 'date' => $dateTimestampExam, 'time_from' => $timeStringExamBegin, 'time_until' => $timeStringExamEnd, 'task' => 'Schreibassistenz');
                // Eintrag in Tabelle "tl_supervisors_exams" vornehmen
                $this->Database->prepare("INSERT INTO tl_supervisors_exams %s")->set($set)->execute();
                // Eingetragene ID herausfinden und in Eintrag von tl_attendees_exams eintragen
                $latestSupervisorsExamsData = Database::getInstance()->prepare("SELECT MAX(id) AS latestID FROM tl_supervisors_exams")->query();
                $latestSupervisorsExamsID = $latestSupervisorsExamsData->latestID;
                $set = array('assistant_id' => $latestSupervisorsExamsID);
                $this->Database->prepare("UPDATE tl_attendees_exams %s WHERE id=$ae_id")->set($set)->execute();
                $text.="Hinzugefügt";
            }
            // Schreibassistenz löschen
            if ($deleteSupervisor === true) {
                $this->Database->prepare("DELETE FROM tl_supervisors_exams WHERE id=$actualSupervisorID")->execute()->affectedRows;
                // Eintrag in tl_attendees_exams aktualisieren
                $set = array('assistant_id' => 0);
                $this->Database->prepare("UPDATE tl_attendees_exams %s WHERE id=$ae_id")->set($set)->execute();
                $text.="Gelöscht";
            }
            // Schreibassistenz aktualisieren
            if ($updateSupervisor === true) {
                $set = array('tstamp' => time(), 'supervisor_id' => $assistantID, 'date' => $dateTimestampExam, 'time_from' => $timeStringExamBegin, 'time_until' => $timeStringExamEnd, 'task' => 'Schreibassistenz');
                $this->Database->prepare("UPDATE tl_supervisors_exams %s WHERE id=$supervisors_exams_ID")->set($set)->execute();
                // Eintrag in tl_attendees_exams aktualisieren
                $set = array('assistant_id' => $actualSupervisorID);
                $this->Database->prepare("UPDATE tl_attendees_exams %s WHERE id=$ae_id")->set($set)->execute();
                $text.="Aktualisiert";
            }

            $this->Template->attendeeChangesSaved = true;
            $this->Template->examID = $examID;
            $this->Template->changesSavedMessage = $GLOBALS['TL_LANG']['miscellaneous']['changesSavedMessage'] . $text;
            $this->Template->linktextBackToAttendeeAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linktextBackToAttendeeAdministration'];
        }
    }

    public function setShowEditAttendeeValues($examID, $attendeeID) {

        $result = Database::getInstance()->prepare("SELECT
                                                    tl_member.firstname, tl_member.lastname, tl_member.username, tl_member.id, tl_member.contact_person,
                                                    tl_attendees_exams.seat, tl_attendees_exams.extra_time, tl_attendees_exams.extra_time_minutes_percent,
                                                    tl_attendees_exams.rehab_devices, tl_attendees_exams.rehab_devices_others, tl_attendees_exams.status,
                                                    tl_attendees_exams.assistant_id
                                                    FROM tl_member, tl_attendees_exams
                                                    WHERE tl_member.id=$attendeeID
                                                    AND tl_attendees_exams.exam_id = $examID
                                                    AND tl_attendees_exams.attendee_id=$attendeeID
                                                    ")->query();

        $examData = ExamsModel::findBy('id', $examID);
        $this->Template->examTitle = $examData->title;
        $this->Template->examID = $examID;
        $this->Template->username = $result->username;
        $this->Template->firstname = $result->firstname;
        $this->Template->lastname = $result->lastname;
        $this->Template->contactPerson = $GLOBALS['TL_LANG']['tl_member'][$result->contact_person];
        $this->Template->seat = "noseat";
        if (!empty($result->seat)) {
            $this->Template->seat = $result->seat;
        }

        $assistantID = $result->assistant_id;

        $this->Template->detailSeat = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->seat];

        $rehab_devices = unserialize($result->rehab_devices);
        $this->Template->rehab_devices = $rehab_devices;

        // Sprachvariablen in Array "REHA-Tools" einsetzen
        for ($i=0; $i < sizeof($rehab_devices); $i++) {
            $rehab_devices[$i] = $GLOBALS['TL_LANG']['tl_member'][$rehab_devices[$i]];
        }
        $this->Template->detailRehabDevices = $rehab_devices;

        $this->Template->rehabDevicesOthers = $result->rehab_devices_others;

        // Aufsichten für Feld "Schreibassistenz" heraussuchen und in Array einsetzen
        $supervisors = MemberModel::findBy('usertype', 'Aufsicht');
        $i = 0;
        $writingAssistance = array();
        foreach ($supervisors as $supervisor) {
            $writingAssistance[$i]["member_id"] = $supervisor->id;
            $writingAssistance[$i]["assistant_id"] = $result->assistant_id;;
            $writingAssistance[$i]["firstname"] = $supervisor->firstname;
            $writingAssistance[$i]["lastname"] = $supervisor->lastname;
            $i++;
        }
        $this->Template->writingAssistanceList = $writingAssistance;

        $this->Template->extraTime = $result->extra_time;
        $this->Template->extraTimeUnit = $result->extra_time_minutes_percent;

        $this->Template->detailExtraTime =  $result->extra_time;
        $this->Template->detailExtraTime .=  " ";
        $this->Template->detailExtraTime .=  $GLOBALS['TL_LANG']['tl_member'][$result->extra_time_minutes_percent];

        $this->Template->status = $result->status;
        $this->Template->detailStatus = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->status][0];
    }

    public function setShowEditAttendeeLangValues() {
        $this->Template->langEditAttendee = $GLOBALS['TL_LANG']['miscellaneous']['edit_Attendee'];
        $this->Template->langAttendeeDetails = $GLOBALS['TL_LANG']['miscellaneous']['details_Attendee'];
        $this->Template->langExam = $GLOBALS['TL_LANG']['miscellaneous']['exam'];

        $this->Template->langUsername = $GLOBALS['TL_LANG']['tl_member']['username'][0];
        $this->Template->langFirstname = $GLOBALS['TL_LANG']['tl_member']['firstname'][0];
        $this->Template->langLastname = $GLOBALS['TL_LANG']['tl_member']['lastname'][0];
        $this->Template->langContactPerson = $GLOBALS['TL_LANG']['tl_member']['contact_person'][0];

        $this->Template->langSeat = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat'][0];
        $this->Template->langNoSeat = $GLOBALS['TL_LANG']['tl_attendees_exams']['no_seat'];
        $this->Template->langSeat1 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat1'];
        $this->Template->langSeat2 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat2'];
        $this->Template->langSeat3 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat3'];
        $this->Template->langSeat4 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat4'];
        $this->Template->langSeat5 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat5'];
        $this->Template->langSeat6 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat6'];
        $this->Template->langSeat7 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat7'];
        $this->Template->langSeat8 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat8'];
        $this->Template->langSeat9 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat9'];
        $this->Template->langSeat10 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat10'];
        $this->Template->langSeat11 = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat11'];


        $this->Template->langRehabDevices = $GLOBALS['TL_LANG']['tl_attendees_exams']['rehab_devices'][0];
        $this->Template->langPC = $GLOBALS['TL_LANG']['tl_member']['pc'];
        $this->Template->langBlindWorkspace = $GLOBALS['TL_LANG']['tl_member']['blind workspace'];
        $this->Template->langZoomtext = $GLOBALS['TL_LANG']['tl_member']['Zoomtext'];
        $this->Template->langScreenMagnifier = $GLOBALS['TL_LANG']['tl_member']['screen magnifier'];
        $this->Template->langScreenReader = $GLOBALS['TL_LANG']['tl_member']['screen reader'];
        $this->Template->langA3Print = $GLOBALS['TL_LANG']['tl_member']['a3 print'];
        $this->Template->langObscuration = $GLOBALS['TL_LANG']['tl_member']['obscuration'];
        $this->Template->langWritingAssistance = $GLOBALS['TL_LANG']['tl_member']['writing assistance'];
        $this->Template->langHighTable = $GLOBALS['TL_LANG']['tl_member']['high table'];
        $this->Template->langNearDoor = $GLOBALS['TL_LANG']['tl_member']['near door'];
        $this->Template->langOwnRoom = $GLOBALS['TL_LANG']['tl_member']['own room'];
        $this->Template->langRDDifferent = $GLOBALS['TL_LANG']['tl_member']['different'];
        $this->Template->langRehabDevicesOthers = $GLOBALS['TL_LANG']['tl_attendees_exams']['rehab_devices_others'][0];

        $this->Template->langWritingAssistance = $GLOBALS['TL_LANG']['tl_attendees_exams']['assistant'];

        $this->Template->langExtraTime = $GLOBALS['TL_LANG']['tl_member']['extra_time'][0];
        $this->Template->langExtraTimeUnit = $GLOBALS['TL_LANG']['tl_member']['extra_time_minutes_percent'][0];
        $this->Template->langExtraTimeMinutes = $GLOBALS['TL_LANG']['tl_member']['minutes'];
        $this->Template->langExtraTimePercent = $GLOBALS['TL_LANG']['tl_member']['percent'];

        $this->Template->langStatus = $GLOBALS['TL_LANG']['tl_attendees_exams']['status'][0];
        $this->Template->langStatusInProgress = $GLOBALS['TL_LANG']['tl_attendees_exams']['in_progress'][0];
        $this->Template->langStatusConfirmed = $GLOBALS['TL_LANG']['tl_attendees_exams']['confirmed'][0];

        $this->Template->langSaveChanges = $GLOBALS['TL_LANG']['miscellaneous']['saveChanges'];
    }
}
