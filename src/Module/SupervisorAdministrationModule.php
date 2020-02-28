<?php

namespace Baul\ExamiaBundle\Module;
use Baul\ExamiaBundle\Model\AttendeesExamsModel;
use Baul\ExamiaBundle\Model\ExamsModel;
use Baul\ExamiaBundle\Model\MemberModel;
use Baul\ExamiaBundle\Model\SupervisorsExamsModel;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;

class SupervisorAdministrationModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_supervisorAdministration';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['supervisorAdministration'][0]) . ' ###';
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
        $this->loadLanguageFile('tl_exams');
        $this->loadLanguageFile('tl_supervisors_exams');

        // Variablen zur Bestimmung des anzuzeigenden Inhalts
        $this->Template->showDetails = false;
        $this->Template->deletePerson = false;

        // Sprachvariablen
        $this->Template->langSupervisorAdministration = $GLOBALS['TL_LANG']['miscellaneous']['supervisorAdministration'];
        $this->Template->langDate = $GLOBALS['TL_LANG']['miscellaneous']['date'];
        $this->Template->orderAltText = $GLOBALS['TL_LANG']['miscellaneous']['orderAltText'];
        $this->Template->langDetails = $GLOBALS['TL_LANG']['miscellaneous']['details'];
        $this->Template->langShowDetails = $GLOBALS['TL_LANG']['miscellaneous']['show_Details'];
        $this->Template->linkTitleShowExamDateDetails = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleShowExamDateDetails'];
        $this->Template->langNoDataAvailable = $GLOBALS['TL_LANG']['miscellaneous']['noDataAvailable'];

        // Alle Klausurdaten laden, die ab dem aktuellen Tag um Mitternacht gelten, sortiert nach Datum
        $todayMidnight = strtotime(date("d.m.Y"));

        // Sortierung nach Datum aufsteigend / absteigend
        if (\Input::get("orderBy") == "dateDESC") {
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

        // Datenbankabfrage nach Klausurdaten
        $results = ExamsModel::findBy(['date > ?'], [$todayMidnight], $options);
        $examsDataAllDates = array();
        $i=0;
        foreach ($results as $result) {
            // Variablen für das Template setzen
            $examDateReadable = date("d.m.Y", $result->date);
            $examDateTimeStamp = strtotime($examDateReadable);
            $examsDataAllDates[$i]['dateReadable'] = $examDateReadable;
            $examsDataAllDates[$i]['time'] = $examDateTimeStamp;
            $i++;
        }
        // Gruppierung nach Datum erfolgt über Überprüfung des Arrays, weil Klausurtimestamp aus Datum & Uhrzeit besteht
        $examsDataTmp = array_unique($examsDataAllDates, SORT_REGULAR);
        $examsData = array_intersect_key($examsDataAllDates, $examsDataTmp);
        $this->Template->examsDataList = $examsData;

        if (\Input::get("do") == "showDetails") {
            $this->showDetails();
        }

        if (\Input::get("action") == "delete") {
            $id = \Input::get("id");
            $date = \Input::get("date");
            $this->deleteSupervisor($id, $date);
        }

        // Formular wurde abgesendet
        if (\Contao\Input::post('FORM_SUBMIT') == 'addSupervisor') {
            $date = \Input::get("date");
            $this->addSupervisor($date);
        }
    }

    public function showDetails() {
        $this->Template->showDetails = true;
        $this->import('Database');

        // Sprachvariablen
        $this->Template->langEditSupervisors = $GLOBALS['TL_LANG']['miscellaneous']['editSupervisors'];
        $this->Template->langExamsAtThisDay = $GLOBALS['TL_LANG']['miscellaneous']['examsAtThisDay'];
        $this->Template->langExamTitle = $GLOBALS['TL_LANG']['tl_exams']['title_short'];
        $this->Template->langBegin = $GLOBALS['TL_LANG']['tl_exams']['time_begin_short'];
        $this->Template->langMaxEndTime = $GLOBALS['TL_LANG']['tl_exams']['max_ending'];
        $this->Template->langExamDepartment = $GLOBALS['TL_LANG']['tl_exams']['department_short'];
        $this->Template->langCurrentSupervisors = $GLOBALS['TL_LANG']['miscellaneous']['currentSupervisors'];
        $this->Template->langSupervisorName = $GLOBALS['TL_LANG']['miscellaneous']['supervisorName'];
        $this->Template->langTimeFrom = $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_from'][0];
        $this->Template->langTimeUntil = $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_until'][0];
        $this->Template->langTimeFormat = $GLOBALS['TL_LANG']['miscellaneous']['timeFormat'];
        $this->Template->langTimePeriod = $GLOBALS['TL_LANG']['miscellaneous']['timePeriod'];
        $this->Template->langTask = $GLOBALS['TL_LANG']['miscellaneous']['task'];
        $this->Template->langDelete = $GLOBALS['TL_LANG']['miscellaneous']['delete'];
        $this->Template->langNoSupervisorsAvailable = $GLOBALS['TL_LANG']['miscellaneous']['noSupervisorsAvailable'];
        $this->Template->langAddSupervisor = $GLOBALS['TL_LANG']['miscellaneous']['addSupervisor'];
        $this->Template->langSupervisor = $GLOBALS['TL_LANG']['miscellaneous']['supervisor'];
        $this->Template->langAssistant = $GLOBALS['TL_LANG']['miscellaneous']['writingAssistant'];
        $this->Template->langDoAddSupervisor = $GLOBALS['TL_LANG']['miscellaneous']['doAddSupervisor'];
        $this->Template->linktextBackToSupervisorAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linktextBackToSupervisorAdministration'];
        $this->Template->linkTitleBackToSupervisorAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleBackToSupervisorAdministration'];

        // Datenbankabfrage Klausuren am gewählten Tag
        $startTime = \Input::get("date");
        $endTime = $startTime + 86399;
        $results = ExamsModel::findBy(['date BETWEEN ?', '?'], [$startTime, $endTime]);
        $examData = array();
        $i = 0;
        foreach ($results as $result) {
            // Variablen für das Template setzen
            $examData[$i]['title'] = $result->title;

            // Verkürzte Schreibweise für den Fachbereich
            // Bei ZDH muss anders gekürzt werden
            if ($result->department != "department13" && $result->department != "department14") {
                $department_whitespaces = explode("-", $GLOBALS['TL_LANG']['tl_exams'][$result->department]);
                $examData[$i]['department'] = trim($department_whitespaces[1]);
            }
            else {
                $examData[$i]['department'] = str_ireplace("-", "", str_ireplace(" ", "", substr($GLOBALS['TL_LANG']['tl_exams'][$result->department], 0, 5)));
            }

            $examData[$i]['begin'] = $result->begin;

            /* Späteste Endzeit berechnen */
            // Maximale Dauer in Minuten berechnen
            $resultsEndTime = AttendeesExamsModel::findBy('exam_id', $result->id);
            $maxDuration = $result->duration;
            foreach ($resultsEndTime as $resultEndTime) {
                if ($resultEndTime->extra_time_unit == "percent") {
                    $multiplicator = 1 + ($resultEndTime->extra_time / 100);
                    $duration = ($result->duration) * $multiplicator;
                } elseif ($resultEndTime->extra_time_unit == "minutes") {
                    $duration = ($result->duration) + $resultEndTime->extra_time;
                }
                if ($duration > $maxDuration) {
                    $maxDuration = $duration;
                }
            }
            // Späteste Endzeit aus Beginn + maximaler Dauer berechnen
            $maxEndTime = ($result->date) + ($maxDuration * 60);
            $maxEndTimeReadable = date("H:i", $maxEndTime);
            $examData[$i]['maxEndTime'] = $maxEndTimeReadable;
        }
        $this->Template->examDataList = $examData;

        // Datenbankabfrage aktuell aufgeteilte Aufsichten
        // Aufgrund der speziellen Abfrage nicht über Model / Collection
        $result = Database::getInstance()->prepare("SELECT 
                                                    tl_member.firstname, tl_member.lastname, 
                                                    tl_supervisors_exams.id, tl_supervisors_exams.date, tl_supervisors_exams.time_from, tl_supervisors_exams.time_until, tl_supervisors_exams.task
                                                    FROM tl_supervisors_exams, tl_member
                                                    WHERE tl_supervisors_exams.date 
                                                    BETWEEN $startTime
                                                    AND $endTime
                                                    AND tl_member.id=tl_supervisors_exams.supervisor_id
                                                    ORDER BY tl_supervisors_exams.time_from
                                                    ")->query();
        $supervisorData = array();
        $j=0;
        while ($result->next()) {
            // Variablen für das Template setzen
            $supervisorData[$j]['date'] = date("d.m.Y", $result->date);
            $supervisorData[$j]['id'] = $result->id;
            $supervisorData[$j]['name'] = $result->firstname;
            $supervisorData[$j]['name'] .= " ";
            $supervisorData[$j]['name'] .= $result->lastname;
            $supervisorData[$j]['timePeriod'] = $result->time_from;
            $supervisorData[$j]['timePeriod'] .= " - ";
            $supervisorData[$j]['timePeriod'] .= $result->time_until;
            $supervisorData[$j]['task'] = $result->task;
            if ($result->task == "Aufsicht") {
                $supervisorData[$j]['linkTitleDelete'] = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleDeleteSupervisor'];
                $supervisorData[$j]['deleteText'] = $GLOBALS['TL_LANG']['miscellaneous']['deleteSupervisorText'];
            }
            if ($result->task == "Schreibassistenz") {
                $supervisorData[$j]['linkTitleDelete'] = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleDeleteAssistance'];
                $supervisorData[$j]['deleteText'] = $GLOBALS['TL_LANG']['miscellaneous']['deleteAssistanceText'];
            }
            $j++;
        }
        $this->Template->supervisorDataList = $supervisorData;
        $this->Template->date = $startTime;
        $this->Template->dateReadable = date("d.m.Y", $startTime);

        // Datenbankabfrage alle Aufsichten
        $results = MemberModel::findBy('usertype', 'Aufsicht');
        $k = 0;
        $supervisorUser = array();
        foreach ($results as $result) {
            $supervisorUser[$k]["id"] = $result->id;
            $supervisorUser[$k]["firstname"] = $result->firstname;
            $supervisorUser[$k]["lastname"] = $result->lastname;
            $k++;
        }
        $this->Template->supervisorUserList = $supervisorUser;
    }

    // Aufsicht löschen
    public function deleteSupervisor($id, $date) {
        $this->Template->deletePerson = true;
        // Schreibassistenz aus Tabelle tl_attendees_exams entfernen (Wert auf 0 setzen)
        $attendeesExamsAssistants = AttendeesExamsModel::findBy('assistant_id', $id);
        foreach ($attendeesExamsAssistants as $attendeesExamsAssistant) {
            $attendeesExamsAssistant->assistant_id = 0;
            $attendeesExamsAssistant->save();
        }
        // Aufsichtsverteilung aus Tabelle tl_supervisors_exams entfernen
        $supervisorExamsModel = SupervisorsExamsModel::findByPk($id);
        if ($supervisorExamsModel->delete()) {
            \Controller::redirect('klausurverwaltung/aufsichtsverwaltung.html?do=showDetails&date=' . $date);
        }
    }

    // Aufsicht hinzufügen
    public function addSupervisor($date) {
        $supervisorId = \Input::post('supervisorId');
        $timeFrom = \Input::post('timeFrom');
        $timeUntil = \Input::post('timeUntil');
        $task = "Aufsicht";
        $newSupervisorExamsModel = new SupervisorsExamsModel();
        $set = array('tstamp' => time(), 'supervisor_id' => $supervisorId, 'date' => $date, 'time_from' => $timeFrom, 'time_until' => $timeUntil, 'task' => $task);
        $newSupervisorExamsModel->setRow($set);
        if ($newSupervisorExamsModel->save()) {
            \Controller::redirect('klausurverwaltung/aufsichtsverwaltung.html?do=showDetails&date=' . $date);
        }
    }
}
