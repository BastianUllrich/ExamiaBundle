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
    protected $strTemplate = 'mod_examRegisteredExamsMember';

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
        $this->loadLanguageFile('tl_attendees_exams');

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;


        $this->import('Database');
        $examParticipationList = array();
        $i = 0;
        $result = Database::getInstance()->prepare(
            "SELECT tl_exams.date, tl_exams.begin, tl_exams.title, tl_exams.lecturer_title, tl_exams.lecturer_prename, tl_exams.lecturer_lastname, tl_attendees_exams.status 
             FROM tl_exams, tl_attendees_exams 
             WHERE tl_exams.id=tl_attendees_exams.exam_id 
             AND tl_attendees_exams.attendee_id=$userID
             ORDER BY tl_exams.date
            ")->query();
        while ($result->next()) {
            $examParticipationList[$i]['date'] = date("d.m.Y", $result->date);
            $examParticipationList[$i]['time'] = $result->begin;
            $examParticipationList[$i]['title'] = $result->title;

            $examParticipationList[$i]['lecturer_name'] = $result->lecturer_title;
            $examParticipationList[$i]['lecturer_name'] .= ' ';
            $examParticipationList[$i]['lecturer_name'] .= $result->lecturer_prename;
            $examParticipationList[$i]['lecturer_name'] .= ' ';
            $examParticipationList[$i]['lecturer_name'] .= $result->lecturer_lastname;

            $examParticipationList[$i]['status'] = $GLOBALS['TL_LANG']['tl_attendees_exams'][$result->status][0];
            $i++;
        }

        $this->Template->examParticipationList = $examParticipationList;

    }
}
