# CI Query Builder for WordPress

A comprehensive CodeIgniter-style Query Builder and DB Forge implementation for WordPress that wraps the native `$wpdb` class. This plugin provides an intuitive, chainable interface for database operations while maintaining WordPress compatibility and security standards.

## Features

- ✅ **Full Query Builder**: SELECT, INSERT, UPDATE, DELETE operations
- ✅ **CodeIgniter-Style Syntax**: Familiar API for CI developers
- ✅ **DB Forge**: Create, modify, and manage database tables
- ✅ **Method Chaining**: Fluent interface for building queries
- ✅ **WordPress Integration**: Uses `$wpdb` under the hood
- ✅ **Auto Prefixing**: Automatically handles WordPress table prefixes
- ✅ **Result Objects**: CodeIgniter-style result handling
- ✅ **Debug Mode**: Built-in query debugging and error display
- ✅ **Global Access**: Available in all themes and plugins
- ✅ **Security**: Leverages WordPress prepared statements

## Installation

1. Download the plugin file
2. Upload to `/wp-content/plugins/ci-query-builder/`
3. Activate through the WordPress 'Plugins' menu

## Basic Usage

### Initializing the Query Builder

```php
// Get singleton instance (recommended)
$qb = CI_QB();

// Or get a new instance
$qb = ci_qb_new();
```

### SELECT Queries

#### Method Chaining Style
```php
$qb = CI_QB();
$query = $qb->select('name, email')
            ->from('users')
            ->where('status', 'active')
            ->order_by('created_at', 'DESC')
            ->limit(10)
            ->get();

$users = $query->result();
```

#### Separate Calls Style
```php
$qb = CI_QB();
$qb->select('name, email');
$qb->from('users');
$qb->where('status', 'active');
$qb->order_by('created_at', 'DESC');
$qb->limit(10);
$query = $qb->get();

$users = $query->result();
```

#### Simple Get
```php
// Get all records from a table
$query = $qb->get('users');
$users = $query->result();
```

### Working with Results

```php
$query = $qb->select('*')->from('users')->get();

// Get all results as objects
$users = $query->result();

// Get all results as arrays
$users = $query->result_array();

// Get first row as object
$user = $query->row();

// Get first row as array
$user = $query->row_array();

// Get specific row (0-indexed)
$user = $query->row(2); // Third row

// Get number of rows
$count = $query->num_rows();

// Get number of fields
$fields = $query->num_fields();

// Iterate through results
foreach ($query->result() as $row) {
    echo $row->name;
}

// Navigate results
$first = $query->first_row();
$last = $query->last_row();
$next = $query->next_row();
$prev = $query->previous_row();
```

## Query Builder Methods

### SELECT

```php
// Select all columns
$qb->select('*');

// Select specific columns
$qb->select('name, email, phone');

// Select with array
$qb->select(['name', 'email', 'phone']);

// Select distinct
$qb->distinct()->select('category');
```

### FROM

```php
// Regular table (auto-prefixes)
$qb->from('users'); // becomes wp_users

// Disable auto-prefix
$qb->from('wp_users', false);

// Use raw table name
$qb->from_raw('wp_users');
```

### WHERE

```php
// Simple where
$qb->where('status', 'active');

// Multiple where (AND)
$qb->where('status', 'active')
   ->where('role', 'admin');

// Array where
$qb->where([
    'status' => 'active',
    'role' => 'admin'
]);

// OR where
$qb->or_where('role', 'editor')
   ->or_where('role', 'admin');

// WHERE IN
$qb->where_in('id', [1, 2, 3, 4, 5]);

// WHERE NOT IN
$qb->where_not_in('status', ['deleted', 'banned']);
```

### LIKE

```php
// Default (both sides)
$qb->like('name', 'john'); // name LIKE '%john%'

// Before
$qb->like('name', 'john', 'before'); // name LIKE '%john'

// After
$qb->like('name', 'john', 'after'); // name LIKE 'john%'

// OR LIKE
$qb->or_like('name', 'jane');
```

### JOIN

