<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Category helper - recursive display functions.
 */
class CategoryHelper
{
    /**
     * Render category tree as HTML (nested UL).
     *
     * @param array<int, array<string, mixed>> $categories
     * @param int $level Current nesting level
     * @param callable|null $renderItem Custom render function
     * @return string HTML
     */
    public static function renderTree(
        array $categories,
        int $level = 0,
        ?callable $renderItem = null
    ): string {
        if (empty($categories)) {
            return '';
        }
        
        $html = '<ul class="category-tree level-' . $level . '">';
        
        foreach ($categories as $category) {
            $children = $category['children'] ?? [];
            $hasChildren = !empty($children);
            
            if ($renderItem !== null) {
                $html .= call_user_func($renderItem, $category, $level, $hasChildren);
            } else {
                $html .= self::renderDefaultItem($category, $level, $hasChildren);
            }
            
            // Render children recursively
            if ($hasChildren) {
                $html .= self::renderTree($children, $level + 1, $renderItem);
            }
        }
        
        $html .= '</ul>';
        return $html;
    }

    /**
     * Default item renderer.
     */
    private static function renderDefaultItem(array $category, int $level, bool $hasChildren): string
    {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $name = htmlspecialchars($category['name']);
        $status = $category['is_active'] ? 'Active' : 'Inactive';
        $statusClass = $category['is_active'] ? 'text-green-600' : 'text-gray-400';
        
        return sprintf(
            '<li class="category-item" data-id="%d" data-level="%d">
                %s<strong>%s</strong> <span class="%s">(%s)</span>
            </li>',
            $category['id'],
            $level,
            $indent,
            $name,
            $statusClass,
            $status
        );
    }

    /**
     * Render category tree as select options.
     *
     * @param array<int, array<string, mixed>> $categories
     * @param int|null $selectedId Currently selected category ID
     * @param int|null $excludeId Category ID to exclude (e.g., when editing)
     * @param int $level Current nesting level
     * @return string HTML options
     */
    public static function renderSelectOptions(
        array $categories,
        ?int $selectedId = null,
        ?int $excludeId = null,
        int $level = 0
    ): string {
        $html = '';
        $prefix = str_repeat('— ', $level);
        
        foreach ($categories as $category) {
            $id = (int) $category['id'];
            
            // Skip excluded category
            if ($excludeId !== null && $id === $excludeId) {
                continue;
            }
            
            $selected = ($selectedId === $id) ? ' selected' : '';
            $name = htmlspecialchars($category['name']);
            $html .= sprintf(
                '<option value="%d"%s>%s%s</option>',
                $id,
                $selected,
                $prefix,
                $name
            );
            
            // Render children
            if (!empty($category['children'])) {
                $html .= self::renderSelectOptions($category['children'], $selectedId, $excludeId, $level + 1);
            }
        }
        
        return $html;
    }

    /**
     * Get category breadcrumb path.
     *
     * @param array<int, array<string, mixed>> $path Category path array
     * @param string $separator Breadcrumb separator
     * @return string HTML breadcrumb
     */
    public static function renderBreadcrumb(array $path, string $separator = ' / '): string
    {
        if (empty($path)) {
            return '';
        }
        
        $items = [];
        foreach ($path as $category) {
            $name = htmlspecialchars($category['name']);
            $items[] = '<span class="breadcrumb-item">' . $name . '</span>';
        }
        
        return '<div class="breadcrumb">' . implode($separator, $items) . '</div>';
    }

    /**
     * Count total categories in tree (including children).
     *
     * @param array<int, array<string, mixed>> $categories
     * @return int Total count
     */
    public static function countTree(array $categories): int
    {
        $count = 0;
        foreach ($categories as $category) {
            $count++;
            if (!empty($category['children'])) {
                $count += self::countTree($category['children']);
            }
        }
        return $count;
    }
}
