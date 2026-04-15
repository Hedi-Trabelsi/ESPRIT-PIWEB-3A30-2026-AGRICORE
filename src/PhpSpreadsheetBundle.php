<?php

namespace App;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle wrapper for phpoffice/phpspreadsheet.
 * Registers the PhpSpreadsheet library as a Symfony bundle.
 */
class PhpSpreadsheetBundle extends Bundle
{
    public function getPath(): string
    {
        return __DIR__;
    }
}
