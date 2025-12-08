# AI Coding Agent Instructions for School Management System

## Project Overview

This is a Laravel 10 school management system using Filament v3 admin interface, designed for Indonesian schools. The system manages students, teachers, classes, assessments, growth records, attendance, and generates report cards with DomPDF.

## Core Architecture Patterns

### Model Naming Convention

-   Use **snake_case** for model names matching Indonesian context: `data_siswa`, `data_guru`, `data_kelas`, `academic_year`
-   Foreign key references follow pattern: `{model_name}_id` (e.g., `data_siswa_id`, `walikelas_id`)
-   Models use `protected $guarded = []` for mass assignment protection

### Filament Resource Structure

-   Each model has corresponding Filament Resource in `app/Filament/Resources/`
-   Resources follow Laravel naming: `DataSiswaResource`, `GrowthRecordResource`
-   Use Filament v3 patterns for form components and table columns
-   Inline editing implemented via `TextInputColumn` for data entry efficiency

### Service Layer Pattern

-   Complex business logic isolated in Services (see `app/Services/ReportCardService.php`)
-   PDF generation using DomPDF with A4 format, 30mm margins standard
-   Service methods handle data aggregation across multiple models

## Database & Relationships

### Key Relationship Patterns

```php
// Student belongs to class via foreign key 'kelas'
data_siswa->kelas (belongs to data_kelas)
data_kelas->walikelas (belongs to data_guru via walikelas_id)
data_siswa->growthRecords (has many GrowthRecord)
```

### Critical Constraints

-   Growth records have unique constraint: `(data_siswa_id, month, year)`
-   Classes linked to teachers via `walikelas_id` (homeroom teacher)
-   Academic year integration across assessments and records

## Essential Workflows

### Development Commands

```bash
# Standard Laravel development
php artisan serve           # Start development server
php artisan migrate         # Run migrations
php artisan filament:upgrade # Update Filament after composer update

# Asset building
npm run dev                 # Development build with Vite
npm run build              # Production build
```

### PDF Report Generation

-   Use `ReportCardService` for consistent PDF formatting
-   Templates support both full reports and content-only generation
-   DomPDF settings: A4, CSS-based styling, UTF-8 encoding

### Growth Record System

-   Bulk generation creates empty records for entire class monthly
-   Inline editing for efficient data entry by homeroom teachers
-   BMI auto-calculation via model accessor
-   Role-based access: only homeroom teachers see their class data

## Project-Specific Conventions

### Authorization Patterns

-   Scope queries by teacher role: `forWaliKelas($guruId)` scope methods
-   User roles managed via Spatie Laravel Permission
-   Data access filtered by class assignments

### Indonesian Localization

-   Month names in Indonesian via `getBulanTahunAttribute()` accessor
-   School data structure includes `telepon`, `alamat` fields
-   Date formatting follows Indonesian conventions

### Model Accessors & Helpers

-   Defensive programming: models include null-safe accessors
-   `getKelasInfo()`, `getWaliKelasInfo()` helper methods handle exceptions
-   Fallback values for missing relationships

## Critical Files for Understanding

-   `app/Models/GrowthRecord.php` - Demonstrates bulk generation patterns
-   `app/Services/ReportCardService.php` - PDF generation and data aggregation
-   `docs/GrowthRecord-Documentation.md` - Feature documentation example
-   `app/Filament/Resources/` - Admin interface patterns

## Integration Points

-   **DomPDF**: Report generation with HTML/CSS-based formatting
-   **Filament v3**: Admin interface with inline editing capabilities
-   **Spatie Permissions**: Role-based access control
-   **Vite**: Asset compilation for Laravel
-   **DOMPDF**: Alternative PDF generation (also available)

## Code Style Notes

-   Mix of PascalCase (GrowthRecord) and snake_case (data_siswa) models
-   Prefer explicit relationship definitions over conventions
-   Use model scopes for complex queries with role-based filtering
-   Service classes handle cross-model business logic and external library integration
