<!DOCTYPE html>
<html>
<head>
<?php
require_once("common.php");
require_once('config.php');
require_once('universeentry.php');
require_once('fppdefines.php');
include 'common/menuHead.inc';

//$settings['Platform'] = "Raspberry Pi"; // Uncomment for testing
//$settings['Platform'] = "BeagleBone Black"; // Uncomment for testing
//$settings['SubPlatform'] = "BeagleBone Black"; // Uncomment for testing
?>
<script>
var currentCapeName = '';
</script>

<?
$currentCape = "";
$currentCapeInfo = json_decode("{ \"provides\": [\"all\"]}", true);
if (isSet($settings['cape-info'])) {
    $currentCapeInfo = $settings['cape-info'];
    $currentCape = $currentCapeInfo["id"];
    echo "<!-- current cape is " . $currentCape . "-->\n";
    printf("<script>\ncurrentCapeName = '%s';\n</script>\n",
        $currentCapeInfo["name"]);
}
if (!isset($currentCapeInfo['provides'])) {
    $currentCapeInfo['provides'][] = "all";
    if (isset($settings['FalconHardwareDetected']) && ($settings['FalconHardwareDetected'] == 1)) {
        $currentCapeInfo['provides'][] = "fpd";
    }
} else if (isset($settings["showAllOptions"]) && $settings["showAllOptions"] == 1) {
    $currentCapeInfo['provides'][] = "all";
    $currentCapeInfo['provides'][] = "fpd";
}
?>

<script language="Javascript">

var channelOutputs = [];
var channelOutputsLookup = [];
var currentTabType = 'UDP';

/////////////////////////////////////////////////////////////////////////////

function UpdateChannelOutputLookup()
{
	var i = 0;
	for (i = 0; i < channelOutputs.channelOutputs.length; i++)
	{
		channelOutputsLookup[channelOutputs.channelOutputs[i].type + "-Enabled"] = channelOutputs.channelOutputs[i].enabled;
		if (channelOutputs.channelOutputs[i].type == "LEDPanelMatrix")
		{
			channelOutputsLookup["LEDPanelMatrix"] = channelOutputs.channelOutputs[i];

			var p = 0;
			for (p = 0; p < channelOutputs.channelOutputs[i].panels.length; p++)
			{
				var r = channelOutputs.channelOutputs[i].panels[p].row;
				var c = channelOutputs.channelOutputs[i].panels[p].col;

				channelOutputsLookup["LEDPanelOutputNumber_" + r + "_" + c]
					= channelOutputs.channelOutputs[i].panels[p];
				channelOutputsLookup["LEDPanelPanelNumber_" + r + "_" + c]
					= channelOutputs.channelOutputs[i].panels[p];
				channelOutputsLookup["LEDPanelColorOrder_" + r + "_" + c]
					= channelOutputs.channelOutputs[i].panels[p];
			}
		}
		else if (channelOutputs.channelOutputs[i].type == "BBB48String")
		{
			channelOutputsLookup["BBB48String"] = channelOutputs.channelOutputs[i];
		}
		else if (channelOutputs.channelOutputs[i].type == "BBBSerial")
		{
			channelOutputsLookup["BBBSerial"] = channelOutputs.channelOutputs[i];
		}
	}
}

function GetChannelOutputConfig()
{
	var config = new Object();

	config.channelOutputs = [];

    var lpc = GetLEDPanelConfig();
	config.channelOutputs.push(lpc);

    channelOutputs = config;
    UpdateChannelOutputLookup();
	var result = JSON.stringify(config);
	return result;
}

function SaveChannelOutputsJSON()
{
	var configStr = GetChannelOutputConfig();

	var postData = "command=setChannelOutputs&file=channelOutputsJSON&data=" + encodeURIComponent(JSON.stringify(configStr));

	$.post("fppjson.php", postData
	).done(function(data) {
		$.jGrowl(" Channel Output configuration saved");
		SetRestartFlag(1);
	}).fail(function() {
		DialogError("Save Channel Output Config", "Save Failed");
	});
}

function inputsAreSane()
{
	var result = 1;

	result &= pixelStringInputsAreSane();

	return result;
}

function okToAddNewInput(type)
{
	var result = 1;

	result &= okToAddNewPixelStringInput(type);

	return result;
}


/////////////////////////////////////////////////////////////////////////////

<?

$channelOutputs = array();
$channelOutputsJSON = "";
if (file_exists($settings['channelOutputsJSON']))
{
	$channelOutputsJSON = file_get_contents($settings['channelOutputsJSON']);
    $channelOutputs = json_decode($channelOutputsJSON, true);
	$channelOutputsJSON = preg_replace("/\n|\r/", "", $channelOutputsJSON);
	$channelOutputsJSON = preg_replace("/\"/", "\\\"", $channelOutputsJSON);
}

// If led panels are enabled, make sure the page is displayed even if the cape is a string cape (could be a colorlight output)
if ($channelOutputs != null && $channelOutputs['channelOutputs'] != null && $channelOutputs['channelOutputs'][0] != null ) {
    if ($channelOutputs['channelOutputs'][0]['type'] == "LEDPanelMatrix" && $channelOutputs['channelOutputs'][0]['enabled'] == 1) {
        $currentCapeInfo["provides"][] = 'panels';
    }
}

