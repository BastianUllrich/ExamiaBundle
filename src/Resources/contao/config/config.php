<?php
// Frontend modules
$GLOBALS['FE_MOD']['examia']['memberGreeting'] = 'Baul\ExamiaBundle\Module\MemberGreetingModule';
$GLOBALS['FE_MOD']['examia']['memberUserData'] = 'Baul\ExamiaBundle\Module\MemberUserDataModule';
$GLOBALS['FE_MOD']['examia']['examRegistration'] = 'Baul\ExamiaBundle\Module\ExamRegistrationModule';
$GLOBALS['FE_MOD']['examia']['examRegisteredExamsMember'] = 'Baul\ExamiaBundle\Module\ExamRegisteredExamsMemberModule';
$GLOBALS['FE_MOD']['examia']['ExamUnsuscribe'] = 'Baul\ExamiaBundle\Module\ExamUnsuscribeModule';


// Backend modules
$GLOBALS['BE_MOD']['examia']['exams'] = [
    'tables' => ['tl_exams'],
];

$GLOBALS['BE_MOD']['examia']['member'] = [
    'tables' => ['tl_member'],
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

?>

