<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'attendance_schedule_start')) {
                $table->string('attendance_schedule_start')->default((string) config('crewly.attendance.schedule_start', '09:00'))->after('timezone');
            }

            if (! Schema::hasColumn('companies', 'attendance_schedule_end')) {
                $table->string('attendance_schedule_end')->default((string) config('crewly.attendance.schedule_end', '18:00'))->after('attendance_schedule_start');
            }

            if (! Schema::hasColumn('companies', 'attendance_break_minutes')) {
                $table->unsignedSmallInteger('attendance_break_minutes')->default((int) config('crewly.attendance.break_minutes', 60))->after('attendance_schedule_end');
            }

            if (! Schema::hasColumn('companies', 'attendance_grace_minutes')) {
                $table->unsignedSmallInteger('attendance_grace_minutes')->default((int) config('crewly.attendance.grace_minutes', 0))->after('attendance_break_minutes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'attendance_grace_minutes')) {
                $table->dropColumn('attendance_grace_minutes');
            }
            if (Schema::hasColumn('companies', 'attendance_break_minutes')) {
                $table->dropColumn('attendance_break_minutes');
            }
            if (Schema::hasColumn('companies', 'attendance_schedule_end')) {
                $table->dropColumn('attendance_schedule_end');
            }
            if (Schema::hasColumn('companies', 'attendance_schedule_start')) {
                $table->dropColumn('attendance_schedule_start');
            }
        });
    }
};
