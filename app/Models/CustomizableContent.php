<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomizableContent extends Model
{
    use HasFactory;

    protected $table = 'customizable_content';

    protected $fillable = [
        'page_name',
        'content',
        'organization_id',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    /**
     * Get the customizable content for the organization-brands page for a specific organization.
     *
     * @param int $organizationId
     * @return array
     */
    public static function getContentForOrganizationBrands($organizationId)
    {
        return self::where('page_name', 'organization-brands')
            ->where('organization_id', $organizationId)
            ->first()
            ->content ?? [];
    }

    /**
     * Update or create the customizable content for the organization-brands page for a specific organization.
     *
     * @param int $organizationId
     * @param array $content
     * @return CustomizableContent
     */
    public static function updateOrCreateOrganizationBrands($organizationId, array $content)
    {
        return self::updateOrCreate(
            [
                'page_name' => 'organization-brands',
                'organization_id' => $organizationId
            ],
            ['content' => $content]
        );
    }
 public static function getContentForPage($pageName)
    {
        $organizationId = request()->route('organization');

        if (!$organizationId) {
            return [];
        }

        $content = self::where('page_name', $pageName)
                       ->where('organization_id', $organizationId)
                       ->first();

        return $content ? json_decode($content->content, true) : [];
    }
}