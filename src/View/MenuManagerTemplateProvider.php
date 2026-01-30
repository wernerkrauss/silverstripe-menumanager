<?php

namespace View;

use Netwerkstatt\Menumanager\Model\MenuSet;
use SilverStripe\Model\List\SS_List;
use SilverStripe\View\TemplateGlobalProvider;

class MenuManagerTemplateProvider implements TemplateGlobalProvider
{
    /**
     * @return array|void
     */
    public static function get_template_global_variables(): array
    {
        return [
            'MenuSet' => 'getMenuSet'
        ];
    }

    public static function getMenuSet(string $slug): ?SS_List
    {
        if (!$slug) {
            return null;
        }
        if ($menuSet = MenuSet::get_by_slug($slug)) {
            return $menuSet->LinkItems();
        }
        return null;
    }
}
