<?php
namespace Netwerkstatt\Menumanager\Admin;

use Netwerkstatt\Menumanager\Model\MenuSet;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Security\Member;

class MenuSetAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static array $managed_models = [
        MenuSet::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static string $url_segment = 'menus';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static string $menu_title = 'Menus';

    /**
     * Menu icon for Left and Main CMS
     * @var string
     */
    private static string $menu_icon_class = 'font-icon-list';

    /**
     * @var int
     */
    private static int $menu_priority = 9;

    /**
     * @param Int $id
     * @param FieldList $fields
     * @return Form
     */
    #[Override]
    public function getEditForm($id = null, $fields = null): Form
    {
        $form = parent::getEditForm($id, $fields);
        $form->Fields()
            ->fieldByName($this->sanitiseClassName($this->modelClass))
            ->getConfig()
            ->removeComponentsByType([
                GridFieldImportButton::class,
                GridFieldExportButton::class,
                GridFieldPrintButton::class,
                GridFieldDeleteAction::class
            ]);
        return $form;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    #[Override]
    public function canView($member = null): bool
    {
        $sets = Config::inst()->get(MenuSet::class, 'sets');
        if (!isset($sets) || !count($sets)) {
            return false;
        }
        return parent::canView($member);
    }
}
