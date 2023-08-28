<?php

$i = 0;

while ($i <= 100) {
    for ($ii = 0; $ii < 10; $ii++) {
        echo "\r";
        $i++;

        echo '[' . $i . '%]' . "\r";
    }
    sleep(1);
}