<?php

namespace App\Validator;

class CustomRules
{
    public function valid_time(string $str): bool
    {
        if (preg_match('/^([0-1]\d|2[0-3]):[0-5]\d$/', $str)) {
            return true;
        }
        return false;
    }
}