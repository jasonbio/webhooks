<?php
	/**
	 * this script takes an unedited video file name and uses as many techniques as possible to strip out the
	 * relevant data, process it through THETVDB's API, and return beautiful looking info (widget)
	 * 
	 * your media server must output containingfile.txt (most windows based media servers have some sort of 
	 * function for this) that can be accessed by this script.
	 * 
	 * Uses the useful TVDB library by Ryan Doherty
	 *
	 * @author Jason Botello <contact@jasonb.io>
	 **/

// include the TheTVDB API files
require 'TVDB.php'; // remember to update this file with your TVDB API key

$txt_file = file_get_contents('containingfile.txt'); // set raw video output file from media server

$rows = explode('*', $txt_file);
$showName = $rows[0];
$fileName = $rows[1];
$playlistPosition = $rows[2];

$shows = TV_Shows::search($showName);
$show = $shows[0];
$finalShowName = $show->seriesName;

// process IMDB link
if ($show->imdbId) {
	$imdb_link = "&raquo; <a href='http://imdb.com/title/$show->imdbId' style='font-weight: bold;color: #ffa531;text-decoration: underline;font-size: 10px;' target='_blank'>IMDB</a>";
} else {
	$imdb_link = null;
}

// process TVDB link
if ($show->id) {
	$tvdbshow_link1 = "<a href='http://thetvdb.com/?tab=series&id=$show->id' style='font-weight: bold;color: #ff7800;text-shadow: 1px 1px 0px #000000;' target='_blank'>";
	$tvdbshow_link2 = "</a>";
} else {
	$tvdbshow_link1 = null;
	$tvdbshow_link2 = null;
}

// process raw filename and attempt to strip out the filler and keep the good stuff
$fileName = str_replace(" ",".",$fileName);
$pattern = "/[sS]?[eE]?[aA]?[sS]?[oO]?[nN]?([0-9]+)[.xX-\s_]?[eE]?[pP]?[iI]?[sS]?[oO]?[dD]?[eE]?([0-9]{2}+)/";
if (preg_match($pattern,$fileName,$n)) {
	$seasonClean = intval($n[1],10);
   	$episodeClean = intval($n[2],10);
} else {
	$seasonClean = null;
}

