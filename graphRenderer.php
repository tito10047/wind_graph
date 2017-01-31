<?php
/**
 * Created by PhpStorm.
 * User: mostkaj
 * Date: 1/2/2017
 * Time: 20:17
 */

//require_once __DIR__ . '/data.php';

class data
{
	public $channel_id;
	public $value;
	public $date;
	public $stat;
}

/** @var data[] $data */
/*$data = json_decode($data259200);
if ($data === null) {
	echo "error parse json";
	return;
}*/
global $smerVetraOffset;
$smerVetraOffset=0;
/**
 * @param $startDate
 * @param $endDate
 * @param data[] $smerVetraData
 * @return array|null
 */
function getAvgWind($startDate,$endDate, &$smerVetraData){
	global $smerVetraOffset;
	$seconds = $smerVetraData[$smerVetraOffset]->date;
	while($smerVetraOffset<count($smerVetraData)-1 && $seconds<$startDate){
		$seconds = $smerVetraData[++$smerVetraOffset]->date;
	}
	$data=[0,0,0,0,0,0];
	$cnt=0;
	while($smerVetraOffset<count($smerVetraData)-1 && $seconds<$endDate){
		$seconds = $smerVetraData[$smerVetraOffset]->date;
		$data[0] += $smerVetraData[$smerVetraOffset]->value;
		$data[$smerVetraData[$smerVetraOffset++]->stat]++;
		$cnt++;
	}
	if ($cnt==0){
		if ($smerVetraOffset>0){
			$smerVetraOffset--;
			return [$smerVetraData[$smerVetraOffset]->date,$smerVetraData[$smerVetraOffset]->stat];
		}else {
			return null;
		}
	}
	$status=0;
	$maxCnt=0;
	for($i=1;$i<6;$i++){
		if ($data[$i]>$maxCnt){
			$status=$i;
			$maxCnt=$data[$i];
		}
	}
	return [$data[0]/$cnt,$status];
}

/** @var  $data */
/** @var int $rychlostVetraChannel */
/** @var int $smerVetraChannel */
/** @var int $width */
/** @var int $height */
//$width = 1024;
//$height = 300;
$graphX = 30;
$graphY = 0;
$widthGraph = $width - $graphX - 15;
$heightGraph = $height - 20;
$widthGraphColumn = 75;
$heightGraphRow = 50;

$startDate = time() * 2;
$endDate = 0;

/** @var data[] $smerVetraData */
$smerVetraData = [];
/** @var data[] $rychlostVetraData */
$rychlostVetraData = [];

$minRychlostVetra = 1000;
$maxRychlostVetra = 0;

foreach ($data as &$item) {
	$item->date = strtotime($item->date);
	if ($item->date < $startDate) {
		$startDate = $item->date;
	}
	if ($item->date > $endDate) {
		$endDate = $item->date;
	}
	switch ($item->channel_id) {
		case $rychlostVetraChannel:
			$rychlostVetraData[] = $item;
			if ($minRychlostVetra > $item->min) {
				$minRychlostVetra = $item->min;
			}
			if ($maxRychlostVetra < $item->max) {
				$maxRychlostVetra = $item->max;
			}
			break;
		case $smerVetraChannel:
			$smerVetraData[] = $item;
			break;
	}
}
unset($data);
/**
 * @param data $d1
 * @param data $d2
 * @return int
 */
$sortFunc = function ($d1, $d2) {
	if ($d1->date == $d2->date) {
		return 0;
	}
	return $d1->date > $d2->date ? 1 : -1;
};

usort($smerVetraData, $sortFunc);
usort($rychlostVetraData, $sortFunc);

// create graph image
$im = imagecreatetruecolor($width, $height);

$black = imagecolorallocate($im, 0, 0, 0);
$grey = imagecolorallocate($im, 236, 236, 236);
$colorColumns = imagecolorallocate($im, 185, 185, 185);
$colorOrange = imagecolorallocate($im, 255, 165, 0);
$colorRed = imagecolorallocate($im, 255, 0, 0);
$colorGreen = imagecolorallocate($im, 0, 255, 0);

imagealphablending($im, false);
imagesavealpha($im, true);

$fill = imagecolorallocatealpha($im, 255, 255, 255, 0);
imagefilledrectangle($im, 0, 0, $width, $height, $fill);
imagealphablending($im, true);

