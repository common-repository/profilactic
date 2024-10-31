<?php
/*
Plugin Name: Profilactic
Plugin URI: http://opindian.com/blog/projects/wp-profilactic/
Description: publishes your LifeStream or informs readers where to find you online by parseing your aggregated online activity feed from your <a href="http://www.profilactic.com/">profilactic.com</a> account. Go to <a href="options-general.php?page=profilactic">Options&rarr;Profilactic</a> to configure.  Use the <a href="widgets.php">widgets</a> or place <code>&lt;?php profilactic(); ?></code> or <code>&lt;?php prof_wtfmo(); ?></code> in a page template.  
Version: 1.2.5
Author: Anish H. Patel
Author URI: http://www.opindian.com/blog/

    Changelog:
	<older changelog entries at http://opindian.com/blog/projects/wp-profilactic/>
	May 13th - v 1.2
		* Added sample Template
		* Added cURL fallback option for hosts that disable file_get_contents from url.  (fixed by Alisson Pelucio, www.sellaro.eti.br)
		* All color related CSS code and list items moved to profilactic.php` (Hover in IE, FF & Opera really works now and plugin updates won't mess us your style)
		* Fixed Footer show/hide option
		* Minor: Support Link, Icons, Documentation updated
	May 26th - v 1.2.2
		* Fixed the parse error that prevented activation of the plugin for some users
	Aug 26th - v 1.2.4
		* Icons now stay visible on Hover
		* Corrected some CSS styling and changed .css filenames (style.css is now prof-style.css; sidebar-style.css is now prof-sidebar-style.css)
	Oct 13th - v 1.2.5
		* Fixed a class issue with Magnolia
		* updated regex to accomodate .se, .in, .info, .tv domains

    Todo:
	* Find easier way to make users aware of what the local favicons should be named.
	* Add option to limit number of post from any given service.
	
    To give credit where it's due... 
	This plugin is inspired by, and a fork of,  Kieran Delaney's SimpleLife plugin - 
		http://kierandelaney.net/blog/projects/simplelife/
	
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
*/

//code for stripos for php4 installations  (from http://us3.php.net/manual/en/function.stripos.php#65287)
if (!function_exists("stripos")) {
    function stripos($haystack, $needle, $offset=0) {
        return strpos(strtolower($haystack), strtolower($needle), $offset);
    }
}

//timezone code
// reference: http://us.php.net/manual/en/function.putenv.php#11811
// reference: http://us.php.net/manual/en/function.date-default-timezone-set.php
if (get_option('prof_tz')) {
	if (!function_exists("date_default_timezone_set")) {
	   putenv('TZ='.get_option('prof_tz'));
	   mktime(0,0,0,1,1,2008);
	} else {
		date_default_timezone_set(get_option('prof_tz'));
	}
}

/* Function: Add External Stylesheet to wp head for non-dynamic styles
  *   Check prof-sidebar-style.css for formatting of widgets (Background, spacing, etc)
  *   Check prof-style.css for formatting of template/page that displays the lifestream
  */
function prof_head() {
	echo '<link rel="stylesheet" href="'. get_bloginfo('home') . '/wp-content/plugins/profilactic/prof-style.css" type="text/css" media="screen" />'."\r\n";

	if(is_active_widget('prof_mash_widget') || is_active_widget('prof_wtfmo_widget')) {
		echo '<link rel="stylesheet" href="'. get_bloginfo('home') . '/wp-content/plugins/profilactic/prof-sidebar-style.css" type="text/css" media="screen" />'."\r\n";
	}
}

//Function: Add Color Picker Javascript to admin head (from: 
function prof_colorpick() { 
	echo '<script src="'.get_bloginfo('home').'/wp-content/plugins/profilactic/js/201a.js" type="text/javascript"></script>'; 
} 

/* Determine Service from URL so that appropriate FAVICON will show
  * most services are dynamically detected, but some have to be handled 
  * as one-offs due since the default pattern matching doesn't work for every site.  */