```php
// Inner join
$qb->join('posts', 'posts.user_id = users.id');

// Left join
$qb->join('posts', 'posts.user_id = users.id', 'LEFT');

// Right join
$qb->join('posts', 'posts.user_id = users.id', 'RIGHT');
```

### GROUP BY & HAVING

```php
// Group by
$qb->group_by('category');

// Multiple group by
$qb->group_by(['category', 'status']);

// Having
$qb->having('COUNT(id) >', 5);
```

### ORDER BY

```php
// Ascending (default)
$qb->order_by('created_at');

// Descending
$qb->order_by('created_at', 'DESC');

// Multiple order by
$qb->order_by('status', 'ASC')
   ->order_by('created_at', 'DESC');
```

### LIMIT & OFFSET

```php
// Limit only
$qb->limit(10);

// Limit with offset
$qb->limit(10, 20);

// Or separate
$qb->limit(10)->offset(20);
```

### COUNT

```php
$count = $qb->from('users')
            ->where('status', 'active')
            ->count_all_results();
```

## INSERT Operations

### Single Insert

```php
// Method 1: Direct insert
$qb->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'active'
]);

// Method 2: Using set()
$qb->set('name', 'John Doe')
   ->set('email', 'john@example.com')
   ->insert('users');

// Method 3: Set with array
$qb->set([
    'name' => 'John Doe',
    'email' => 'john@example.com'
])->insert('users');

// Get inserted ID
$id = $qb->insert_id();
```

### Batch Insert

```php
$data = [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com']
];

$qb->insert_batch('users', $data);
```

## UPDATE Operations

```php
// Method 1: Direct update
$qb->update('users', 
    ['status' => 'inactive'], 
    ['id' => 5]
);

// Method 2: Using where()
$qb->where('id', 5)
   ->update('users', ['status' => 'inactive']);

// Method 3: Using set()
$qb->set('status', 'inactive')
   ->where('id', 5)
   ->update('users');

// Get affected rows
$affected = $qb->affected_rows();
```

## DELETE Operations

```php
// Method 1: Direct delete
$qb->delete('users', ['id' => 5]);

// Method 2: Using where()
$qb->where('status', 'deleted')
   ->delete('users');

// Delete with multiple conditions
$qb->where('status', 'inactive')
   ->where('last_login <', '2020-01-01')
   ->delete('users');
```

### Empty & Truncate

```php
// Empty table (DELETE FROM)
$qb->empty_table('users');

// Truncate table
$qb->truncate('users');
```

## DB Forge - Table Management

### Initialize DB Forge

```php
$forge = CI_Forge();
```

### Create Table

```php
$forge->add_field([
    'id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => true,
        'auto_increment' => true
    ],
    'username' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
        'null' => false
    ],
    'email' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
        'null' => false
    ],
    'status' => [
        'type' => 'ENUM',
        'constraint' => ['active', 'inactive', 'banned'],
        'default' => 'active'
    ],
    'created_at' => [
        'type' => 'DATETIME',
        'null' => false
    ]
]);

$forge->add_key('id', true); // Primary key
$forge->add_key('email'); // Index
$forge->create_table('my_users', true); // true = IF NOT EXISTS
```

### Quick ID Field

```php
// Adds auto-increment BIGINT id as primary key
$forge->add_field('id');
```

### Field Types

```php
// Common field definitions
[
    'field_name' => [
        'type' => 'VARCHAR|INT|BIGINT|TEXT|DATETIME|ENUM|...',
        'constraint' => 100, // Length or values for ENUM
        'unsigned' => true,
        'null' => false,
        'default' => 'value',
        'auto_increment' => true
    ]
]
```

### Foreign Keys

```php
$forge->add_foreign_key(
    'user_id',           // Field in current table
    'users',             // Reference table
    'id',                // Reference field
    'CASCADE',           // On delete
    'CASCADE'            // On update
);
```

### Modify Tables

