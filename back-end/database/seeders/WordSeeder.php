<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Theme;
use App\Models\Word;

class WordSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'fruits' => ['APPLE', 'GRAPE', 'LEMON', 'MANGO', 'PEACH', 'KIWI'],
            'animals' => ['TIGER', 'LION', 'BEAR', 'WOLF', 'PANDA', 'FOX'],
            'space' => ['STAR', 'MOON', 'SUN', 'MARS', 'COMET', 'ORBIT'],
        ];

        foreach ($data as $themeName => $words) {
            $theme = Theme::create(['name' => $themeName]);
            foreach ($words as $wordText) {
                Word::create([
                    'theme_id' => $theme->id,
                    'text' => $wordText,
                ]);
            }
        }
    }
}
