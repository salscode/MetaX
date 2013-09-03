<?php
/*
	MetaX (Meta Tags Extended) for Revolution
	Version: 2.0.2
	Author: Salvatore Sodano - http://salscode.com
	Other Contributors:
		Mike Stop Continues - http://mikestopcontinues.com
		Jakob Class - http://www.class-zec.de <jakob.class@class-zec.de>
		Stefan Rochlitzer - icebear-solutions.com <Stefan.Rochlitzer@icebear-solutions.com>
	Support Page: http://salscode.com/index.php?id=93

	Description: Automatically generates Meta tags for your pages, along with a base and a couple link tags.
	For a full description visit http://salscode.com/index.php?id=93.

	MODx Versions:
	Evolution - This is the Revo version, visit http://modx.com/extras/package/metax-evo for Evo.
	Revolution - All versions, tested up to 2.2.

	Parameters:
	http://salscode.com/index.php?id=93
	 
	Version History:
	1.0 - Private beta.
	1.1 - First Public release.
	1.2 - Thank you "Mike Stop Continues" for cleaning up some of the code. He made all of the
	variables below work as inline parameters (they can still be set below if you wish)
	and added mobile icon support. Also I've added RSS tag support and support for
	different HTML and xHTML syntaxes.
	1.3 - Dublin Core support via &dublin parameter.
	Also added &tabs parameter to control the number of tabs in front of each element.
	Added the &ietool and &css parameters. As always, fully backward compatible.
	1.3.1 - Fixes issues with getTvOutput function. Adds &spaces parameter which controls the
	number of spaces in front of each tag.
	1.4 - Canonical URL now supports Archivist URLs (Enabled in Revo only), thank you Jakob Class
	for working on this feature. Fixed possible issue with a function declaration.
	1.5 - Implemented StripTags function for all document fields (just in case you put some html in a
	description field). Also removed all deprecated functions in preparation for Revo 2.1.
	1.6 - Add HTML5 compat.
	2.0 - Large rewrite. Employs TPL chunks to allow maximum flexibility.
	2.0.1 - Small error fix about getOne use.
	2.0.2 - Fix undefined variable warnings.
*/
/**********Variables***********/
$configs = $modx->getConfig();
$sitename = trim($configs['site_name']);
$sitestart = trim($configs['site_start']);
 
//HTML
$html = empty($html) ? 0 : $html;
$html5 = empty($html5) ? 0 : $html5;
 
//Favicon Relative Path - Default is set to the site root, shown below.
//If the code for your favicon does not display, then the below path may be incorrect.
$favicon = empty($favicon) ? 'favicon.ico' : $favicon;
$mobile = empty($mobile) ? 'mobile.png' : $mobile;

//Copyrighted until - Blank defaults to current year.
$copytill = empty($copytill) ? '' : $copytill;

//Copyrighted since - Blank omits this from view.
$copyfrom = empty($copyfrom) ? '' : $copyfrom;

//RSS & CSS
$rss = empty($rss) ? '' : $rss;
$css = empty($css) ? '' : $css;

$id = $modx->resource->get('id');
$siteurl = $configs['site_url'];

/*----------End Variables----------*/

if($html5 == 1) {$html = 2;}
switch ($html)
{
	default:
	case 0:
		$tagend = " />";
		$tpl = empty($tpl) ? 'metax-xhtml4' : $tpl;
		break;
	case 1:
		$tagend = " >";
		$tpl = empty($tpl) ? 'metax-html4' : $tpl;
		break;
	case 2:
		$tagend = " />";
		$tpl = empty($tpl) ? 'metax-html5' : $tpl;
		break;
}
/*--------------------*/

/**Robots**/
$tmp = $modx->resource->get('searchable');
if ($tmp == 1) {$output["metax.robots"] = "index, follow";}
else {$output["metax.robots"] = "noindex, nofollow";}
/*--------------------*/

/**Creator/Editor**/
$created = $modx->resource->get('createdby');
if ($created != '')
{
	$user = $modx->getObject("modUser", $created);
	if($user != NULL) {$output["metax.createdby"] = $user->getOne("Profile")->get("fullname");}
	else {$output["metax.createdby"] = $sitename;}
}
$edited = $modx->resource->get('editedby');
if ($edited != '')
{
	$user = $modx->getObject("modUser", $edited);
	if($user != NULL) {$output["metax.editedby"] = $user->getOne("Profile")->get("fullname");}
	else {$output["metax.editedby"] = $sitename;}
}
/*--------------------*/

