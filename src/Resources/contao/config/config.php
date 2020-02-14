<?php
// Frontend modules
$GLOBALS['FE_MOD']['examia']['memberUserData'] = 'Baul\ExamiaBundle\Module\MemberUserDataModule';
$GLOBALS['FE_MOD']['examia']['examRegistration'] = 'Baul\ExamiaBundle\Module\ExamRegistrationModule';
$GLOBALS['FE_MOD']['examia']['examRegisteredExamsMember'] = 'Baul\ExamiaBundle\Module\ExamRegisteredExamsMemberModule';
$GLOBALS['FE_MOD']['examia']['examUnsubscribe'] = 'Baul\ExamiaBundle\Module\ExamUnsubscribeModule';
$GLOBALS['FE_MOD']['examia']['memberAdministration'] = 'Baul\ExamiaBundle\Module\MemberAdministrationModule';
$GLOBALS['FE_MOD']['examia']['examAdministration'] = 'Baul\ExamiaBundle\Module\ExamAdministrationModule';
$GLOBALS['FE_MOD']['examia']['supervisorAdministration'] = 'Baul\ExamiaBundle\Module\SupervisorAdministrationModule';
$GLOBALS['FE_MOD']['examia']['supervisorOverview'] = 'Baul\ExamiaBundle\Module\SupervisorOverviewModule';

// Backend modules
$GLOBALS['BE_MOD']['examia']['exams'] = [
    'tables' => ['tl_exams'],
];

$GLOBALS['BE_MOD']['examia']['attendees_exams'] = [
    'tables' => ['tl_attendees_exams'],
];

$GLOBALS['BE_MOD']['examia']['supervisors_exams'] = [
    'tables' => ['tl_supervisors_exams'],
];

// Models
use Baul\ExamiaBundle\Model\MemberModel;
$GLOBALS['TL_MODELS']['tl_member'] = MemberModel::class;

use Baul\ExamiaBundle\Model\ExamsModel;
$GLOBALS['TL_MODELS']['tl_exams'] = ExamsModel::class;

use Baul\ExamiaBundle\Model\AttendeesExamsModel;
$GLOBALS['TL_MODELS']['tl_attendees_exams'] = AttendeesExamsModel::class;

use Baul\ExamiaBundle\Model\SupervisorsExamsModel;
$GLOBALS['TL_MODELS']['tl_supervisors_exams'] = SupervisorsExamsModel::class;

?>