function determineService($title, $url) {
	if (stripos($title, 'friendfeed') !== false) {
		$class = 'friendfeed';
	} else if (stripos($title, 'del.icio.us') !== false) {
		$class = 'delicious';
	} else if (stripos($title, 'My bookmarks') !== false) {
		$class = 'magnolia';
	} else if (stripos($title, 'last.fm') !== false) {
		$class = 'lastfm';
	} else if (stripos($title, 'disqus') !== false) {
		$class = 'disqus';
	} else if (stripos($title, 'yelp') !== false) {
		$class = 'yelp';
	} else if (stripos($title, 'blinklist') !== false) {
		$class = 'blinklist';
	} else if (stripos($title, 'google reader') !== false) {
		$class = 'greader';
	} else {
		$class = serviceNameExtractor($url);
	}
	return $class;
}

// Extracts Service Name from title (used to dynamically pull favicon; also used to clean up/shorten post titles)
  // Regex is not my friend so feel free to suggest a better way to pull the service name from the URL
/* corredted
	*/
function serviceNameExtractor($url) {
	$service = strtolower($url);
	// remove the protocol (http) and domain extensions (.com, .net, etc)
	$service = eregi_replace('(http://www\.|http://|\.com.+|\.net.+|\.org.+|\.se.+|\.info.+|\.in.+|\.tv.+)', '', $service);
	// remove everything after the initial domain
	$service = eregi_replace('/[a-z].+', '', $service);
	// remove everything before the initial <dot> (i.e,, www removed from www.google.com)
	$service = eregi_replace('^[a-zA-Z0-9]+\.', '', $service);
	$service = eregi_replace('\.', '', $service);
	
	return $service;
}

function prof_parserCheck(){
	if (class_exists('SimplePie') && (SIMPLEPIE_BUILD >= 20080102221556)) {
		$parserToUse = 'sp';
	} else if (phpversion() >= 5.0) {
		$parserToUse = 'sx';
	} else $parserToUser = 'default';
	
	return $parserToUse;
}

