<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;
use Baul\ExamiaBundle\Model\MemberModel;
use Baul\ExamiaBundle\Model\AttendeesExamsModel;
use Baul\ExamiaBundle\Model\SupervisorsExamsModel;


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

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $this->Template->userID = $objUser->id;

        $this->Template->showDetails = false;
        $this->Template->deletePerson = false;
        $this->Template->addPerson = false;

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

        if ($_GET["do"] == "showDetails") {
            $this->showDetails();
        }
    }

    public function showDetails() {
        $this->Template->showDetails = true;

        // Sprachvariablen
        $this->Template->langEditSupervisors = $GLOBALS['TL_LANG']['miscellaneous']['editSupervisors'];
        $this->Template->langCurrentSupervisors = $GLOBALS['TL_LANG']['miscellaneous']['currentSupervisors'];
        $this->Template->langSupervisorName = $GLOBALS['TL_LANG']['miscellaneous']['supervisorName'];
        $this->Template->langTimePeriod = $GLOBALS['TL_LANG']['miscellaneous']['timePeriod'];
        $this->Template->langTask = $GLOBALS['TL_LANG']['miscellaneous']['task'];
        $this->Template->langDelete = $GLOBALS['TL_LANG']['miscellaneous']['delete'];
        $this->Template->deleteSupervisorText = $GLOBALS['TL_LANG']['miscellaneous']['deleteText'];
        $this->Template->deleteAssistanceText = $GLOBALS['TL_LANG']['miscellaneous']['deleteAssistanceText'];

        $startTime = $_GET["date"];
        $endTime = $startTime + 86399;
        // Datenbankabfrage
        $result = Database::getInstance()->prepare("SELECT 
                                                    tl_member.id, tl_member.firstname, tl_member.lastname, 
                                                    tl_supervisors_exams.date, tl_supervisors_exams.time_from, tl_supervisors_exams.time_until, tl_supervisors_exams.task
                                                    FROM tl_supervisors_exams, tl_member
                                                    WHERE tl_supervisors_exams.date 
                                                    BETWEEN $startTime
                                                    AND $endTime
                                                    AND tl_member.id=tl_supervisors_exams.supervisor_id
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
    }
}
