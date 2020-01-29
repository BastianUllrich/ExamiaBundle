<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;
use Baul\ExamiaBundle\Model\ExamsModel;
use Baul\ExamiaBundle\Model\MemberModel;
use Baul\ExamiaBundle\Model\AttendeesExamsModel;


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
        $this->Template->editAttendees = false;

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $this->Template->userID = $objUser->id;

        // Sprachvariablen setzen
        $this->Template->manage_exams = $GLOBALS['TL_LANG']['miscellaneous']['manage_exams'];

        $this->Template->headerDate = $GLOBALS['TL_LANG']['tl_exams']['date'][0];
        $this->Template->headerBegin = $GLOBALS['TL_LANG']['tl_exams']['time_begin'][0];
        $this->Template->headerExamTitle = $GLOBALS['TL_LANG']['tl_exams']['title_short'];
        $this->Template->headerDepartment = $GLOBALS['TL_LANG']['tl_exams']['department_short'];
        $this->Template->headerAction = $GLOBALS['TL_LANG']['miscellaneous']['action'];
        $this->Template->orderAltText =  $GLOBALS['TL_LANG']['miscellaneous']['orderAltText'];

        /* Deaktiviert -> alle Klausuren anzeigen, auch vergangene!
        // Heute 0 Uhr festlegen -> wichtig für Datenbankabfrage
        $today_midnight = date("d.m.Y");
        $today_midnight .= " ";
        $today_midnight .= "00:00:00";
        $today_midnight_time = strtotime($today_midnight);
        */

        // Daten der Klausuren aus der Datenbank laden, je nach Sortierung -> wegen Sortierung nicht über Model/Collection gelöst
        $this->import('Database');

        if ($_GET["orderBy"] == "dateDESC") {
            $result = Database::getInstance()->prepare("SELECT id, date, begin, title, department FROM tl_exams ORDER BY date DESC")->query();
            $this->Template->isOrderedBy = "dateDESC";
            $this->Template->orderByDateText = $GLOBALS['TL_LANG']['miscellaneous']['orderByDateASC'];
        }
        else {
            $result = Database::getInstance()->prepare("SELECT id, date, begin, title, department FROM tl_exams ORDER BY date ASC")->query();
            $this->Template->isOrderedBy = "dateASC";
            $this->Template->orderByDateText = $GLOBALS['TL_LANG']['miscellaneous']['orderByDateDESC'];
        }

        $i = 0;
        $examData = array();
        while ($result->next()) {
            // Variablen für das Template setzen
            $examData[$i]['date'] = date("d.m.Y", $result->date);
            $examData[$i]['begin'] = $result->begin;
            $examData[$i]['title'] = $result->title;
            // Verkürzte Schreibweise für den Fachbereich
            $examData[$i]['department'] = str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_exams'][$result->department],0,5)));
            $examData[$i]['id'] = $result->id;
            $i++;
        }
        $this->Template->examDataList = $examData;

        if ($_GET["do"] == "viewDetails") {
            $this->Template->showDetails = true;
            $exam = $_GET["exam"];
            $examDetails = ExamsModel::findBy('id', $exam);
            $this->setLangValuesViewEditDetails();
            $this->setExamValuesViewDetails($examDetails);
        }

        if ($_GET["do"] == "editDetails") {
            $this->Template->showEditForm = true;
            $exam = $_GET["exam"];
            $examDetails = ExamsModel::findBy('id', $exam);
            $this->setLangValuesViewEditDetails();
            $this->setExamValuesEdit($examDetails);
        }

        if (\Contao\Input::post('FORM_SUBMIT') == 'editExam') {
            $examID = $_GET["exam"];
            $this->saveExamChanges($examID);
        }

        if ($_GET["do"] == "editAttendees") {
            $this->Template->editAttendees = true;
            $this->Template->showEditAttendeeForm = false;
            $exam = $_GET["exam"];
            $examData = ExamsModel::findBy('id', $exam);
            $this->Template->examTitle = $examData->title;
            $this->Template->examID = $exam;
            $result = Database::getInstance()->prepare("SELECT 
                                                        tl_member.firstname, tl_member.lastname, tl_member.id,
                                                        tl_attendees_exams.seat, tl_attendees_exams.extra_time, tl_attendees_exams.extra_time_minutes_percent, tl_attendees_exams.rehab_devices
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
                $attendeeData[$i]['seat'] = $result->seat;
                $attendeeData[$i]['extraTime'] = $result->extra_time;
                $attendeeData[$i]['extraTime'] .= " ";
                $attendeeData[$i]['extraTime'] .= $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->extra_time_minutes_percent];
                // Überprüfen, ob eine Schreibassistenz benötigt wird
                $rehab_devices = unserialize($result->rehab_devices);
                for ($j = 0; $j < sizeof($rehab_devices); $j++) {
                    if ($rehab_devices[$j] == "own room") $attendeeData[$i]['writingAssistance'] = $GLOBALS['TL_LANG']['miscellaneous']['writingAssistanceRequired'];
                    else $attendeeData[$i]['writingAssistance'] = $GLOBALS['TL_LANG']['miscellaneous']['writingAssistanceNotRequired'];
                }
                $i++;
            }
            $this->Template->attendeeDataList = $attendeeData;

            $this->setLangValuesEditAttendees();
        }

        // Mitglied löschen
        if ($_GET["do"] == "delete") {
            $member = $_GET["member"];
            $this->Template->showConfirmationQuestion = true;
            $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['deleteMemberConfirmationQuestion'];
            $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['deleteMemberConfirmationYes'];
            $this->Template->confirmationNo = $GLOBALS['TL_LANG']['miscellaneous']['deleteMemberConfirmationNo'];

            // Verhindern, dass ein Administrator gelöscht wird
            $memberDeleteData = MemberModel::findBy('id', $member);
            if ($memberDeleteData->usertype == "Administrator") {
                \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
            }
            else {
                // Mitglied erst nach Bestätigung löschen
                if (($_GET["confirmed"] == "yes")) {

                    // Aufsichtsverteilung, Klausurzuweisung und ggf. Klausur aus Datenbank löschen

                    // KlausurIDs von Teilnahme auslesen und in Array speichern
                    $i = 0;
                    $examIDs = array();
                    $attendeesExams = AttendeesExamsModel::findBy('attendee_id', $member);
                    if (!is_null($attendeesExams)) {
                        while ($attendeesExams->next()) {
                            $examIDs[$i]['exam_id'] = $attendeesExams->exam_id;
                            $i++;
                        }

                        // Klausurteilnahmen des Mitglieds aus Datenbank löschen
                        $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE attendee_id=$member")->execute()->affectedRows;

                        // Klausuren aus Datenbank löschen, falls sie keinen Teilnehmer mehr haben
                        foreach ($examIDs as $exam_id) {
                            $exID = $exam_id['exam_id'];
                            $getAttendeesExam = AttendeesExamsModel::findBy('exam_id', $exID);
                            // Klausur aus Datenbank löschen, falls niemand mehr dafür angemeldet ist
                            if (empty($getAttendeesExam->exam_id)) {
                                $this->Database->prepare("DELETE FROM tl_exams WHERE id=$exID")->execute()->affectedRows;
                            }
                        }
                    }

                    // Aufsichtsverteilung des Mitglieds aus Datenbank löschen
                    $this->Database->prepare("DELETE FROM tl_supervisors_exams WHERE supervisor_id=$member")->execute()->affectedRows;

                    // Mitglied aus Datenbank löschen und zur Seite "Mitglieder verwalten" zurückkehren
                    if ($deleteMember = $this->Database->prepare("DELETE FROM tl_member WHERE id=$member")->execute()->affectedRows) {
                        \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
                    }
                }
                elseif ($_GET["confirmed"] == "no") {
                    \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
                }
            }
        }

    }

    public function setExamValuesViewDetails($examDetails) {
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
        $result = Database::getInstance()->prepare("SELECT extra_time, extra_time_minutes_percent FROM tl_attendees_exams WHERE exam_id=$examDetails->id")->query();
        $i = 0;
        $maxDuration = $examDetails->duration;
        while ($result->next()) {
            if ($result->extra_time_minutes_percent == "percent") {
                $multiplicator = 1+($result->extra_time/100);
                $duration = ($examDetails->duration)*$multiplicator;
            }
            elseif ($result->extra_time_minutes_percent == "minutes") {
                $duration = ($examDetails->duration)+$result->extra_time;
            }
            if ($duration > $maxDuration) {
                $maxDuration = $duration;
            }
        }
        // Späteste Endzeit berechnen
        $maxEndTime = ($examDetails->date) + ($maxDuration*60);
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
        $dayExamLastSecond = $dayExamMidnightTimeStamp + 86400;

        $result = Database::getInstance()->prepare("SELECT tl_member.firstname, tl_member.lastname, tl_supervisors_exams.time_from, tl_supervisors_exams.time_until, tl_supervisors_exams.task
                                                    FROM tl_member, tl_supervisors_exams
                                                    WHERE tl_supervisors_exams.supervisor_id=tl_member.id
                                                    AND tl_supervisors_exams.date
                                                    BETWEEN $dayExamMidnightTimeStamp
                                                    AND $dayExamLastSecond
                                                    ")->query();
        $i=0;
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
        $i=0;
        $attendeesData = array();
        while ($result->next()) {
            // Variablen für das Template setzen
            $attendeesData[$i]['firstname'] = $result->firstname;
            $attendeesData[$i]['lastname'] = $result->lastname;
            $attendeesData[$i]['extra_time'] = $result->extra_time;
            $attendeesData[$i]['extra_time_minutes_percent'] = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->extra_time_minutes_percent];
            $i++;
        }
        $this->Template->attendeesDataList = $attendeesData;

        $this->Template->detailRemarks = $examDetails->remarks;
    }

    public function setLangValuesViewEditDetails() {
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

    public function setLangValuesEditAttendees() {
        $this->Template->langShowAttendees = $GLOBALS['TL_LANG']['miscellaneous']['show_Attendees'] ;
        $this->Template->langExam = $GLOBALS['TL_LANG']['miscellaneous']['exam'];
        $this->Template->headerFirstname = $GLOBALS['TL_LANG']['tl_member']['firstname'][0];
        $this->Template->headerLastname = $GLOBALS['TL_LANG']['tl_member']['lastname'][0];
        $this->Template->headerSeat = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat'][0];
        $this->Template->headerWritingAssistance = $GLOBALS['TL_LANG']['tl_attendees_exams']['assistant'];
        $this->Template->headerExtraTime = $GLOBALS['TL_LANG']['tl_attendees_exams']['extra_time'][0];
    }

    public function setExamValuesEdit($examData) {
        $this->Template->title = $examData->title;
        $this->Template->date = date("Y-m-d", $examData->date);
        $this->Template->begin = $examData->begin;
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
        $date = \Input::post('date');
        $exam->date = strtotime($date);
        $exam->begin = \Input::post('begin');
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
        if($exam->save()) {
            $this->Template->changesSaved = true;
            $this->Template->changesSavedMessage = $GLOBALS['TL_LANG']['miscellaneous']['changesSavedMessage'];
            $this->Template->linktextBackToExamsAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linktextBackToExamsAdministration'];
        }
    }




}
