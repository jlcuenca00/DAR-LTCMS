<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeploymentHttpSmokeChecker
{
    public function check(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        if (parse_url($baseUrl, PHP_URL_SCHEME) !== 'https' || ! parse_url($baseUrl, PHP_URL_HOST)) {
            throw new RuntimeException('Deployment smoke checks require an HTTPS application URL.');
        }

        $this->fetch($baseUrl.'/up');
        $login = $this->fetch($baseUrl.'/login');
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($login);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $hasLoginForm = false;
        foreach ($document->getElementsByTagName('form') as $form) {
            if (strtolower($form->getAttribute('method')) === 'post'
                && parse_url($form->getAttribute('action'), PHP_URL_PATH) === '/login') {
                $hasLoginForm = true;
            }
        }
        if (! $hasLoginForm) {
            throw new RuntimeException('The live login response does not contain the expected login form.');
        }

        $assets = [];
        foreach (['link' => 'href', 'script' => 'src'] as $tag => $attribute) {
            foreach ($document->getElementsByTagName($tag) as $node) {
                if (($tag === 'link' && $node->getAttribute('rel') !== 'stylesheet')
                    || ($tag === 'script' && $node->getAttribute('type') !== 'module')) {
                    continue;
                }
                $url = $node->getAttribute($attribute);
                $path = parse_url($url, PHP_URL_PATH);
                if (! is_string($path) || ! str_starts_with($path, '/build/assets/')) {
                    continue;
                }
                // Check only the application's own Vite assets, never external URLs.
                if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
                    $url = $baseUrl.$url;
                }
                if (parse_url($url, PHP_URL_SCHEME) !== 'https'
                    || parse_url($url, PHP_URL_HOST) !== parse_url($baseUrl, PHP_URL_HOST)
                    || parse_url($url, PHP_URL_PORT) !== parse_url($baseUrl, PHP_URL_PORT)) {
                    throw new RuntimeException('A built login asset does not use the application origin.');
                }
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                if (in_array($extension, ['css', 'js'], true)) {
                    $assets[$url] = $extension;
                }
            }
        }
        if (! in_array('css', $assets, true) || ! in_array('js', $assets, true)) {
            throw new RuntimeException('The live login page must reference built CSS and JavaScript.');
        }
        foreach ($assets as $url => $extension) {
            $this->fetch($url, $extension);
        }

        return ['health' => true, 'login' => true, 'assets_checked' => count($assets)];
    }

    private function fetch(string $url, ?string $extension = null): string
    {
        // Redirects must not turn a missing asset or unhealthy route into a false pass.
        $response = Http::connectTimeout(5)->timeout(15)->withoutRedirecting()->get($url);
        if ($response->status() !== 200 || trim($response->body()) === '') {
            throw new RuntimeException('A live deployment HTTP check failed: '.$url);
        }
        $contentType = strtolower((string) $response->header('Content-Type'));
        if (($extension === 'css' && ! str_contains($contentType, 'text/css'))
            || ($extension === 'js' && ! str_contains($contentType, 'javascript'))) {
            throw new RuntimeException('A built asset returned an unexpected content type: '.$url);
        }

        return $response->body();
    }
}
