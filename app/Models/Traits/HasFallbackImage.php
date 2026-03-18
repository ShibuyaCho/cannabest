<?php

namespace App\Models\Traits;


trait HasFallbackImage
{
    /**
     * Return the first existing image URL, in this order:
     * 1) $primaryPath   — e.g. "uploads/inventories/{$id}.jpg"
     * 2) $secondaryPath — e.g. "uploads/category/{$category_id}.jpg"
     * 3) $defaultPath   — e.g. "herbs/noimage.jpg"
     */
  public function fallbackImageUrl(
    string $primaryPath,
    string $secondaryPath,
    string $defaultPath
): string {
    $fullPrimary   = public_path($primaryPath);
    $fullSecondary = public_path($secondaryPath);

    \Log::debug('FallbackImage paths check', [
        'primary'   => $fullPrimary,
        'exists1?'  => file_exists($fullPrimary),
        'secondary' => $fullSecondary,
        'exists2?'  => file_exists($fullSecondary),
    ]);

    if (file_exists($fullPrimary)) {
        return asset($primaryPath);
    }

    if ($secondaryPath && file_exists($fullSecondary)) {
        return asset($secondaryPath);
    }

    return asset($defaultPath);
}


}

