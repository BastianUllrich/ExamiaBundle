<?php

namespace Baul\ExamiaBundle\Module;
use Contao\Database;
use Contao\Module;
use Contao\FrontendUser;
use Baul\ExamiaBundle\Model\MemberModel;
use Baul\ExamiaBundle\Model\AttendeesExamsModel;


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
        // Sprachdateien einbinden
        $this->loadLanguageFile('miscellaneous');
        $this->loadLanguageFile('tl_member');

        $this->Template->showConfirmationQuestion = false;
        $this->Template->showDetails = false;
        $this->Template->showEditForm = false;

        // FrontendUser Variablen laden
        $objUser = FrontendUser::getInstance();
        $this->Template->userID = $objUser->id;

        // Daten des Mitglieds aus der Datenbank laden -> wegen Sortierung nicht über Model/Collection gelöst
        $this->import('Database');
        $allMembers = Database::getInstance()->prepare("SELECT * FROM tl_member ORDER BY disable DESC, usertype ASC")->query();

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
            if ($member != $objUser->id) {
                $this->Database->prepare("UPDATE tl_member SET disable='' WHERE id=$member")->execute()->affectedRows;
            }
            \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
        }
        if ($_GET["do"] == "deactivate") {
            $member = $_GET["member"];
            if ($member != $objUser->id) {
                $this->Database->prepare("UPDATE tl_member SET disable=1 WHERE id=$member")->execute()->affectedRows;
            }
            \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
        }

        if ($_GET["do"] == "viewDetails") {
            $member = $_GET["member"];
            $memberDetailsData = MemberModel::findBy('id', $member);
            $this->Template->showDetails = true;

            $this->Template->memberType = $memberDetailsData->usertype;
            $this->Template->detailFirstname = $memberDetailsData->firstname;
            $this->Template->detailLastname = $memberDetailsData->lastname;
            $this->Template->detailUsername = $memberDetailsData->username;
            $this->Template->detailEmail = $memberDetailsData->email;
            $this->Template->detailDateOfBirth = date("d.m.Y", $memberDetailsData->dateOfBirth);
            $this->Template->detailGender = $memberDetailsData->gender;
            $this->Template->detailPhone = $memberDetailsData->phone;
            $this->Template->detailMobile = $memberDetailsData->mobile;
            $this->Template->detailCourse = $memberDetailsData->study_course;
            $this->Template->detailDepartment = $GLOBALS['TL_LANG']['tl_member'][$memberDetailsData->department];
            $this->Template->detailChipcardNr = $memberDetailsData->chipcard_nr;
            $this->Template->detailContactPerson = $GLOBALS['TL_LANG']['tl_member'][$memberDetailsData->contact_person];
            $this->Template->detailHandicaps = $memberDetailsData->handicaps;
            $this->Template->detailHandicapsOthers = $memberDetailsData->handicaps_others;
            $this->Template->detailRehabDevices = $memberDetailsData->rehab_devices;
            $this->Template->detailRehabDevicesOthers = $memberDetailsData->rehab_devices_others;
            $this->Template->detailExtraTime = $memberDetailsData->extra_time;
            $this->Template->detailExtraTimeUnit = $GLOBALS['TL_LANG']['tl_member'][$memberDetailsData->extra_time_minutes_percent];
        }

        // Mitglied löschen
        if ($_GET["do"] == "delete") {
            $member = $_GET["member"];
            $this->Template->showConfirmationQuestion = true;
            $this->Template->confirmationQuestion = $GLOBALS['TL_LANG']['miscellaneous']['deleteMemberConfirmationQuestion'];
            $this->Template->confirmationYes = $GLOBALS['TL_LANG']['miscellaneous']['deleteMemberConfirmationYes'];
            $this->Template->confirmationNo = $GLOBALS['TL_LANG']['miscellaneous']['deleteMemberConfirmationNo'];

            // Verhindern, dass ein Administrator gelöscht wird
            $memberDeleteData = MemberModel::findBy('id', $member);
            if ($memberDeleteData->usertype == "Administrator") {
                \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
            }
            else {
                // Mitglied erst nach Bestätigung löschen
                if (($_GET["confirmed"] == "yes")) {

                    // Aufsichtsverteilung, Klausurzuweisung und ggf. Klausur aus Datenbank löschen

                    // KlausurIDs von Teilnahme auslesen und in Array speichern
                    $i = 0;
                    $examIDs = array();
                    $attendeesExams = AttendeesExamsModel::findBy('attendee_id', $member);
                    if (!is_null($attendeesExams)) {
                        while ($attendeesExams->next()) {
                            $examIDs[$i]['exam_id'] = $attendeesExams->exam_id;
                            $i++;
                        }

                        // Klausurteilnahmen des Mitglieds aus Datenbank löschen
                        $this->Database->prepare("DELETE FROM tl_attendees_exams WHERE attendee_id=$member")->execute()->affectedRows;

                        // Klausuren aus Datenbank löschen, falls sie keinen Teilnehmer mehr haben
                        foreach ($examIDs as $exam_id) {
                            $exID = $exam_id['exam_id'];
                            $getAttendeesExam = AttendeesExamsModel::findBy('exam_id', $exID);
                            // Klausur aus Datenbank löschen, falls niemand mehr dafür angemeldet ist
                            if (empty($getAttendeesExam->exam_id)) {
                                $this->Database->prepare("DELETE FROM tl_exams WHERE id=$exID")->execute()->affectedRows;
                            }
                        }
                    }

                    // Aufsichtsverteilung des Mitglieds aus Datenbank löschen
                    $this->Database->prepare("DELETE FROM tl_supervisors_exams WHERE supervisor_id=$member")->execute()->affectedRows;

                    // Mitglied aus Datenbank löschen und zur Seite "Mitglieder verwalten" zurückkehren
                    if ($deleteMember = $this->Database->prepare("DELETE FROM tl_member WHERE id=$member")->execute()->affectedRows) {
                        \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
                    }
                }
                elseif ($_GET["confirmed"] == "no") {
                    \Controller::redirect('benutzerbereich/mitglieder-verwalten.html');
                }
            }
        }

        if ($_GET["do"] == "editDetails") {
            $this->Template->showEditForm = true;
            $member = $_GET["member"];
            $memberData = MemberModel::findBy('id', $member);
            $this->setLangValuesEdit();
            $this->setMemberValuesEdit($memberData);
        }
    }

    public function setMemberValuesEdit($memberData) {
        $this->Template->firstname = $memberData->firstname;
        $this->Template->lastname = $memberData->lastname;
        $this->Template->dateOfBirth = date("Y-m-d", $memberData->dateOfBirth);
        $this->Template->gender = $memberData->gender;

        // Behinderungen
        $this->Template->handicaps = $memberData->handicaps;

        $this->Template->handicaps_others = $memberData->handicaps_others;
        $this->Template->phone = $memberData->phone;
        $this->Template->mobile = $memberData->mobile;
        $this->Template->email = $memberData->email;
        $this->Template->username = $memberData->username;
        $this->Template->study_course = $memberData->study_course;
        $this->Template->chipcard_nr = $memberData->chipcard_nr;
        $this->Template->usr_department = $memberData->department;
        $this->Template->contact_person = $memberData->contact_person;

        // Hilfsmittel
        $this->Template->rehab_devices = $memberData->rehab_devices;

        $this->Template->rehab_devices_others = $memberData->rehab_devices_others;
        $this->Template->extra_time = $memberData->extra_time;
        $this->Template->extra_time_minutes_percent = $memberData->extra_time_minutes_percent;
        $this->Template->comments = $memberData->comments;
    }

    public function setLangValuesEdit() {
        $this->Template->mandatory = $GLOBALS['TL_LANG']['miscellaneous']['mandatory'];
    }
}
