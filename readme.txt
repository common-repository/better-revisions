=== Better Revisions ===
Contributors: slehner
Donate link: https://www.silvius.at/
Tags: revision, revisions, post revision, page revision, history, log changes, monitoring, logging, contentmanagement, content, management, enterprise cms, wordpress for enterprise
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 4.4
Tested up to: 6.2.0
Stable tag: 0.5

Extend your Revisions: Add important fields like "Permalink" or "Status" to the revisions for a better Content Management.

== Description ==

Revisions are a main part of every content management system.

Normal wordpress revisions only containing the title, content and excerpt. And Wordpress by itself will only add a new revision, when one (or more) of these three fields were changed. This means, if someone only changes the permalink for example, then Wordpress doesn't add any revision. And if someone changes the content and maybe the author, then Wordpress adds a revision, but only with the old content - the author change will be lost.

For small blogs this will be ok, but what's about multi-author websites or blogs?

I wrote this small plugin for you - for a better revision management in multi-author blogs and sites, for better monitoring the changes of every site, post or custom-post-type and finally for better restoring older revisions.

So, this plugin adds following fields to the revision system:

* The Author
* Post Date
* Permalink
* Post Status
* Post Password
* Comment Status
* Ping Status
* Post/Page Parent
* Menu Order
* more to come

This Plugin also adds a new revision, if only one of the fields above were changed. And it works with automated post/page saves (to the server) too. But I have disabled the client side autosave - becaus on multi-author blogs/sites these function makes no sense and confuses authors more than it helps. But the autosave to the server works perfectly well with all of the fields above added.

If you want to restore a revision, the fields above will restored too. And in case of deleting a post/page, than all revision with all fields above will be deleted too - for a smaller and cleaner database.

No further configuration is needed, the plugin doesn't add any database tables rather saves additional revision data to post-meta and works with custom-post-types too. It's translation ready and allready translated into german.

= Gutenberg support was added =

Now it works with the new Block-Editoe (Gutenberg) and the old Classic-Editor (tinyMCE).

== Installation ==

1. Upload the `better-revisions` folder to the `/wp-content/plugins/` directory
1. Activate the Better Revisions plugin through the 'Plugins' menu in WordPress
1. No further configuration is needed

== Frequently Asked Questions ==

Nothing yet - more to come!

== Screenshots ==

1. All now revisioned post/page fields.
2. Revisions were also made if only some of the additional fields were changed.
3. Same as above with the permalink.
4. Works with autosaves too.

== Changelog ==

= 0.5 =
- refactor inline JS
- some improvements
- test for newest WP and Klassik Editor Updates

= 0.4 =
- test for newest WP and Klassik Editor Updates

= 0.3 =
- Gutenberg support added
- minor bug fixes

= 0.2 =
- WP 4.9.1 check and documentation

= 0.1 =
- Initial Release