```php
// Add column
$forge->add_column('users', [
    'phone' => [
        'type' => 'VARCHAR',
        'constraint' => 20
    ]
], 'email'); // After 'email' column

// Drop column
$forge->drop_column('users', 'phone');

// Modify column
$forge->modify_column('users', [
    'username' => [
        'type' => 'VARCHAR',
        'constraint' => 150
    ]
]);
```

### Drop & Rename Tables

```php
// Drop table
$forge->drop_table('old_table');

// Drop if exists
$forge->drop_table('old_table', true);

// Rename table
$forge->rename_table('old_name', 'new_name');
```

## Debugging

### Enable Debug Mode

```php
$qb = CI_QB();
$qb->debug(true); // Enable debugging

$query = $qb->from('users')->get();
// Displays query and any errors
```

### Get Last Query

```php
$query = $qb->select('*')->from('users')->get();

// Get last executed query
echo $qb->last_query();

// Or print formatted
$qb->print_query();
```

### Get Error Information

```php
$query = $qb->from('nonexistent_table')->get();

$error = $qb->error();
echo $error['message']; // Error message
echo $error['query'];   // Query that caused error
```

### WordPress Debug Mode

Enable in `wp-config.php`:
```php
define('WP_DEBUG', true);
```

Errors will automatically display when WP_DEBUG is enabled.

## Complete Examples

### User Management System

```php
// Create users table
$forge = CI_Forge();
$forge->add_field('id')
      ->add_field([
          'username' => ['type' => 'VARCHAR', 'constraint' => 100],
          'email' => ['type' => 'VARCHAR', 'constraint' => 100],
          'password' => ['type' => 'VARCHAR', 'constraint' => 255],
          'role' => ['type' => 'ENUM', 'constraint' => ['admin', 'user'], 'default' => 'user'],
          'created_at' => ['type' => 'DATETIME']
      ])
      ->add_key('id', true)
      ->add_key('email')
      ->create_table('app_users', true);

// Insert user
$qb = CI_QB();
$qb->insert('app_users', [
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'password' => wp_hash_password('secret'),
    'created_at' => current_time('mysql')
]);

// Get active users
$query = $qb->select('id, username, email')
            ->from('app_users')
            ->where('role', 'user')
            ->order_by('created_at', 'DESC')
            ->get();

foreach ($query->result() as $user) {
    echo $user->username . ' - ' . $user->email . '<br>';
}

// Update user role
$qb->where('id', 5)
   ->update('app_users', ['role' => 'admin']);

// Delete inactive users
$qb->where('role', 'user')
   ->where('created_at <', date('Y-m-d', strtotime('-1 year')))
   ->delete('app_users');
```

### Blog Post System

```php
// Create posts table with foreign key
$forge = CI_Forge();
$forge->add_field('id')
      ->add_field([
          'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
          'title' => ['type' => 'VARCHAR', 'constraint' => 255],
          'content' => ['type' => 'TEXT'],
          'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
          'created_at' => ['type' => 'DATETIME']
      ])
      ->add_key('id', true)
      ->add_key('user_id')
      ->add_foreign_key('user_id', 'app_users', 'id', 'CASCADE', 'CASCADE')
      ->create_table('posts', true);

// Get posts with user info (JOIN)
$qb = CI_QB();
$query = $qb->select('posts.*, app_users.username')
            ->from('posts')
            ->join('app_users', 'posts.user_id = app_users.id')
            ->where('posts.status', 'published')
            ->order_by('posts.created_at', 'DESC')
            ->limit(10)
            ->get();

$posts = $query->result();

// Search posts
$query = $qb->select('*')
            ->from('posts')
            ->like('title', 'wordpress', 'both')
            ->or_like('content', 'wordpress', 'both')
            ->get();

// Count posts by status
$draft_count = $qb->from('posts')
                  ->where('status', 'draft')
                  ->count_all_results();
```

### E-commerce Product System

