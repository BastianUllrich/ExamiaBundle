<?php

namespace Baul\ExamiaBundle\Module;
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
        $this->loadLanguageFile('tl_exams');

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;

        $this->Template->showDetails = false;
        $this->Template->showExamDetails = false;

        // Alle Klausurdaten laden, die der Person zugeordenet sind und ab dem aktuellen Tag um Mitternacht gelten, gruppiert nach Datum und Aufgabe, sortiert nach Datum
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

        if ($_GET["do"] == "showDetails") {
            $detailsDate = $_GET["date"];
            $this->showDetails($detailsDate);
        }
    }

    public function showDetails($detailsDate) {
        $this->Template->showDetails = true;
        $detailsDateEnd = $detailsDate + 86399;

        // Zusätzliche Sprachvariablen setzen
        $this->Template->langExamTitle = $GLOBALS['TL_LANG']['miscellaneous']['exam'];

        $this->Template->langNrAttendees = $GLOBALS['TL_LANG']['miscellaneous']['nrAttendees'];
        $this->Template->langBegin = $GLOBALS['TL_LANG']['tl_exams']['time_begin'][0];
        $this->Template->langDuration = $GLOBALS['TL_LANG']['tl_exams']['exam_reg_duration'];
        $this->Template->langLatestEnding = $GLOBALS['TL_LANG']['tl_exams']['max_ending'];

        // Klausurabfrage
        $result = Database::getInstance()->prepare("SELECT id, title, department, date, begin, duration
                                                    FROM tl_exams 
                                                    WHERE date
                                                    BETWEEN $detailsDate
                                                    AND $detailsDateEnd
                                                    ORDER BY date ASC
                                                    ")->query();
        $examData = array();
        $i = 0;
        while ($result->next()) {
            // Variablen für das Template setzen
            $examData[$i]['id'] = $result->id;
            $examData[$i]['title'] = $result->title;
            $examData[$i]['department'] = str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_exams'][$result->department], 0, 5)));
            $examData[$i]['dateReadable'] = date("d.m.Y", $result->date);
            $examData[$i]['begin'] = $result->begin;
            $examData[$i]['duration'] = $result->duration;
            $numberOfAttendees = Database::getInstance()->prepare("SELECT COUNT(*) AS 'nrOfAttendees' FROM tl_attendees_exams WHERE exam_id = $result->id")->query();
            $examData[$i]['nrOfAttendees']  = $numberOfAttendees->nrOfAttendees;

            // Maximale Dauer in Minuten berechnen
            $endTimeQuestion = Database::getInstance()->prepare("SELECT extra_time, extra_time_minutes_percent FROM tl_attendees_exams WHERE exam_id=$result->id")->query();
            $i = 0;
            $maxDuration = $result->duration;
            while ($endTimeQuestion->next()) {
                if ($endTimeQuestion->extra_time_minutes_percent == "percent") {
                    $multiplicator = 1 + ($endTimeQuestion->extra_time / 100);
                    $duration = ($result->duration) * $multiplicator;
                } elseif ($endTimeQuestion->extra_time_minutes_percent == "minutes") {
                    $duration = ($result->duration) + $endTimeQuestion->extra_time;
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

}
