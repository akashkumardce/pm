<?php
/**
 * MongoDB Schema Setup
 * Creates collections and indexes
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mongodb.php';

function setupMongoDBSchema() {
    try {
        $db = getDBConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }
        
        $results = [];
        
        // Users collection
        $users = MongoDBHelper::collection('users');
        MongoDBHelper::createIndex('users', ['email' => 1], ['unique' => true]);
        MongoDBHelper::createIndex('users', ['status' => 1]);
        $results[] = 'Users collection ready';
        
        // Roles collection
        $roles = MongoDBHelper::collection('roles');
        MongoDBHelper::createIndex('roles', ['slug' => 1], ['unique' => true]);
        
        // Insert default roles if they don't exist
        $defaultRoles = [
            ['name' => 'Property Owner', 'slug' => 'property_owner', 'description' => 'User who owns properties'],
            ['name' => 'Tenant', 'slug' => 'tenant', 'description' => 'User who rents properties'],
            ['name' => 'Property Manager', 'slug' => 'property_manager', 'description' => 'User who manages properties'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'System administrator']
        ];
        
        foreach ($defaultRoles as $role) {
            $existing = MongoDBHelper::findOne('roles', ['slug' => $role['slug']]);
            if (!$existing) {
                MongoDBHelper::insertOne('roles', $role);
            }
        }
        $results[] = 'Roles collection ready with default data';
        
        // User roles collection (junction)
        $userRoles = MongoDBHelper::collection('user_roles');
        MongoDBHelper::createIndex('user_roles', ['user_id' => 1, 'role_id' => 1], ['unique' => true]);
        MongoDBHelper::createIndex('user_roles', ['user_id' => 1]);
        MongoDBHelper::createIndex('user_roles', ['role_id' => 1]);
        $results[] = 'User roles collection ready';
        
        // Property types collection
        $propertyTypes = MongoDBHelper::collection('property_types');
        MongoDBHelper::createIndex('property_types', ['slug' => 1], ['unique' => true]);
        
        // Insert default property types
        // Note: Floors only needed for PG, not for apartments/flats
        $defaultPropertyTypes = [
            ['name' => 'Land', 'slug' => 'land', 'description' => 'Empty land or plot', 'has_rooms' => false, 'has_floors' => false],
            ['name' => 'Home', 'slug' => 'home', 'description' => 'Residential home', 'has_rooms' => false, 'has_floors' => false],
            ['name' => 'PG (Paying Guest)', 'slug' => 'pg', 'description' => 'Paying Guest accommodation', 'has_rooms' => true, 'has_floors' => true],
            ['name' => 'Villa', 'slug' => 'villa', 'description' => 'Luxury villa', 'has_rooms' => false, 'has_floors' => false],
            ['name' => 'Flat/Apartment', 'slug' => 'flat', 'description' => 'Apartment or flat', 'has_rooms' => false, 'has_floors' => false],
            ['name' => 'Commercial', 'slug' => 'commercial', 'description' => 'Commercial property', 'has_rooms' => false, 'has_floors' => false]
        ];
        
        foreach ($defaultPropertyTypes as $type) {
            $existing = MongoDBHelper::findOne('property_types', ['slug' => $type['slug']]);
            if (!$existing) {
                MongoDBHelper::insertOne('property_types', $type);
            }
        }
        $results[] = 'Property types collection ready with default data';
        
        // Properties collection
        $properties = MongoDBHelper::collection('properties');
        MongoDBHelper::createIndex('properties', ['user_id' => 1]);
        MongoDBHelper::createIndex('properties', ['property_type_id' => 1]);
        MongoDBHelper::createIndex('properties', ['status' => 1]);
        $results[] = 'Properties collection ready';
        
        // Property details collection
        $propertyDetails = MongoDBHelper::collection('property_details');
        MongoDBHelper::createIndex('property_details', ['property_id' => 1, 'key' => 1], ['unique' => true]);
        MongoDBHelper::createIndex('property_details', ['property_id' => 1]);
        $results[] = 'Property details collection ready';
        
        // Floors collection
        $floors = MongoDBHelper::collection('floors');
        MongoDBHelper::createIndex('floors', ['property_id' => 1, 'floor_number' => 1], ['unique' => true]);
        MongoDBHelper::createIndex('floors', ['property_id' => 1]);
        $results[] = 'Floors collection ready';
        
        // Rooms collection
        $rooms = MongoDBHelper::collection('rooms');
        MongoDBHelper::createIndex('rooms', ['property_id' => 1]);
        MongoDBHelper::createIndex('rooms', ['floor_id' => 1]);
        MongoDBHelper::createIndex('rooms', ['status' => 1]);
        $results[] = 'Rooms collection ready';
        
        // Renters collection
        $renters = MongoDBHelper::collection('renters');
        MongoDBHelper::createIndex('renters', ['property_id' => 1]);
        MongoDBHelper::createIndex('renters', ['room_id' => 1]);
        MongoDBHelper::createIndex('renters', ['user_id' => 1]);
        MongoDBHelper::createIndex('renters', ['status' => 1]);
        MongoDBHelper::createIndex('renters', ['mobile' => 1]);
        $results[] = 'Renters collection ready';
        
        // Notifications collection
        $notifications = MongoDBHelper::collection('notifications');
        MongoDBHelper::createIndex('notifications', ['renter_id' => 1]);
        MongoDBHelper::createIndex('notifications', ['property_id' => 1]);
        MongoDBHelper::createIndex('notifications', ['sender_id' => 1]);
        MongoDBHelper::createIndex('notifications', ['status' => 1]);
        $results[] = 'Notifications collection ready';
        
        // Complaints/Tasks/Requests collection
        $complaints = MongoDBHelper::collection('complaints');
        MongoDBHelper::createIndex('complaints', ['renter_id' => 1]);
        MongoDBHelper::createIndex('complaints', ['property_id' => 1]);
        MongoDBHelper::createIndex('complaints', ['room_id' => 1]);
        MongoDBHelper::createIndex('complaints', ['status' => 1]);
        MongoDBHelper::createIndex('complaints', ['created_at' => -1]);
        $results[] = 'Complaints collection ready';
        
        // Complaint replies collection
        $complaintReplies = MongoDBHelper::collection('complaint_replies');
        MongoDBHelper::createIndex('complaint_replies', ['complaint_id' => 1]);
        MongoDBHelper::createIndex('complaint_replies', ['user_id' => 1]);
        MongoDBHelper::createIndex('complaint_replies', ['created_at' => 1]);
        $results[] = 'Complaint replies collection ready';
        
        // Property listings collection (for rental listings)
        $propertyListings = MongoDBHelper::collection('property_listings');
        MongoDBHelper::createIndex('property_listings', ['property_id' => 1], ['unique' => true]);
        MongoDBHelper::createIndex('property_listings', ['status' => 1]);
        MongoDBHelper::createIndex('property_listings', ['created_at' => -1]);
        $results[] = 'Property listings collection ready';
        
        // Rent payments collection
        $rentPayments = MongoDBHelper::collection('rent_payments');
        MongoDBHelper::createIndex('rent_payments', ['renter_id' => 1]);
        MongoDBHelper::createIndex('rent_payments', ['property_id' => 1]);
        MongoDBHelper::createIndex('rent_payments', ['room_id' => 1]);
        MongoDBHelper::createIndex('rent_payments', ['status' => 1]);
        MongoDBHelper::createIndex('rent_payments', ['payment_date' => -1]);
        MongoDBHelper::createIndex('rent_payments', ['created_at' => -1]);
        $results[] = 'Rent payments collection ready';
        
        return [
            'success' => true,
            'message' => 'MongoDB schema setup completed',
            'details' => $results
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Schema setup failed: ' . $e->getMessage()
        ];
    }
}

