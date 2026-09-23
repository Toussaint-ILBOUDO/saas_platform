<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $documents = DB::table('document_bibliotheques')
            ->whereNull('slug')
            ->orWhere('slug', '')
            ->get();

        foreach ($documents as $doc) {
            $slug = Str::slug($doc->titre);
            $original = $slug;
            $counter = 1;

            while (DB::table('document_bibliotheques')->where('slug', $slug)->where('id', '!=', $doc->id)->exists()) {
                $slug = $original . '-' . $counter;
                $counter++;
            }

            DB::table('document_bibliotheques')
                ->where('id', $doc->id)
                ->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        DB::table('document_bibliotheques')
            ->whereNull('slug')
            ->update(['slug' => null]);
    }
};
