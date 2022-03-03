<?php

namespace App\Models;

use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Lesson
 *
 * @property int $id
 * @property int $syllabus_id
 * @property int $number
 * @property string $content
 * @property LessonType $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson query()
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson whereSyllabusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Lesson whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Lesson extends Model
{
    use HasFactory;

    const EXAM_NUMBER = 99;

    protected $fillable = [
        'number',
        'content',
        'satellite',
        'type'
    ];

    protected $casts = [
        'type' => LessonType::class
    ];

    public function isInPersonal(): bool
    {
        return $this->type === LessonType::IN_PERSON;
    }

    public function isVideo(): bool
    {
        return $this->type === LessonType::ON_DEMAND;
    }

    public function isHighFlex(): bool
    {
        return $this->type === LessonType::HIGH_FLEX;
    }

    public function isOther(): bool
    {
        return $this->type === LessonType::OTHER;
    }

    public function isExam(): bool
    {
        return $this->number === self::EXAM_NUMBER;
    }
}
