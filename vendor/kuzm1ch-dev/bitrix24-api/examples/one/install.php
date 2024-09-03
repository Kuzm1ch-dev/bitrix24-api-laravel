<?php
require 'vendor/autoload.php';
require 'b24api.php';

B24Api::install();
?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <script src="//api.bitrix24.com/api/v1/"></script>
    <title>Установка</title>
    <script>
        BX24.init(function () {
            BX24.installFinish();
        });
    </script>
</head>
<body>
installation has been finished
</body>
</html>