$fill = imagecolorallocatealpha($im, 0, 0, 0, 112);
imagerectangle($im, 0, 0, $width - 1, $height - 1, $fill);

// rows
$totalTime = $endDate - $startDate;
$rowCount = floor($heightGraph/$heightGraphRow);

for($i=0, $y=$graphY+$heightGraph-$heightGraphRow; $i<$rowCount; $i++, $y-=$heightGraphRow){
	imageline($im,$graphX,$y,$graphX+$widthGraph,$y,$colorColumns);
}
$minRychlostVetra = floor($minRychlostVetra);
$maxRychlostVetra = ceil($maxRychlostVetra);
$step = round(($maxRychlostVetra-$minRychlostVetra)/$rowCount);
$fontSize=9;
$font=__DIR__.'/font.ttf';
//draw vertical nums
for($i=0, $y=$graphY+$heightGraph, $str=$minRychlostVetra; $i<=$rowCount; $i++, $y-=$heightGraphRow,$str+=$step){
	$dimensions = imagettfbbox($fontSize, 0, $font, $str);
	$textWidth = abs($dimensions[4] - $dimensions[0]);
	$offset = $textWidth;
	imagettftext($im,$fontSize,0,$graphX-$textWidth-2,$y+$fontSize/2-3,$black,$font,$str);
}

// columns
$columnCount = floor($widthGraph/$widthGraphColumn);
$columnOffset = ($widthGraph - ($columnCount*$widthGraphColumn))/2;
const
MINUTE=60,
HOUR=(60 * 60),
DAY=(60 * 60 * 24),
WEEK=(60 * 60 * 24 * 7),
MONTH=(60 * 60 * 24 * 31),
YEAR=(60 * 60 * 24 * 31 * 12);
define('GMZ',date('Z'));
$dateRanges=[
	MINUTE*5=>"H:i",
	MINUTE*10=>"H:i",
	MINUTE*30=>"H:i",
	HOUR=>"H:i",
	HOUR*2=>"H:i",
	HOUR*6=>"H:i",
	HOUR*12=>"H:i",
	DAY=>"d M",
	DAY*2=>"d M",
	DAY*5=>"d M",
	WEEK=>"d M",
	WEEK*2=>"d M",
	MONTH=>"M y",
	MONTH*2=>"M y",
	MONTH*6=>"M y"
];

function formatDate($format,$timestamp){
	switch ($format){
		// round to day
		case 'H:i':
			if ((($timestamp+GMZ)%86400)==0){
				$format="d M";
			}
			break;
	}

	$ret = date($format,$timestamp);
	return $ret;
}

$dateRange = ($endDate-$startDate)/$columnCount;
$dateStep=null;
$dateFormat=null;
foreach($dateRanges as $range=>$dateFormats){
	if ($dateRange<$range){
		break;
	}
	$dateFormat=$dateFormats;
	$dateStep=$range;
}
$columnCount = round(($endDate-$startDate)/$dateStep);
$widthGraphColumn=$widthGraph/$columnCount;
$columnOffset = ($widthGraph - ($columnCount*$widthGraphColumn))/2;

