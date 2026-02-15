<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Location;

/**
 * Location helper - recursive retrieval and display.
 */
class LocationHelper
{
    /**
     * Get full hierarchy tree (recursive).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTree(bool $activeOnly = true): array
    {
        return Location::getTree($activeOnly);
    }

    /**
     * Get path from root to location (breadcrumb).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getPath(int $locationId): array
    {
        return Location::getPath($locationId);
    }

    /**
     * Render path as breadcrumb string.
     *
     * @param array<int, array<string, mixed>> $path
     */
    public static function renderBreadcrumb(array $path, string $separator = ' → '): string
    {
        if (empty($path)) {
            return '';
        }
        $names = array_map(static fn($loc) => htmlspecialchars($loc['name']), $path);
        return implode($separator, $names);
    }

    /**
     * Render location tree as nested HTML (e.g. for admin list).
     *
     * @param array<int, array<string, mixed>> $locations
     * @param int $level
     * @return string
     */
    public static function renderTree(array $locations, int $level = 0): string
    {
        if (empty($locations)) {
            return '';
        }
        $html = '<ul class="location-tree level-' . $level . '">';
        foreach ($locations as $loc) {
            $children = $loc['children'] ?? [];
            $typeLabel = ucfirst($loc['type']);
            $activeLabel = !empty($loc['is_active']) ? 'Active' : 'Inactive';
            $html .= '<li data-id="' . (int) $loc['id'] . '" data-type="' . htmlspecialchars($loc['type']) . '">';
            $html .= '<strong>' . htmlspecialchars($loc['name']) . '</strong> <span class="text-gray-500">(' . $typeLabel . ', ' . $activeLabel . ')</span>';
            if (!empty($children)) {
                $html .= self::renderTree($children, $level + 1);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Get type label for display.
     */
    public static function typeLabel(string $type): string
    {
        return ucfirst($type);
    }

    /**
     * All location types in order.
     *
     * @return array<int, string>
     */
    public static function types(): array
    {
        return Location::TYPE_HIERARCHY;
    }
}
