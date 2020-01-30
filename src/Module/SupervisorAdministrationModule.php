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

        // Alle Klausurdaten laden, die ab dem aktuellen Tag um Mitternacht gelten, gruppiert und sortiert nach Datum
        $todayMidnight = strtotime(date("d.m.Y"));
        $result = Database::getInstance()->prepare("SELECT * 
                                                    FROM tl_exams 
                                                    WHERE time > $todayMidnight
                                                    GROUP BY date
                                                    ORDER BY date ASC
                                                    ")->query();
        $supervisorData = array();
        $i=0;
        while ($result->next()) {
            // Variablen für das Template setzen
            $supervisorData[$i]['date'] = $result->date;
            $i++;
        }
        $this->Template->supervisorDataList = $supervisorData;

        $this->Template->langSupervisorAdministration = $GLOBALS['TL_LANG']['miscellaneous']['supervisorAdministration'];
        $this->Template->langDate = $GLOBALS['TL_LANG']['miscellaneous']['date'];
        $this->Template->langDetails = $GLOBALS['TL_LANG']['miscellaneous']['details'];
        $this->Template->langShowDetails = $GLOBALS['TL_LANG']['miscellaneous']['show_Details'];
    }
}
