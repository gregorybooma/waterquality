<!DOCTYPE html>

<html lang="en">

<head>
<title>MIT Sea Grant Water Quality Data Service</title>
<meta charset="utf-8" />
<META HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">
<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
<script src="https://seagrant.mit.edu/Scripts/dropdowns.js"></script>
<script type="text/javascript" src="https://seagrant.mit.edu/Scripts/mootools.svn.js"></script>
<script type="text/javascript">
window.addEvent('domready', function(){
	var accordion = new Accordion('h3.menusection', 'div.menusection', {

		opacity: false,

		<? if (!empty($nav_display)) { echo 'show: '.$nav_display.', display: false, '; } ?>

		onActive: function(toggler, element){
			toggler.setStyle('color', '#333333');
			/*toggler.setStyle('background', '');*/
		},


		onBackground: function(toggler, element){
			toggler.setStyle('color', '#333333');
			/*toggler.setStyle('background', '');*/
		}

	}, $('accordion'));
});

function resize_image(image, w, h) {

if (typeof(image) != 'object') image = document.getElementById(image);

	var img_width=image.clientWidth;

    if (img_width>w) {
	  if (w == null || w == undefined)
		  w = (h / image.clientHeight) * image.clientWidth;

	  if (h == null || h == undefined)
		  h = (w / image.clientWidth) * image.clientHeight;

	  image.style['height'] = h + 'px';
	  image.style['width'] = w + 'px';
	  return;
	}
}

</script>

	<link rel="stylesheet" href="css/global.css" />
	<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

