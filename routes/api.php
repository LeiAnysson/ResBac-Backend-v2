<?php

foreach (glob(__DIR__ . '/modules/*.php') as $routeFile) {
    require $routeFile;
}
