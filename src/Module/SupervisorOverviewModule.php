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

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;

        $this->Template->showDetails = false;

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
    }
}
