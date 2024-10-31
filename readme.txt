=== Plugin Name ===
Contributors: ahpatel
Donate Link: http://donorschoose.org/
Tags: profilactic, lifestream, rss, mashup, links, widget, social, timeline, feed, profile
Requires at least: 2.3
Tested up to: 2.7
Stable tag: 1.2.5

WP-Profilactic publishes your LifeStream or informs readers where to find you online by parsing your aggregated activity mashup from Profilactic.

== Description ==

WP-Profilactic publishes your LifeStream or informs readers where to find you online by parsing your aggregated activity mashup from [Profilactic.com](http://www.profilactic.com/).

- **[Lifestream Demo](http://opindian.com/blog/lifestream/)**
- **[Changelog](http://opindian.com/blog/projects/wp-profilactic/)**

**Features:**

* **Aggregates your social networks**, per your Profilactic.com account.
* **Dynamically pulls favicons** for corresponding services.
* **Sidebar widgets** for displaying recent lifestream posts or 'where to find me online'.


**NOTE: [SimplePie Core for WP](http://wordpress.org/extend/plugins/simplepie-core/) is required if you run PHP4 and highly recommended for PHP5** installations.  It aggregates and caches the feed data more efficiently that the native PHP5 parser (i.e., faster load time for your visitors).


== Installation ==

1. Download and unzip the most recent version of Profilactic
2. Upload the entire profilactic folder to `/wp-content/plugins/`
3. Login to your WP Admin panel, click Plugins, and activate Profilactic
4. Go to Options and then click the Profilactic link. Enter your Profilactic username and customize your settings (colors, # of posts, etc.).
5. To show your lifestream on a page (like [My Lifestream](http://opindian.com/blog/lifestream/)):
	* Copy `<?php profilactic(); ?>` or `<?php prof_wtfmo(); ?>` into a template file (sample file, `profilactic.tpl.php`, included in the plugin directory)
	* *Save the template file to `/wp-content/themes/your theme/`*
	* Create a new page (*Write -> Page*) and use the profilactic template (under the *Page Options*, scroll down to *Page Template* and select `Profilactic`).
6. To show your lifestream or wtfmo in the sidebar, add the appropriate Profilactic widget from *Design -> Widgets*.
7. That's it!

To upgrade, simply replace the old Profilactic directory with the newest version.  If you've customized your formatting, make sure you don't override your .css files.

== Frequently Asked Questions ==

= Was this plugin written from scratch!? =

No. This plugin was inspired by, and a fork of, [Kieran Delany](kierandelaney.net/blog/)'s [SimpleLife plugin](kierandelaney.net/blog/projects/simplelife/) for WP.

= Can I add a custom favicon for a custom feed? =

Yes.  Simply place the icon (generally, favicons are 16x16) in the `plugin/profilactic/images/` folder.  The plugin should be named <service>.png... For example, if you have a custom icon for del.icio.us, the icon should be named delicious.png.  For more examples, take a look at the `profilactic/images` directory.

= Do you support PHP4? =

Yes, but only if you install the [SimplePie Core](http://wordpress.org/extend/plugins/simplepie-core/) Plugin.

= Support =

Post your problems, questions, suggestions, or compliments on the [WP-Profilactic Forum](http://wordpress.org/tags/profilactic) or email me at <a title="Reveal this e-mail address" onclick="window.open('http://mailhide.recaptcha.net/d?k=01V95Vbx5WxxTlZd0TekS3GA==&amp;c=6rLQjC-aPY9RoZk66ffiVVrkctSxxpjPdtgIlQiSO-E=', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" href="http://mailhide.recaptcha.net/d?k=01V95Vbx5WxxTlZd0TekS3GA==&amp;c=6rLQjC-aPY9RoZk66ffiVVrkctSxxpjPdtgIlQiSO-E=">click-to-see</a>@opindian.com).

== Screenshots ==

* [Anish's Lifestream Demo](http://opindian.com/blog/lifestream/)
* To add your lifestream here, email me at <a title="Reveal this e-mail address" onclick="window.open('http://mailhide.recaptcha.net/d?k=01V95Vbx5WxxTlZd0TekS3GA==&amp;c=6rLQjC-aPY9RoZk66ffiVVrkctSxxpjPdtgIlQiSO-E=', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" href="http://mailhide.recaptcha.net/d?k=01V95Vbx5WxxTlZd0TekS3GA==&amp;c=6rLQjC-aPY9RoZk66ffiVVrkctSxxpjPdtgIlQiSO-E=">click-to-see</a>@opindian.com