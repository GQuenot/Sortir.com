<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct()
    {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_exists', [$this, 'asset_exists']),
        ];
    }

    public function asset_exists($path): bool
    {
        return is_file($path);
    }
}