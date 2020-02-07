<?php

namespace Baul\ExamiaBundle\Module;
use Baul\ExamiaBundle\Model\MemberModel;
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
        $this->loadLanguageFile('tl_supervisors_exams');

        $this->Template->showDetails = false;
        $this->Template->deletePerson = false;

        // Alle Klausurdaten laden, die ab dem aktuellen Tag um Mitternacht gelten, gruppiert und sortiert nach Datum
        $todayMidnight = strtotime(date("d.m.Y"));
        $result = Database::getInstance()->prepare("SELECT * 
                                                    FROM tl_exams 
                                                    WHERE date > $todayMidnight
                                                    GROUP BY date
                                                    ORDER BY date ASC
                                                    ")->query();
        $examsData = array();
        $i=0;
        while ($result->next()) {
            // Variablen für das Template setzen
            $examsData[$i]['dateReadable'] = date("d.m.Y", $result->date);
            $examsData[$i]['time'] = $result->date;
            $i++;
        }
        $this->Template->examsDataList = $examsData;
        $this->Template->langSupervisorAdministration = $GLOBALS['TL_LANG']['miscellaneous']['supervisorAdministration'];
        $this->Template->langDate = $GLOBALS['TL_LANG']['miscellaneous']['date'];
        $this->Template->langDetails = $GLOBALS['TL_LANG']['miscellaneous']['details'];
        $this->Template->langShowDetails = $GLOBALS['TL_LANG']['miscellaneous']['show_Details'];
        $this->Template->linkTitleShowExamDateDetails = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleShowExamDateDetails'];
        $this->Template->linkTitleDeleteSupervisor = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleDeleteSupervisor'];

        $this->Template->langNoDataAvailable = $GLOBALS['TL_LANG']['miscellaneous']['noDataAvailable'];

        if ($_GET["do"] == "showDetails") {
            $this->showDetails();
        }

        if ($_GET["action"] == "delete") {
            $id = $_GET["id"];
            $date = $_GET["date"];
            $this->deleteSupervisor($id, $date);
        }

        // Formular wurde abgesendet
        if (\Contao\Input::post('FORM_SUBMIT') == 'addSupervisor') {
            $date = $_GET["date"];
            $this->addSupervisor($date);
        }
    }

    public function showDetails() {
        $this->Template->showDetails = true;

        // Sprachvariablen
        $this->Template->langEditSupervisors = $GLOBALS['TL_LANG']['miscellaneous']['editSupervisors'];
        $this->Template->langCurrentSupervisors = $GLOBALS['TL_LANG']['miscellaneous']['currentSupervisors'];
        $this->Template->langSupervisorName = $GLOBALS['TL_LANG']['miscellaneous']['supervisorName'];
        $this->Template->langTimeFrom = $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_from'][0];
        $this->Template->langTimeUntil = $GLOBALS['TL_LANG']['tl_supervisors_exams']['time_until'][0];
        $this->Template->langTimeFormat = $GLOBALS['TL_LANG']['miscellaneous']['timeFormat'];
        $this->Template->langTimePeriod = $GLOBALS['TL_LANG']['miscellaneous']['timePeriod'];
        $this->Template->langTask = $GLOBALS['TL_LANG']['miscellaneous']['task'];
        $this->Template->langDelete = $GLOBALS['TL_LANG']['miscellaneous']['delete'];
        $this->Template->deleteSupervisorText = $GLOBALS['TL_LANG']['miscellaneous']['deleteSupervisorText'];
        $this->Template->langNoSupervisorsAvailable = $GLOBALS['TL_LANG']['miscellaneous']['noSupervisorsAvailable'];
        $this->Template->langAddSupervisor = $GLOBALS['TL_LANG']['miscellaneous']['addSupervisor'];
        $this->Template->langSupervisor = $GLOBALS['TL_LANG']['miscellaneous']['supervisor'];
        $this->Template->langAssistant = $GLOBALS['TL_LANG']['miscellaneous']['writingAssistant'];
        $this->Template->langDoAddSupervisor = $GLOBALS['TL_LANG']['miscellaneous']['doAddSupervisor'];
        $this->Template->linktextBackToSupervisorAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linktextBackToSupervisorAdministration'];
        $this->Template->linkTitleBackToSupervisorAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linkTitleBackToSupervisorAdministration'];

        $startTime = $_GET["date"];
        $endTime = $startTime + 86399;

        // Datenbankabfrage aktuell aufgeteilte Aufsichten
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
        $i=0;
        while ($result->next()) {
            // Variablen für das Template setzen
            $supervisorData[$i]['date'] = date("d.m.Y", $result->date);
            $supervisorData[$i]['id'] = $result->id;
            $supervisorData[$i]['name'] = $result->firstname;
            $supervisorData[$i]['name'] .= " ";
            $supervisorData[$i]['name'] .= $result->lastname;
            $supervisorData[$i]['timePeriod'] = $result->time_from;
            $supervisorData[$i]['timePeriod'] .= " - ";
            $supervisorData[$i]['timePeriod'] .= $result->time_until;
            $supervisorData[$i]['task'] = $result->task;
            $i++;
        }
        $this->Template->supervisorDataList = $supervisorData;
        $this->Template->date = $startTime;
        $this->Template->dateReadable = date("d.m.Y", $startTime);

        // Datenbankabfrage alle Aufsichten
        $results = MemberModel::findBy('usertype', 'Aufsicht');
        $i = 0;
        $supervisorUser = array();
        foreach ($results as $result) {
            $supervisorUser[$i]["id"] = $result->id;
            $supervisorUser[$i]["firstname"] = $result->firstname;
            $supervisorUser[$i]["lastname"] = $result->lastname;
            $i++;
        }
        $this->Template->supervisorUserList = $supervisorUser;
    }

    // Aufsicht löschen
    public function deleteSupervisor($id, $date) {
        $this->Template->deletePerson = true;
        if ($this->Database->prepare("DELETE FROM tl_supervisors_exams WHERE id=$id")->execute()->affectedRows) {
            \Controller::redirect('klausurverwaltung/aufsichtsverwaltung.html?do=showDetails&date=' . $date);
        }
    }

    // Aufsicht hinzufügen
    public function addSupervisor($date) {
        $supervisorId = \Input::post('supervisorId');
        $timeFrom = \Input::post('timeFrom');
        $timeUntil = \Input::post('timeUntil');
        $task = \Input::post('personTask');
        if ($task == "supervisor") {
            $task = "Aufsicht";
        }
        if ($task == "writingassistant") {
            $task = "Schreibassistenz";
        }
        $this->import('Database');
        $set = array('tstamp' => time(), 'supervisor_id' => $supervisorId, 'date' => $date, 'time_from' => $timeFrom, 'time_until' => $timeUntil, 'task' => $task);

        if ($this->Database->prepare("INSERT INTO tl_supervisors_exams %s")->set($set)->execute()) {
            \Controller::redirect('klausurverwaltung/aufsichtsverwaltung.html?do=showDetails&date=' . $date);
        }
    }
}
