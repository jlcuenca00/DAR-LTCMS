<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class ClearanceBrowserPdfRenderer
{
    public function render(string $html): string
    {
        $browser = $this->resolveBrowserExecutable();
        $workingDirectory = storage_path('app/tmp/clearance-pdf');

        File::ensureDirectoryExists($workingDirectory, 0700, true);

        $token = (string) Str::uuid();
        $htmlPath = $workingDirectory . DIRECTORY_SEPARATOR . $token . '.html';
        $pdfPath = $workingDirectory . DIRECTORY_SEPARATOR . $token . '.pdf';
        $profilePath = $workingDirectory . DIRECTORY_SEPARATOR . $token . '-profile';

        File::ensureDirectoryExists($profilePath, 0700, true);
        File::put($htmlPath, $html);

        try {
            $process = new Process([
                $browser,
                '--headless',
                '--disable-gpu',
                '--disable-extensions',
                '--no-first-run',
                '--no-default-browser-check',
                '--no-pdf-header-footer',
                '--print-to-pdf-no-header',
                '--allow-file-access-from-files',
                '--user-data-dir=' . $profilePath,
                '--print-to-pdf=' . $pdfPath,
                $this->fileUri($htmlPath),
            ]);

            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($pdfPath) || filesize($pdfPath) === 0) {
                throw new RuntimeException(
                    'Unable to generate the clearance PDF with the browser renderer. ' .
                    trim($process->getErrorOutput() ?: $process->getOutput())
                );
            }

            $pdf = File::get($pdfPath);

            if ($pdf === '') {
                throw new RuntimeException('The browser renderer generated an empty clearance PDF.');
            }

            return $pdf;
        } finally {
            File::delete($htmlPath);
            File::delete($pdfPath);
            File::deleteDirectory($profilePath);
        }
    }

    private function resolveBrowserExecutable(): string
    {
        $configured = config('services.clearance_pdf.browser_path');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        foreach ($this->browserCandidates() as $candidate) {
            if ($candidate !== null && is_file($candidate)) {
                return $candidate;
            }
        }

        foreach ($this->browserCommandNames() as $command) {
            $resolved = $this->resolveCommand($command);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        throw new RuntimeException(
            'No supported browser executable was found for clearance PDF generation. ' .
            'Install Chrome, Chromium, Brave, or Edge, or set CLEARANCE_PDF_BROWSER_PATH.'
        );
    }

    private function browserCandidates(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $programFiles = getenv('PROGRAMFILES') ?: null;
            $programFilesX86 = getenv('PROGRAMFILES(X86)') ?: null;
            $localAppData = getenv('LOCALAPPDATA') ?: null;

            return array_values(array_filter([
                $programFiles ? $programFiles . '\\BraveSoftware\\Brave-Browser\\Application\\brave.exe' : null,
                $programFilesX86 ? $programFilesX86 . '\\BraveSoftware\\Brave-Browser\\Application\\brave.exe' : null,
                $localAppData ? $localAppData . '\\BraveSoftware\\Brave-Browser\\Application\\brave.exe' : null,
                $programFiles ? $programFiles . '\\Google\\Chrome\\Application\\chrome.exe' : null,
                $programFilesX86 ? $programFilesX86 . '\\Google\\Chrome\\Application\\chrome.exe' : null,
                $localAppData ? $localAppData . '\\Google\\Chrome\\Application\\chrome.exe' : null,
                $programFiles ? $programFiles . '\\Microsoft\\Edge\\Application\\msedge.exe' : null,
                $programFilesX86 ? $programFilesX86 . '\\Microsoft\\Edge\\Application\\msedge.exe' : null,
            ]));
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            return [
                '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
                '/Applications/Brave Browser.app/Contents/MacOS/Brave Browser',
                '/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge',
                '/Applications/Chromium.app/Contents/MacOS/Chromium',
            ];
        }

        return [
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/usr/bin/brave-browser',
            '/usr/bin/microsoft-edge',
            '/snap/bin/chromium',
        ];
    }

    private function browserCommandNames(): array
    {
        return PHP_OS_FAMILY === 'Windows'
            ? ['brave', 'chrome', 'msedge', 'chromium']
            : ['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser', 'brave-browser', 'microsoft-edge'];
    }

    private function resolveCommand(string $command): ?string
    {
        $locator = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $process = new Process([$locator, $command]);
        $process->setTimeout(5);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $firstResult = collect(preg_split('/\r\n|\r|\n/', trim($process->getOutput())))
            ->filter()
            ->first();

        return is_string($firstResult) && is_file($firstResult) ? $firstResult : null;
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = str_replace(' ', '%20', $normalized);

        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            return 'file:///' . $normalized;
        }

        return 'file://' . $normalized;
    }
}
