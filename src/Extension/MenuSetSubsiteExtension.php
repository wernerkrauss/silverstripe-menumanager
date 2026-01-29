<?php

namespace Netwerkstatt\Menumanager\Extension;

;

use SilverStripe\Core\Extension;
use SilverStripe\Subsites\Model\Subsite;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;

/**
 * Adds subsite support if installed
 *
 * @package silverstripe
 * @subpackage silverstripe-menu
 */
class MenuSetSubsite extends Extension
{
    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Subsite' => Subsite::class
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $fields->addFieldToTab(
            "Root.Main",
            HiddenField::create(
                'SubsiteID',
                'SubsiteID',
                Subsite::currentSubsiteID()
            )
        );
        return $fields;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        $owner = $this->getOwner();
        if (!$owner->ID && !$owner->SubsiteID) {
            $owner->SubsiteID = Subsite::currentSubsiteID();
        }
    }
}
