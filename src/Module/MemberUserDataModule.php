<?php

namespace Baul\ExamiaBundle\Module;
use Baul\ExamiaBundle\Model\MemberModel;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;


class MemberUserDataModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_memberUserData';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['memberUserData'][0]) . ' ###';
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
        $this->loadLanguageFile('miscellaneous');

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;

        // Daten des Mitglieds aus der Datenbank laden
        $userdata = MemberModel::findBy('id', $userID);



        // Sprachvariablen für das Template setzen
        $this->Template->headline = $GLOBALS['TL_LANG']['miscellaneous']['showMasterData'];
        $this->Template->langFirstname = $GLOBALS['TL_LANG']['tl_member']['firstname'];
        $this->Template->langLastname = $GLOBALS['TL_LANG']['tl_member']['lastname'];
        $this->Template->langEmail = $GLOBALS['TL_LANG']['tl_member']['email'];
        $this->Template->langUsername = $GLOBALS['TL_LANG']['tl_member']['username'];
        $this->Template->langContact = $GLOBALS['TL_LANG']['tl_member']['contact_person'][0];
        $this->Template->langDepartment = $GLOBALS['TL_LANG']['tl_member']['department'][0];
        $this->Template->langCourse = $GLOBALS['TL_LANG']['tl_member']['study_course'][0];

        // Variablen für das Template setzen
        $this->Template->firstname = $userdata->firstname;
        $this->Template->lastname = $userdata->lastname;
        $this->Template->email = $userdata->email;
        $this->Template->username = $userdata->username;
        $this->Template->contact = $GLOBALS['TL_LANG']['tl_member'][$userdata->contact_person];
        $this->Template->department = $GLOBALS['TL_LANG']['tl_member'][$userdata->department];
        $this->Template->course = $userdata->study_course;

    }
}
