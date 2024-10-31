<?php
	$mashup_src = 'http://www.profilactic.com/badge/mashup/' . get_option('s_profilactic');
?>
<script src="<?php echo $mashup_src; ?>"></script>
<script>
<!--
	var urlPrefix = 'http://www.profilactic.com/';
	var counter = '<?php echo $count; ?>';
	var mashups = Mashups();
	document.write('<div class="badge">');
	document.write('<div class="badge_list">');
	for (var i = 0; i < counter; i++) {
	   document.write('<div class="badge_site">');
	   if (mashups[i].favico != '') document.write('<img align="absmiddle" src="' + urlPrefix + mashups[i].favico + '" />');
	   document.write('<a href="' + mashups[i].link + '">' + mashups[i].title + '</a>');
	   document.write('</div>');
	}
	document.write('<div class="prof_link"><a href="' + urlPrefix + 'mashup/' + username + '">My complete mashup</a></div>');
	document.write('</div></div><br />');
// END -->
</script>