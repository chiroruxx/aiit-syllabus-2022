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
        $compulsory = $row->get('???????????????');

        $name_ja = $row->get('?????????');
        if ($name_ja === 'DESIGN ???RE???THINKING') {
            $name_ja = str_replace(' ???', '???', $name_ja);
        }

        return [
            'group' => $row->get('?????????'),
            'name_en' => $row->get('??????????????????'),
            'teacher' => $row->get('?????????'),
            'abstract' => $row->get('??????'),
            'purpose' => $row->get('???????????????'),
            'precondition' => $row->get('???????????? ??????????????????????????? ???????????????????????? ??????'),
            'higher_goal' => $row->get('??????????????????'),
            'lower_goal' => $row->get('??????????????????'),
            'outside_learning' => $row->get('??????????????????'),
            'inside_learning' => $row->get('???????????????'),
            'evaluation' => $row->get('????????????'),
            'text' => $row->get('??????????????????'),
            'reference' => $row->get('????????????'),
            'compulsory' => match ($compulsory) {
                '??????' => CompulsoryType::COMPULSORY,
                '??????' => CompulsoryType::SELECTABLE,
                '????????????' => CompulsoryType::SELECTABLE_COMPULSORY,
                default => throw new DomainException("Compulsory {$compulsory} is not defined."),
            },
            'credit' => (int)$row->get('?????????'),
            'quarter' => (int)substr($row->get('??????'), 0, 1),
            'name_ja' => $name_ja,
        ];
    }

    private function getFormsAttributes(CsvRow $row): array
    {
        $postfixes = [
            '????????????' => FormType::FORM_TYPE_IN_PERSON->value,
            '???????????????????????????' => FormType::FORM_TYPE_HIGH_FLEX->value,
            '????????????????????????' => FormType::FORM_TYPE_ON_DEMAND->value,
            '???????????????' => FormType::FORM_TYPE_OTHER->value,
        ];

        $attributes = [];

        foreach ($postfixes as $postfix => $typeValue) {
            $attributes[] = [
                'type' => FormType::from($typeValue),
                'degree' => $this->getFormDegree($row->get("??????{$postfix}")),
                'feature' => $row->get("??????????????????{$postfix}")
            ];
        }

        return $attributes;
    }

    private function getFormDegree(string $symbol): FormDegree
    {
        return match ($symbol) {
            '???' => FormDegree::OFTEN,
            '???' => FormDegree::SOMETIMES,
            '???' => FormDegree::NONE,
            default => throw new DomainException("Degree {$symbol} is not defined.")
        };
    }

    private function getLessonsAttributes(CsvRow $row): array
    {
        $numbers = [
            '???' => 1,
            '???' => 2,
            '???' => 3,
            '???' => 4,
            '???' => 5,
            '???' => 6,
            '???' => 7,
            '???' => 8,
            '???' => 9,
            '???' => 10,
            '???' => 11,
            '???' => 12,
            '???' => 13,
            '???' => 14,
            '???' => 15,
            '????????????' => 99,
        ];

        $attributes = [];
        foreach ($numbers as $label => $number) {
            $type = $row->get("??????????????????{$label}");

            $attributes[] = [
                'number' => $number,
                'content' => $row->get("??????{$label}"),
                'type' => match ($type) {
                    '[???]' => LessonType::IN_PERSON,
                    '[???]' => LessonType::ON_DEMAND,
                    '[???]' => LessonType::HIGH_FLEX,
                    '?????????' => LessonType::OTHER,
                    default => throw new DomainException("Lesson type {$type} is not defined."),
                },
            ];
        }

        return $attributes;
    }

    private function getCourse(CsvRow $row, Collection $courses): Course
    {
        return $courses->where('name', '=', $row->get('????????????'))->firstOrFail();
    }
}
