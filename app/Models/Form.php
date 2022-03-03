<?php

namespace App\Models;

use App\Enums\FormDegree;
use App\Enums\FormType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Form
 *
 * @property int $id
 * @property int $syllabus_id
 * @property FormType $type
 * @property FormDegree $degree
 * @property string $feature
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Form newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Form newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Form query()
 * @method static \Illuminate\Database\Eloquent\Builder|Form whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Form whereDegree($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Form whereFeature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Form whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Form whereSyllabusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Form whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Form whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'degree',
        'feature',
    ];

    protected $casts = [
        'type' => FormType::class,
        'degree' => FormDegree::class,
    ];

    public function getTypeLabel(): string
    {
        return FormType::label($this->type);
    }

    public function isDegreeOften(): bool
    {
        return $this->degree === FormDegree::OFTEN;
    }

    public function isDegreeSometimes(): bool
    {
        return $this->degree === FormDegree::SOMETIMES;
    }

    public function isDegreeNone(): bool
    {
        return $this->degree === FormDegree::NONE;
    }
}