```php
// Create products table
$forge = CI_Forge();
$forge->add_field('id')
      ->add_field([
          'name' => ['type' => 'VARCHAR', 'constraint' => 255],
          'sku' => ['type' => 'VARCHAR', 'constraint' => 50],
          'price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
          'stock' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
          'category' => ['type' => 'VARCHAR', 'constraint' => 100],
          'status' => ['type' => 'VARCHAR', 'constraint' => 20]
      ])
      ->add_key('id', true)
      ->add_key(['sku', 'category'])
      ->create_table('products', true);

// Batch insert products
$qb = CI_QB();
$products = [
    ['name' => 'Product 1', 'sku' => 'SKU001', 'price' => 29.99, 'stock' => 100, 'category' => 'Electronics', 'status' => 'active'],
    ['name' => 'Product 2', 'sku' => 'SKU002', 'price' => 49.99, 'stock' => 50, 'category' => 'Electronics', 'status' => 'active'],
    ['name' => 'Product 3', 'sku' => 'SKU003', 'price' => 19.99, 'stock' => 200, 'category' => 'Clothing', 'status' => 'active']
];
$qb->insert_batch('products', $products);

// Get products by category
$query = $qb->select('*')
            ->from('products')
            ->where('category', 'Electronics')
            ->where('status', 'active')
            ->where('stock >', 0)
            ->order_by('price', 'ASC')
            ->get();

// Get low stock products
$query = $qb->select('name, sku, stock')
            ->from('products')
            ->where('stock <', 10)
            ->where('status', 'active')
            ->get();

// Update stock
$qb->where('sku', 'SKU001')
   ->set('stock', 'stock - 1', false) // false = don't escape
   ->update('products');

// Get products grouped by category
$query = $qb->select('category, COUNT(*) as total, SUM(stock) as total_stock')
            ->from('products')
            ->where('status', 'active')
            ->group_by('category')
            ->having('total >', 5)
            ->get();
```

## Using in Themes

Add to your theme's `functions.php`:

```php
function mytheme_get_custom_posts() {
    if (!function_exists('CI_QB')) {
        return [];
    }
    
    $qb = CI_QB();
    $query = $qb->select('*')
                ->from('my_custom_posts')
                ->where('status', 'published')
                ->order_by('date', 'DESC')
                ->limit(10)
                ->get();
    
    return $query->result();
}

// Use in template
$posts = mytheme_get_custom_posts();
foreach ($posts as $post) {
    echo '<h2>' . esc_html($post->title) . '</h2>';
}
```

## Using in Other Plugins

```php
// Check if CI Query Builder is available
if (function_exists('CI_QB')) {
    $qb = CI_QB();
    // Your code here
} else {
    // Fallback or show notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>CI Query Builder plugin is required!</p></div>';
    });
}
```

## Best Practices

1. **Always check if functions exist** when using in themes/plugins
2. **Use table names WITHOUT the WordPress prefix** - it's added automatically
3. **Enable debug mode during development** to catch errors early
4. **Use prepared statements** (automatic with this plugin)
5. **Reset queries** are handled automatically after `get()`, `insert()`, `update()`, `delete()`
6. **Use `ci_qb_new()`** when you need multiple independent query builders
7. **Check `num_rows()`** before processing results
8. **Use appropriate field types** in DB Forge for better performance

## Security Notes

- All values are automatically escaped using WordPress's `$wpdb->prepare()`
- Uses WordPress's built-in sanitization and validation
- LIKE queries use `$wpdb->esc_like()` to prevent SQL injection
- Foreign key constraints help maintain data integrity

## Troubleshooting

### Query Returns Empty Array

1. Enable debug mode: `$qb->debug(true)`
2. Check table name (don't include `wp_` prefix)
3. Verify table exists in database
4. Check last query: `$qb->last_query()`

### Errors Not Showing

Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
```

### Table Not Created

1. Check if table name conflicts with existing tables
2. Verify field definitions are correct
3. Check database user has CREATE TABLE permissions
4. Use `$forge->drop_table('table_name', true)` to remove and recreate

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher

## License

GPL v2 or later

## Support

For issues, questions, or contributions, please contact the plugin author.

## Changelog

### Version 1.0.0
- Initial release
- Full Query Builder implementation
- DB Forge for table management
- Result object with CodeIgniter-style methods
- Debug mode and error handling
- Global access functions
- WordPress integration
