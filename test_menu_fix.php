<?php

require_once 'vendor/autoload.php';

use App\Models\Menu;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Fixed Menu Query for role_id = 3 ===\n\n";

$roleId = 3;

// Test the new query logic
$menus = Menu::whereNull('parent_id')
    ->where(function($query) use ($roleId) {
        $query->whereHas('children', function($childQuery) use ($roleId) {
            $childQuery->whereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
                $permQuery->where('role_id', $roleId)
                        ->where('can_view', true);
            });
        })
        ->orWhereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
            $permQuery->where('role_id', $roleId)
                    ->where('can_view', true);
        });
    })
    ->with(['children' => function ($query) use ($roleId) {
        $query->where('is_active', true)
            ->whereHas('roleMenuPermissions', function($permQuery) use ($roleId) {
                $permQuery->where('role_id', $roleId)
                        ->where('can_view', true);
            })
            ->with(['roleMenuPermissions' => function($permQuery) use ($roleId) {
                $permQuery->where('role_id', $roleId)
                        ->select('menu_id', 'can_view', 'can_add', 'can_edit', 'can_delete');
            }]);
    }])
    ->orderBy('sort_order')
    ->orderBy('name')
    ->get();

if ($menus->count() > 0) {
    echo "✓ Found " . $menus->count() . " root menus:\n";
    foreach ($menus as $menu) {
        echo "   - ID: " . $menu->id . " | Name: " . $menu->name . " | Children: " . $menu->children->count() . "\n";
        foreach ($menu->children as $child) {
            echo "     └─ Child ID: " . $child->id . " | Name: " . $child->name . "\n";
            if ($child->roleMenuPermissions->count() > 0) {
                foreach ($child->roleMenuPermissions as $perm) {
                    echo "        └─ Permissions: View=" . ($perm->can_view ? 'Y' : 'N') . 
                         ", Add=" . ($perm->can_add ? 'Y' : 'N') . 
                         ", Edit=" . ($perm->can_edit ? 'Y' : 'N') . 
                         ", Delete=" . ($perm->can_delete ? 'Y' : 'N') . "\n";
                }
            }
        }
    }
} else {
    echo "✗ No menus found!\n";
}

echo "\n=== Test Complete ===\n";
