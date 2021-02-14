#!/usr/bin/php

<?

$pauseAmount = 0; // Number of seconds to pause between activites

//////////// MAIN ////////////
$tasks = array(
    "systemInfo" => 'getSystemInfo',
    "outputProcessors" => 'getOutputProcessors',
    "files" => 'getFiles',
    "models" => 'getModels',
    "multisync" => 'getMultiSync',
    "plugins" => 'getPlugins',
    "schedule" => 'getSchedule',

);

foreach ($tasks as $key => $fun) {
    try {
        $obj[$key] = call_user_func($fun);
    } catch (exception $e) {
        echo ("Call to $t failed");
    }
}
$json = json_encode($obj, JSON_PRETTY_PRINT);
echo ("$json\n");

//////////// End Main ////////////

function validateAndAdd(&$obj, &$input, &$mapping)
{
    foreach ($mapping as $newKey => $oldKey) {
        if (isset($input[$oldKey])) {
            $obj[$newKey] = $input[$oldKey];
        }
    }
}

function getSystemInfo()
{
    $rc = array();
    $data = json_decode(file_get_contents("http://localhost/api/system/status"), true);
    $mapping = array(
        "mqtt" => "MQTT",
        "fppdStatus" => "fppd",
        "fppdMode" => "mode_name",
        "fppdUptimeSeconds" => "uptimeTotalSeconds",
    );
    validateAndAdd($rc, $data, $mapping);

    $rc["wifiInterfaceCount"] = count($data["wifi"]);

    if (isset($data["advancedView"])) {
        $mapping = array(
            "platform" => "Platform",
            "platformVariant" => "Variant",
            "version" => "Version",
            "majorVersion" => "majorVersion",
            "minorVersion" => "minorVersion",
            "typeId" => "typeId",
            "branch" => "Branch",
            "osVersion" => "OSVersion",
            "Kernel" => "Kernel",
            "osRelease" => "OSRelease",
            "channelRanges" => "channelRanges",
            "utilization" => "Utilization",
        );
        validateAndAdd($rc, $data['advancedView'], $mapping);

    }
    return $rc;
}

function getOutputProcessors()
{
    $rc = array();
    $data = json_decode(file_get_contents("http://localhost/api/channel/output/processors"), true);
    if (isset($data['outputProcessors'])) {
        foreach ($data['outputProcessors'] as $obj) {
            $type = $obj['type'];
            if (!isset($rc[$type])) {
                $rc[$type] = array("activeCnt" => 0, "totalCnt" => 0);
            }
            $rc[$type]["totalCnt"] += 1;
            if ($obj['active'] === 1) {
                $rc[$type]["activeCnt"] += 1;
            }
        }
    }

    return $rc;
}

function getFiles()
{
    $types = array("sequences", "effects", "music", "videos");
    $rc = array();
    foreach ($types as $type) {
        $data = json_decode(file_get_contents("http://localhost/api/files/$type"), true);
        if (isset($data['files'])) {
            $cnt = 0;
            $bytes = 0;
            foreach ($data['files'] as $file) {
                $cnt += 1;
                $bytes += $file["sizeBytes"];
            }
            $rc[$type] = array("cnt" => $cnt, "bytes" => $bytes);
        }
    }
    return $rc;
}

function getMultiSync()
{
    $mapping = array(
        "fppModeString" => "fppModeString",
        "channelRanges" => "channelRanges",
        "lastSeen" => "lastSeen",
        "version" => "version",
        "majorVersion" => "majorVersion",
        "minorVersion" => "minorVersion",
        "type" => "type",
        "typeId" => "typeId",
    );

    $data = json_decode(file_get_contents("http://localhost/api/fppd/multiSyncSystems"), true);
    $rc = array();
    if (isset($data["systems"])) {
        foreach ($data["systems"] as $system) {
            $rec = array();
            validateAndAdd($rec, $system, $mapping);
            array_push($rc, $rec);
        }
    }
    return $rc;
}

function getSchedule()
{
    $data = json_decode(file_get_contents("http://localhost/api/fppd/schedule"), true);
    $rc = array();
    if (isset($data["schedule"])) {
        $rc["enabled"] = $data["schedule"]["enabled"];
        $types = array();
        if (isset($data["schedule"]["entries"])) {
            foreach ($data["schedule"]["entries"] as $rec) {
                $type = $rec['type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                }
                if (isset($rec["enabled"]) && $rec['enabled'] == 1) {
                    $types[$type] += 1;
                }
            }
            $rc["types"] = $types;
        }
    }

    return $rc;
}

function getModels()
{
    $data = json_decode(file_get_contents("http://localhost/api/models"), true);
    $rc = array("count" => 0);
    if (is_array($data)) {
        $rc["count"] = count($data);
    }

    return $rc;
}

function getPlugins()
{
    $data = json_decode(file_get_contents("http://localhost/api/plugin"), true);
    return $data;

}

?>