/**Copyright**/
if ($copyfrom != '' && $copytill != '' && $copyfrom != $copytill) {$copyyears = $copyfrom." - ".$copytill;}
elseif (($copyfrom == '' && $copytill != '') || ($copytill != '' && $copytill == $copyfrom)) {$copyyears = $copytill;}
elseif ($copyfrom != '' && $copytill == '') {$copyyears = $copyfrom." - ".date('Y');}
elseif ($copyfrom == '' && $copytill == '') {$copyyears = date('Y');}
$output["metax.copyyears"] = $copyyears;
/*--------------------*/

/**Pragma & Cache-Control**/
$pragma = $modx->resource->get('cacheable');
if ($pragma == 1)
{
	if($html5 != 1) {$output["metax.cache"] = "cache";}
	else {$output["metax.cache"] = "public";}
}
else {$output["metax.cache"] = "no-cache";}

/**Canonical Link**/
if ($id == $sitestart) {$output["metax.canonical"] = $siteurl.$urlExtension;}
else
{
	$urlExtension = '';
	try
	{
		if($modx->getObject('modSnippet', array('name' => 'Archivist')) != NULL)
		{
			/* Support for FURLs created by Archivist: append year and month to canonical url */
			$archivistFilterPrefix = $modx->getOption('archivistFilterPrefix',$scriptProperties,'arc_');
			$archivist = $modx->getService('archivist','Archivist',$modx->getOption('archivist.core_path',null,$modx->getOption('core_path').'components/archivist/').'model/archivist/',array('filterPrefix' => $archivistFilterPrefix));
			if (($archivist instanceof Archivist))
			{
				// Archivist is installed...
				$year = $modx->getOption($archivistFilterPrefix.'year',$_REQUEST,$modx->getOption('year',$scriptProperties,''));
				$year = (int)$archivist->sanitize($year);
				if(!empty($year)) {$urlExtension = $year;}
				$month = $modx->getOption($archivistFilterPrefix.'month',$_REQUEST,$modx->getOption('month',$scriptProperties,''));
				$month = (int)$archivist->sanitize($month);
				if (!empty($month))
				{
					if (strlen($month) == 1) $month = '0'.$month;
					$urlExtension .= '/'.$month;
				}
			}
		}
	}
	catch (Exception $e) {/*No Archivist, do nothing.*/}
	$output["metax.canonical"] = $modx->makeUrl($id, '', '', 'full').$urlExtension;
}
/*--------------------*/

/**Favicon**/
if (is_file($favicon)) {$favicon = $siteurl.$favicon;}
else {$favicon = '';}
$output["metax.favicon"] = $favicon;

/**Mobile icon**/
if (is_file($mobile)) {$mobile = $siteurl.$mobile;}
else {$mobile = '';}
$output["metax.mobile"] = $mobile;
/*--------------------*/

/**RSS Feed**/
$rss = explode(",", $rss);
$count = count($rss);
$i = 0;
$output["metax.rss"] = "";
while ($i < $count)
{
	$rss[$i] = trim($rss[$i]);
	$tmpdoc = $modx->getObject('modResource',array('id' => $rss[$i]));
	if ($tmpdoc != NULL)
	{
		$output["metax.rss"] .= "<link href=\"".$modx->makeUrl($rss[$i], '', '', 'full')."\" rel=\"alternate\" type=\"application/rss+xml\" title=\"".$tmpdoc->get('pagetitle')."\"".$tagend;
	}
	$i++;
	if($i < $count) {$output["metax.rss"] .= "\n";}
}
/*--------------------*/

/**CSS Links**/
$css = explode(",", $css);
$count = count($css);
$i = 0;
while ($i < $count)
{
	$temp = $css[$i];
	$temp = explode(":", $temp);
	$countt = count($temp);
	$temp[0] = trim($temp[0]);
	if (is_file($temp[0]))
	{
		if ($countt == 1) {$output["metax.css"] .= "<link rel=\"stylesheet\" href=\"".$temp[0]."\" type=\"text/css\"".$tagend;}
		else
		{
			$temp[1] = trim($temp[1]);
			$output["metax.css"] .= "<!--[if ".$temp[1]."]><link rel=\"stylesheet\" href=\"".$temp[0]."\" type=\"text/css\"".$tagend."<![endif]-->";
		}
	}
	$i++;
	if($i < $count) {$output["metax.css"] .= "\n";}
}
/*--------------------*/

echo $modx->getChunk($tpl, $output);
?>
