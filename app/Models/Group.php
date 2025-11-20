<?php
namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Factories\HasFactory;


    class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'teacher_id',
        'notes',
        'type'
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
