<?php

namespace AcidSolutions\PdfPhantom;

use Illuminate\Support\Facades\Facade;

class PdfPhantomFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'PdfPhantom';
    }
}