if ($dateStep===null){
	echo "bad range<br>{$dateRange}";
	return;
}
$dimensions = imagettfbbox($fontSize, 0, $font, $str);
$textHeight = abs($dimensions[4] - $dimensions[6]);
$realStartDate = $startDate-(($startDate+GMZ)%$dateStep);
// draw dates
for($i=0,$x=$graphX+$columnOffset,$date=$realStartDate;$i<=$columnCount;$i++,$x+=$widthGraphColumn,$date+=$dateStep){
	$str = formatDate($dateFormat,$date);
	$dimensions = imagettfbbox($fontSize, 0, $font, $str);
	$textWidth = abs($dimensions[4] - $dimensions[0]);
	imageline($im,$x,$graphY,$x,$graphY+$heightGraph,$colorColumns);
	imagettftext($im,$fontSize,0,$x-$textWidth/2,$graphY+$heightGraph+$textHeight+6,$black,$font,$str);
}
$pixelsPerStepWidth=$widthGraph/count($rychlostVetraData);
$pixelsPerStepHeight=$heightGraph/($maxRychlostVetra-$minRychlostVetra);
// draw data
$prewY = ($rychlostVetraData[0]->value-$minRychlostVetra)*$pixelsPerStepHeight;
$prewMinY = ($rychlostVetraData[0]->value-$minRychlostVetra)*$pixelsPerStepHeight;
$prewMaxY = ($rychlostVetraData[0]->value-$minRychlostVetra)*$pixelsPerStepHeight;
$prewX=1;
$xOffset = $graphX;
$yOffset = $graphY+$heightGraph;
for($i=1;$i<count($rychlostVetraData);$i++){
	$x=$prewX+$pixelsPerStepWidth;
	$y=($rychlostVetraData[$i]->value-$minRychlostVetra)*$pixelsPerStepHeight;

	$maxVal=$rychlostVetraData[$i]->value;
	$minVal=$rychlostVetraData[$i]->value;
	for($t=$i,$lastDate=$rychlostVetraData[$i]->date;$t>0 && $lastDate>$rychlostVetraData[$i]->date-MIN_MAX_AVG_MINUTES*60;$t--){
		if ($rychlostVetraData[$t]->max>$maxVal){
			$maxVal=$rychlostVetraData[$t]->max;
		}
		if ($rychlostVetraData[$t]->min<$minVal){
			$minVal=$rychlostVetraData[$t]->min;
		}
		$lastDate=$rychlostVetraData[$t]->date;
	}
	$minY=($minVal-$minRychlostVetra)*$pixelsPerStepHeight;
	$maxY=($maxVal-$minRychlostVetra)*$pixelsPerStepHeight;

	imageline($im,round($xOffset+$prewX),round($yOffset-$prewMaxY),round($xOffset+$x),round($yOffset-$maxY),$colorRed);
	imageline($im,round($xOffset+$prewX),round($yOffset-$prewY),round($xOffset+$x),round($yOffset-$y),$colorOrange);
	imageline($im,round($xOffset+$prewX),round($yOffset-$prewMinY),round($xOffset+$x),round($yOffset-$minY),$colorGreen);

	$prewMinY=$minY;
	$prewMaxY=$maxY;
	$prewX=$x;
	$prewY=$y;
}

$imgYeloow = imagecreatefrompng(__DIR__.'/arrow_mini_yellow.png');
$imgRed = imagecreatefrompng(__DIR__.'/arrow_mini_red.png');

// draw arrows
$arrowSize=15;
$arrowSpace=15;
$imgWidth=imagesx($imgYeloow);
$imgHeight=imagesy($imgYeloow);
$prewWind=null;
$prewWindStatus=null;
$startSelectedDate=$startDate;
$fill = imagecolorallocatealpha($im, 255, 255, 255, 127);
for($x=$graphX+$arrowSpace; $x<$graphX+$widthGraph-$arrowSpace-$arrowSize;$x+=$arrowSize+$arrowSpace){
	$data = getAvgWind($startSelectedDate,$startSelectedDate+$dateStep,$smerVetraData);
	$startSelectedDate+=$dateStep;
	$wind=$prewWind;
	$status=$prewWindStatus;
	if ($data!==null){
		$wind=$data[0];
		$status=$data[1];
		$prewWind=$wind;
		$prewWindStatus=$status;
	}
	if ($wind===null){
		continue;
	}
	$img=$imgYeloow;
	if ($status == 2) {
		$img=$imgRed;
	}
	$rotated = imagerotate($img,$wind,$fill);
	$new_width = imagesx($rotated); // whese dimensions are
	$new_height = imagesy($rotated);// the scaled ones (by imagerotate)
	imagecopyresampled($im,$rotated,$x,$graphY+2,($new_width-$imgWidth)/2,
		($new_height-$imgHeight)/2,
		$imgWidth,
		$imgHeight,
		$imgWidth,
		$imgHeight);
}

imagedestroy($imgYeloow);
imagedestroy($imgRed);
//exit;
//horizontal
imageline($im, $graphX, $graphY + $heightGraph, $graphX + $widthGraph, $graphY + $heightGraph, $black);
//vertical
imageline($im, $graphX, $heightGraph, $graphX, $graphY, $black);

$e = error_get_last();
if ($e!==null) {
	var_dump($e);
	return;
}
header('Content-Type: image/png');
header('Cache-Control: max-age=0');

imagepng($im);
imagedestroy($im);