// process season / episode numbers
// you'll notice I've started splitting $tvdbshow_link and $episode into 1 / 2. This is because TVDB doesn't really handle
// multi part episodes very well. For example, TV shows that have two segments per episode will only count the first
// segment as the complete episode. TV shows that do a full half-hour/hour long format shouldn't have this problem.
// Splitting the vars like this allows for testing of a second part that's been incorrectly labeled as an entirely 
// different episode. I tried to find patterns in TVDBs API that would allow detection weird formatting like that,
// but there really isn't any. Something to do with DVD order vs aired order? I dunno, but this is best I've gotten it so far.
if (is_int($seasonClean) && is_int($episodeClean)) {

	$episode2;
	$overview2;
	$spacer;
	$spacer2 = "&nbsp; / &nbsp;";
	$tvdbepisode1_link1;
	$tvdbepisode1_link2;
	$tvdbepisode2_link1;
	$tvdbepisode2_link2;

	function is_decimal($val) {
    	return is_numeric($val) && floor($val) != $val;
	}

	$episode = $show->getEpisode($seasonClean,$episodeClean);
	$episode2 = $show->getEpisode($seasonClean,$episodeClean+1);
	if ($episode2) {
		$overview2 = $episode2->overview;
	} else {
		$overview2 = null;
	}

	if ($episode->name) {
		$spacer = " - ";
	}

	if ($episode->firstAired) {
		$aired = "Aired:";
		$firstAired = date('F j, Y',$episode->firstAired);
	} else {
		$aired = "Aired:";
		$firstAired = "<font style='color:#aaa;'>n/a</font>";
	}

	if ($episode->overview) {
		$overview = $episode->overview;
		if(substr($overview,0,3) == 'A. ') {
			$overview = substr($overview, 3);
 		}
		$overview = substr($overview,0,120).'';
	} else { 
		$overview = "No overview available for this episode";
	}

	if (!$ignore && $seasonClean && is_decimal($episode->dvdEpisodeNumber) || $force) {
		$episode2 = $show->getEpisode($seasonClean,$episodeClean+1);
		if ($episode2) {
			if ($episode2->overview) {
				$overview2 = $episode2->overview;
				if(substr($overview2,0,3) == 'B. ') {
					$overview2 = substr($overview2, 3);
 				}
				$overview2 = substr($overview2,0,120).'';
			} else {
				$overview2;
			}
		}
	}

	if ($episode->id) {
		$tvdbepisode1_link1 = "<a href='http://thetvdb.com/?tab=episode&seriesid=$episode->seriesId&seasonid=$episode->seasonId&id=$episode->id' style='font-weight: bold;color: #ff7800;text-shadow: 1px 1px 0px #000000;' target='_blank'>";
		$tvdbepisode1_link2 = "</a>";
		if ($episode2) {
			if ($episode2->id) {
				$tvdbepisode2_link1 = "<a href='http://thetvdb.com/?tab=episode&seriesid=$episode2->seriesId&seasonid=$episode2->seasonId&id=$episode2->id' style='font-weight: bold;color: #ff7800;text-shadow: 1px 1px 0px #000000;' target='_blank'>";
				$tvdbepisode2_link2 = "</a>";
				$spacer2 = "&nbsp; / &nbsp;";
			}
		}
	}

	// if episode is a special, rename season to special to avoid confusion
	if ($seasonClean === 0) {
		$seasonClean = "Special";
	}
 
	$overviewCombined = $overview . $spacer2 . $overview2;
	$overviewCombined = substr($overviewCombined,0,120).' '.$tvdbepisode1_link1.'...'.$tvdbepisode1_link2;

	// HTML widget output
	echo "<div style='font: 11px Arial, Helvetica, sans-serif;line-height: 1.5;color:#fff;'><span style='font-weight:bold;color: #ff7800;text-shadow: 1px 1px 0px #000000;white-space: nowrap;'>".$tvdbshow_link1."".$finalShowName."".$tvdbshow_link2."".$spacer."".$tvdbepisode1_link1."".$episode->name."".$tvdbepisode1_link2."".$spacer2."".$tvdbepisode2_link1."".$episode2->name."".$tvdbepisode2_link2."</span><br />
			Season: <strong>".$seasonClean."</strong> Episode: <strong>".$episodeClean."</strong> &nbsp; ".$aired." <strong>".$firstAired."</strong> &nbsp; <span style='font-size:10px;bottom: 1px;position: relative;'>".$imdb_link."</span><br />
			<span style='font-size:11px;color:#aaa;'>".$overviewCombined."</span></div>";

	$myFile2 = "episodeinfo.html";
	$sn2 = fopen($myFile2, 'w');
	$stringData  = "<html><head><meta http-equiv='refresh' content='60'></head><body style='margin:0;padding:0;'><div style='font: 11px Arial, Helvetica, sans-serif;line-height: 1.5;color:#fff;'><span style='font-weight:bold;color: #ff7800;text-shadow: 1px 1px 0px #000000;white-space: nowrap;'>".$tvdbshow_link1."".$finalShowName."".$tvdbshow_link2."".$spacer."".$tvdbepisode1_link1."".$episode->name."".$tvdbepisode1_link2."".$spacer2."".$tvdbepisode2_link1."".$episode2->name."".$tvdbepisode2_link2."</span><br />
		Season: <strong>".$seasonClean."</strong> Episode: <strong>".$episodeClean."</strong> &nbsp; ".$aired." <strong>".$firstAired."</strong> &nbsp; <span style='font-size:10px;bottom: 1px;position: relative;'>".$imdb_link."</span><br />
		<span style='font-size:11px;color:#aaa;'>".$overviewCombined."</span></div></body></html>";
	fwrite($sn2, $stringData);
	fclose($sn2);	

// if episode not found
} else {
	$myFile2 = "episodeinfo.html";
	$sn2 = fopen($myFile2, 'w');
	$stringData  = "<html><head><meta http-equiv='refresh' content='60'></head><body style='margin:0'><div style='font: 12px Arial, Helvetica, sans-serif;line-height: 1.5;color:#fff;'><span style='font-weight:bold;color: #ff7800;text-shadow: 1px 1px 0px #000000;white-space: nowrap;'>".$tvdbshow_link1."".$finalShowName."".$tvdbshow_link2."</span><br />
	Episode information not found &nbsp; <span style='font-size:10px;bottom: 1px;position: relative;'>".$imdb_link."</span><br />
	<span style='font-size:11px;color:#aaa;'>Episode overview information not found.</span></div></body></html>";
	fwrite($sn2, $stringData);
	fclose($sn2);	
}

?>
