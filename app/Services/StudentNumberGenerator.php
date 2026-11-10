<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StudentNumberGenerator
{
    public function next(): string
    {
        $number = DB::transaction(function (): int {
            $now = now();
            DB::table('student_number_sequences')->insertOrIgnore([
                'key' => 'global',
                'last_number' => $this->existingMaximum(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sequence = DB::table('student_number_sequences')
                ->where('key', 'global')
                ->lockForUpdate()
                ->first();
            $next = $sequence->last_number + 1;

            DB::table('student_number_sequences')
                ->where('key', 'global')
                ->update(['last_number' => $next, 'updated_at' => $now]);

            return $next;
        });

        return sprintf('UEMP-%s-%06d', now()->format('Y'), $number);
    }

    private function existingMaximum(): int
    {
        return DB::table('users')
            ->whereNotNull('student_number')
            ->pluck('student_number')
            ->reduce(function (int $maximum, string $studentNumber): int {
                if (preg_match('/^UEMP-\d{4}-(\d+)$/', $studentNumber, $matches)) {
                    return max($maximum, (int) $matches[1]);
                }

                return $maximum;
            }, 0);
    }
}
