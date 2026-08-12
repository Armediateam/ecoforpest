<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

class Employee extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, LogsActivity;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'npwp',
        'bpjs_number',
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'join_date' => 'date',
        'employee_income' => 'array',
        'employee_expense' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'employment_status',
        'photo_url',
    ];

    /**
     * Get the employee's photo URL with default based on gender.
     */
    public function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->photo) {
                    return Storage::disk('public')->url($this->photo);
                }

                $style = $this->gender === 'female' ? 'avataaars' : 'adventurer';
                return sprintf(
                    'https://api.dicebear.com/7.x/%s/svg?seed=%s&backgroundColor=%s',
                    $style,
                    urlencode($this->name),
                    urlencode($this->gender === 'female' ? 'ffdfeb' : 'e7f3ff')
                );
            },
        );
    }

    /**
     * Get the employee's employment status from the latest contract.
     */
    public function employmentStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $latestContract = $this->contracts()->latest('id')->first();
                return $latestContract ? $latestContract->type : null;
            },
        );
    }

    protected static $logAttributes = ['*'];
    protected static $logName = 'employee';
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('employee')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->logUnguarded();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        $causerName = auth()->user()->name ?? 'System';

        $descriptions = [
            'created' => "{$causerName} membuat Employee {$this->name}",
            'updated' => "{$causerName} memperbarui Employee {$this->name}",
            'deleted' => "{$causerName} menghapus Employee {$this->name}",
            'restored' => "{$causerName} memulihkan Employee {$this->name}",
        ];

        return $descriptions[$eventName] ?? "{$causerName} melakukan aksi {$eventName} pada Emmployee {$this->name}";
    }

    /**
     * Get the position of the employee.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the shift of the employee (override).
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the effective shift for this employee using hierarchy fallback.
     * Priority: employee.shift_id > position.default_shift_id > department.default_shift_id
     */
    public function getEffectiveShift(): ?Shift
    {
        // 1. Check employee-specific shift (highest priority)
        if ($this->shift_id) {
            return $this->shift;
        }

        // 2. Check position default shift
        if ($this->position && $this->position->default_shift_id) {
            return $this->position->defaultShift;
        }

        // 3. Check department default shift (fallback)
        if ($this->position && $this->position->department && $this->position->department->default_shift_id) {
            return $this->position->department->defaultShift;
        }

        return null;
    }

    /**
     * Get the addresses for the employee.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    /**
     * Get the contracts for the employee.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    /**
     * Get the career histories for the employee.
     */
    public function careerHistories(): HasMany
    {
        return $this->hasMany(CareerHistory::class);
    }

    /**
     * Get the locations for the employee.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(EmployeeLocations::class);
    }

    /**
     * Get the access requests for the employee.
     */
    public function accessRequests(): HasMany
    {
        return $this->hasMany(AccessRequest::class);
    }

    /**
     * Get the user that created the employee.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that last updated the employee.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user that deleted the employee.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
}
