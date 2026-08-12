<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class EmployeeLocations extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'latitude',
        'longitude',
        'position',
        'info',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'info' => 'array',
    ];

    /**
     * Get the employee that owns this location.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Create or update a location record with position automatically set based on latitude and longitude.
     *
     * @param array<string, mixed> $attributes
     * @return static
     */
    public static function renewLocation(array $attributes): self
    {
        // Ensure latitude and longitude are present
        if (!isset($attributes['latitude']) || !isset($attributes['longitude'])) {
            throw new \InvalidArgumentException('Latitude and longitude are required');
        }

        // Create point geometry from latitude and longitude
        $latitude = (float) $attributes['latitude'];
        $longitude = (float) $attributes['longitude'];

        // Set the position attribute using DB::raw
        $attributes['position'] = DB::raw("ST_GeomFromText('POINT($longitude $latitude)', 4326)");

        // Use updateOrCreate to either update an existing record or create a new one
        $conditions = ['employee_id' => $attributes['employee_id']];

        // If ID is provided, include it in the conditions
        if (isset($attributes['id'])) {
            $conditions['id'] = $attributes['id'];
        }

        return self::updateOrCreate($conditions, $attributes);
    }

    /**
     * Find locations within a certain distance from a point.
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $distanceInKm
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findNearby(float $latitude, float $longitude, float $distanceInKm = 5)
    {
        $point = "ST_GeomFromText('POINT($longitude $latitude)', 4326)";
        $distance = "ST_Distance_Sphere($point, position)";

        return self::whereRaw("$distance <= ?", [$distanceInKm * 1000])
            ->selectRaw("*, $distance as distance")
            ->orderByRaw("$distance ASC")
            ->get();
    }

    /**
     * Get formatted address or location information.
     *
     * @return string|null
     */
    public function getFormattedAddressAttribute(): ?string
    {
        return $this->info['formatted_address'] ?? null;
    }

    /**
     * Get the distance to another point in kilometers.
     *
     * @param float $latitude
     * @param float $longitude
     * @return float|null
     */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if (!$this->position) {
            return null;
        }

        $point = "ST_GeomFromText('POINT($longitude $latitude)', 4326)";
        $result = DB::selectOne("SELECT ST_Distance_Sphere({$point}, ?) as distance", [$this->position]);

        return $result ? round($result->distance / 1000, 2) : null;
    }

    /**
     * Get all active employee locations for live tracking.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLiveLocations()
    {
        return self::with(['employee.position.department'])
            ->whereHas('employee', function ($query) {
                $query->where('status', 'active');
            })
            ->whereDate('updated_at', now())
            ->latest('updated_at')
            ->get();
    }

    /**
     * Check if this location was updated recently (within last 5 minutes).
     *
     * @return bool
     */
    public function isRecentlyUpdated(): bool
    {
        return $this->updated_at->diffInMinutes(now()) <= 5;
    }

    /**
     * Get the employee's current status based on location update time.
     *
     * @return string
     */
    public function getLocationStatusAttribute(): string
    {
        if ($this->isRecentlyUpdated()) {
            return 'online';
        } elseif ($this->updated_at->diffInHours(now()) <= 1) {
            return 'recently_active';
        } else {
            return 'offline';
        }
    }
}
