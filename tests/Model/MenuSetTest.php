<?php

namespace Netwerkstatt\Menumanager\Tests\Model;

use Netwerkstatt\Menumanager\Model\MenuSet;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;

class MenuSetTest extends SapphireTest
{
    protected static $fixture_file = '../fixtures/MenuSetTest.yml';

    /**
     * Tests if a menu is correctly found via its slug.
     */
    public function testGetBySlug()
    {
        $mainMenu = $this->objFromFixture(MenuSet::class, 'main');

        $found = MenuSet::get_by_slug('main-menu');
        $this->assertNotNull($found);
        $this->assertEquals($mainMenu->ID, $found->ID);

        $not_found = MenuSet::get_by_slug('non-existent');
        $this->assertNull($not_found);
    }

    /**
     * Tests the default permissions (security).
     */
    public function testPermissions()
    {
        $menu = MenuSet::create();

        // Menus should not be allowed to be created or deleted via CMS by default
        $this->assertFalse($menu->canCreate());
        $this->assertFalse($menu->canDelete());
    }

    /**
     * Tests if requireDefaultRecords creates menus from config.
     */
    public function testRequireDefaultRecords()
    {
        Config::modify()->set(MenuSet::class, 'sets', [
            'header' => 'Header Menu'
        ]);

        $menu = MenuSet::get()->find('Slug', 'header');
        $this->assertNull($menu, 'Menu should not exist before requireDefaultRecords');

        (new MenuSet())->requireDefaultRecords();

        $menu = MenuSet::get()->find('Slug', 'header');
        $this->assertNotNull($menu, 'Menu should exist after requireDefaultRecords');
        $this->assertEquals('Header Menu', $menu->Title);
    }
}
