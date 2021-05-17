<?php

// Regenradar - Modul zum zyklischen Pollen vom morgenwirdes.de-Niederschlagsradar
// Quelle: Deutscher Wetterdienst          IPS-Modul v1.0 Build 1000 von ika

$location = 'lat=52.22&long=7.48';
//$location = 'plz=48432';

$treshold = 0;

$urltext = 'https://morgenwirdes.de/api/v2/rtxt.php?'.$location;
$urljson = 'https://morgenwirdes.de/api/v2/rjson.php?'.$location; 

$buffertext = @implode('', file($urltext));
if (empty($buffertext)) { FC_DL_Error(true); return; };
SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), "Forecast_Text", 3, "", ""), $buffertext);

$bufferjson = @implode('', file($urljson));
if (empty($bufferjson)) { FC_DL_Error(true); return; };
$json = json_decode($bufferjson,true);
$intensityonstart = $treshold; $rainstart = 0; $rainstartts = 0; $rainendts = 0;

for ($i = 0; $i <= 24; $i++) {
SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), sprintf("%'.02d", $i)."_FC_Intensity", 2, "", " ".$json[$i]['time']), $json[$i]['dbz']);
if(($intensityonstart<=$treshold) && ($json[$i]['dbz']>$treshold)) { $intensityonstart = $json[$i]['dbz']; $rainstart = $json[$i]['time']; $rainstartts = $json[$i]['timestamp']; };
if($json[$i]['dbz']>0) { $rainendts = $json[$i]['timestamp']; };
};

SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), "Rain_Intensity_on_Start", 2, "", " ".$rainstart), $intensityonstart);
SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), "FC_Rain_Start", 1, "~UnixTimestamp", ""), $rainstartts);
SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), "FC_Rain_End", 1, "~UnixTimestamp", ""), $rainendts);
FC_DL_Error(false);


function FC_Variables($id, $name, $type, $profile = "", $fctime)
{
    # type: 0=boolean, 1 = integer, 2 = float, 3 = string;
    global $IPS_SELF;
    $vid = @IPS_GetObjectIDByIdent($name, $id);
    if($vid === false)
    {
        $vid = IPS_CreateVariable($type);
        IPS_SetParent($vid, $id);
        IPS_SetIdent($vid, $name);
        IPS_SetName($vid, $name);
        if($profile !== "") { IPS_SetVariableCustomProfile($vid, $profile); }
    }
    IPS_SetName($vid, $name.$fctime);
    return $vid;
};

function FC_DL_Error($increase = true)
{
    $id = IPS_GetParent($_IPS['SELF']);
    $name = "DL_Error";
    $vid = @IPS_GetObjectIDByIdent($name, $id);
    if($vid === false)
    {
        $vid = IPS_CreateVariable(1);
        IPS_SetParent($vid, $id);
        IPS_SetIdent($vid, $name);
        IPS_SetName($vid, $name);
    };
    if($increase) 
    { 
        SetValue($vid, GetValue($vid)+1); 
        echo "Fehler beim Download!"; } 
        else { SetValue($vid, 0); 
    };
    if(GetValue($vid)>=3) 
    { 
        SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), "Rain_Intensity_on_Start", 2, "", " ERROR"), -99); 
        SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), "FC_Rain_Start", 1, "~UnixTimestamp", " ERROR"), 0);
        SetValue(FC_Variables(IPS_GetParent($_IPS['SELF']), "FC_Rain_End", 1, "~UnixTimestamp", " ERROR"), 0);
        IPS_LogMessage($_IPS['SELF'], "+++++ Fehler beim Download des Regenradar/Forecast! +++++");
    };
    return $vid;
};
