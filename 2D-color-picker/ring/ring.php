<?php

/*
 * This program draws a ring of colors. This ring represents the *entire*
 * color space -- its width specifies the B component while its circumference
 * is a flattened (R,G) component space.
 *
 * A bijection from a finite two-dimensional set to a finite one-dimensional
 * set is used here. This bijection works for sets AxA where |A| is a power of 3
 *
 * In this case, all components range from 0 to 26.
 */

/*
 * This function takes two coordinates (range 0..2) and a path number and returns
 * the value of the respective generator which generates the path.
 *
 * paths:
 *
 *  0   1   2   3   4   5   6   7   8   9   10  11
 *  
 * S+E E+S S++ E++ S++ E++ ++S ++E ++S ++E +++ +++
 * +++ +++ +++ +++ +++ +++ +++ +++ +++ +++ +++ +++
 * +++ +++ ++E ++S E++ S++ ++E ++S E++ S++ E+S S+E
 * 
 */
function getIndex($x, $y, $path)
{
    $generatorAB = Array(0, 7, 8,
                         1, 6, 5,
                         2, 3, 4);

    $generatorAC = Array(0, 1, 2,
                         5, 4, 3,
                         6, 7, 8);

    switch(floor($path/2))
    {
        case 0: $retval = $generatorAB[$x + $y*3]; break;
        case 1: $retval = $generatorAC[$x + $y*3]; break;
        case 2: $retval = $generatorAB[$y + $x*3]; break;
        case 3: $retval = $generatorAB[$y + (2-$x)*3]; break;
        case 4: $retval = $generatorAC[2-$x + $y*3]; break;
        case 5: $retval = $generatorAB[2-$x + (2-$y)*3]; break;
    }
    if($path%2) $retval = 8-$retval;
    return $retval;
}

/*
 * This function returns a path for a given subsquare
 *
 */
function getPath($x, $y, $path)
{
    $pathAB = Array( 4, 9, 0,
                     4, 1, 3,
                     2,11, 9);

    $pathAC = Array( 0, 0, 2,
                    10,10, 8,
                     2,11,11);

    $pathAD = Array( 0, 0, 2,
                     8, 5, 6,
                     4, 3, 8);

    $pathBC = Array( 8, 1, 1,
                     4, 7, 2,
                     2, 9, 6);

    $pathBD = Array( 8, 1, 1,
                     2,11,11,
                    10,10, 8);

    $pathCD = Array( 8, 1, 3,
                     2,11, 7,
                    10, 8, 7);

    switch(floor($path/2))
    {
        case 0: $retval = $pathAB[$x + $y*3]; break;
        case 1: $retval = $pathAC[$x + $y*3]; break;
        case 2: $retval = $pathAD[$x + $y*3]; break;
        case 3: $retval = $pathBC[$x + $y*3]; break;
        case 4: $retval = $pathBD[$x + $y*3]; break;
        case 5: $retval = $pathCD[$x + $y*3]; break;
    }
    if($path%2) $retval = $retval - ($retval%2) + (($retval+1)%2);
    return $retval;
}

/* 
 * This function takes two coordinates and a degree of the set (k where |A|=k)
 * and returns the value of the bijection. Set of all possible paths is
 * given above.
 * 
 */
function bijection($x, $y, $path, $degree)
{
    $lower = floor($degree/3);
    $qx = floor($x/$lower);
    $qy = floor($y/$lower);
    $i = getIndex($qx, $qy, $path);
    $newPath = getPath($qx, $qy, $path);
    if($lower>1) $c = bijection($x%$lower, $y%$lower, $newPath, $lower); else $c = 0;
    return $lower*$lower*$i+$c;
}

function generateBijection()
{
    $table = Array();
    for($r=0; $r<27; ++$r)
    {
        for($g=0; $g<27; ++$g)
        {
            $a = bijection($r, $g, 2, 27);
            $table[$a] = Array($r, $g);
        }
    }
    return $table;
}

function generateRing($table, $radius, $blue, $SIZE, $im)
{
    for($y=-$radius-$blue; $y<$radius+$blue; ++$y)
    {
        for($x=-$radius-$blue; $x<$radius+$blue; ++$x)
        {
            $thisRadius = $x*$x+$y*$y;
            if($thisRadius>=$radius*$radius && $thisRadius<=($radius+$blue)*($radius+$blue))
            {
                $b = (sqrt($thisRadius)-$radius);
                if($x<0 && !$y) $a = 546;
                if($x>0 && !$y) $a = 182;
                if($y) 
                {
                    $a = -floor(729*atan($x/$y)/(2*3.14159));
                    if($y>0) 
                    {
                        $a+=364;
                    }
                    elseif($x<0) $a+=728;
                }
                if($a<=364) $a2 = $a*2;
                else $a2 = 1457 - $a*2;
                list($r, $g) = $table[$a2];
                $cr = floor($r*9.48);
                $cg = floor($g*9.48);
                $cb = floor($b*255/$blue);
                $color = imagecolorallocate($im, $cr, $cg, $cb);
                imagesetpixel($im, $x+floor($SIZE/2), $y+floor($SIZE/2), $color);
            }
        }
    }
}

function generateBelt($table, $SIZE, $im)
{
    for($y=0; $y<$SIZE; ++$y)
    {
        for($x=0; $x<$SIZE*$SIZE; ++$x)
        {
            list($r, $g) = $table[$x];
            $color = imagecolorallocate($im, $r*9.48, $g*9.48, $y*255/($SIZE-1));
            imagesetpixel($im, $x, $y, $color);
        }
    }
}

function drawBelt($showHeaders)
{
    $SIZE = 27;

    $im = imagecreatetruecolor($SIZE*$SIZE, $SIZE);
    $white = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, $SIZE*$SIZE, $SIZE, $white);

    $table = generateBijection();
    generateBelt($table, $SIZE, $im);
    if($showHeaders) header('Content-type: image/png');
    imagepng($im);
}

function drawRing($showHeaders)
{
    $SIZE = 524;

    $im = imagecreatetruecolor($SIZE, $SIZE);
    $white = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, $SIZE, $SIZE, $white);

    $table = generateBijection();
    generateRing($table, 129, 129, $SIZE, $im);
    if($showHeaders) header('Content-type: image/png');
    imagepng($im);
}

// drawRing($true);
drawBelt($true);

?>
