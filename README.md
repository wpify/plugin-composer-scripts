# WPify Plugin Composer Scripts

Composer package with script for wpify/plugin-skeleton based projects. Includes following scripts:

## `rename-wpify-plugin`

Replace references for `wpify-plugin-skeleton` and variants with real plugin name during the [project creation](https://packagist.org/packages/wpify/plugin-skeleton), so the developer has everything ready at the beginning.

**Usage**: `$ composer rename-wpify-plugin wpify-plugin-skeleton some-other-plugin-name`

**Arguments**:

* `search`: Slug to search for, defaults to "wpify-plugin-skeleton".
* `replace`: Replacement slug, defaults to plugin folder basename.
