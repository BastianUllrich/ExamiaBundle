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

        $userdata = MemberModel::findBy('id', $userID);

        $this->loadLanguageFile('tl_member');

        $this->Template->firstname = $userdata->firstname;
        $this->Template->lastname = $userdata->lastname;
        $this->Template->email = $userdata->email;
        $this->Template->username = $userdata->username;

        if ($userdata->contact_person == 'contact1') {
            $this->Template->contact = $GLOBALS['TL_LANG']['tl_member']['contact1'];
        }
        elseif ($userdata->contact_person == 'contact2') {
            $this->Template->contact = $GLOBALS['TL_LANG']['tl_member']['contact2'];
        }

    }
}
