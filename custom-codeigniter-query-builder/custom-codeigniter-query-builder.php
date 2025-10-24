<?php
/**
 * Plugin Name: CI Query Builder for WordPress
 * Plugin URI: https://github.com/jhonivancuaco/CI-Query-Builder-for-WordPress
 * Description: CodeIgniter-style Query Builder and DB Forge for WordPress using $wpdb
 * Version: 1.0.0
 * Author: Jhon Ivan Cuaco
 * Author URI: https://github.com/jhonivancuaco
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CI Query Result Class
 * Handles query results with CodeIgniter-style methods
 */
class CI_Query_Result {
    protected $result_object;
    protected $result_array;
    protected $num_rows = 0;
    
    public function __construct($results) {
        if (is_array($results)) {
            $this->result_object = $results;
            $this->num_rows = count($results);
        } else {
            $this->result_object = [];
            $this->num_rows = 0;
        }
    }
    
    /**
     * Get results as objects
     */
    public function result($type = 'object') {
        if ($type === 'array') {
            return $this->result_array();
        }
        return $this->result_object;
    }
    
    /**
     * Get results as associative arrays
     */
    public function result_array() {
        if ($this->result_array === null) {
            $this->result_array = [];
            foreach ($this->result_object as $row) {
                $this->result_array[] = (array) $row;
            }
        }
        return $this->result_array;
    }
    
    /**
     * Get first row as object
     */
    public function row($n = 0, $type = 'object') {
        if (!isset($this->result_object[$n])) {
            return null;
        }
        
        if ($type === 'array') {
            return (array) $this->result_object[$n];
        }
        
        return $this->result_object[$n];
    }
    
    /**
     * Get first row as array
     */
    public function row_array($n = 0) {
        return $this->row($n, 'array');
    }
    
    /**
     * Get first row as object
     */
    public function first_row($type = 'object') {
        return $this->row(0, $type);
    }
    
    /**
     * Get last row as object
     */
    public function last_row($type = 'object') {
        return $this->row($this->num_rows - 1, $type);
    }
    
    /**
     * Get next row as object
     */
    public function next_row($type = 'object') {
        static $current_row = 0;
        
        if (isset($this->result_object[$current_row])) {
            $row = $this->row($current_row, $type);
            $current_row++;
            return $row;
        }
        
        $current_row = 0;
        return null;
    }
    
    /**
     * Get previous row as object
     */
    public function previous_row($type = 'object') {
        static $current_row = 0;
        
        if (isset($this->result_object[$current_row - 1])) {
            $current_row--;
            return $this->row($current_row, $type);
        }
        
        return null;
    }
    
    /**
     * Get number of rows
     */
    public function num_rows() {
        return $this->num_rows;
    }
    
    /**
     * Get number of fields
     */
    public function num_fields() {
        if (isset($this->result_object[0])) {
            return count((array) $this->result_object[0]);
        }
        return 0;
    }
    
    /**
     * Free result
     */
    public function free_result() {
        $this->result_object = [];
        $this->result_array = null;
        $this->num_rows = 0;
    }
}

/**
 * CI Query Builder Class
 * CodeIgniter-style query builder that wraps WordPress $wpdb
 */
