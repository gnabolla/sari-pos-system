# Database Migration System

This document explains how to use the database migration system for the Sari-Sari POS project.

## Overview

The migration system allows you to:
- Version control your database schema
- Easily deploy database changes
- Rollback problematic migrations
- Seed your database with initial data

## Quick Start

### For New Deployments

1. **Complete Setup (Creates database from scratch)**:
   ```bash
   php setup.php
   ```
   Or visit `http://yoursite.com/sari/setup.php`
   
   This will:
   - Create the database if it doesn't exist
   - Run all migrations
   - Optionally add sample data

2. **Web Interface (If database already exists)**:
   - Visit `http://yoursite.com/sari/install.php`
   - Click "Complete Setup" for automatic installation
   - Or run individual operations as needed

3. **Command Line (If database already exists)**:
   ```bash
   php migrate.php fresh --seed
   ```

### For Updates

```bash
php migrate.php migrate
```

## Command Line Usage

### Available Commands

| Command | Description |
|---------|-------------|
| `migrate` | Run all pending migrations |
| `rollback` | Rollback the last batch of migrations |
| `reset` | Rollback all migrations |
| `status` | Show migration status |
| `seed` | Run all database seeders |
| `seed [file]` | Run specific seeder file |
| `create [name]` | Create new migration file |
| `fresh` | Reset and re-run all migrations |
| `fresh --seed` | Reset, migrate, and seed |
| `help` | Show help information |

### Examples

```bash
# Run all pending migrations
php migrate.php migrate

# Create a new migration
php migrate.php create add_customer_table

# Run specific seeder
php migrate.php seed 01_default_categories.php

# Fresh install with sample data
php migrate.php fresh --seed

# Check migration status
php migrate.php status
```

## Migration Structure

### Directory Layout

```
database/
├── migrations/           # Migration files
├── seeds/               # Seed data files
└── MigrationManager.php # Migration engine
```

### Migration Files

Migration files follow the naming convention:
`YYYY_MM_DD_HHMMSS_description.php`

Example: `2024_01_01_120000_create_users_table.php`

### Migration Class Structure

```php
<?php

class Migration_2024_01_01_120000_CreateUsersTable {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function up() {
        // Create table or add columns
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL
        )";
        
        $this->db->exec($sql);
    }
    
    public function down() {
        // Rollback changes
        $sql = "DROP TABLE IF EXISTS users";
        $this->db->exec($sql);
    }
}
```

## Creating Migrations

### Using the CLI

```bash
php migrate.php create create_products_table
```

This creates a new migration file with the proper structure.

### Manual Creation

1. Create a file in `database/migrations/` with timestamp prefix
2. Follow the naming convention
3. Implement `up()` and `down()` methods

### Best Practices

- **Always include a rollback**: Implement the `down()` method
- **Use descriptive names**: `create_products_table` not `new_table`
- **One change per migration**: Don't mix unrelated changes
- **Test rollbacks**: Ensure your `down()` method works
- **Use transactions**: For complex changes

## Database Schema

### Current Tables

| Table | Description |
|-------|-------------|
| `tenants` | Store information for multi-tenancy |
| `users` | System users (admin, manager, cashier) |
| `categories` | Product categories |
| `suppliers` | Supplier information |
| `products` | Product catalog |
| `sales` | Sales transactions |
| `sale_items` | Individual items in sales |
| `inventory_transactions` | Stock movement history |
| `settings` | System settings per tenant |
| `user_sessions` | Session management |
| `audit_logs` | Activity logging |
| `migrations` | Migration tracking (auto-created) |

### Key Relationships

- `tenants` → `users` (1:many)
- `tenants` → `products` (1:many)
- `categories` → `products` (1:many)
- `suppliers` → `products` (1:many)
- `sales` → `sale_items` (1:many)
- `products` → `sale_items` (1:many)

## Seeding Data

### Default Seeders

| File | Description |
|------|-------------|
| `01_default_categories.php` | Common sari-sari store categories |
| `02_sample_products.php` | Sample products for testing |
| `03_default_settings.php` | Default system settings |

### Running Seeders

```bash
# Run all seeders
php migrate.php seed

# Run specific seeder
php migrate.php seed 01_default_categories.php
```

### Creating Custom Seeders

Create PHP files in `database/seeds/` directory:

```php
<?php
require_once __DIR__ . '/../../../config/database.php';

$tenant_id = $_SESSION['tenant_id'] ?? null;
if (!$tenant_id) return;

// Your seeding logic here
$data = [
    ['name' => 'Sample Item', 'price' => 10.00]
];

$stmt = $db->prepare("INSERT INTO items (tenant_id, name, price) VALUES (?, ?, ?)");
foreach ($data as $item) {
    $stmt->execute([$tenant_id, $item['name'], $item['price']]);
}

echo "✓ Custom data seeded successfully\n";
```

## Web Interface

### Installation Page

Access `http://yoursite.com/sari/install.php` for a user-friendly interface to:

- View migration status
- Run migrations
- Seed database
- Perform complete setup
- View operation logs

### Security

**Important**: Disable the web interface in production by setting:

```php
$allow_web_install = false; // in install.php
```

Or remove the `install.php` file after setup.

## Deployment Workflow

### Development

1. Create migrations for schema changes
2. Test migrations (up and down)
3. Commit migration files to version control
4. Test with fresh database

### Staging/Production

1. Pull latest code
2. Run migrations:
   ```bash
   php migrate.php migrate
   ```
3. Verify deployment
4. Rollback if needed:
   ```bash
   php migrate.php rollback
   ```

### Rollback Strategy

- Migrations run in batches
- `rollback` command undoes the last batch
- `reset` command undoes all migrations
- Always test rollbacks in staging first

## Troubleshooting

### Common Issues

**Migration fails with foreign key constraint**:
- Check the order of table creation
- Ensure referenced tables exist first

**Permission denied**:
```bash
chmod +x migrate.php
```

**Class not found error**:
- Check class name matches filename convention
- Verify file syntax with `php -l filename.php`

**Database connection error**:
- Check `config/database.php` settings
- Verify database credentials and server access

### Recovery

**Broken migration**:
1. Fix the migration file
2. If needed, manually mark as executed:
   ```sql
   DELETE FROM migrations WHERE migration = 'problematic_migration.php';
   ```
3. Re-run the migration

**Corrupted migration state**:
1. Check current state: `php migrate.php status`
2. Manually clean the migrations table if needed
3. Re-run from a known good state

## Performance Considerations

- Use indexes for foreign keys
- Consider data size for large seed files
- Use transactions for multi-statement operations
- Test migration performance on production-sized data

## Multi-Tenancy

The system supports multi-tenancy:
- Each tenant has isolated data
- Seeds run per tenant context
- Migration schema is shared
- Use `tenant_id` in all tenant-specific tables

## Contributing

When adding new features:

1. Create appropriate migrations
2. Add seed data if applicable
3. Update this documentation
4. Test thoroughly (including rollbacks)
5. Follow naming conventions