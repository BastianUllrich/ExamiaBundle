<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;


class ExamRegisteredExamsMemberModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'examRegisteredExamsMember';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['examRegisteredExamsMember'][0]) . ' ###';
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
        $this->loadLanguageFile('tl_member');

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;


        $this->import('Database');
        $result = Database::getInstance()->prepare(
            "SELECT tl_exams.date, tl_exams.begin, tl_exams.title, tl_exams.lecturer_title, tl_exams.lecturer_prename, tl_exams.lecturer_lastname, tl_attendees_exams.status 
             FROM tl_exams, tl_attendees_exams 
             WHERE tl_exams.id=tl_attendees_exams.exam_id 
             AND tl_attendees_exams.attendee_id=$userID
            ")->query();

        $this->Template->examParticipationList = $result;

    }
}
