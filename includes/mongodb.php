<?php
/**
 * MongoDB Helper/ORM Layer
 * Provides easy-to-use methods for MongoDB operations
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class MongoDBHelper {
    private static $db = null;
    
    /**
     * Get database instance
     */
    public static function getDB() {
        if (self::$db === null) {
            self::$db = getDBConnection();
        }
        return self::$db;
    }
    
    /**
     * Get collection
     */
    public static function collection($name) {
        $db = self::getDB();
        if (!$db) {
            throw new Exception('Database connection not available');
        }
        return $db->selectCollection($name);
    }
    
    /**
     * Find one document
     */
    public static function findOne($collection, $filter = [], $options = []) {
        try {
            $col = self::collection($collection);
            $result = $col->findOne($filter, $options);
            return $result ? self::convertToArray($result) : null;
        } catch (Exception $e) {
            error_log("MongoDB findOne error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find multiple documents
     */
    public static function find($collection, $filter = [], $options = []) {
        try {
            $col = self::collection($collection);
            $cursor = $col->find($filter, $options);
            $results = [];
            foreach ($cursor as $document) {
                $results[] = self::convertToArray($document);
            }
            return $results;
        } catch (Exception $e) {
            error_log("MongoDB find error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Insert one document
     */
    public static function insertOne($collection, $document) {
        try {
            $col = self::collection($collection);
            
            // Add timestamps
            $document['created_at'] = new MongoDB\BSON\UTCDateTime();
            $document['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $col->insertOne($document);
            return $result->getInsertedId();
        } catch (Exception $e) {
            error_log("MongoDB insertOne error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Insert multiple documents
     */
    public static function insertMany($collection, $documents) {
        try {
            $col = self::collection($collection);
            
            // Add timestamps to all documents
            $now = new MongoDB\BSON\UTCDateTime();
            foreach ($documents as &$doc) {
                $doc['created_at'] = $now;
                $doc['updated_at'] = $now;
            }
            
            $result = $col->insertMany($documents);
            return $result->getInsertedIds();
        } catch (Exception $e) {
            error_log("MongoDB insertMany error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update one document
     */
    public static function updateOne($collection, $filter, $update, $options = []) {
        try {
            $col = self::collection($collection);
            
            // Add updated_at timestamp
            if (!isset($update['$set'])) {
                $update['$set'] = [];
            }
            $update['$set']['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $col->updateOne($filter, $update, $options);
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            error_log("MongoDB updateOne error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update multiple documents
     */
    public static function updateMany($collection, $filter, $update, $options = []) {
        try {
            $col = self::collection($collection);
            
            // Add updated_at timestamp
            if (!isset($update['$set'])) {
                $update['$set'] = [];
            }
            $update['$set']['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $col->updateMany($filter, $update, $options);
            return $result->getModifiedCount();
        } catch (Exception $e) {
            error_log("MongoDB updateMany error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete one document
     */
    public static function deleteOne($collection, $filter) {
        try {
            $col = self::collection($collection);
            $result = $col->deleteOne($filter);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            error_log("MongoDB deleteOne error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete multiple documents
     */
    public static function deleteMany($collection, $filter) {
        try {
            $col = self::collection($collection);
            $result = $col->deleteMany($filter);
            return $result->getDeletedCount();
        } catch (Exception $e) {
            error_log("MongoDB deleteMany error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Count documents
     */
    public static function count($collection, $filter = []) {
        try {
            $col = self::collection($collection);
            return $col->countDocuments($filter);
        } catch (Exception $e) {
            error_log("MongoDB count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create index
     */
    public static function createIndex($collection, $keys, $options = []) {
        try {
            $col = self::collection($collection);
            $col->createIndex($keys, $options);
            return true;
        } catch (Exception $e) {
            error_log("MongoDB createIndex error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convert MongoDB document to array
     */
    private static function convertToArray($document) {
        if ($document instanceof MongoDB\Model\BSONDocument) {
            $array = $document->toArray();
            // Convert ObjectId to string
            foreach ($array as $key => $value) {
                if ($value instanceof MongoDB\BSON\ObjectId) {
                    $array[$key] = (string)$value;
                } elseif ($value instanceof MongoDB\BSON\UTCDateTime) {
                    $array[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
                } elseif (is_array($value) || $value instanceof MongoDB\Model\BSONArray) {
                    $array[$key] = self::convertToArray($value);
                }
            }
            return $array;
        }
        return $document;
    }
    
    /**
     * Convert string ID to ObjectId
     */
    public static function toObjectId($id) {
        if ($id instanceof MongoDB\BSON\ObjectId) {
            return $id;
        }
        try {
            return new MongoDB\BSON\ObjectId($id);
        } catch (Exception $e) {
            return null;
        }
    }
}

// Backward compatibility functions (similar to old PDO functions)
function dbFetchOne($collection, $filter = [], $options = []) {
    return MongoDBHelper::findOne($collection, $filter, $options);
}

function dbFetchAll($collection, $filter = [], $options = []) {
    return MongoDBHelper::find($collection, $filter, $options);
}

function dbInsert($collection, $document) {
    return MongoDBHelper::insertOne($collection, $document);
}

function dbQuery($collection, $filter = [], $operation = 'find', $data = null) {
    switch ($operation) {
        case 'find':
            return MongoDBHelper::find($collection, $filter);
        case 'findOne':
            return MongoDBHelper::findOne($collection, $filter);
        case 'insert':
            return MongoDBHelper::insertOne($collection, $data ?: $filter);
        case 'update':
            return MongoDBHelper::updateOne($collection, $filter, $data);
        case 'delete':
            return MongoDBHelper::deleteOne($collection, $filter);
        default:
            return null;
    }
}

function getDB() {
    return MongoDBHelper::getDB();
}

