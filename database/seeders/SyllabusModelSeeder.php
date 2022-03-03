<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Model;
use App\Models\Syllabus;
use App\Services\CsvReader;
use App\Services\CsvRow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyllabusModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Throwable When failed transactions.
     */
    public function run()
    {
        $models = Model::all(['id', 'name']);
        $syllabi = Syllabus::all(['id', 'name_ja']);

        $syllabusModels = [];
        $csvList = ['ia_models.csv', 'ct_models.csv', 'bd_models.csv'];
        foreach ($csvList as $csv) {
            $syllabusModels = [
                ...$syllabusModels,
                ...$this->readCsv("app/seeds/{$csv}", $models, $syllabi)
            ];
        }

        DB::transaction(function () use ($syllabusModels): void {
            foreach ($syllabusModels as $record) {
                /** @var Syllabus $syllabus */
                $syllabus = $record['syllabus'];
                $syllabus->models()->sync($record['sync']);
            }
        });
    }

    /**
     * @param string $relativePath
     * @param Collection<Model> $models
     * @param Collection<Syllabus> $syllabi
     * @return array
     */
    private function readCsv(string $relativePath, Collection $models, Collection $syllabi): array
    {
        $records = [];

        $path = storage_path($relativePath);

        $reader = new CsvReader($path);
        $row = $reader->next();
        while ($row !== null) {
            $syllabus = $this->getSyllabus($row, $syllabi);

            if ($syllabus === null) {
                $row = $reader->next();
                continue;
            }

            $record = ['syllabus' => $syllabus];

            $row = $row->reject('科目')->reject('科目名');

            foreach ($row as $heading => $value) {
                if ($row->isEmpty($heading)) {
                    continue;
                }

                $model = $this->getModel($heading, $models);

                $record['sync'][$model->id] = [
                    'is_basic' => $value === '☆',
                ];
            }

            $records[] = $record;

            $row = $reader->next();
        }

        return $records;
    }

    /**
     * @param CsvRow $row
     * @param Collection<Syllabus> $syllabi
     * @return Syllabus|null
     */
    private function getSyllabus(CsvRow $row, Collection $syllabi): ?Syllabus
    {
        $name = $row->has('科目名') ? $row->get('科目名') : $row->get('科目');
        $name = str_replace(' ', '', $name);

        // 誤植修正
        $name = match ($name) {
            'DESING[RE]THINKING', 'ESIGN[RE]THINKING' => 'DESIGN［RE］THINKING',
            '事業方向性設計演習・' => '事業方向性設計演習',
            'ビックデータ解析特論' => 'ビッグデータ解析特論',
            'ET（EmbeddedTechnology）特別演習' => 'ET(Embedded Technology)特別演習',
            'TechnicalWritinginEnglish' => 'Technical Writing in English',
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

    /**
     * @param string $heading
     * @param Collection<Model> $models
     * @return Model
     */
    private function getModel(string $heading, Collection $models): Model
    {
        // 誤植修正
        $heading = match ($heading) {
            'プロジェクトマネージャ' => 'プロジェクトマネージャー',
            'アントレプレナーモデル' => 'アントレプレナー',
            'イントラプレナーモデル' => 'イントラプレナー',
            '事業承継モデル' => '事業承継',
            default => $heading,
        };

        return $models->where('name', $heading)->firstOrFail();
    }
}
