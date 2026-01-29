<?php

namespace Netwerkstatt\Menumanager\Model;

use Override;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * MenuSet
 *
 * @property string $Title
 * @property string $Slug
 * @property bool $AllowChildren
 * @method HasManyList|MenuLink[] Links()
 * @package silverstripe-menu
 */
class MenuSet extends DataObject implements PermissionProvider
{
    /**
     * Defines the database table name
     * @var string
     */
    private static string $table_name = 'Menu_MenuSet';

    /**
     * Singular name for CMS
     * @var string
     */
    private static string $singular_name = 'Menu';

    /**
     * Plural name for CMS
     * @var string
     */
    private static string $plural_name = 'Menus';

    /**
     * Database fields
     * @var array
     */
    private static array $db = [
        'Title' => 'Varchar(255)',
        'Slug' => 'Varchar(255)', //@todo: move to slug extension
        'AllowChildren' => 'Boolean'
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static array $has_many = [
        'LinkItems' => LinkItem::class,
    ];

    private static array $owns = [
        'LinkItems',
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static array $summary_fields = [
        'Title' => 'Title',
        'LinkItems.Count' => 'Links'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static array $searchable_fields = [
        'Title'
    ];

//    /**
//     * CMS Fields
//     * @return FieldList
//     */
    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();
        if ($this->exists()) {
            $itemsGrid = $fields->dataFieldByName('LinkItems');
            $fields->removeFieldFromTab('Root', 'LinkItems');

            $itemsGrid->getConfig()->addComponent(GridFieldOrderableRows::create('SortOrder'));
            $fields->addFieldToTab('Root.Main', $itemsGrid);
        }
        return $fields;
    }

    /**
     * Return a map of permission codes to add to the dropdown shown in the Security section of the CMS
     * @return array
     */
    public function providePermissions(): array
    {
        $permissions = [];
        foreach (MenuSet::get() as $menuset) {
            $key = $menuset->PermissionKey();
            $permissions[$key] = [
                'name' => _t(
                    self::class . '.EDITMENUSET',
                    "Manage links with in '{name}'",
                    [
                        'name' => $menuset->obj('Title')
                    ]
                ),
                'category' => _t(self::class . '.MENUSETS', 'Menu sets')
            ];
        }
        return $permissions;
    }

    /**
     * @return string
     */
    public function PermissionKey(): string
    {
        return $this->obj('Slug')->Uppercase() . 'EDIT';
    }

    /**
     * Creating Permissions.
     * This module is not intended to allow creating menus via CMS.
     * @return boolean
     */
    #[Override]
    public function canCreate($member = null, $context = []): bool
    {
        return false;
    }

    /**
     * Deleting Permissions
     * This module is not intended to allow deleting menus via CMS
     * @param mixed $member
     * @return boolean
     */
    #[Override]
    public function canDelete($member = null): bool
    {
        return false;
    }

    /**
     * Editing Permissions
     * @param mixed $member
     * @return boolean
     */
    #[Override]
    public function canEdit($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        // Restrict permissions based on saved key
        if ($this->isInDB()) {
            return Permission::check($this->PermissionKey(), 'any', $member);
        }

        // If canEdit() is called on an unsaved singleton, default to any users with CMS access
        // This allows MenuLink objects to be created via gridfield,
        // which will call the singleton MenuSet::canEdit()
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Viewing Permissions
     * @param mixed $member
     * @return boolean
     */
    #[Override]
    public function canView($member = null): bool
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check($this->PermissionKey(), 'any', $member);
    }

    /**
     * Set up default records based on the yaml config
     */
    #[Override]
    public function requireDefaultRecords(): void
    {
        parent::requireDefaultRecords();
        $default_menu_sets = $this->config()->get('sets') ?: [];
        foreach ($default_menu_sets as $slug => $options) {
            if (is_array($options)) {
                $title = $options['title'];
                $allowChildren = $options['allow_children'] ?? false;
            } else {
                $title = $options;
                $allowChildren = false;
            }
            $slug = Convert::raw2htmlid($slug);
            $record = MenuSet::get()->find('Slug', $slug);
            if (!$record) {
                $record = MenuSet::create();
                DB::alteration_message("Menu '$title' created", 'created');
            } else {
                DB::alteration_message("Menu '$title' updated", 'updated');
            }
            $record->Slug = $slug;
            $record->Title = $title;
            $record->AllowChildren = $allowChildren;
            $record->write();
        }
    }

    /**
     * Generates a link to edit this page in the CMS.
     *
     * @return string
     */
    #[Override]
    public function CMSEditLink(): string
    {
        return Controller::join_links(
            Controller::curr()->Link(),
            'EditForm',
            'field',
            $this->ClassName,
            'item',
            $this->ID
        );
    }

    /**
     * Return the first menuset matching the given slug.
     *
     * @return self|null
     */
    public static function get_by_slug($slug): ?self
    {
        if ($slug) {
            return self::get()->find('Slug', $slug);
        }

        return null;
    }

    /**
     * Relationship accessor for Graphql
     * @return HasManyList MenuLink
     */
    public function getLinks(): HasManyList
    {
        return $this->Links()->filter([
            'ParentID' => 0
        ]);
    }
}
