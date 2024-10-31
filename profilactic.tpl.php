<?php
/*
Template Name: Profilactic
*/
?>

<?php get_header(); ?>

<div id="content">

<h1>My [Internet] Life</h1>

	<?php 
	if (function_exists('profilactic')) {
		profilactic();
	} else 
		echo 'My life isn\'t aggregating correctly at the moment.  Check back in a few hours!';
	?>

</div>

<?php get_sidebar(); ?>	
<?php get_footer(); ?>