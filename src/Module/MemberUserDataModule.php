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

        $this->Template->content = $this->getContent($this->$userdata);
    }

    public function getContent($userdata) {

        // Content für alle Anwender
        $content =
        '
        <tr class="row_0 row_first even">
            <td class="label">Vorname</td>
            <td class="value"><?= $userdata->firstname; ?> </td>
        </tr>
        <tr class="row_1 odd">
            <td class="label">Nachname</td>
            <td class="value"><?= $userdata->lastname; ?> </td>
        </tr>
        <tr class="row_2 even">
            <td class="label">E-Mail-Adresse</td>
            <td class="value"><?= $userdata->email; ?> </td>
        </tr>
        <tr class="row_3 odd">
            <td class="label">Benutzername</td>
            <td class="value"><?= $userdata->uname; ?> </td>
        </tr>
        ';

        return $content;
    }
}
