<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Score;
use App\Models\Syllabus;
use App\Services\CsvReader;
use App\Services\CsvRow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Throwable When failed transactions.
     */
    public function run()
    {
        $syllabi = Syllabus::all(['id', 'name_ja']);

        $scores = [];
        $csvList = ['scores_2020_1.csv', 'scores_2020_2.csv', 'scores_2020_3.csv', 'scores_2020_4.csv'];
        foreach ($csvList as $csv) {
            $scores = [...$scores, ...$this->readCsv("app/seeds/{$csv}", $syllabi)];
        }

        DB::transaction(function () use ($scores): void {
            foreach ($scores as $record) {
                /** @var Syllabus $syllabus */
                $syllabus = $record['syllabus'];

                $syllabus->load('score');
                if ($syllabus->score === null) {
                    $syllabus->score()->save(new Score($record['score']));
                } else {
                    $score = $syllabus->score;
                    foreach ($score->getFillable() as $attribute) {
                        $score->$attribute += $record['score'][$attribute];
                    }
                    $score->save();
                }
            }
        });
    }

    /**
     * @param string $relativePath
     * @param Collection<Syllabus> $syllabi
     * @return array
     */
    private function readCsv(string $relativePath, Collection $syllabi): array
    {
        $scores = [];

        $path = storage_path($relativePath);

        $reader = new CsvReader($path);
        $row = $reader->next();

        while ($row !== null) {
            $syllabus = $this->getSyllabus($row, $syllabi);

            if ($syllabus === null) {
                $row = $reader->next();
                continue;
            }

            $score = [
                'syllabus' => $syllabus,
                'score' => [
                    'participants' => $row->get('受講者数'),
                    'score_5' => $row->get('5'),
                    'score_4' => $row->get('4'),
                    'score_3' => $row->get('3'),
                    'score_2' => $row->get('2'),
                    'score_1' => $row->get('1'),
                    'score_0' => $row->get('0'),
                ],
            ];

            $scores[] = $score;
            $row = $reader->next();
        }

        return $scores;
    }

    /**
     * @param CsvRow $row
     * @param Collection<Syllabus> $syllabi
     * @return Syllabus|null
     */
    private function getSyllabus(CsvRow $row, Collection $syllabi): ?Syllabus
    {
        $name = $row->get('科目名');

        // 誤植修正
        $name = match ($name) {
            '会計・ファインナンス工学特論' => '会計・ファイナンス工学特論',
            'IT・CIO特論コース' => 'IT・CIO特論',
            '統計・数理軽量ファインナンス特別演習' => '統計・数理計量ファイナンス特別演習',
            'DESIGN ［RE］ THINKING' => 'DESIGN［RE］THINKING',
            'ET（Embedded Technology）特別演習' => 'ET(Embedded Technology)特別演習',
            default => $name,
        };

        // 科目としては存在しているようだが、シラバスの情報が存在しないためスキップする
        if (in_array($name, ['IT・CIO特論', '標準化と知財戦略', 'システムインテグレーション特論', 'サービス工学特論'], true)) {
            return null;
        }

        return $syllabi
            ->where('name_ja', $name)
            ->firstOrFail();
    }
}
