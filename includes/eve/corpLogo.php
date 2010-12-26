<?php

    $logo = imagecreatetruecolor(64, 64);
    imagesavealpha($logo, true);
    $trans = imagecolorallocatealpha($logo, 0, 0, 0, 127);
    imagefill($logo, 0, 0, $trans);

    if (isset($_GET['s']) && isset($_GET['c'])) {
        $shapes = explode(',', $_GET['s']);
        $colors = explode(',', $_GET['c']);

        for ($i = 2; $i >= 0; $i--) {
            $layer[$i] = imagecreatefrompng('../../eveimages/corplogos/' . (int)$colors[$i] . '/' . (int)$shapes[$i] . '.png');
            imagecopy($logo, $layer[$i], 0, 0, 0, 0, 64, 64);
        }
    }

    header('Content-type: image/png');
    imagepng($logo);

?>