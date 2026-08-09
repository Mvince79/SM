<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE missing_children MODIFY status ENUM('PENDING', 'APPROVED', 'FOUND', 'CLOSED', 'REJECTED', 'ARCHIVED') NOT NULL DEFAULT 'PENDING'");
        }

        Schema::table('missing_children', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->after('found_at');
            $table->timestamp('expiration_warning_sent_at')->nullable()->after('expires_at');
            $table->timestamp('archived_at')->nullable()->after('expiration_warning_sent_at');
            $table->string('status_before_archive', 20)->nullable()->after('archived_at');

            $table->index(
                ['status', 'expiration_warning_sent_at', 'expires_at'],
                'missing_children_expiration_lookup_index',
            );
        });

        DB::table('missing_children')
            ->select(['id', 'created_at'])
            ->whereNull('expires_at')
            ->orderBy('id')
            ->chunkById(500, function ($children): void {
                foreach ($children as $child) {
                    DB::table('missing_children')
                        ->where('id', $child->id)
                        ->update([
                            'expires_at' => CarbonImmutable::parse($child->created_at)->addYears(2),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::table('missing_children')
                ->where('status', 'ARCHIVED')
                ->update(['status' => 'CLOSED']);

            DB::statement("ALTER TABLE missing_children MODIFY status ENUM('PENDING', 'APPROVED', 'FOUND', 'CLOSED', 'REJECTED') NOT NULL DEFAULT 'PENDING'");
        }

        Schema::table('missing_children', function (Blueprint $table): void {
            $table->dropIndex('missing_children_expiration_lookup_index');
            $table->dropColumn([
                'expires_at',
                'expiration_warning_sent_at',
                'archived_at',
                'status_before_archive',
            ]);
        });
    }
};
