<?php

if (! function_exists('pdf_phantom')) {
    /**
     * @return \AcidSolutions\PdfPhantom\PdfGenerator
     */
    function pdf_phantom()
    {
        return app('pdf_phantom');
    }
}

