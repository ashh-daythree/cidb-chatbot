# Phase 2 Foundation

This phase created the non-business backend foundation that future phases will build on.

## Files Created

- `.env.example`
  - template for local and production environment variables
  - used by developers and deployment pipelines

- `composer.json`
  - defines PHP requirements
  - sets PSR-4 autoloading for the backend namespace

- `backend/config/EnvironmentLoader.php`
  - loads `.env` values into `$_ENV`, `$_SERVER`, and `getenv()`
  - used by all configuration classes

- `backend/config/AppConfig.php`
  - holds application-level settings
  - used for app name, environment, debug, and URL

- `backend/config/DatabaseConfig.php`
  - holds database connection settings
  - used by the PDO connection layer

- `backend/config/StorageConfig.php`
  - holds storage and log path settings
  - used by file handling and logging later

- `backend/config/LoggingConfig.php`
  - holds logging path and level
  - used by the logger foundation

- `backend/config/CimsConfig.php`
  - holds CIMS placeholder settings
  - used later by the integration layer

- `backend/config/Configuration.php`
  - aggregates all config objects
  - used by the bootstrap layer to create shared dependencies

- `backend/config/DatabaseConnection.php`
  - reusable PDO connection wrapper
  - used by repositories and migrations

- `backend/repositories/BaseRepository.php`
  - generic database access base class
  - used later by all table-specific repositories

- `backend/repositories/MigrationHistoryRepository.php`
  - repository for the migration history table
  - used by the migration runner

- `backend/migrations/MigrationInterface.php`
  - contract for future migration files
  - used by the migration executor

- `backend/migrations/AbstractMigration.php`
  - helper base for SQL migrations
  - used by future migration definitions

- `backend/migrations/MigrationExecutor.php`
  - runs a single migration and records it
  - ensures migrations are applied once

- `backend/migrations/MigrationManager.php`
  - discovers and runs pending migrations
  - used during bootstrap or deployment

- `backend/bootstrap/Container.php`
  - lightweight dependency injection container
  - used to register and resolve shared infrastructure services

- `backend/bootstrap/Bootstrap.php`
  - assembles configuration, database, logging, and migration services
  - used by the future app entry point

- `backend/utils/JsonResponse.php`
  - consistent success/error response structure
  - used by future HTTP layers and error handling

- `backend/utils/Logger.php`
  - file-based logging foundation
  - used by infrastructure and later application layers

- `backend/utils/ErrorHandler.php`
  - centralized exception and error handling
  - used by the front controller later

- `backend/utils/Exceptions/AppException.php`
  - base application exception with HTTP metadata
  - used throughout the backend

- `backend/utils/Exceptions/ConfigurationException.php`
  - configuration-specific exception type
  - used when environment configuration is invalid

- `backend/utils/Exceptions/DatabaseException.php`
  - database-specific exception type
  - used when database initialization or access fails

## How This Foundation Will Be Used Later

- controllers will receive dependencies from the container
- services will use the database connection and repositories
- validators will use configuration and helper utilities
- migrations will be executed before application startup
- error handling will convert exceptions into consistent JSON responses
- logging will capture infrastructure failures without exposing secrets

## What Was Deliberately Not Built Yet

- controllers
- services
- validators
- routes
- uploads
- CIMS integration logic
- frontend code
- business workflows

