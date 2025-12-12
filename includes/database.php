<?php
/**
 * Database Helper Functions (MongoDB)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mongodb.php';

/**
 * Get database connection
 */
function getDB() {
    return getDBConnection();
}

/**
 * Execute a query and return results (MongoDB compatible)
 * @deprecated Use MongoDBHelper methods directly
 */
function dbQuery($collection, $filter = [], $operation = 'find', $data = null) {
    return MongoDBHelper::find($collection, $filter);
}

/**
 * Get single document (MongoDB)
 */
function dbFetchOne($collection, $filter = [], $options = []) {
    return MongoDBHelper::findOne($collection, $filter, $options);
}

/**
 * Get all documents (MongoDB)
 */
function dbFetchAll($collection, $filter = [], $options = []) {
    return MongoDBHelper::find($collection, $filter, $options);
}

/**
 * Insert document and return inserted ID (MongoDB)
 */
function dbInsert($collection, $document) {
    return MongoDBHelper::insertOne($collection, $document);
}

