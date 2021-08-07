<?php

namespace Snowflake\Pool;

use JetBrains\PhpStorm\Pure;

trait Alias
{

    /**
     * @param $cds
     * @param false $isMaster
     * @return string
     */
    #[Pure] public function name($cds, bool $isMaster = false): string
    {
        if ($isMaster === true) {
            return $cds . '_master';
        } else {
            return $cds . '_slave';
        }
    }

}