// curl workarround for webhosts that do not allow URL file access vs (file_get_contents)
function curl_file_get_contents($url){
	$ch = curl_init($url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
	$get_pf = curl_exec($ch);
	curl_close($ch);

	if ($get_pf) return $get_pf;
	else return false;
}

//create options page
function prof_options() {
	if (function_exists('add_options_page')) {
		add_options_page('Profilactic Options', 'Profilactic', 8, basename(__FILE__), 'prof_optionsPage');
    }
}

function prof_optionsPage() {
	if (isset($_POST['info_update'])) { 
?>
		<div id="message" class="updated fade">
		<p><strong>
<?php
		if($_POST['s_profilactic']){
		   update_option('s_profilactic', $_POST['s_profilactic']);
			if(!$_POST['profback'] || !$_POST['proftext']){
			_e('Warning: You\'ve given me a Profilactic Username - but you\'ve not chosen all the style info!<br />', 'English');
			}
		   update_option('profback', $_POST['profback']);
		   update_option('proftext', $_POST['proftext']);
		}else{
		   update_option('s_profilactic', '');
		   update_option('proftext', '');
		   update_option('profback', '');
		}

		if($_POST['prof_flimit']){
		   update_option('prof_flimit', $_POST['prof_flimit']);
		}else{
		   update_option('prof_flimit', '20');
		}
		
		if($_POST['prof_cache']){
		   update_option('prof_cache', $_POST['prof_cache']);
		}else{
			_e('Warning: You haven\'t chosen a cache location yet.<br />', 'English');
		   update_option('prof_cache', '15');
		}

		if($_POST['prof_hovertext']){
		   update_option('prof_hovertext', $_POST['prof_hovertext']);
		}else{
		   _e('Warning: You\'ve not chosen a text color for the hover style. Things will get ugly.<br />', 'English');
		   update_option('prof_hovertext', '');
		}

		if($_POST['prof_hoverback']){
		   update_option('prof_hoverback', $_POST['prof_hoverback']);
		}else{
		   _e('Warning: You\'ve not chosen a background color for the hover style. Things will get ugly.<br />', 'English');
		   update_option('prof_hoverback', '');
		}

		if($_POST['prof_hoverborder']){
		   update_option('prof_hoverborder', $_POST['prof_hoverborder']);
		}else{
		   _e('Warning: You\'ve not chosen a border color for the hover style. Things will get ineresting.<br />', 'English');
		   update_option('prof_hoverborder', '');
		}

		if($_POST['prof_linewidth']){
		   update_option('prof_linewidth', $_POST['prof_linewidth']);
		}else{
		   update_option('prof_linewidth', '86.5');
		}
		if($_POST['prof_time']){
		   update_option('prof_time', $_POST['prof_time']);
		}else{
		   _e('Warning: You\'ve not chosen a time format - resetting to default<br />', 'English');
		   update_option('prof_time', 'H:i');
		}

		if($_POST['prof_date']){
		   update_option('prof_date', $_POST['prof_date']);
		}else{
		   _e('Warning: You\'ve not chosen a date format - resetting to default<br />', 'English');
		   update_option('prof_date', 'M jS');
		}
		
		if($_POST['prof_dateborder']){
		   update_option('prof_dateborder', $_POST['prof_dateborder']);
		}else{
		   _e('Warning: You\'ve not chosen a border color for the border under the Date.<br />', 'English');
		   update_option('prof_dateborder', '');
		}

		if($_POST['prof_tz']){
		   update_option('prof_tz', $_POST['prof_tz']);
		}else{
		   _e('Warning: You\'ve not chosen a timezone - using server default.<br />', 'English');
		   update_option('prof_tz', '');
		}
		
		if($_POST['prof_servicename']){
			update_option('prof_servicename', $_POST['prof_servicename']);
		} else{
			update_option('prof_servicename', '0');
		}

		if($_POST['prof_footer']){
			update_option('prof_footer', $_POST['prof_footer']);
		} else{
			update_option('prof_footer', '0');
		}			
?> 
		OPTIONS UPDATED
		</strong></p>
		</div>
<?php } ?>

<div id="colorpicker201" class="colorpicker201"></div>
  	<div class="wrap" id="profilactic-options">
	<form method="post">
        <?php echo '<h2>Profilactic Options</h2>'; ?>
		<br />
		<fieldset><legend><strong>Plugin Summary</strong></legend>
		The Profilactic WP Plugin parses <a href="http://www.profilactic.com/">profilactic.com</a>'s aggregated content to publish your LifeStream on your blog.<br /><br />
		After setting the options below, <strong>insert <code>&lt;?php profilactic(); ?></code> or <code>&lt;?php prof_wtfmo(); ?></code> into any page template</strong> to show your lifestream.  Additionally, you can use the <a href="widgets.php">sidebar widgets</a>.<br /><br /> See the <a href="http://www.opindian.com/blog/projects/wp-profilactic/">Profilactic WP Plugin Website</a> for documentation and updates.&nbsp;&nbsp;<a href="http://opindian.com/blog/lifestream/">Lifestream Demo</a><br /><br />
		This plugin will pull favicons from Profilactic; however, <strong>custom FAVICONs can be saved as .png's in the plugin's <em>images</em> directory</strong>.<br />Images should be saved as servicename.png (i.e., lastfm.png or google.png or delicious.png). &nbsp;&nbsp; <a href="http://www.convertico.com/">Convert .ico to .png</a><br />
		</legend>
		</fieldset>
		<br />
		<fieldset class="options"><legend><strong>Profilactic Options</strong></legend>
		<table>
			<tr><td width="250px"><label for="s_profilactic"><?php _e('Your Profilactic Username: ', 'English') ?></label></td><td><input type="text" name="s_profilactic" id="s_profilactic" maxlength="20" size="20" value="<?php echo get_option('s_profilactic'); ?>" /></td></tr>
	        <tr><td><label for="prof_flimit"><?php _e('Max No. Of Items To Show: ', 'English') ?></label></td><td><input type="text" name="prof_flimit" id="prof_flimit" maxlength="2" size="2" value="<?php if(get_option('prof_flimit')){ echo get_option('prof_flimit');} else { echo '0';} ?>" /></td></tr>
			<tr><td><label for="prof_servicename"><?php _e('Add Service Name to Post Titles: ', 'English') ?></label></td><td><input type="checkbox" name="prof_servicename" id="prof_servicename" value="1" <?php if (get_option('prof_servicename')) echo "checked"; ?> /></td></tr>
			</table>
			</fieldset>
			<br />
			<fieldset class="options">
			<legend><strong>General Settings</strong></legend>
		<table>
			<tr><td width="250px"><label for="prof_cache"><?php _e('Cache Feeds For (Min): ', 'English') ?></label></td><td><input type="text" name="prof_cache" id="prof_cache" maxlength="2" size="2" value="<?php if(get_option('prof_cache')){ echo get_option('prof_cache');} else { echo '0';} ?>" /></td></tr>
			<tr><td><label for="prof_tz"><?php _e('Timezone: ', 'English') ?></label></td><td><input type="text" name="prof_tz" id="prof_tz" maxlength="20" size="20" value="<?php echo get_option('prof_tz'); ?>" /></td><td><em><a href="http://us3.php.net/manual/en/timezones.php">Timezone Documentation</a></em></td></tr>
			<tr><td><label for="prof_time"><?php _e('Time Format: ', 'English') ?></label></td><td width=250px><input type="text" name="prof_time" id="prof_time" maxlength="10" size="7" value="<?php echo get_option('prof_time'); ?>" />&nbsp;&nbsp; Output: <strong><?php echo date(get_option('prof_time')); ?></strong></td><td><em><a href="http://us3.php.net/date">Date/Time Format Documentation</a></em></td></tr>
			<tr><td><label for="prof_date"><?php _e('Date Format: ', 'English') ?></label></td><td><input type="text" name="prof_date" id="prof_date" maxlength="10" size="7" value="<?php echo get_option('prof_date'); ?>" />&nbsp;&nbsp; Output: <strong><?php echo date(get_option('prof_date')); ?></strong></td><td><em>Update options to update time/date previews.</em></td></tr>
			<tr><td><label for="prof_footer"><?php _e('Display Footer: ', 'English') ?></label></td><td><input type="checkbox" name="prof_footer" id="prof_footer" value="1" <?php if (get_option('prof_footer')) echo "checked"; ?> /></td></tr>
		</table>
		</fieldset><br />
		<fieldset><legend><strong>Formatting Styles</strong></legend>
		<table>
			<tr><td colspan=3><?php _e('These are the styles that the lifestream entries will use by default.', 'English') ?></td></tr>
			<tr><td width="250px"><label for="prof_linewidth"><?php _e('Line Width (%): ', 'English') ?></label></td><td><input type="text" name="prof_linewidth" id="prof_linewidth" maxlength="7" size="7" value="<?php if(get_option('prof_linewidth')){ echo get_option('prof_linewidth');} else { echo '86.5';} ?>" /></td></tr>
			<tr><td width="250px"><a href="javascript:onclick=showColorGrid2('profback','profback');">Select Background:</a>&nbsp;</td><td><input type="text" id="profback" name="profback" size="7" <?php if(get_option('profback')) echo 'style="background-color: '. get_option('profback') .';"'; ?> value="<?php echo get_option('profback') ?>"></td></tr>
			<tr><td><a href="javascript:onclick=showColorGrid2('proftext','proftext');">Select Text:</a>&nbsp;</td><td><input type="text" id="proftext" name="proftext" size="7" <?php if(get_option('proftext')) echo 'style="background-color: '. get_option('proftext') .';"'; ?> value="<?php echo get_option('proftext') ?>"></td></tr>
			<tr><td><a href="javascript:onclick=showColorGrid2('prof_dateborder','prof_dateborder');">Select Bottom-Border for Date:</a>&nbsp;</td><td><input type="text" id="prof_dateborder" name="prof_dateborder" size="7" <?php if(get_option('prof_dateborder')) echo 'style="background-color: '. get_option('prof_dateborder') .';"'; ?> value="<?php echo get_option('prof_dateborder') ?>"</td><td>&nbsp;<em>Line below displayed Dates</em></td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr><td colspan=3><?php _e('These are the styles that the lifestream entries will assume when you hover over them. The top and bottom have a 1px border, you can color the background and color the text.', 'English') ?></td></tr>
			<tr><td><a href="javascript:onclick=showColorGrid2('prof_hoverback','prof_hoverback');">Select Background:</a>&nbsp;</td><td><input type="text" id="prof_hoverback" name="prof_hoverback" size="7" <?php if(get_option('prof_hoverback')) echo 'style="background-color: '. get_option('prof_hoverback') .';"'; ?> value="<?php echo get_option('prof_hoverback') ?>"></td></tr>
			<tr><td><a href="javascript:onclick=showColorGrid2('prof_hovertext','prof_hovertext');">Select Text:</a>&nbsp;</td><td><input type="text" id="prof_hovertext" name="prof_hovertext" size="7" <?php if(get_option('prof_hovertext')) echo 'style="background-color: '. get_option('prof_hovertext') .';"'; ?> value="<?php echo get_option('prof_hovertext') ?>"></td></tr>
			<tr><td><a href="javascript:onclick=showColorGrid2('prof_hoverborder','prof_hoverborder');">Select Border:</a>&nbsp;</td><td><input type="text" id="prof_hoverborder" name="prof_hoverborder" size="7" <?php if(get_option('prof_hoverborder')) echo 'style="background-color: '. get_option('prof_hoverborder') .';"'; ?> value="<?php echo get_option('prof_hoverborder') ?>"></td></tr>
		</table>
		</fieldset><br />
		<fieldset><legend><strong>Optimization & Debug Assistance</strong></legend>
		<ul>
			<li>
				<?php
				if (class_exists('SimplePie')): 
					if (SIMPLEPIE_BUILD >= 20080102221556): echo 'SimplePie Core Support: <strong>Valid and Enabled!</strong>';
					else: echo 'This plugin requires a newer version of the <a href="http://wordpress.org/extend/plugins/simplepie-core">SimplePie Core</a> plugin to enable important functionality. Please upgrade the plugin to the latest version.';
					endif;
				else: 
					if (phpversion() >= 5.0): echo 'Your lifestream (currently parsed by PHP5), will <strong>parse faster by enabling the <a href="http://wordpress.org/extend/plugins/simplepie-core/">SimplePie Core</a> plugin</strong>.';
					else: echo 'You must either: <br />&nbsp;&nbsp;- <strong>Download the <a href="http://wordpress.org/extend/plugins/simplepie-core/">SimplePie Core</a> plugin (recommended)</strong> or <br />&nbsp;&nbsp;- Ask your sysadmin to install PHP5.';
					endif;
				endif;
				?>
			</li>
			<li>Your server is running PHP version: <strong><?php echo phpversion(); ?></strong></li>
			<li>Your cache is expected to be at: <strong><?php echo CACHEDIR; ?></strong></li>
			
			<?php if(!function_exists('wp_cache_enable') && !class_exists('SimplePie')): ?> 
				<li>The use of a caching plugin is highly recommended.  I suggest trying <a href="http://wordpress.org/extend/plugins/wp-super-cache/#post-2572">WP-Super-Cache</a>.</li>
			<?php endif; ?>
			<?php if (!is_dir(CACHEDIR)): ?> 
				<li>No, WordPress' default cache directory <strong>does not exist</strong>! &mdash; Please make sure that a cache directory exists (and is writable).</li>
			<?php elseif (!is_writable(CACHEDIR)): ?> 
				<li>No, WordPress' default cache directory is <strong>not writable</strong>! &mdash; Please make sure that the cache directory is writable by the server.</li>
			<?php else: ?> <li>Yes, WordPress' default cache directory <strong>exists</strong> and <strong>is writable.</strong></li>
			<?php endif; ?>
			
			<li>File bug reports or request enhancements at the plugin's <a href="http://wordpress.org/tags/profilactic">Support Forum</a>.</li>
		</ul></fieldset><br />
		<div class="submit"><input type="submit" name="info_update" value="<?php _e('Update Options', 'English'); ?> &raquo;" /></div>	 
	 </form>
	</div>

<?php   
}

function profilactic(){
?>
	<div id="profilactic">
	<!-- Lifestream-->
	<ul>
<?php
		$pf = '';
		$pf = 'http://www.profilactic.com/rss/'. get_option('s_profilactic') . '?count=' . get_option('prof_flimit');
		
		$parserToUse = prof_parserCheck();

		// Set up date variable.
		$stored_date = '';
		$AllServices = array();

		//echo 'parser being used is: ' . $parserToUse;
		
		switch ($parserToUse) {
			// use SimpleXML from PHP5.0
			case "sx":
				if(@file_get_contents($pf)) {
					$feed = new SimpleXmlElement(file_get_contents($pf));
				} else if (@curl_file_get_contents($pf)) {
					$feed = new SimpleXmlElement(curl_file_get_contents($pf));
				} else echo 'The PHP5 XML Parser cannot pull your profilactic feed due to restrictions on your server.  Try installing/activating the <a href="http://wordpress.org/extend/plugins/simplepie-core">SimplePie Core Plugin</a> and then re-enabling this plugin.';
				
				$count = 0;
				foreach ($feed->channel->item as $item) 
				{
					if ($count < get_option('prof_flimit')) {
						$count++;
						$url = $item->guid;
						$title = $item->title;
						
						// What is the date of the current feed item?
						$item_time = date(get_option('prof_time'), strtotime($item->pubDate));
						$item_date = date(get_option('prof_date'), strtotime($item->pubDate));
						// Is the item's date the same as what is already stored?
						// - Yes? Don't display it again because we've already displayed it for this date.
						// - No? So we have something different.  We should display that.
						if ($stored_date != $item_date) {
							$stored_date = $item_date;
							echo '<li class="profdate">' . $stored_date . '</li>' . "\r\n";
						}
						$class = determineService($title, $url);
						// Option to remove Serivce Name from title
						if (get_option('prof_servicename')=='0') {
							$title = eregi_replace('^([A-Za-z0-9./ ]+:)', '', $title);
						}
						// Check to see if the service has been seen before.  If not, add it to the Services list (used to create dynamic stylesheet and pull favicons)
						if(!in_array($class, $AllServices)) { 
							$AllServices[] = $class; 
						}
					}
					// Display the feed item	
					if ($class !== 'friendfeed') {
						echo '<li><a class="' . eregi_replace('^[0-9].', '', $class) . '" href="' . $url . '"><span class="proftime">' . $item_time . '</span>' . $title . '</a></li>' . "\r\n";
					} else $count--;
				}
			break;
			
			// use SimplePie
			case "sp":
				$feed = new SimplePie($pf, CACHEDIR, 60*get_option('prof_cache'));
				foreach ($feed->get_items(0,get_option('prof_flimit')) as $item)	
				{				
					$url = $item->get_permalink();
					$title = $item->get_title();
					
					// What is the date of the current feed item?
					$item_date = $item->get_date(get_option('prof_date'));
					$item_time = $item->get_date(get_option('prof_time'));
					// Is the item's date the same as what is already stored?
					// - Yes? Don't display it again because we've already displayed it for this date.
					// - No? So we have something different.  We should display that.
					if ($stored_date != $item_date) {
						$stored_date = $item_date;
						echo '<li class="profdate">' . $stored_date . '</li>' . "\r\n";
					}
						
					$class = determineService($title, $url);
					// Option to remove Serivce Name from title
					if (get_option('prof_servicename')=='0') {
						$title = eregi_replace('^([A-Za-z0-9./ ]+:)', '', $title);
					}
					// Check to see if the service has been seen before.  If not, add it to the Services list (used to create dynamic stylesheet and pull favicons)
					if (!in_array($class, $AllServices)) { 
						$AllServices[] = $class; 
					}
					// Display the feed item	
					if ($class !== 'friendfeed') {
						echo '<li><a class="' . eregi_replace('^[0-9].', '', $class) . '" href="' . $url . '"><span class="proftime">' . $item_time . '</span> ' . $title . '</a></li>' . "\r\n";
					} else $count--;
				}
			break;
			default:
				echo "The WP Admin needs to upgrade to PHP5 or install the SimplePie Core Plugin";
		}
		echo '</ul><br /><br />';	
		// footer/credits
		if (get_option('prof_footer')=='1') {
			echo '<div align="right"><small><em> plugin by <a href="http://www.opindian.com/blog/">Anish H. Patel</a>; powered by <a href="http://www.profilactic.com/" target="_blank" style="color:orange;">Profilactic</a>.</em></small></div>';
		}
		echo '</div>';
		
		// DYNAMIC STYLESHEET.  Add static formatting code to the plugin's PROF-STYLE.CSS  file //
		echo "\n" . '<!-- Dynamic Styleshhet for WP-Profilactic -->' . "\n";
		echo '<style type="text/css">' . "\n" . '<!-- Favicons for all your services -->' . "\n\t" .'a.default {	background: '. get_option('profback') .' url('. get_bloginfo('home') .'/wp-content/plugins/profilactic/images/rss.png) no-repeat 10px 50% !important; }';
		foreach ($AllServices as $service) {
			$class = eregi_replace('^[0-9].', '', $service);
			$favicon = get_bloginfo('home') . '/wp-content/plugins/profilactic/images/' . $service . '.png';
			$local_icon = WP_CONTENT . '/plugins/profilactic/images/'. $service .'.png';
			$remote_icon = @getimagesize('http://www.profilactic.com/images/favicons/' . $service . '.gif');
			if (file_exists($local_icon)){
				echo "\n\t" . 'a.' . $class . "\t" . '{ background: ' . get_option('profback') . ' url('. $favicon .') no-repeat 5px top !important; }';
			} else if($remote_icon){
				echo "\n\t" . 'a.' . $class . "\t" . '{ background: ' . get_option('profback') . ' url(http://www.profilactic.com/images/favicons/'. $service . '.gif) no-repeat 5px top !important; }';
			} else { 
				echo "\n\t" . 'a.' . $class . "\t" . '{ background: ' . get_option('profback') . ' url('. get_bloginfo('home') .'/wp-content/plugins/profilactic/images/rss.png) no-repeat 5px top !important; }';
			}
		}
?>
/* Lifestream Style Info Below */
#profilactic ul a:link, #profilactic ul a:visited {
	display: block;
	text-decoration: none;
}
#profilactic li a:link, #profilactic li a:visited {
	display: block;
	margin: 0;
	width: <?php echo get_option('prof_linewidth') ?>%;
	padding: 2px 1px 2px 30px;
}
<!-- Hover for all links -->
#profilactic ul li a:link {
	list-style-type: none;
	list-style-position: outside;
	border-top: 1px solid <?php echo get_option('profback') ?> !important;	
	border-bottom: 1px solid <?php echo get_option('profback') ?> !important;
}