class CI_Query_Builder {
    protected $wpdb;
    protected $select = '*';
    protected $from = '';
    protected $join = [];
    protected $where = [];
    protected $or_where = [];
    protected $where_in = [];
    protected $where_not_in = [];
    protected $like = [];
    protected $or_like = [];
    protected $group_by = [];
    protected $having = [];
    protected $order_by = [];
    protected $limit = null;
    protected $offset = null;
    protected $set = [];
    protected $distinct = false;
    protected $last_query = '';
    protected $debug_mode = false;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdb->show_errors();
    }
    
    /**
     * Enable debug mode
     */
    public function debug($enable = true) {
        $this->debug_mode = $enable;
        return $this;
    }
    
    /**
     * Get last executed query
     */
    public function last_query() {
        return $this->last_query;
    }
    
    /**
     * Get last database error
     */
    public function error() {
        return [
            'message' => $this->wpdb->last_error,
            'query' => $this->last_query
        ];
    }
    
    /**
     * Print last query (for debugging)
     */
    public function print_query() {
        echo '<pre>';
        echo htmlspecialchars($this->last_query);
        echo '</pre>';
        return $this;
    }
    
    /**
     * Reset all query components
     */
    protected function reset_query() {
        $this->select = '*';
        $this->from = '';
        $this->join = [];
        $this->where = [];
        $this->or_where = [];
        $this->where_in = [];
        $this->where_not_in = [];
        $this->like = [];
        $this->or_like = [];
        $this->group_by = [];
        $this->having = [];
        $this->order_by = [];
        $this->limit = null;
        $this->offset = null;
        $this->set = [];
        $this->distinct = false;
    }
    
    /**
     * SELECT clause
     */
    public function select($select = '*', $escape = true) {
        if (is_array($select)) {
            $select = implode(', ', $select);
        }
        $this->select = $escape ? $this->escape_identifiers($select) : $select;
        return $this;
    }
    
    /**
     * SELECT DISTINCT
     */
    public function distinct($val = true) {
        $this->distinct = $val;
        return $this;
    }
    
    /**
     * FROM clause
     */
    public function from($table, $add_prefix = true) {
        if ($add_prefix) {
            $this->from = $this->wpdb->prefix . $table;
        } else {
            $this->from = $table;
        }
        return $this;
    }
    
    /**
     * FROM clause with full table name (no prefix added)
     */
    public function from_raw($table) {
        $this->from = $table;
        return $this;
    }
    
    /**
     * JOIN clause
     */
    public function join($table, $condition, $type = 'INNER') {
        $table = $this->wpdb->prefix . $table;
        $type = strtoupper(trim($type));
        $this->join[] = "{$type} JOIN {$table} ON {$condition}";
        return $this;
    }
    
    /**
     * WHERE clause
     */
    public function where($key, $value = null, $escape = true) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->where($k, $v, $escape);
            }
            return $this;
        }
        
        if ($escape) {
            $value = $this->wpdb->prepare('%s', $value);
        }
        
        $this->where[] = "{$key} = {$value}";
        return $this;
    }
    
    /**
     * OR WHERE clause
     */
    public function or_where($key, $value = null, $escape = true) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->or_where($k, $v, $escape);
            }
            return $this;
        }
        
        if ($escape) {
            $value = $this->wpdb->prepare('%s', $value);
        }
        
        $this->or_where[] = "{$key} = {$value}";
        return $this;
    }
    
    /**
     * WHERE IN clause
     */
    public function where_in($key, $values) {
        if (empty($values)) {
            return $this;
        }
        
        $placeholders = implode(',', array_fill(0, count($values), '%s'));
        $escaped = $this->wpdb->prepare($placeholders, $values);
        $this->where_in[] = "{$key} IN ({$escaped})";
        return $this;
    }
    
    /**
     * WHERE NOT IN clause
     */
    public function where_not_in($key, $values) {
        if (empty($values)) {
            return $this;
        }
        
        $placeholders = implode(',', array_fill(0, count($values), '%s'));
        $escaped = $this->wpdb->prepare($placeholders, $values);
        $this->where_not_in[] = "{$key} NOT IN ({$escaped})";
        return $this;
    }
    
    /**
     * LIKE clause
     */
    public function like($field, $match, $side = 'both') {
        $match = $this->escape_like_str($match);
        
        switch ($side) {
            case 'before':
                $match = "%{$match}";
                break;
            case 'after':
                $match = "{$match}%";
                break;
            case 'both':
            default:
                $match = "%{$match}%";
                break;
        }
        
        $this->like[] = $this->wpdb->prepare("{$field} LIKE %s", $match);
        return $this;
    }
    
    /**
     * OR LIKE clause
     */
    public function or_like($field, $match, $side = 'both') {
        $match = $this->escape_like_str($match);
        
        switch ($side) {
            case 'before':
                $match = "%{$match}";
                break;
            case 'after':
                $match = "{$match}%";
                break;
            case 'both':
            default:
                $match = "%{$match}%";
                break;
        }
        
        $this->or_like[] = $this->wpdb->prepare("{$field} LIKE %s", $match);
        return $this;
    }
    
    /**
     * GROUP BY clause
     */
    public function group_by($by) {
        if (is_array($by)) {
            $this->group_by = array_merge($this->group_by, $by);
        } else {
            $this->group_by[] = $by;
        }
        return $this;
    }
    
    /**
     * HAVING clause
     */
    public function having($key, $value = null, $escape = true) {
        if ($escape) {
            $value = $this->wpdb->prepare('%s', $value);
        }
        $this->having[] = "{$key} = {$value}";
        return $this;
    }
    
    /**
     * ORDER BY clause
     */
    public function order_by($orderby, $direction = 'ASC') {
        $direction = strtoupper(trim($direction));
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        $this->order_by[] = "{$orderby} {$direction}";
        return $this;
    }
    
    /**
     * LIMIT clause
     */
    public function limit($limit, $offset = null) {
        $this->limit = (int) $limit;
        if ($offset !== null) {
            $this->offset = (int) $offset;
        }
        return $this;
    }
    
    /**
     * OFFSET clause
     */
    public function offset($offset) {
        $this->offset = (int) $offset;
        return $this;
    }
    
    /**
     * SET clause for INSERT/UPDATE
     */
    public function set($key, $value = '', $escape = true) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v, $escape);
            }
            return $this;
        }
        
        $this->set[$key] = $escape ? $value : $this->wpdb->prepare('%s', $value);
        return $this;
    }
    
    /**
     * Build the complete SQL query
     */
    protected function build_query() {
        $sql = 'SELECT ';
        
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        
        $sql .= $this->select;
        $sql .= " FROM {$this->from}";
        
        // JOIN
        if (!empty($this->join)) {
            $sql .= ' ' . implode(' ', $this->join);
        }
        
        // WHERE
        $where_clauses = array_merge($this->where, $this->where_in, $this->where_not_in, $this->like);
        if (!empty($where_clauses) || !empty($this->or_where) || !empty($this->or_like)) {
            $sql .= ' WHERE ';
            
            if (!empty($where_clauses)) {
                $sql .= '(' . implode(' AND ', $where_clauses) . ')';
            }
            
            $or_clauses = array_merge($this->or_where, $this->or_like);
            if (!empty($or_clauses)) {
                if (!empty($where_clauses)) {
                    $sql .= ' OR ';
                }
                $sql .= '(' . implode(' OR ', $or_clauses) . ')';
            }
        }
        
        // GROUP BY
        if (!empty($this->group_by)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->group_by);
        }
        
        // HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }
        
        // ORDER BY
        if (!empty($this->order_by)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->order_by);
        }
        
        // LIMIT
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        
        return $sql;
    }
    
    /**
     * Get query results - Returns CI_Query_Result object
     */
    public function get($table = null, $limit = null, $offset = null) {
        if ($table !== null) {
            $this->from($table);
        }
        
        if ($limit !== null) {
            $this->limit($limit, $offset);
        }
        
        $sql = $this->build_query();
        $this->last_query = $sql;
        
        // Debug mode - print query
        if ($this->debug_mode) {
            echo '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 10px; margin: 10px 0;">';
            echo '<strong>Query:</strong><br>';
            echo '<code>' . htmlspecialchars($sql) . '</code>';
            echo '</div>';
        }
        
        $results = $this->wpdb->get_results($sql);
        
        // Check for errors
        if ($this->wpdb->last_error) {
            if ($this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG)) {
                echo '<div style="background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; color: #c62828;">';
                echo '<strong>Database Error:</strong><br>';
                echo htmlspecialchars($this->wpdb->last_error) . '<br><br>';
                echo '<strong>Query:</strong><br>';
                echo '<code>' . htmlspecialchars($sql) . '</code>';
                echo '</div>';
            }
            error_log('CI_QB Error: ' . $this->wpdb->last_error . ' | Query: ' . $sql);
        }
        
        $this->reset_query();
        
        return new CI_Query_Result($results ? $results : []);
    }
    
    /**
     * Get results with WHERE clause
     */
    public function get_where($table, $where = [], $limit = null, $offset = null) {
        $this->from($table)->where($where);
        
        if ($limit !== null) {
            $this->limit($limit, $offset);
        }
        
        return $this->get();
    }
    
    /**
     * Count results
     */
    public function count_all_results($table = null) {
        if ($table !== null) {
            $this->from($table);
        }
        
        $this->select = 'COUNT(*) as num_rows';
        $sql = $this->build_query();
        $result = $this->wpdb->get_var($sql);
        $this->reset_query();
        
        return (int) $result;
    }
    
    /**
     * INSERT query
     */
    public function insert($table, $data = []) {
        $table = $this->wpdb->prefix . $table;
        
        if (!empty($data)) {
            $this->set = $data;
        }
        
        $result = $this->wpdb->insert($table, $this->set);
        $this->last_query = $this->wpdb->last_query;
        
        if ($this->wpdb->last_error) {
            if ($this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG)) {
                echo '<div style="background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; color: #c62828;">';
                echo '<strong>Insert Error:</strong><br>';
                echo htmlspecialchars($this->wpdb->last_error);
                echo '</div>';
            }
            error_log('CI_QB Insert Error: ' . $this->wpdb->last_error);
        }
        
        $this->reset_query();
        
        return $result !== false;
    }
    
    /**
     * INSERT multiple rows
     */
    public function insert_batch($table, $data) {
        $table = $this->wpdb->prefix . $table;
        
        $success = true;
        foreach ($data as $row) {
            $result = $this->wpdb->insert($table, $row);
            if ($result === false) {
                $success = false;
                if ($this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG)) {
                    echo '<div style="background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; color: #c62828;">';
                    echo '<strong>Batch Insert Error:</strong><br>';
                    echo htmlspecialchars($this->wpdb->last_error);
                    echo '</div>';
                }
            }
        }
        
        $this->last_query = $this->wpdb->last_query;
        return $success;
    }
    
    /**
     * UPDATE query
     */
    public function update($table, $data = [], $where = []) {
        $table = $this->wpdb->prefix . $table;
        
        if (!empty($data)) {
            $this->set = $data;
        }
        
        if (!empty($where)) {
            $this->where($where);
        }
        
        // Build WHERE clause from query builder
        $where_clause = [];
        foreach ($this->where as $condition) {
            $parts = explode(' = ', $condition);
            if (count($parts) == 2) {
                $where_clause[trim($parts[0])] = trim($parts[1], "'");
            }
        }
        
        $result = $this->wpdb->update($table, $this->set, $where_clause);
        $this->last_query = $this->wpdb->last_query;
        
        if ($this->wpdb->last_error) {
            if ($this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG)) {
                echo '<div style="background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; color: #c62828;">';
                echo '<strong>Update Error:</strong><br>';
                echo htmlspecialchars($this->wpdb->last_error);
                echo '</div>';
            }
            error_log('CI_QB Update Error: ' . $this->wpdb->last_error);
        }
        
        $this->reset_query();
        
        return $result !== false;
    }
    
    /**
     * DELETE query
     */
    public function delete($table, $where = []) {
        $table = $this->wpdb->prefix . $table;
        
        if (!empty($where)) {
            $this->where($where);
        }
        
        // Build WHERE clause
        $where_clause = [];
        foreach ($this->where as $condition) {
            $parts = explode(' = ', $condition);
            if (count($parts) == 2) {
                $where_clause[trim($parts[0])] = trim($parts[1], "'");
            }
        }
        
        $result = $this->wpdb->delete($table, $where_clause);
        $this->last_query = $this->wpdb->last_query;
        
        if ($this->wpdb->last_error) {
            if ($this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG)) {
                echo '<div style="background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; color: #c62828;">';
                echo '<strong>Delete Error:</strong><br>';
                echo htmlspecialchars($this->wpdb->last_error);
                echo '</div>';
            }
            error_log('CI_QB Delete Error: ' . $this->wpdb->last_error);
        }
        
        $this->reset_query();
        
        return $result !== false;
    }
    
    /**
     * Truncate table
     */
    public function truncate($table) {
        $table = $this->wpdb->prefix . $table;
        return $this->wpdb->query("TRUNCATE TABLE {$table}");
    }
    
    /**
     * Empty table
     */
    public function empty_table($table) {
        $table = $this->wpdb->prefix . $table;
        return $this->wpdb->query("DELETE FROM {$table}");
    }
    
    /**
     * Get last inserted ID
     */
    public function insert_id() {
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get affected rows
     */
    public function affected_rows() {
        return $this->wpdb->rows_affected;
    }
    
    /**
     * Execute raw query
     */
    public function query($sql) {
        return $this->wpdb->query($sql);
    }
    
    /**
     * Escape identifiers
     */
    protected function escape_identifiers($item) {
        return $item;
    }
    
    /**
     * Escape LIKE string
     */
    protected function escape_like_str($str) {
        return $this->wpdb->esc_like($str);
    }
}

