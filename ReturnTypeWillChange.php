<?php

if (!class_exists('\ReturnTypeWillChange')) {
    #[\Attribute(\Attribute::TARGET_METHOD)]
    final class ReturnTypeWillChange
    {


        public function __construct()
        {
        }

    }
}

