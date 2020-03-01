<?php

namespace Baul\ExamiaBundle\Module;
use Baul\ExamiaBundle\Model\AttendeesExamsModel;
use Baul\ExamiaBundle\Model\ExamsModel;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;
use Baul\ExamiaBundle\Model\SupervisorsExamsModel;

class SupervisorOverviewModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_supervisorOverview';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['supervisorOverview'][0]) . ' ###';
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
        $this->loadLanguageFile('tl_supervisors_exams');
        $this->loadLanguageFile('tl_attendees_exams');
        $this->loadLanguageFile('tl_exams');

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;

        $this->Template->showDetails = false;
        $this->Template->showAttendeeDetails = false;

        // Alle Klausurdaten laden, die der Person zugeordenet sind und ab dem aktuellen Tag um Mitternacht gelten, gruppiert nach Datum und Aufgabe, sortiert nach Datum
        // Aufgrund der Gruppierung nicht über Model / Collection
        $todayMidnight = strtotime(date("d.m.Y"));
        $result = Database::getInstance()->prepare("SELECT date, time_from, time_until, task
                                                    FROM tl_supervisors_exams 
                                                    WHERE supervisor_id = $userID
                                                    AND date > $todayMidnight
                                                    GROUP BY date, task
                                                    ORDER BY date ASC
                                                   ")->query();
        $supervisorData = array();
        $i = 0;

        while ($result->next()) {
            // Variablen für das Template setzen
            $supervisorData[$i]['dateReadable'] = date("d.m.Y", $result->date);
            $supervisorData[$i]['time'] = $result->date;
            // Beginn & Ende der Aufsicht/Schreibassistenz aus Datenbank lesen
            // Aufgrund der speziellen Abfrage nicht über Model / Collection
            $min_time_question = Database::getInstance()->prepare("SELECT MIN(time_from) AS 'mintime' FROM tl_supervisors_exams WHERE supervisor_id = $userID AND date = $result->date AND task = '$result->task'")->query();
            $max_time_question = Database::getInstance()->prepare("SELECT MAX(time_until) AS 'maxtime' FROM tl_supervisors_exams WHERE supervisor_id = $userID AND date = $result->date AND task = '$result->task'")->query();
            $supervisorData[$i]['begin'] = $min_time_question->mintime;
            $supervisorData[$i]['end'] = $max_time_question->maxtime;
            $supervisorData[$i]['task'] = $result->task;
            $i++;
        }
        $this->Template->supervisorDataList = $supervisorData;

        $this->Template->langSupervisorOverview = $GLOBALS['TL_LANG']['miscellaneous']['supervisorOverview'];
        $this->Template->langDate = $GLOBALS['TL_LANG']['miscellaneous']['date'];
        $this->Template->langTimeFrom = $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_from'][0];
        $this->Template->langTimeUntil = $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_until'][0];
        $this->Template->langTask = $GLOBALS['TL_LANG']['miscellaneous']['task'];
        $this->Template->langDetails = $GLOBALS['TL_LANG']['miscellaneous']['details'];
        $this->Template->langShowDetails = $GLOBALS['TL_LANG']['miscellaneous']['show_Details'];
        $this->Template->linkTitleShowExamsDetails = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleShowExamsDetails'];

        if (\Input::get("do") == "showDetails") {
            $detailsDate = \Input::get("date");
            $this->showDetails($detailsDate);
        }

        if (\Input::get("show") == "attendees") {
            $examId = \Input::get("examid");
            $this->showAttendeeDetails($examId);
        }
    }

    public function showDetails($detailsDate) {
        $this->Template->showDetails = true;
        $detailsDateEnd = $detailsDate + 86399;

        // Zusätzliche Sprachvariablen setzen
        $this->Template->langExams = $GLOBALS['TL_LANG']['miscellaneous']['exams'];
        $this->Template->langExamTitle = $GLOBALS['TL_LANG']['miscellaneous']['exam'];
        $this->Template->langNrAttendees = $GLOBALS['TL_LANG']['miscellaneous']['nrAttendees'];
        $this->Template->langBegin = $GLOBALS['TL_LANG']['tl_exams']['time_begin'][0];
        $this->Template->langDuration = $GLOBALS['TL_LANG']['tl_exams']['exam_reg_duration'];
        $this->Template->langLatestEnding = $GLOBALS['TL_LANG']['tl_exams']['max_ending'];
        $this->Template->dateReadable = date("d.m.Y", $detailsDate);
        $this->Template->langShowAttendeeDetails = $GLOBALS['TL_LANG']['miscellaneous']['details_Attendee'];
        $this->Template->linkTitleShowAttendees = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleShowAttendees'];
        $this->Template->langNoDataAvailable = $GLOBALS['TL_LANG']['miscellaneous']['noDataAvailable'];
        $this->Template->langBackToSupervisorOverview = $GLOBALS['TL_LANG']['miscellaneous']['backToSupervisorOverview'];
        $this->Template->linkTitleBackToSupervisorOverview = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleBackToSupervisorOverview'];
        $this->Template->linktextBack = $GLOBALS['TL_LANG']['miscellaneous']['linktextBack'];
        $this->Template->linkTitleBack = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleBack'];

        // Klausurenabfrage
        $options = [
            'order' => 'date ASC'
        ];
        $results = ExamsModel::findBy(['date BETWEEN ?', '?'], [$detailsDate, $detailsDateEnd], $options);
        $examData = array();
        $i = 0;
        foreach ($results AS $result) {
        //while ($result->next()) {
            // Variablen für das Template setzen
            $examData[$i]['id'] = $result->id;
            $examData[$i]['title'] = $result->title;

            // Verkürzte Schreibweise für den Fachbereich, außer bei JLU
            // Bei ZDH muss anders gekürzt werden
            if ($result->department != "department13" && $result->department != "department14") {
                $department_whitespaces = explode("-", $GLOBALS['TL_LANG']['tl_exams'][$result->department]);
                $department = trim($department_whitespaces[1]);
                $examData[$i]['department'] = $department;
            }
            else {
                $examData[$i]['department'] = str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_exams'][$result->department], 0, 5)));
            }

            $examData[$i]['dateReadable'] = date("d.m.Y", $result->date);
            $examData[$i]['begin'] = $result->begin;
            $examData[$i]['duration'] = $result->duration;
            // Anzahl der Teilnehmer aus Datenbank abfragen
            $numberOfAttendees = AttendeesExamsModel::countBy('exam_id', $result->id);
            $examData[$i]['nrOfAttendees'] = $numberOfAttendees;

            // Maximale Dauer in Minuten berechnen
            $endTimeQuestion = AttendeesExamsModel::findBy("exam_id", $result->id);
            $maxDuration = $result->duration;
            foreach ($endTimeQuestion as $endTime) {
                if ($endTime->extra_time_unit == "percent") {
                    $multiplicator = 1 + ($endTime->extra_time / 100);
                    $duration = ($result->duration) * $multiplicator;
                } elseif ($endTime->extra_time_unit == "minutes") {
                    $duration = ($result->duration) + $endTime->extra_time;
                }
                if ($duration > $maxDuration) {
                    $maxDuration = $duration;
                }
            }
            // Späteste Endzeit berechnen
            $maxEndTime = ($result->date) + ($maxDuration * 60);
            $maxEndTimeReadable = date("H:i", $maxEndTime);
            $examData[$i]['maxEndTime'] = $maxEndTimeReadable;
            $i++;
        }
        $this->Template->examDataList = $examData;
    }

    public function showAttendeeDetails($examID) {
        $this->Template->showAttendeeDetails = true;

        // Zusätzliche Sprachvariablen setzen
        $this->Template->langAttendeeDetails = $GLOBALS['TL_LANG']['miscellaneous']['attendeeDetails'];
        $this->Template->langSeat = $GLOBALS['TL_LANG']['tl_attendees_exams']['seat'][0];
        $this->Template->langShowRoomPlan = $GLOBALS['TL_LANG']['miscellaneous']['showRoomPlan'];
        $this->Template->langRehabTools = $GLOBALS['TL_LANG']['tl_attendees_exams']['rehab_devices'][0];
        $this->Template->langRehabToolsOthers = $GLOBALS['TL_LANG']['tl_attendees_exams']['rehab_devices_others'][0];
        $this->Template->langTimeAddition = $GLOBALS['TL_LANG']['tl_attendees_exams']['extra_time'][0];
        $this->Template->langEndTime = $GLOBALS['TL_LANG']['miscellaneous']['endTime'];

        // Klausurabfrage
        // Aufgrund der speziellen Abfrage nicht über Model / Collection
        $result = Database::getInstance()->prepare("SELECT tl_exams.title, tl_exams.begin, tl_attendees_exams.seat, tl_exams.duration, tl_exams.date,
                                                    tl_attendees_exams.rehab_devices, tl_attendees_exams.rehab_devices_others,  
                                                    tl_attendees_exams.extra_time, tl_attendees_exams.extra_time_unit
                                                    FROM tl_exams, tl_attendees_exams, tl_member
                                                    WHERE tl_exams.id = $examID
                                                    AND tl_attendees_exams.exam_id = $examID
                                                    AND tl_attendees_exams.attendee_id = tl_member.id
                                                    ORDER BY tl_attendees_exams.seat ASC
                                                    ")->query();
        $attendeeData = array();
        $i = 0;
        while ($result->next()) {
            // Variablen für das Template setzen
            if (empty($result->seat)) {
                $attendeeData[$i]['seat'] = $GLOBALS['TL_LANG']['tl_attendees_exams']["no_seat"];
            }
            else {
                $attendeeData[$i]['seat'] = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->seat];
            }
            $rehab_tools = unserialize($result->rehab_devices);
            for ($j=0; $j<sizeof($rehab_tools); $j++) {
                $rehab_tools[$j] = $GLOBALS['TL_LANG']['tl_attendees_exams'][$rehab_tools[$j]];
            }
            $attendeeData[$i]['rehabTools'] = $rehab_tools;
            $attendeeData[$i]['rehabToolsOthers'] = $result->rehab_devices_others;
            $attendeeData[$i]['extraTime'] = $result->extra_time;
            $attendeeData[$i]['extraTime'] .= " ";
            $attendeeData[$i]['extraTime'] .= $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->extra_time_unit];

            // Endzeit des Teilnehmers berechnen
            $duration = $result->duration;
            if ($result->extra_time_unit == "percent") {
                $multiplicator = 1 + ($result->extra_time / 100);
                $duration = $duration * $multiplicator;
            } elseif ($result->extra_time_unit == "minutes") {
                $duration = $duration + $result->extra_time;
            }
            $endTime = ($result->date) + ($duration * 60);
            $endTimeReadable = date("H:i", $endTime);
            $attendeeData[$i]['endTime'] = $endTimeReadable;

            $i++;
        }

        $this->Template->attendeeDataList = $attendeeData;

    }

}
