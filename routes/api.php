<?php

foreach (glob(__DIR__ . '/modules/*.php') as $routeFile) {
    require $routeFile;
}
foreach (glob(__DIR__ . '/modules/residentModules/*.php') as $routeFile) {
    require $routeFile;
}
