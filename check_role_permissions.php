<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Checking Role Menu Permissions for role_id = 3 ===\n\n";

// Check if role_menu_permissions table exists
if (!Schema::hasTable('role_menu_permissions')) {
    echo "ERROR: role_menu_permissions table does not exist!\n";
    exit(1);
}

// Check if role_id = 3 exists in roles table
echo "1. Checking if role_id = 3 exists in roles table:\n";
$role = DB::table('roles')->where('id', 3)->first();
if ($role) {
    echo "   ✓ Role found: " . $role->name . " (ID: " . $role->id . ")\n";
} else {
    echo "   ✗ Role with ID 3 does not exist!\n";
}

// Check all permissions for role_id = 3
echo "\n2. All role_menu_permissions for role_id = 3:\n";
$permissions = DB::table('role_menu_permissions')->where('role_id', 3)->get();
if ($permissions->count() > 0) {
    foreach ($permissions as $perm) {
        echo "   - Menu ID: " . $perm->menu_id . 
             " | Can View: " . ($perm->can_view ? 'YES' : 'NO') .
             " | Can Add: " . ($perm->can_add ? 'YES' : 'NO') .
             " | Can Edit: " . ($perm->can_edit ? 'YES' : 'NO') .
             " | Can Delete: " . ($perm->can_delete ? 'YES' : 'NO') .
             " | Is Parent: " . ($perm->is_parent ?? 'NULL') . "\n";
    }
} else {
    echo "   ✗ No permissions found for role_id = 3!\n";
}

// Check permissions with can_view = true specifically
echo "\n3. Permissions with can_view = true for role_id = 3:\n";
$viewPermissions = DB::table('role_menu_permissions')
    ->where('role_id', 3)
    ->where('can_view', true)
    ->get();
    
if ($viewPermissions->count() > 0) {
    foreach ($viewPermissions as $perm) {
        echo "   - Menu ID: " . $perm->menu_id . "\n";
    }
} else {
    echo "   ✗ No permissions with can_view = true found for role_id = 3!\n";
}

// Check menu details for the permitted menus
echo "\n4. Menu details for permitted menus:\n";
$menuIds = $permissions->pluck('menu_id')->toArray();
if (!empty($menuIds)) {
    $menus = DB::table('menus')->whereIn('id', $menuIds)->get();
    foreach ($menus as $menu) {
        echo "   - ID: " . $menu->id . 
             " | Name: " . $menu->name . 
             " | Parent ID: " . ($menu->parent_id ?? 'NULL') .
             " | Active: " . ($menu->is_active ?? 'NULL') . "\n";
    }
} else {
    echo "   ✗ No menus to check!\n";
}

// Test the actual query used in getCompleteTree
echo "\n5. Testing the actual query from getCompleteTree method:\n";
$roleId = 3;
$menuTreeQuery = DB::table('menus')
    ->whereNull('parent_id')
    ->join('role_menu_permissions', function($join) use ($roleId) {
        $join->on('menus.id', '=', 'role_menu_permissions.menu_id')
             ->where('role_menu_permissions.role_id', '=', $roleId)
             ->where('role_menu_permissions.can_view', true);
    });

$testResults = $menuTreeQuery->get();
if ($testResults->count() > 0) {
    echo "   ✓ Found " . $testResults->count() . " root menus with permissions:\n";
    foreach ($testResults as $result) {
        echo "     - ID: " . $result->id . " | Name: " . $result->name . "\n";
    }
} else {
    echo "   ✗ No root menus found with can_view = true for role_id = 3!\n";
}

echo "\n=== Check Complete ===\n";