?>

function handleCOKeypress(e)
{
	if (e.keyCode == 113) {
		if (currentTabType == 'strings')
			setPixelStringsStartChannelOnNextRow();
	}
}


$(document).ready(function(){
	$(document).on('keydown', handleCOKeypress);

	var channelOutputsJSON = "<? echo $channelOutputsJSON; ?>";

	if (channelOutputsJSON != "")
		channelOutputs = jQuery.parseJSON(channelOutputsJSON);
	else
		channelOutputs.channelOutputs = [];

	UpdateChannelOutputLookup();

	// Init tabs
  $tabs = $("#tabs").tabs({
  		activate: function(e, ui) {
			currentTabType = $(ui.newTab).find('a').attr('tabType');
		},
  		cache: true,
		spinner: "",
		fx: {
			opacity: 'toggle',
			height: 'toggle'
		}
	});

	var total = $tabs.find('.ui-tabs-nav li').length;
	var currentLoadingTab = 1;
	$tabs.bind('tabsload',function(){
		currentLoadingTab++;
		if (currentLoadingTab < total)
			$tabs.tabs('load',currentLoadingTab);
		else
			$tabs.unbind('tabsload');
	}).tabs('load',currentLoadingTab);

    $(document).tooltip();
});

</script>
<title><? echo $pageTitle; ?></title>
</head>
<body>
	<div id="bodyWrapper">
		<?php include 'menu.inc'; ?>
		<br/>

<div id='channelOutputManager'>
		<div class='title'>Channel Outputs</div>
		<div id="tabs">
			<ul>
				<li><a href="#tab-e131" tabType='UDP'>E1.31 / ArtNet / DDP</a></li>
<?
	if ($settings['Platform'] == "Raspberry Pi")
	{
        if (in_array('fpd', $currentCapeInfo["provides"])) {
            echo "<li><a href='#tab-fpd' tabType='FPD'>Falcon Pixelnet/DMX</a></li>\n";
        }
        if (in_array('all', $currentCapeInfo["provides"]) || in_array('strings', $currentCapeInfo["provides"])) {
            echo "<li><a href='#tab-PixelStrings' id='stringTab' tabType='strings'>Pi Pixel Strings</a></li>\n";
        }
	}
	if ($settings['Platform'] == "BeagleBone Black") {
        if (in_array('all', $currentCapeInfo["provides"]) || in_array('strings', $currentCapeInfo["provides"])) {
		echo "<li><a href='#tab-BBB48String' id='stringTab' tabType='strings'>";
		if ($currentCape != "") {
		        if (isset($currentCapeInfo["name"]))
				echo $currentCapeInfo["name"];
			else
				echo $currentCape;
		} else {
			echo "BBB Strings";
		}
		echo "</a></li>\n";
        }
	}
    if ((file_exists('/usr/include/X11/Xlib.h')) &&
        ($settings['Platform'] == "Linux")) {
        echo "<li><a href='#tab-PixelStrings' id='stringTab' tabType='strings'>X11 Pixel Strings</a></li>\n";
    }
    if (in_array('all', $currentCapeInfo["provides"])
	|| in_array('panels', $currentCapeInfo["provides"])
	|| !in_array('strings', $currentCapeInfo["provides"])) {
        echo "<li><a href='#tab-LEDPanels' tabType='panels'>LED Panels</a></li>\n";
    }
?>
				<li><a href="#tab-other" tabType='other'>Other</a></li>
			</ul>

<!-- --------------------------------------------------------------------- -->

<?

include_once('co-universes.php');

if ($settings['Platform'] == "Raspberry Pi")
{
    if (in_array('fpd', $currentCapeInfo["provides"])) {
        include_once('co-fpd.php');
    }
    if (in_array('all', $currentCapeInfo["provides"]) || in_array('strings', $currentCapeInfo["provides"])) {
        include_once('co-piPixelString.php');
    }
}

if (in_array('all', $currentCapeInfo["provides"]) 
    || in_array('panels', $currentCapeInfo["provides"])
    || !in_array('strings', $currentCapeInfo["provides"])) {
    include_once('co-ledPanels.php');
}

if ($settings['Platform'] == "BeagleBone Black")
{
    if (in_array('all', $currentCapeInfo["provides"]) || in_array('strings', $currentCapeInfo["provides"])) {
        include_once('co-bbbStrings.php');
    }
}

if ((file_exists('/usr/include/X11/Xlib.h')) &&
    ($settings['Platform'] == "Linux")) {
    include_once('co-piPixelString.php');
}

include_once("co-other.php");
?>

<!-- --------------------------------------------------------------------- -->

</div>
</div>

<div id='debugOutput'>
</div>

<!-- FIXME, can we put this in co-ledPanels.php? -->
<div id="dialog-panelLayout" title="panelLayout" style="display: none">
  <div id="layoutText">
  </div>
</div>

	<?php	include 'common/footer.inc'; ?>
</div>
</body>
</html>
