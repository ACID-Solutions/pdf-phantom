<?php

namespace AcidSolutions\PdfPhantom;

use Exception;
use RuntimeException;
use Symfony\Component\Process\Process;

class PdfGenerator
{
    /**
     * @var string
     */
    protected $baseUrl;
    /**
     * @var string
     */
    protected $binaryPath;
    /**
     * @var string
     */
    protected $storagePath;
    /**
     * @var string
     */
    protected $headerPath;
    /**
     * @var string
     */
    protected $footerPath;
    /**
     * @var string
     */
    protected $contentPath;
    /**
     * @var string
     */
    protected $pdfPath;
    /**
     * @var string|object
     */
    protected $headerView;
    /**
     * @var string|object
     */
    protected $footerView;
    /**
     * @var string|object
     */
    protected $contentView;
    /**
     * @var int
     */
    protected $timeout = 60;
    /**
     * @var array
     */
    protected $commandLineOptions = [];
    /**
     * @var string
     */
    protected $orientation = 'portrait';
    /**
     * @var string
     */
    protected $headerHeight = '1cm';
    /**
     * @var string
     */
    protected $footerHeight = '1cm';
    /**
     * @var int
     */
    protected $dpi = 72;
    /**
     * @var int
     */
    protected $waitTime = 100;
    /**
     * @var string
     */
    protected $convertScript;
    /**
     * @var string
     */
    protected $uniqid;

    /**
     * @param string $view
     * @param string $path
     *
     * @throws \Exception
     */
    public function saveFromView($view, $path)
    {
        $this->contentView = $view;

        $this->generateFilePaths();

        $this->generatePdf();

        rename($this->pdfPath, $path);
    }

    /**
     * Generate paths for temporary files.
     *
     * @throws Exception
     */
    protected function generateFilePaths()
    {
        $this->validateStoragePath();

        $path = $this->storagePath . DIRECTORY_SEPARATOR;

        if (! isset($this->uniqid) || is_null($this->uniqid)) {
            $this->uniqid = uniqid('', true);
        }

        $this->headerPath = $path . 'header-' . $this->uniqid . '.html';

        $this->footerPath = $path . 'footer-' . $this->uniqid . '.html';

        $this->contentPath = $path . 'content-' . $this->uniqid . '.html';

        $this->pdfPath = $path . 'pdf-' . $this->uniqid . '.pdf';
    }

    /**
     * Validate that the storage path is set and is writable.
     *
     * @throws Exception
     */
    protected function validateStoragePath()
    {
        if (is_null($this->storagePath)) {
            throw new Exception('A storage path has not been set');
        }

        if (! is_dir($this->storagePath) || ! is_writable($this->storagePath)) {
            throw new Exception('The specified storage path is not writable');
        }
    }

    /**
     * Run the script with PhantomJS.
     */
    protected function generatePdf()
    {
        $this->viewToString();

        $this->saveHtml();

        $command = [
            $this->binaryPath,
            implode(' ', $this->commandLineOptions),
            $this->convertScript,
            $this->pdfPath,
            $this->prefixHtmlPaths($this->contentPath),
            $this->prefixHtmlPaths($this->headerPath),
            $this->prefixHtmlPaths($this->footerPath),
            $this->orientation,
            $this->headerHeight,
            $this->footerHeight,
            $this->dpi,
            $this->waitTime,
        ];

        $process = new Process($command, __DIR__);
        $process->setTimeout($this->timeout);
        $process->run();

        if ($errorOutput = $process->getErrorOutput()) {
            throw new RuntimeException('PhantomJS: ' . $errorOutput);
        }

        // Remove temporary HTML files
        @unlink($this->headerPath);
        @unlink($this->contentPath);
        @unlink($this->footerPath);
    }

    /**
     * Convert the provided view to a string. The __toString method is called manually to be able to catch exceptions
     * in the view which is not possible otherwise.
     */
    protected function viewToString()
    {
        if (is_object($this->headerView)) {
            $this->headerView = $this->headerView->__toString();
        }

        if (is_object($this->footerView)) {
            $this->footerView = $this->footerView->__toString();
        }

        if (is_object($this->contentView)) {
            $this->contentView = $this->contentView->__toString();
        }
    }

