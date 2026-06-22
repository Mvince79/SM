<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('face_match_results', function (Blueprint $table) {
            $table->enum('confidence_level', ['LOW', 'MEDIUM', 'HIGH'])
                ->default('LOW')
                ->after('is_matched');
        });

        $matchThreshold = (float) config('face_match.score_threshold', 0.70);
        $highThreshold = (float) config('face_match.high_confidence_threshold', 0.85);

        DB::table('face_match_results')->update([
            'confidence_level' => DB::raw(
                "CASE
                    WHEN score >= {$highThreshold} THEN 'HIGH'
                    WHEN score >= {$matchThreshold} THEN 'MEDIUM'
                    ELSE 'LOW'
                END"
            ),
        ]);
    }

    public function down(): void
    {
        Schema::table('face_match_results', function (Blueprint $table) {
            $table->dropColumn('confidence_level');
        });
    }
};
