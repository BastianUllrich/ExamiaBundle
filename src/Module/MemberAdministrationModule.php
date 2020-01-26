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
            //$this->setLangValuesEdit();
            $this->setMemberValuesEdit($memberData);
        }
    }

    public function setMemberValuesEdit($memberData) {
        $this->Template->usr_department = $memberData->department;
        $this->Template->contact_person = $memberData->contact_person;
        $this->Template->extra_time_minutes_percent = $memberData->extra_time_minutes_percent;
    }

    public function setLangValuesEdit() {
        $this->Template->mandatory = $GLOBALS['TL_LANG']['miscellaneous']['mandatory'];

        // Legends & Labels setzen
        $this->Template->personalData_legend = $GLOBALS['TL_LANG']['miscellaneous']['personalData'];
        $this->Template->firstname_label = $GLOBALS['TL_LANG']['tl_member']['firstname'][0];
        $this->Template->lastname_label = $GLOBALS['TL_LANG']['tl_member']['lastname'][0];
        $this->Template->dateOfBirth_label = $GLOBALS['TL_LANG']['tl_member']['dateOfBirth'][0];
        $this->Template->gender_label = $GLOBALS['TL_LANG']['tl_member']['gender'][0];
        $this->Template->handicaps_label = $GLOBALS['TL_LANG']['tl_member']['handicaps'][0];
        $this->Template->handicaps_others_label = $GLOBALS['TL_LANG']['tl_member']['handicaps_others'][0];

        $this->Template->contactData_legend = $GLOBALS['TL_LANG']['tl_member']['contactData'];
        $this->Template->phone_label = $GLOBALS['TL_LANG']['tl_member']['phone'][0];
        $this->Template->mobile_label = $GLOBALS['TL_LANG']['tl_member']['mobile'][0];
        $this->Template->email_label = $GLOBALS['TL_LANG']['tl_member']['email'][0];

        $this->Template->loginData_legend = $GLOBALS['TL_LANG']['tl_member']['loginData'];
        $this->Template->username_label = $GLOBALS['TL_LANG']['tl_member']['username'][0];

        $this->Template->study_legend = $GLOBALS['TL_LANG']['tl_member']['studyDetails'];
        $this->Template->study_course_label = $GLOBALS['TL_LANG']['tl_member']['study_course'][0];
        $this->Template->chipcard_nr_label = $GLOBALS['TL_LANG']['tl_member']['chipcard_nr'][0];
        $this->Template->department_label = $GLOBALS['TL_LANG']['tl_member']['department'][0];
        $this->Template->contact_person_label = $GLOBALS['TL_LANG']['tl_member']['contact_person'][0];

        $this->Template->exam_legend = $GLOBALS['TL_LANG']['tl_member']['examDetails'];
        $this->Template->rehab_devices_label = $GLOBALS['TL_LANG']['tl_member']['rehab_devices'][0];
        $this->Template->rehab_devices_others_label = $GLOBALS['TL_LANG']['tl_member']['rehab_devices_others'][0];
        $this->Template->extra_time_label = $GLOBALS['TL_LANG']['tl_member']['extra_time'][0];
        $this->Template->extra_time_minutes_percent_label = $GLOBALS['TL_LANG']['tl_member']['extra_time_minutes_percent'][0];
        $this->Template->comments_label = $GLOBALS = ['TL_LANG']['tl_member']['comments'][0];

        // Options setzen
        $this->Template->male = $GLOBALS['TL_LANG']['tl_member']['male'];
        $this->Template->female = $GLOBALS['TL_LANG']['tl_member']['female'];
        $this->Template->divers = $GLOBALS['TL_LANG']['tl_member']['divers'];

        $this->Template->blind = $GLOBALS['TL_LANG']['tl_member']['blind'];
        $this->Template->visually_impaired = $GLOBALS['TL_LANG']['tl_member']['visually impaired'];
        $this->Template->deaf = $GLOBALS['TL_LANG']['tl_member']['deaf'];
        $this->Template->motorically_restricted = $GLOBALS['TL_LANG']['tl_member']['motorically restricted'];
        $this->Template->autism = $GLOBALS['TL_LANG']['tl_member']['autism'];
        $this->Template->mental_disorder = $GLOBALS['TL_LANG']['tl_member']['mental disorder'];
        $this->Template->chronic_disorder = $GLOBALS['TL_LANG']['tl_member']['chronic disorder'];
        $this->Template->acute_illness = $GLOBALS['TL_LANG']['tl_member']['acute illness'];
        $this->Template->different = $GLOBALS['TL_LANG']['tl_member']['different'];

        $this->Template->department1 = $GLOBALS['TL_LANG']['tl_member']['department1'];
        $this->Template->department2 = $GLOBALS['TL_LANG']['tl_member']['department2'];
        $this->Template->department3 = $GLOBALS['TL_LANG']['tl_member']['department3'];
        $this->Template->department4 = $GLOBALS['TL_LANG']['tl_member']['department4'];
        $this->Template->department5 = $GLOBALS['TL_LANG']['tl_member']['department5'];
        $this->Template->department6 = $GLOBALS['TL_LANG']['tl_member']['department6'];
        $this->Template->department7 = $GLOBALS['TL_LANG']['tl_member']['department7'];
        $this->Template->department8 = $GLOBALS['TL_LANG']['tl_member']['department8'];
        $this->Template->department9 = $GLOBALS['TL_LANG']['tl_member']['department9'];
        $this->Template->department10 = $GLOBALS['TL_LANG']['tl_member']['department10'];
        $this->Template->department11 = $GLOBALS['TL_LANG']['tl_member']['department11'];
        $this->Template->department12 = $GLOBALS['TL_LANG']['tl_member']['department12'];
        $this->Template->department13 = $GLOBALS['TL_LANG']['tl_member']['department13'];
        $this->Template->department14 = $GLOBALS['TL_LANG']['tl_member']['department14'];

        $this->Template->contact1 = $GLOBALS['TL_LANG']['tl_member']['contact1'];
        $this->Template->contact2 = $GLOBALS['TL_LANG']['tl_member']['contact2'];

        $this->Template->pc = $GLOBALS['TL_LANG']['tl_member']['pc'];
        $this->Template->blind_workspace = $GLOBALS['TL_LANG']['tl_member']['blind workspace'];
        $this->Template->zoomtext = $GLOBALS['TL_LANG']['tl_member']['Zoomtext'];
        $this->Template->screen_magnifier = $GLOBALS['TL_LANG']['tl_member']['screen magnifier'];
        $this->Template->screen_reader = $GLOBALS['TL_LANG']['tl_member']['screen reader'];
        $this->Template->a3_print = $GLOBALS['TL_LANG']['tl_member']['a3 print'];
        $this->Template->obscuration = $GLOBALS['TL_LANG']['tl_member']['obscuration'];
        $this->Template->writing_assistance = $GLOBALS['TL_LANG']['tl_member']['writing assistance'];
        $this->Template->high_table = $GLOBALS['TL_LANG']['tl_member']['high table'];
        $this->Template->near_door = $GLOBALS['TL_LANG']['tl_member']['near door'];
        $this->Template->own_room = $GLOBALS['TL_LANG']['tl_member']['own room'];
        $this->Template->different = $GLOBALS['TL_LANG']['tl_member']['different'];

        $this->Template->minutes = $GLOBALS['TL_LANG']['tl_member']['minutes'];
        $this->Template->percent = $GLOBALS['TL_LANG']['tl_member']['percent'];
    }
}
