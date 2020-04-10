<?php

require __DIR__ . '/GoogleDrive.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

$drive = new GoogleDrive();

$content = $drive->convertDocTOPdf('testdoc1.docx');


