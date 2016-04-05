=== Feed Importer ===
Contributors: bfolliot
Tags: feed, rss, atom
Requires at least: 3.1
Tested up to: 4.0
License: BSD-3-Clause
License URI: https://opensource.org/licenses/BSD-3-Clause

WordPress plugin to import post from RSS or Atom feed.

== Description ==

This plugin help you to create post from feed (RSS or Atom).


Development of this plugin is done on [GitHub](https://github.com/bfolliot/feed-importer). Pull requests welcome. Please see [issues](https://github.com/bfolliot/feed-importer/issues) reported there before going to the plugin forum.


== Instalation ==

Feed Importer use [composer](https://getcomposer.org/), if you already use composer in your project, juste add bfolliot/feed-importer in your composer.json :


```json
{
    "require": {
        "bfolliot/feed-importer": "~0.3"
    }
}
```

If you do not use it (you should really consider using it to manage your project), in your plugins directory (by default, `wp-content/plugins`) :

```sh
git clone --branch 0.1.0 https://github.com/bfolliot/feed-importer.git
cd feed-importer
```

[Get composer](https://getcomposer.org/download/) and run :

```sh
composer.phar install
```