    /**
     * Save string contents into temporary HTML file.
     */
    protected function saveHtml()
    {
        $this->insertBaseTag();

        file_put_contents($this->headerPath, $this->headerView);

        file_put_contents($this->footerPath, $this->footerView);

        file_put_contents($this->contentPath, $this->contentView);
    }

    /**
     * Insert a base tag after the head tag to allow relative references to assets.
     */
    protected function insertBaseTag()
    {
        if (is_null($this->baseUrl)) {
            return;
        }

        $this->headerView = str_replace('<head>', '<head><base href="' . $this->baseUrl . '">', $this->headerView);
        $this->footerView = str_replace('<head>', '<head><base href="' . $this->baseUrl . '">', $this->footerView);
        $this->contentView = str_replace('<head>', '<head><base href="' . $this->baseUrl . '">', $this->contentView);
    }

    /**
     * Prefix the input paths for windows versions of PhantomJS.
     *
     * @param string file
     *
     * @return string
     */
    protected function prefixHtmlPaths($file)
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? 'file:///' . $file
            : $file;
    }

    /**
     * Set the base url for the base tag.
     *
     * @param string $url
     */
    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;
    }

    /**
     * Set the binary path.
     *
     * @param string $path
     */
    public function setBinaryPath($path)
    {
        $this->binaryPath = $path;
    }

    /**
     * Set the storage path for temporary files.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setStoragePath($path)
    {
        $this->storagePath = $path;

        return $this;
    }

    /**
     * Set the process timeout.
     *
     * @param int $seconds
     *
     * @return $this
     */
    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Add a command line option for PhantomJS
     *
     * @param string $option
     *
     * @return $this
     */
    public function addCommandLineOption($option)
    {
        $this->commandLineOptions[] = $option;

        return $this;
    }

    /**
     * Use a custom script to be run via PhantomJS
     *
     * @param string $path
     *
     * @return $this
     */
    public function useScript($path)
    {
        $this->convertScript = $path;

        return $this;
    }

    public function setWaitTimeForGeneration($milliseconds)
    {
        $this->waitTime = $milliseconds;

        return $this;
    }

    /**
     * Set the contents of the header view.
     *
     * @param string $view
     *
     * @return $this
     */
    public function setHeader($view)
    {
        $this->headerView = $view;

        return $this;
    }

    /**
     * Set the contents of the footer view.
     *
     * @param string $view
     *
     * @return $this
     */
    public function setFooter($view)
    {
        $this->footerView = $view;

        return $this;
    }

    /**
     * Set the page orientation.
     *
     * @param string $orientation
     *
     * @return $this
     */
    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Set the page header height.
     *
     * @param string $headerHeight
     *
     * @return $this
     */
    public function setHeaderHeight($headerHeight)
    {
        $this->headerHeight = $headerHeight;

        return $this;
    }

    /**
     * Set the page footer height.
     *
     * @param string $footerHeight
     *
     * @return $this
     */
    public function setFooterHeight($footerHeight)
    {
        $this->footerHeight = $footerHeight;

        return $this;
    }

    /**
     * Set the page DPI.
     *
     * @param integer $dpi
     *
     * @return $this
     */
    public function setDpi($dpi)
    {
        $this->dpi = $dpi;

        return $this;
    }

    /**
     * Merge multiple pdf documents together
     *
     * @param string $output The file to write the result to
     * @param array  $files  List of files to merge.
     *
     * @throws \Exception
     */
    public function merge($output, $files = [])
    {
        foreach ($files as $pdf) {
            $fileParts = pathinfo($pdf);
            if (strtolower($fileParts['extension']) !== 'pdf') {
                throw new \Exception('Tous les documents doivent être dans le format PDF');
            }
        }
        $pdfs = implode(' ', $files);
        $command = 'pdfunite ' . $pdfs . ' ' . $output;
        shell_exec($command);
    }
}
