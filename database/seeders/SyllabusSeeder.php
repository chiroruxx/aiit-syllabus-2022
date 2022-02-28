<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CompulsoryType;
use App\Enums\FormDegree;
use App\Enums\FormType;
use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Form;
use App\Models\Lesson;
use App\Models\Syllabus;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class SyllabusSeeder extends Seeder
{
    private array $header = [];

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Throwable When failed transactions.
     */
    public function run()
    {
        $courses = Course::all();

        $syllabi = [];

        $path = storage_path('app/seeds/syllabi.csv');
        if (!file_exists($path)) {
            echo("Cannot find seed file at '{$path}'");
        }

        $file = new SplFileObject($path);
        while (!$file->eof()) {
            $row = $file->fgetcsv();

            if ($row === false) {
                throw new RuntimeException("Can not read row at line {$file->getCurrentLine()}");
            }

            if ($row === null || (!isset($row[0]))) {
                continue;
            }

            if ($this->hasHeader()) {
                $this->setHeader($row);
                continue;
            }

            $syllabus = [];
            foreach ($row as $key => $column) {
                $heading = $this->getHeading($key);
                // FIXME match式にする
                if ($heading === 'コース名') {
                    $course = $courses->where('name', '=', $column)->first();
                    if ($course === null) {
                        throw new ModelNotFoundException("Course {$column} is not found.");
                    }
                    $syllabus['course'] = $course;
                } elseif ($heading === '必修・選択') {
                    $value = match ($column) {
                        '必修' => CompulsoryType::COMPULSORY,
                        '選択' => CompulsoryType::SELECTABLE,
                        '選択必修' => CompulsoryType::SELECTABLE_COMPULSORY,
                        default => throw new DomainException("Compulsory {$column} is not defined."),
                    };
                    $syllabus['compulsory'] = $value;
                } elseif ($heading === '単位数') {
                    $syllabus['credit'] = (int)$column;
                } elseif ($heading === '学期') {
                    $syllabus['quarter'] = (int)substr($column, 0, 1);
                } elseif ($heading === '科目群') {
                    $syllabus['group'] = $column;
                } elseif ($heading === '科目名') {
                    // 謎のスペースが空いているので取る
                    if ($column === 'DESIGN ［RE］THINKING') {
                        $column = str_replace(' ［', '［', $column);
                    }
                    $syllabus['name_ja'] = $column;
                } elseif ($heading === '（英文表記）') {
                    $syllabus['name_en'] = $column;
                } elseif ($heading === '教員名') {
                    $syllabus['teacher'] = $column;
                } elseif ($heading === '概要') {
                    $syllabus['abstract'] = $column;
                } elseif ($heading === '目的・狙い') {
                    $syllabus['purpose'] = $column;
                } elseif ($heading === '履修条件 （履修者数の上限、 要求する前提知識 等）') {
                    $syllabus['precondition'] = $column;
                } elseif ($heading === '上位到達目標') {
                    $syllabus['higher_goal'] = $column;
                } elseif ($heading === '下位到達目標') {
                    $syllabus['lower_goal'] = $column;
                } elseif (str_starts_with($heading, '程度')) {
                    $syllabus['forms'][$this->getFormType($heading)] = ['type' => $this->getFormType($heading), 'degree' => $this->getFormValue($column)];
                } elseif (str_starts_with($heading, '特徴・留意点')) {
                    $syllabus['forms'][$this->getFormType($heading)]['feature'] = $column;
                } elseif ($heading === '授業外の学習') {
                    $syllabus['outside_learning'] = $column;
                } elseif ($heading === '授業の内容') {
                    $syllabus['inside_learning'] = $column;
                } elseif (str_starts_with($heading, '内容')) {
                    $number = $this->getLessonNumber($heading);
                    $syllabus['lessons'][$number]['number'] = $number;
                    $syllabus['lessons'][$number]['content'] = $column;
                } elseif (str_starts_with($heading, '授業実施形態')) {
                    $number = $this->getLessonNumber($heading);
                    $syllabus['lessons'][$number]['type'] = match ($column) {
                        '[対]' => LessonType::IN_PERSON,
                        '[録]' => LessonType::ON_DEMAND,
                        '[ハ]' => LessonType::HIGH_FLEX,
                        'その他' => LessonType::OTHER,
                        default => throw new DomainException("Lesson type {$column} is not defined."),
                    };
                } elseif ($heading === '成績評価') {
                    $syllabus['evaluation'] = $column;
                } elseif ($heading === '教科書・教材') {
                    $syllabus['text'] = $column;
                } elseif ($heading === '参考図書') {
                    $syllabus['reference'] = $column;
                }
            }

            $syllabi[] = $syllabus;
        }

        DB::transaction(function () use ($syllabi) {
            foreach ($syllabi as $record) {
                $syllabus = new Syllabus(Arr::except($record, ['forms', 'lessons', 'course']));
                $syllabus->course()->associate($record['course']);
                $syllabus->save();

                $forms = array_map(fn(array $attributes): Form => new Form($attributes), $record['forms']);
                $syllabus->forms()->saveMany($forms);

                $lessons = array_map(fn(array $attributes): Lesson => new Lesson($attributes), $record['lessons']);
                $syllabus->lessons()->saveMany($lessons);
            }
        });
    }

    private function hasHeader(): bool
    {
        return count($this->header) === 0;
    }

    private function setHeader(array $header): void
    {
        $this->header = $header;
    }

    private function getHeading(int $key): string
    {
        return $this->header[$key];
    }

    private function getFormType(string $heading): int
    {
        $typeStart = strpos($heading, '（');
        if ($typeStart === false) {
            throw new DomainException('Can not split form type.');
        }

        $type = substr($heading, $typeStart);
        return match ($type) {
            '（対面）' => FormType::FORM_TYPE_IN_PERSON,
            '（ハイフレックス）' => FormType::FORM_TYPE_HIGH_FLEX,
            '（オンデマンド）' => FormType::FORM_TYPE_ON_DEMAND,
            '（その他）' => FormType::FORM_TYPE_OTHER,
            default => throw new DomainException("Form type '{$heading}' is not found."),
        };
    }

    private function getFormValue(string $symbol): int
    {
        return match ($symbol) {
            '◎' => FormDegree::OFTEN,
            '○' => FormDegree::SOMETIMES,
            '―' => FormDegree::NONE,
            default => throw new DomainException("Degree {$symbol} is not defined.")
        };
    }

    private function getLessonNumber(string $heading): int
    {
        return match (mb_substr($heading, -1)) {
            '①' => 1,
            '②' => 2,
            '③' => 3,
            '④' => 4,
            '⑤' => 5,
            '⑥' => 6,
            '⑦' => 7,
            '⑧' => 8,
            '⑨' => 9,
            '⑩' => 10,
            '⑪' => 11,
            '⑫' => 12,
            '⑬' => 13,
            '⑭' => 14,
            '⑮' => 15,
            default => 99,
        };
    }
}