/**
 * CI DB Forge Class
 * CodeIgniter-style database forge for creating/modifying tables
 */
class CI_DB_Forge {
    protected $wpdb;
    protected $fields = [];
    protected $keys = [];
    protected $primary_keys = [];
    protected $foreign_keys = [];
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->wpdb->show_errors();
    }
    
    /**
     * Reset forge properties
     */
    protected function reset() {
        $this->fields = [];
        $this->keys = [];
        $this->primary_keys = [];
        $this->foreign_keys = [];
    }
    
    /**
     * Add field(s) to table
     */
    public function add_field($field) {
        if (is_string($field)) {
            if ($field === 'id') {
                $this->fields['id'] = [
                    'type' => 'BIGINT',
                    'constraint' => 20,
                    'unsigned' => true,
                    'auto_increment' => true
                ];
                $this->primary_keys[] = 'id';
                return $this;
            }
            
            return $this;
        }
        
        foreach ($field as $name => $attributes) {
            $this->fields[$name] = $attributes;
        }
        
        return $this;
    }
    
    /**
     * Add key(s)
     */
    public function add_key($key, $primary = false) {
        if ($primary) {
            if (is_array($key)) {
                $this->primary_keys = array_merge($this->primary_keys, $key);
            } else {
                $this->primary_keys[] = $key;
            }
        } else {
            if (is_array($key)) {
                $this->keys = array_merge($this->keys, $key);
            } else {
                $this->keys[] = $key;
            }
        }
        
        return $this;
    }
    
    /**
     * Add foreign key
     */
    public function add_foreign_key($field, $reference_table, $reference_field, $on_delete = 'CASCADE', $on_update = 'CASCADE') {
        $this->foreign_keys[] = [
            'field' => $field,
            'reference_table' => $this->wpdb->prefix . $reference_table,
            'reference_field' => $reference_field,
            'on_delete' => $on_delete,
            'on_update' => $on_update
        ];
        
        return $this;
    }
    
    /**
     * Build field definition
     */
    protected function build_field_definition($field, $attributes) {
        $sql = "`{$field}` ";
        
        // Type
        $type = strtoupper($attributes['type']);
        
        // Constraint/Length
        if (isset($attributes['constraint'])) {
            $sql .= "{$type}({$attributes['constraint']})";
        } else {
            $sql .= $type;
        }
        
        // Unsigned
        if (isset($attributes['unsigned']) && $attributes['unsigned']) {
            $sql .= ' UNSIGNED';
        }
        
        // Null
        if (isset($attributes['null']) && $attributes['null']) {
            $sql .= ' NULL';
        } else {
            $sql .= ' NOT NULL';
        }
        
        // Default
        if (isset($attributes['default'])) {
            $sql .= " DEFAULT '{$attributes['default']}'";
        }
        
        // Auto increment
        if (isset($attributes['auto_increment']) && $attributes['auto_increment']) {
            $sql .= ' AUTO_INCREMENT';
        }
        
        return $sql;
    }
    
    /**
     * Create table
     */
    public function create_table($table, $if_not_exists = false) {
        $table = $this->wpdb->prefix . $table;
        
        $sql = 'CREATE TABLE ';
        
        if ($if_not_exists) {
            $sql .= 'IF NOT EXISTS ';
        }
        
        $sql .= "`{$table}` (";
        
        // Fields
        $field_definitions = [];
        foreach ($this->fields as $field => $attributes) {
            $field_definitions[] = $this->build_field_definition($field, $attributes);
        }
        $sql .= implode(', ', $field_definitions);
        
        // Primary Key
        if (!empty($this->primary_keys)) {
            $sql .= ', PRIMARY KEY (' . implode(', ', array_map(function($key) {
                return "`{$key}`";
            }, $this->primary_keys)) . ')';
        }
        
        // Keys/Indexes
        if (!empty($this->keys)) {
            foreach ($this->keys as $key) {
                $sql .= ", KEY `{$key}` (`{$key}`)";
            }
        }
        
        // Foreign Keys
        if (!empty($this->foreign_keys)) {
            foreach ($this->foreign_keys as $fk) {
                $sql .= ", FOREIGN KEY (`{$fk['field']}`) REFERENCES `{$fk['reference_table']}` (`{$fk['reference_field']}`) ON DELETE {$fk['on_delete']} ON UPDATE {$fk['on_update']}";
            }
        }
        
        $sql .= ') ' . $this->wpdb->get_charset_collate() . ';';
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $this->reset();
        
        return true;
    }
    
    /**
     * Drop table
     */
    public function drop_table($table, $if_exists = false) {
        $table = $this->wpdb->prefix . $table;
        
        $sql = 'DROP TABLE ';
        
        if ($if_exists) {
            $sql .= 'IF EXISTS ';
        }
        
        $sql .= "`{$table}`";
        
        return $this->wpdb->query($sql);
    }
    
    /**
     * Rename table
     */
    public function rename_table($old_name, $new_name) {
        $old_name = $this->wpdb->prefix . $old_name;
        $new_name = $this->wpdb->prefix . $new_name;
        
        $sql = "RENAME TABLE `{$old_name}` TO `{$new_name}`";
        
        return $this->wpdb->query($sql);
    }
    
    /**
     * Add column to existing table
     */
    public function add_column($table, $field, $after_field = '') {
        $table = $this->wpdb->prefix . $table;
        
        $sql = "ALTER TABLE `{$table}` ADD ";
        
        foreach ($field as $name => $attributes) {
            $sql .= $this->build_field_definition($name, $attributes);
        }
        
        if ($after_field !== '') {
            $sql .= " AFTER `{$after_field}`";
        }
        
        return $this->wpdb->query($sql);
    }
    
    /**
     * Drop column from table
     */
    public function drop_column($table, $column) {
        $table = $this->wpdb->prefix . $table;
        
        $sql = "ALTER TABLE `{$table}` DROP COLUMN `{$column}`";
        
        return $this->wpdb->query($sql);
    }
    
    /**
     * Modify column in table
     */
    public function modify_column($table, $field) {
        $table = $this->wpdb->prefix . $table;
        
        $sql = "ALTER TABLE `{$table}` MODIFY ";
        
        foreach ($field as $name => $attributes) {
            $sql .= $this->build_field_definition($name, $attributes);
        }
        
        return $this->wpdb->query($sql);
    }
}

