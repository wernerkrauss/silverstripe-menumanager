<?php

namespace Netwerkstatt\Menumanager\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\Hierarchy;

class LinkItem extends DataObject
{
    private static string $table_name = 'Menu_LinkItem';

    private static array $extensions = [
        Hierarchy::class,
    ];

    private static array $db = [
        'Enabled' => 'Boolean',
        'SortOrder' => 'Int'
    ];

    private static array $has_one = [
        'MenuSet' => MenuSet::class,
//        'Parent' => Link::class . '.Children',
        'Link' => Link::class,
    ];

    private static array $has_many = [
        'Children' => LinkItem::class . '.Parent',
    ];

    private static array $owns = [
        'Link'
    ];

    private static string $sort_field = 'SortOrder';

    private static string $default_sort = 'SortOrder';

    private static  array $summary_fields = [
        'Enabled.Nice' => 'Enabled',
        'Title' => 'Title',
        'Link.MenuTitle' => 'Type',
        'Link.Description' => 'Link',
        'Children.Count' => 'Children'
    ];

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('MenuSetID');
        $fields->removeByName('ParentID');
        $fields->removeByName('SortOrder');


        return $fields;
    }


    public function getTitle(): string
    {
        return $this->Link()?->Title ?? 'New Link Item';
    }

    public function getHasLink(): bool
    {
        if ($this->Link() instanceof SiteTreeLink && !$this->Link()->Page()->exists()) {
            return false;
        }
        if ($this->Link() instanceof FileLink && !$this->Link()->File()->exists()) {
            return false;
        }

        return $this->Link()->exists() && $this->Link()->URL !== "";
    }

    public function getIsEnabled(): bool
    {
        return $this->Enabled && $this->getHasLink();
    }

}