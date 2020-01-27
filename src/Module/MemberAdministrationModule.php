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
        $this->Template->changesSaved = false;

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
            $this->Template->detailHandicaps = unserialize($memberDetailsData->handicaps);
            $this->Template->detailHandicapsOthers = $memberDetailsData->handicaps_others;
            $this->Template->detailRehabDevices = unserialize($memberDetailsData->rehab_devices);
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

        if (\Contao\Input::post('FORM_SUBMIT') == 'editMember') {
            $this->saveChanges($memberData->usertype, $member);
        }
    }

    public function setMemberValuesEdit($memberData) {
        $this->Template->memberType = $memberData->usertype;

        $this->Template->firstname = $memberData->firstname;
        $this->Template->lastname = $memberData->lastname;
        $this->Template->dateOfBirth = date("Y-m-d", $memberData->dateOfBirth);
        $this->Template->gender = $memberData->gender;
        $this->Template->handicaps = unserialize($memberData->handicaps);

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
        $this->Template->rehab_devices = unserialize($memberData->rehab_devices);

        $this->Template->rehab_devices_others = $memberData->rehab_devices_others;
        $this->Template->extra_time = $memberData->extra_time;
        $this->Template->extra_time_minutes_percent = $memberData->extra_time_minutes_percent;
        $this->Template->comments = $memberData->comments;
    }

    public function setLangValuesEdit() {
        $this->Template->mandatory = $GLOBALS['TL_LANG']['miscellaneous']['mandatory'];

        $this->Template->langPersonalData = $GLOBALS['TL_LANG']['tl_member']['personal_legend'];
        $this->Template->langFirstname = $GLOBALS['TL_LANG']['tl_member']['firstname'][0];
        $this->Template->langLastname = $GLOBALS['TL_LANG']['tl_member']['lastname'][0];
        $this->Template->langDateOfBirth = $GLOBALS['TL_LANG']['tl_member']['dateOfBirth'][0];

        $this->Template->langGender = $GLOBALS['TL_LANG']['tl_member']['gender'][0];
        $this->Template->langGenderMale = $GLOBALS['TL_LANG']['tl_member']['male'];
        $this->Template->langGenderFemale = $GLOBALS['TL_LANG']['tl_member']['female'];
        $this->Template->langGenderDivers = $GLOBALS['TL_LANG']['tl_member']['divers'];

        $this->Template->langHandicaps = $GLOBALS['TL_LANG']['tl_member']['handicaps'][0];
        $this->Template->langBlind = $GLOBALS['TL_LANG']['tl_member']['blind'];
        $this->Template->langVisuallyImpaired = $GLOBALS['TL_LANG']['tl_member']['visually impaired'];
        $this->Template->langDeaf = $GLOBALS['TL_LANG']['tl_member']['deaf'];
        $this->Template->langMotoricallyRestricted = $GLOBALS['TL_LANG']['tl_member']['motorically restricted'];
        $this->Template->langAutism = $GLOBALS['TL_LANG']['tl_member']['autism'];
        $this->Template->langMentalDisorder = $GLOBALS['TL_LANG']['tl_member']['mental disorder'];
        $this->Template->langChronicDisorder = $GLOBALS['TL_LANG']['tl_member']['chronic disorder'];
        $this->Template->langAcuteIllness = $GLOBALS['TL_LANG']['tl_member']['acute illness'];
        $this->Template->langHandicapDifferent = $GLOBALS['TL_LANG']['tl_member']['different'];
        $this->Template->langHandicapsOthers = $GLOBALS['TL_LANG']['tl_member']['handicaps_others'][0];

        $this->Template->langContactData = $GLOBALS['TL_LANG']['tl_member']['contact_legend'];
        $this->Template->langPhone = $GLOBALS['TL_LANG']['tl_member']['phone'][0];
        $this->Template->langMobile = $GLOBALS['TL_LANG']['tl_member']['mobile'][0];
        $this->Template->langEmail = $GLOBALS['TL_LANG']['tl_member']['email'][0];

        $this->Template->langLoginData = $GLOBALS['TL_LANG']['tl_member']['login_legend'];
        $this->Template->langUsername = $GLOBALS['TL_LANG']['tl_member']['username'][0];

        $this->Template->langStudyData = $GLOBALS['TL_LANG']['tl_member']['study_legend'];
        $this->Template->langStudyCourse = $GLOBALS['TL_LANG']['tl_member']['study_course'][0];
        $this->Template->langChipcardNr = $GLOBALS['TL_LANG']['tl_member']['chipcard_nr'][0];
        $this->Template->langDepartment = $GLOBALS['TL_LANG']['tl_member']['department'][0];
        $this->Template->langDepartment1 = $GLOBALS['TL_LANG']['tl_member']['department1'];
        $this->Template->langDepartment2 = $GLOBALS['TL_LANG']['tl_member']['department2'];
        $this->Template->langDepartment3 = $GLOBALS['TL_LANG']['tl_member']['department3'];
        $this->Template->langDepartment4 = $GLOBALS['TL_LANG']['tl_member']['department4'];
        $this->Template->langDepartment5 = $GLOBALS['TL_LANG']['tl_member']['department5'];
        $this->Template->langDepartment6 = $GLOBALS['TL_LANG']['tl_member']['department6'];
        $this->Template->langDepartment7 = $GLOBALS['TL_LANG']['tl_member']['department7'];
        $this->Template->langDepartment8 = $GLOBALS['TL_LANG']['tl_member']['department8'];
        $this->Template->langDepartment9 = $GLOBALS['TL_LANG']['tl_member']['department9'];
        $this->Template->langDepartment10 = $GLOBALS['TL_LANG']['tl_member']['department10'];
        $this->Template->langDepartment11 = $GLOBALS['TL_LANG']['tl_member']['department11'];
        $this->Template->langDepartment12 = $GLOBALS['TL_LANG']['tl_member']['department12'];
        $this->Template->langDepartment13 = $GLOBALS['TL_LANG']['tl_member']['department13'];
        $this->Template->langDepartment14 = $GLOBALS['TL_LANG']['tl_member']['department14'];
        $this->Template->langContactPerson = $GLOBALS['TL_LANG']['tl_member']['contact_person'][0];
        $this->Template->langContact1 = $GLOBALS['TL_LANG']['tl_member']['contact1'];
        $this->Template->langContact2 = $GLOBALS['TL_LANG']['tl_member']['contact2'];

        $this->Template->langExamData = $GLOBALS['TL_LANG']['tl_member']['exam_legend'];
        $this->Template->langRehabDevices = $GLOBALS['TL_LANG']['tl_member']['rehab_devices'][0];
        $this->Template->langPC = $GLOBALS['TL_LANG']['tl_member']['pc'];
        $this->Template->langBlindWorkspace = $GLOBALS['TL_LANG']['tl_member']['blind workspace'];
        $this->Template->langZoomtext = $GLOBALS['TL_LANG']['tl_member']['Zoomtext'];
        $this->Template->langScreenMagnifier = $GLOBALS['TL_LANG']['tl_member']['screen magnifier'];
        $this->Template->langScreenReader = $GLOBALS['TL_LANG']['tl_member']['screen reader'];
        $this->Template->langA3Print = $GLOBALS['TL_LANG']['tl_member']['a3 print'];
        $this->Template->langObscuration = $GLOBALS['TL_LANG']['tl_member']['obscuration'];
        $this->Template->langWritingAssistance = $GLOBALS['TL_LANG']['tl_member']['writing assistance'];
        $this->Template->langHighTable = $GLOBALS['TL_LANG']['tl_member']['high table'];
        $this->Template->langNearDoor = $GLOBALS['TL_LANG']['tl_member']['near door'];
        $this->Template->langOwnRoom = $GLOBALS['TL_LANG']['tl_member']['own room'];
        $this->Template->langRDDifferent = $GLOBALS['TL_LANG']['tl_member']['different'];
        $this->Template->langRehabDevicesOthers = $GLOBALS['TL_LANG']['tl_member']['rehab_devices_others'][0];

        $this->Template->langComments = $GLOBALS['TL_LANG']['tl_member']['comments'][0];

        $this->Template->langExtraTime = $GLOBALS['TL_LANG']['tl_member']['extra_time'][0];
        $this->Template->langExtraTimeUnit = $GLOBALS['TL_LANG']['tl_member']['extra_time_minutes_percent'][0];
        $this->Template->langExtraTimeMinutes = $GLOBALS['TL_LANG']['tl_member']['minutes'];
        $this->Template->langExtraTimePercent = $GLOBALS['TL_LANG']['tl_member']['percent'];

        $this->Template->langSaveChanges = $GLOBALS['TL_LANG']['miscellaneous']['saveChanges'];
    }

    public function saveChanges($usertype, $member)
    {
        // Felder auslesen
        // Allgemeine Felder für alle Mitgliedstypen

        $firstname = \Input::post('firstname');
        $lastname = \Input::post('lastname');
        $email = \Input::post('email');
        $username = \Input::post('username');

        // Felder für Mitgliedstypen Student und Aufsicht
        if ($usertype != "Administrator") {
            $mobile = \Input::post('mobile');
        }

        // Felder für Mitgliedstyp Student
        if ($usertype == "Student") {
            $phone = \Input::post('phone');
            $dateOfBirth = \Input::post('dateOfBirth');
            $dateOfBirth = strtotime($dateOfBirth);
            $gender = \Input::post('gender');
            $handicaps = serialize(\Input::post('handicaps'));
            $handicaps_others = \Input::post('handicaps_others');
            $study_course = \Input::post('study_course');
            $chipcard_nr = \Input::post('chipcard_nr');
            $department = \Input::post('department');
            $contact_person = \Input::post('contact_person');
            $rehab_devices = serialize(\Input::post('rehab_devices'));
            $rehab_devices_others = \Input::post('rehab_devices_others');
            $extra_time = \Input::post('extra_time');
            $extra_time_minutes_percent = \Input::post('extra_time_minutes_percent');
            $comments = \Input::post('comments');
        }

        // Verschiedene Abfragen erstellen
        switch ($usertype) {
            case "Administrator" : $set = array('firstname'=>$firstname, 'lastname'=>$lastname, 'email'=>$email, 'username'=>$username); break;
            case "Aufsicht" : $set =    array('firstname'=>$firstname, 'lastname'=>$lastname, 'email'=>$email, 'username'=>$username, 'mobile'=>$mobile); break;
            case "Student" : $set =     array(  'firstname'=>$firstname, 'lastname'=>$lastname, 'email'=>$email, 'username'=>$username, 'mobile'=>$mobile, 'phone'=>$phone, 'dateOfBirth'=>$dateOfBirth,
                                                'gender'=>$gender, 'handicaps'=>$handicaps, 'handicaps_others'=>$handicaps_others, 'study_course'=>$study_course, 'chipcard_nr'=>$chipcard_nr,
                                                'department'=>$department, 'contact_person'=>$contact_person, 'rehab_devices'=>$rehab_devices, 'rehab_devices_others'=>$rehab_devices_others,
                                                'extra_time'=>$extra_time, 'extra_time_minutes_percent'=>$extra_time_minutes_percent, 'comments'=>$comments); break;
            default: break;
        }

        if($this->Database->prepare("UPDATE tl_member %s WHERE id=$member")->set($set)->execute()) {
            $this->Template->changesSaved = true;
            $this->Template->changesSavedMessage = $GLOBALS['TL_LANG']['miscellaneous']['changesSavedMessage'];
            $this->Template->linktextBackToMemberAdministration = $GLOBALS['TL_LANG']['miscellaneous']['linktextBackToMemberAdministration'];
        }
    }
}
