<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;


class MemberAdministrationModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_memberAdministration';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['memberAdministration'][0]) . ' ###';
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

        // Daten des Mitglieds aus der Datenbank laden
        $this->import('Database');
        $allMembers = Database::getInstance()->prepare("SELECT * FROM tl_member ORDER BY disable")->query();
        $i = 0;
        $memberData = array();

        while ($allMembers->next()) {
            // Variablen für das Template setzen
            $memberData[$i]['firstname'] = $allMembers->firstname;
            $memberData[$i]['lastname'] = $allMembers->lastname;
            $memberData[$i]['username'] = $allMembers->username;
            $memberData[$i]['usertype'] = $allMembers->usertype;
            $memberData[$i]['disable'] = $allMembers->disable;
            $memberData[$i]['id'] = $allMembers->id;
            $i++;
        }
        $this->Template->memberDataList = $memberData;

        // Mitglied aktivieren / deaktivieren
        if ($_GET["do"] == "activate") {
            $member = $_GET["member"];
            if ($activateMember = $this->Database->prepare("UPDATE tl_member SET disable='' WHERE id=$member")->execute()->affectedRows) {
                \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
            }
        }
        if ($_GET["do"] == "deactivate") {
            $member = $_GET["member"];
            if ($activateMember = $this->Database->prepare("UPDATE tl_member SET disable=1 WHERE id=$member")->execute()->affectedRows) {
                \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
            }
        }
    }
}
