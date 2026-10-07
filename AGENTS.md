# AI ASSISTANT GUIDELINES & PROJECT RULES

## CRITICAL DATABASE SAFETY RULE
- **NEVER** run `php artisan migrate:fresh`.
- **NEVER** run `php artisan db:wipe`.
- **NEVER** run `php artisan migrate:reset`.
- **NEVER** run commands that drop or wipe database tables or user records.
- When new database changes are needed, **ALWAYS** create a new incremental migration using:
  ```bash
  php artisan make:migration <descriptive_name>
  php artisan migrate
  ```
- All destructive database commands are strictly blocked in `AppServiceProvider.php`.

## CODEBASE LANGUAGE POLICY
- All code, variable names, database columns, and user interface text must be in clean **English**.
- Do not write Urdu in views, templates, or code.
