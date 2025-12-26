<?php
/**
 * Database Schema Management
 * File: includes/class-pz-database.php
 */

if (!defined('ABSPATH')) exit;

class PZ_Database
{
    /**
     * Create/Update all database tables
     */
    public static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // School licenses table (UPDATED - removed teacher/student user IDs)
        $table_school = $wpdb->prefix . 'pz_school_licenses';
        $sql1 = "CREATE TABLE IF NOT EXISTS $table_school (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            school_name varchar(255) NOT NULL,
            license_key varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'active',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime NOT NULL,
            max_students int(11) DEFAULT 9999,
            max_teachers int(11) DEFAULT 9999,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";

        // Student licenses table
        $table_student = $wpdb->prefix . 'pz_student_licenses';
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_student (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            license_key varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'active',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";

        // School members table (NEW - for manual assignments)
        $table_members = $wpdb->prefix . 'pz_school_members';
        $sql3 = "CREATE TABLE IF NOT EXISTS $table_members (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            school_license_id bigint(20) NOT NULL,
            assigned_user_id bigint(20) NOT NULL,
            member_type enum('teacher','student') NOT NULL,
            status varchar(20) DEFAULT 'active',
            assigned_by bigint(20) NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            removed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_assignment (school_license_id, assigned_user_id, member_type),
            KEY school_license_id (school_license_id),
            KEY assigned_user_id (assigned_user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);

        // Create flipbooks table
        PZ_Flipbooks::create_table();
    }

    /**
     * Migrate existing data (removes old teacher/student user IDs)
     */
    public static function migrate_existing_data()
    {
        global $wpdb;
        $table_school = $wpdb->prefix . 'pz_school_licenses';

        // Check if old columns exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_school LIKE 'teacher_user_id'");
        
        if (!empty($columns)) {
            // Remove old columns if they exist
            $wpdb->query("ALTER TABLE $table_school DROP COLUMN IF EXISTS teacher_user_id");
            $wpdb->query("ALTER TABLE $table_school DROP COLUMN IF EXISTS student_user_id");
        }
    }
}