/**
 * Global instance holder
 */
global $ci_qb_instance, $ci_forge_instance;
$ci_qb_instance = null;
$ci_forge_instance = null;

/**
 * Get Query Builder instance (Singleton)
 * Can be used in any plugin or theme
 */
if (!function_exists('CI_QB')) {
    function CI_QB() {
        global $ci_qb_instance;
        if ($ci_qb_instance === null) {
            $ci_qb_instance = new CI_Query_Builder();
        }
        return $ci_qb_instance;
    }
}

/**
 * Get DB Forge instance (Singleton)
 * Can be used in any plugin or theme
 */
if (!function_exists('CI_Forge')) {
    function CI_Forge() {
        global $ci_forge_instance;
        if ($ci_forge_instance === null) {
            $ci_forge_instance = new CI_DB_Forge();
        }
        return $ci_forge_instance;
    }
}

/**
 * Alternative: Get new Query Builder instance (Factory)
 * Use this if you need multiple independent query builder instances
 */
if (!function_exists('ci_qb_new')) {
    function ci_qb_new() {
        return new CI_Query_Builder();
    }
}

/**
 * Alternative: Get new DB Forge instance (Factory)
 * Use this if you need multiple independent forge instances
 */
if (!function_exists('ci_forge_new')) {
    function ci_forge_new() {
        return new CI_DB_Forge();
    }
}

/**
 * Example usage hook (remove or modify as needed)
 */
function ci_qb_example_usage() {
    // Only run this once for demonstration
    if (get_option('ci_qb_example_run')) {
        return;
    }
    
    // Example: Create a custom table
    $forge = CI_Forge();
    
    $forge->add_field([
        'id' => [
            'type' => 'BIGINT',
            'constraint' => 20,
            'unsigned' => true,
            'auto_increment' => true
        ],
        'name' => [
            'type' => 'VARCHAR',
            'constraint' => 100,
            'null' => false
        ],
        'email' => [
            'type' => 'VARCHAR',
            'constraint' => 100,
            'null' => false
        ],
        'created_at' => [
            'type' => 'DATETIME',
            'null' => false
        ]
    ]);
    
    $forge->add_key('id', true);
    $forge->add_key('email');
    $forge->create_table('ci_example_table', true);
    
    // Example: Insert data
    $qb = CI_QB();
    $qb->insert('ci_example_table', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_at' => current_time('mysql')
    ]);
    
    // Mark example as run
    update_option('ci_qb_example_run', true);
}
// Uncomment to run example on activation
// register_activation_hook(__FILE__, 'ci_qb_example_usage');