#profilactic ul li a:hover {
	background-color: <?php echo get_option('prof_hoverback') ?> !important;
	border: 1px dotted <?php echo get_option('prof_hoverborder') ?> !important;
	color: <?php echo get_option('prof_hovertext') ?> !important;
	text-decoration: none;
	padding: 1px 1px 1px 30px;
	width: <?php echo get_option('prof_linewidth') ?>%;
}

ul .profdate {
	border-bottom: 1px solid <?php echo get_option('prof_dateborder') ?>;
}

	</style>
<?php 
	// END DYNAMIC STYLESHEET //
}

//set initial defaults for feeds
add_option('s_profilactic', '');
add_option('profback', '');
add_option('proftext', '');

add_option('prof_hoverback', '');
add_option('prof_hovertext', '');
add_option('prof_hoverborder', '');
add_option('prof_linewidth', '');

add_option('prof_flimit', '20');
add_option('prof_cache', '15');

add_option('prof_time', 'H:i');
add_option('prof_date', 'M jS');
add_option('prof_dateborder', '');

add_option('prof_tz', '');
add_option('prof_servicename', '0');
add_option('prof_footer', '1');

//cache me up
define('WP_CONTENT', eregi_replace('/plugins/.+', '', dirname(__FILE__)));
define('CACHEDIR', WP_CONTENT . '/cache/');


