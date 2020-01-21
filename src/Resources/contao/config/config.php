<?php
// Frontend modules
$GLOBALS['FE_MOD']['examia']['memberList'] = 'Baul\ExamiaBundle\Module\MemberListModule';
$GLOBALS['FE_MOD']['examia']['memberGreeting'] = 'Baul\ExamiaBundle\Module\MemberGreetingModule';
$GLOBALS['FE_MOD']['examia']['memberUserData'] = 'Baul\ExamiaBundle\Module\MemberUserDataModule';

//Backend modules

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

?>

