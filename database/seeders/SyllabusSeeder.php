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
use App\Services\CsvReader;
use App\Services\CsvRow;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyllabusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Throwable When failed transactions.
     */
    public function run()
    {
        $courses = Course::all();

        $syllabi = [];

        $path = storage_path('app/seeds/syllabi.csv');
        if (!file_exists($path)) {
            echo("Cannot find seed file at '{$path}'");
        }

        $reader = new CsvReader(storage_path('app/seeds/syllabi.csv'));

        $row = $reader->next();

        while ($row  !== null) {
            $syllabi[] = [
                'syllabus' => $this->getSyllabusAttributes($row),
                'forms' => $this->getFormsAttributes($row),
                'lessons' => $this->getLessonsAttributes($row),
                'course' => $this->getCourse($row, $courses),
            ];

            $row = $reader->next();
        }

        DB::transaction(function () use ($syllabi) {
            foreach ($syllabi as $record) {
                $syllabus = new Syllabus($record['syllabus']);
                $syllabus->course()->associate($record['course']);
                $syllabus->save();

                $forms = array_map(fn(array $attributes): Form => new Form($attributes), $record['forms']);
                $syllabus->forms()->saveMany($forms);

                $lessons = array_map(fn(array $attributes): Lesson => new Lesson($attributes), $record['lessons']);
                $syllabus->lessons()->saveMany($lessons);
            }
        });
    }

    private function getSyllabusAttributes(CsvRow $row): array
    {
        $compulsory = $row->get('必修・選択');

        $name_ja = $row->get('科目名');
        if ($name_ja === 'DESIGN ［RE］THINKING') {
            $name_ja = str_replace(' ［', '［', $name_ja);
        }

        return [
            'group' => $row->get('科目群'),
            'name_en' => $row->get('（英文表記）'),
            'teacher' => $row->get('教員名'),
            'abstract' => $row->get('概要'),
            'purpose' => $row->get('目的・狙い'),
            'precondition' => $row->get('履修条件 （履修者数の上限、 要求する前提知識 等）'),
            'higher_goal' => $row->get('上位到達目標'),
            'lower_goal' => $row->get('下位到達目標'),
            'outside_learning' => $row->get('授業外の学習'),
            'inside_learning' => $row->get('授業の内容'),
            'evaluation' => $row->get('成績評価'),
            'text' => $row->get('教科書・教材'),
            'reference' => $row->get('参考図書'),
            'compulsory' => match ($compulsory) {
                '必修' => CompulsoryType::COMPULSORY,
                '選択' => CompulsoryType::SELECTABLE,
                '選択必修' => CompulsoryType::SELECTABLE_COMPULSORY,
                default => throw new DomainException("Compulsory {$compulsory} is not defined."),
            },
            'credit' => (int)$row->get('単位数'),
            'quarter' => (int)substr($row->get('学期'), 0, 1),
            'name_ja' => $name_ja,
        ];
    }

    private function getFormsAttributes(CsvRow $row): array
    {
        $postfixes = [
            '（対面）' => FormType::FORM_TYPE_IN_PERSON->value,
            '（ハイフレックス）' => FormType::FORM_TYPE_HIGH_FLEX->value,
            '（オンデマンド）' => FormType::FORM_TYPE_ON_DEMAND->value,
            '（その他）' => FormType::FORM_TYPE_OTHER->value,
        ];

        $attributes = [];

        foreach ($postfixes as $postfix => $typeValue) {
            $attributes[] = [
                'type' => FormType::from($typeValue),
                'degree' => $this->getFormDegree($row->get("程度{$postfix}")),
                'feature' => $row->get("特徴・留意点{$postfix}")
            ];
        }

        return $attributes;
    }

    private function getFormDegree(string $symbol): FormDegree
    {
        return match ($symbol) {
            '◎' => FormDegree::OFTEN,
            '○' => FormDegree::SOMETIMES,
            '―' => FormDegree::NONE,
            default => throw new DomainException("Degree {$symbol} is not defined.")
        };
    }

    private function getLessonsAttributes(CsvRow $row): array
    {
        $numbers = [
            '①' => 1,
            '②' => 2,
            '➂' => 3,
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
            '（試験）' => 99,
        ];

        $attributes = [];
        foreach ($numbers as $label => $number) {
            $type = $row->get("授業実施形態{$label}");

            $attributes[] = [
                'number' => $number,
                'content' => $row->get("内容{$label}"),
                'type' => match ($type) {
                    '[対]' => LessonType::IN_PERSON,
                    '[録]' => LessonType::ON_DEMAND,
                    '[ハ]' => LessonType::HIGH_FLEX,
                    'その他' => LessonType::OTHER,
                    default => throw new DomainException("Lesson type {$type} is not defined."),
                },
            ];
        }

        return $attributes;
    }

    private function getCourse(CsvRow $row, Collection $courses): Course
    {
        return $courses->where('name', '=', $row->get('コース名'))->firstOrFail();
    }
}