//--WIDGET-FUNCTIONS--//
//--- scripts pulled from http://www.profilactic.com/badge.jsp ---//

// Lifestream/Mashup
function prof_mash($count) {
	include("js/mashup_widget.js.php");
}

// Where To Find Me Online (WTFMO) Script
function prof_wtfmo() {
	include("js/wtfmo_widget.js.php");
}

// Clippings (coming soon...)
/*
function prof_clip() {
	include("js/clip_widget.js.php");
}
*/

// Widget Initialization & Options Code
function prof_widget_init() {
	if (!function_exists('register_sidebar_widget'))
		return;
	
	function prof_mash_widget($args) {
		extract($args);
	  
		$options = get_option('prof_mash_widget');
		$title = $options['title'];
		$count = $options['count'];
		if ($count > 7) $count = 7;

		echo $before_widget . $before_title . $title . $after_title;
		prof_mash($count);
		echo $after_widget;
	} 
	
	function prof_mash_widget_control() {
		$options = get_option('prof_mash_widget');
		if ( !is_array($options) )
			$options = array('title'=>'My Mashup', 'count'=>'7');
		if ( $_POST['prof-mash-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['prof-mash-title']));
			$options['count'] = is_numeric($_POST['prof-mash-count']) ? $_POST['prof-mash-count'] : 7;
			update_option('prof_mash_widget', $options);
		}
		$options['title'] = htmlspecialchars($options['title'], ENT_QUOTES);		
		?>
		<p>Displays the latest X items from your Lifestream</p>
		<p><label for="prof-title"><?php echo __('Title:'); ?>
			<input style="width: 200px;" id="prof-mash-title" name="prof-mash-title" type="text" value="<?php echo $options['title'] ?>" />
		</label></p>
		<p><label for="prof-count"><?php echo __('Number of Posts (1-7):'); ?>
			<input style="width: 80px;" id="prof-mash-count" name="prof-mash-count" type="text" value="<?php echo $options['count'] ?>" />
		</label></p>
		<input type="hidden" name="prof-mash-submit" value="1" />
		<?php
	}
	
	function prof_wtfmo_widget($args) {
		extract($args);

		$options = get_option('prof_wtfmo_widget');
		$title = $options['title'];	
		$user = get_option('s_profilactic');
		
		echo $before_widget . $before_title . $title . $after_title;
		prof_wtfmo();
		echo $after_widget;
	} 
		
	function prof_wtfmo_widget_control() {
		$options = get_option('prof_wtfmo_widget');
		if ( !is_array($options) )
			$options = array('title'=>'WTFMO');
		if ( $_POST['prof-wtfmo-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['prof-wtfmo-title']));
			update_option('prof_wtfmo_widget', $options);
		}
		$options['title'] = htmlspecialchars($options['title'], ENT_QUOTES);
		
		?>
		<p>Where To Find Me Online (WTFMO)</p>
		<p><label for="prof-title"><?php echo __('Title:'); ?>
			<input style="width: 200px;" id="prof-wtfmo-title" name="prof-wtfmo-title" type="text" value="<?php echo $options['title'] ?>" />
		</label></p>
		<input type="hidden" name="prof-wtfmo-submit" value="1" />
		<?php
	}
	/*  // Coded this in anticipation of a Clipping function from Profilactic //
	function prof_clip_widget($args) {
		extract($args);

		$options = get_option('prof_clip_widget');
		$title = $options['title'];	
		$user = get_option('s_profilactic');
		
		echo $before_widget . $before_title . $title . $after_title;
		prof_clip();
		echo $after_widget;
	} 

	function prof_clip_widget_control() {
		$options = get_option('prof_clip_widget');
		if ( !is_array($options) )
			$options = array('title'=>'My Clippings', 'count'=>'5');
		if ( $_POST['prof-clip-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['prof-cip-title']));
			$options['count'] = is_numeric($_POST['prof-clip-count']) ? $_POST['prof-clip-count'] : 5;
			update_option('prof_clip_widget', $options);
		}
		$options['title'] = htmlspecialchars($options['title'], ENT_QUOTES);
		
		?>
		<p>Displays the latest X clippings</p>
		<p><label for="prof-title"><?php echo __('Title:'); ?>
			<input style="width: 200px;" id="prof-clip-title" name="prof-clip-title" type="text" value="<?php echo $options['title'] ?>" />
		</label></p>
		<p><label for="prof-count"><?php echo __('Number of Clippings:'); ?>
			<input style="width: 80px;" id="prof-clip-count" name="prof-clip-count" type="text" value="<?php echo $options['count'] ?>" />
		</label></p>
		<input type="hidden" name="prof-clip-submit" value="1" />
		<?php
	}*/
	
	register_sidebar_widget(array('Profilactic Mashup', 'widgets'), 'prof_mash_widget');
	register_widget_control(array('Profilactic Mashup', 'widgets'), 'prof_mash_widget_control');
	register_sidebar_widget(array('Profilactic WTFMO', 'widgets'), 'prof_wtfmo_widget');
	register_widget_control(array('Profilactic WTFMO', 'widgets'), 'prof_wtfmo_widget_control');
	//register_sidebar_widget(array('Profilactic Clippings', 'widgets'), 'prof_clip_widget');
	//register_widget_control(array('Profilactic Clippings', 'widgets'), 'prof_clip_widget_control');
}	

//Add Menu Items
add_action('admin_menu',	'prof_options');
add_action('admin_head', 	'prof_colorpick');
add_action('widgets_init',	'prof_widget_init');
add_action('wp_head',		'prof_head');

?>