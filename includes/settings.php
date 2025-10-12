<?php
/**
 * Settings Management Functions
 */

require_once __DIR__ . '/db.php';

// Cache for settings to avoid multiple database queries
$settings_cache = [];

/**
 * Get a single setting value
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function get_setting($key, $default = null) {
    global $settings_cache;
    
    // Return from cache if available
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $settings_cache[$key] = $result['value'];
            return $result['value'];
        }
    } catch (PDOException $e) {
        error_log("Error fetching setting: " . $e->getMessage());
    }
    
    return $default;
}

/**
 * Get all settings in a category
 * @param string $category Category name
 * @return array Array of settings
 */
function get_all_settings($category = null) {
    global $pdo;
    
    try {
        if ($category) {
            $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE category = ? ORDER BY updated_at ASC");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM system_settings ORDER BY category, updated_at ASC");
            $stmt->execute();
        }
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row;
        }
        return $settings;
    } catch (PDOException $e) {
        error_log("Error fetching settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all setting categories
 * @return array Array of category names
 */
function get_setting_categories() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT category FROM system_settings ORDER BY category ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching setting categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Update multiple settings at once
 * @param array $settings Array of key-value pairs
 * @return bool Success status
 */
function update_settings($settings) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE system_settings SET value = ? WHERE `key` = ?");
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error updating settings: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear the settings cache
 */
function clear_settings_cache() {
    global $settings_cache;
    $settings_cache = [];
}