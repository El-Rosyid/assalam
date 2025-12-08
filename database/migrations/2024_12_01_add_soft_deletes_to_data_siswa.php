<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('data_siswa', function (Blueprint $table) {
            // Check if deleted_at doesn't exist before adding
            if (!Schema::hasColumn('data_siswa', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
            
            // Add deleted_by for audit trail (only if not exists)
            if (!Schema::hasColumn('data_siswa', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            }
        });
        
        // Add foreign key for deleted_by if not exists
        if (Schema::hasColumn('data_siswa', 'deleted_by')) {
            Schema::table('data_siswa', function (Blueprint $table) {
                // Check if foreign key doesn't exist
                $foreignKeys = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableForeignKeys('data_siswa');
                
                $hasForeignKey = collect($foreignKeys)->contains(function($fk) {
                    return in_array('deleted_by', $fk->getColumns());
                });
                
                if (!$hasForeignKey) {
                    $table->foreign('deleted_by')
                        ->references('user_id')  // users table uses user_id, not id
                        ->on('users')
                        ->onDelete('set null');
                }
            });
        }
        
        // Add index for soft delete queries if not exists
        Schema::table('data_siswa', function (Blueprint $table) {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('data_siswa');
            
            $hasDeletedAtIndex = collect($indexes)->contains(function($index) {
                return in_array('deleted_at', $index->getColumns());
            });
            
            if (!$hasDeletedAtIndex) {
                $table->index('deleted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_siswa', function (Blueprint $table) {
            // Drop foreign key first if exists
            if (Schema::hasColumn('data_siswa', 'deleted_by')) {
                try {
                    $table->dropForeign(['deleted_by']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
            
            // Drop columns if exist
            if (Schema::hasColumn('data_siswa', 'deleted_by')) {
                $table->dropColumn('deleted_by');
            }
            
            if (Schema::hasColumn('data_siswa', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};
