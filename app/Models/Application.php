<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'applications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'evaluation_cycle',
        'kra1_score',
        'kra2_score',
        'kra3_score',
        'kra4_score',
        'final_score',
        'remarks',
    ];

    /**
     * Get the user who owns the application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- START: KRA SUBMISSION RELATIONSHIPS ---

    /**
     * Get all of the instruction submissions for the Application.
     */
    public function instructions(): HasMany
    {
        return $this->hasMany(Instruction::class);
    }

    /**
     * Get all of the research submissions for the Application.
     */
    public function researches(): HasMany
    {
        return $this->hasMany(Research::class);
    }

    /**
     * Get all of the extension submissions for the Application.
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(Extension::class);
    }

    /**
     * Get all of the professional development submissions for the Application.
     */
    public function professionalDevelopments(): HasMany
    {
        return $this->hasMany(ProfessionalDevelopment::class);
    }

    // --- END: KRA SUBMISSION RELATIONSHIPS ---
}
