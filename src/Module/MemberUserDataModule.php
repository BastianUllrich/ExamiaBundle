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
        $objUser = FrontendUser::getInstance();
        $userID = $objUser->id;
        $objUserName = $objUser->username;

        $firstname = $this->getUserData($userID)->firstname;
        $lastname =  $this->getUserData($userID)->lastname;
        $email = $this->getUserData($userID)->email;

        $username = MemberModel::findBy('username', $objUserName);

        $this->Template->firstname = $firstname;
        $this->Template->lastname = $lastname;
        $this->Template->email = $email;
        $this->Template->uname = $username;
    }

    public function getUserData($userID) {
        $this->import('Database');
        $result = Database::getInstance()->prepare("SELECT * FROM tl_member WHERE id = $userID")->query();
        return $result;
    }
}
