<?php
	$wtfmo_src = 'http://www.profilactic.com/badge/wtfmo/' . get_option('s_profilactic');
?>
<script src="<?php echo $wtfmo_src; ?>"></script>
<script>
<!--
var urlPrefix = 'http://www.profilactic.com/';
var wtfmo = WtfmoSites();
document.write('<div class="badge">');
document.write('   <div class="badge_list">');
document.write('   <div class="badge_site"><img align="absmiddle" src="' + urlPrefix + 'images/favicons/profilactic.png' + '" alt="P" />&nbsp;<a href="' + urlPrefix + 'mashup/' + username + '">Profilactic</a></div>');
for (var i = 0; i < wtfmo.length; i++) {
   document.write('   <div class="badge_site">');
   if (wtfmo[i].avatar != '') document.write('<img align="absmiddle" src="' + urlPrefix + wtfmo[i].avatar + '" alt="' + wtfmo[i].wtfmo.site_name + '" />&nbsp;');
   document.write('<a href="' + wtfmo[i].wtfmo.site_url + '">' + wtfmo[i].wtfmo.site_name + '</a>');
   document.write('   </div>');
}
document.write('</div></div><br />');
// END -->
</script>