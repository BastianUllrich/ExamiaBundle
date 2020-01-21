<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;


class MemberGreetingModule extends \Module
{
    /**
     * @var string
     */
    protected $strTemplate = 'mod_memberGreeting';

    /**
     * Displays a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $template = new \BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['memberGreeting'][0]) . ' ###';
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
        $this->Template->welcomeMessage = $this->getWelcomeMessage();
    }

    public function getWelcomeMessage() {
        //Individuelle Begrüßungsnachricht je nach Benutzergruppe ausgeben
        //Gruppe 4 = Studenten
        //Gruppe 5 = Administratoren
        //Gruppe 7 = Aufsichten

        $objUser = FrontendUser::getInstance();

        $welcomeMessage = "<p><strong>Herzlich Willkommen, " . $objUser->firstname . " " . $objUser->lastname . "</strong></p>";

        //Begrüßungsnachricht für Studenten erweitern
        if ($objUser->isMemberOf(4)) {
            $welcomeMessage .= "<p>Unter dem Menüpunkt \"Benutzer\" können Sie Ihre Stammdaten einsehen und sich ausloggen.</p>";
            $welcomeMessage .= "<p>Unter dem Menüpunkt \"Klausuren\" können Sie sich für Prüfungen mit genehmigten Nachteilsausgleich anmelden und den Anmeldestatus überprüfen.</p>";
        }

        //Begrüßungsnachricht für Administratoren erweitern
        if ($objUser->isMemberOf(5)) {
            $welcomeMessage .= "<p>Unter dem Menüpunkt \"Benutzer\" können Sie Studenten, Aufsichten und Administratoren anlegen sowie ";
            $welcomeMessage .= "deren Stammdaten bearbeiten.<br />";
            $welcomeMessage .= "Außerdem können Sie Studenten und Aufsichten löschen.</p>";
            $welcomeMessage .= "<p>Unter dem Menüpunkt \"Klausuren\" können Sie alle Prüfungen verwalten und die Aufsichtsverteilung vornehmen.</p>";
        }

        //Begrüßungsnachricht für Aufsichten erweitern
        if ($objUser->isMemberOf(7)) {
            $welcomeMessage .= "<p>Unter dem Menüpunkt \"Benutzer\" können Sie Ihre Stammdaten einsehen und sich ausloggen.</p>";
            $welcomeMessage .= "<p>Unter dem Menüpunkt \"Klausuren\" können Sie einsehen, zu welcher Prüfung Sie eingeteilt sind sowie Details zur Prüfung einsehen.</p>";
        }

        return $welcomeMessage;
    }
}
