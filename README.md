# Silverstripe Menu Manager

A simple menu manager for Silverstripe CMS 6, using the `silverstripe/linkfield` module for managing links. It allows you to define menu sets (e.g., "Main Menu", "Footer Menu") in your configuration and manage their items in the CMS.

## Features

- Manage multiple menu sets.
- Nested menu items (if allowed).
- Uses `LinkField` for versatile link types (Internal, External, Email, Phone, File).
- Permission-based editing for each menu set.
- Subsite support (if `silverstripe/subsites` is installed).

## Requirements

- PHP ^8.4
- Silverstripe Framework ^6
- Silverstripe CMS ^6
- Silverstripe LinkField ^4
- GridField Extensions ^5

## Installation

```bash
composer require wernerkrauss/silverstripe-menumanager
```

## Configuration

You can define your menu sets in a YAML configuration file (e.g., `app/_config/menu.yml`).

```yaml
---
Name: Site-menus
---
Netwerkstatt\Menumanager\Model\MenuSet:
  sets:
    main:
      title: 'Main Menu'
    footer:
      title: 'Footer Menu'
      allow_children: true
```

After adding or changing this configuration, make sure to run a `dev/build`. The module will automatically create or update the `MenuSet` records in the database.

## Usage

### Template Helper

The module provides a global template variable `MenuSet` to access links of a specific set by its slug.

```ss
<% with $MenuSet('footer') %>
    <nav>
        <ul>
            <% loop $Items %>
                <% if $IsEnabled %>
                    <li>
                        <a href="{$Link.URL}" <% if $Link.OpenInNew %>target="_blank"<% end_if %>>
                            {$Title}
                        </a>
                        <% if $Children %>
                            <ul>
                                <% loop $Children %>
                                    <% if $IsEnabled %>
                                        <li>
                                            <a href="{$Link.URL}">{$Title}</a>
                                        </li>
                                    <% end_if %>
                                <% end_loop %>
                            </ul>
                        <% end_if %>
                    </li>
                <% end_if %>
            <% end_loop %>
        </ul>
    </nav>
<% end_with %>
```

Note: In the context of `MenuSet('slug')`, you get a list of `LinkItem` objects.

### Built-in Template

You can also include the provided default template:

```ss
<% with $MenuSet('footer') %>
    <% include Netwerkstatt\\Menumanager\\Model\\Includes\\MenuSet %>
<% end_with %>
```

## Permissions

For each menu set defined in the config, a specific permission is created (e.g., "Manage links within 'Footer Menu'"). This allows you to delegate menu management to specific CMS users or groups without giving them full access to all menus.

## License

This module is licensed under the BSD-3-Clause license.

## Thanks to
GorrieCoe for creating menu manager modules for previous Silverstripe versions.

## Need Help?

If you need some help with your Silverstripe project, feel free to [contact me](mailto:werner.krauss@netwerkstatt.at) ✉️.

See you at next [StripeCon](https://stripecon.eu) 👋
