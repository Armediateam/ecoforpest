<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        $attributes['latitude'] = (float) $attributes['latitude'];
        $attributes['longitude'] = (float) $attributes['longitude'];

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
        $distance = self::haversineSql($latitude, $longitude);

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
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latDelta = deg2rad($latitude - (float) $this->latitude);
        $lonDelta = deg2rad($longitude - (float) $this->longitude);
        $startLat = deg2rad((float) $this->latitude);
        $endLat = deg2rad($latitude);

        $a = sin($latDelta / 2) ** 2
            + cos($startLat) * cos($endLat) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }

    private static function haversineSql(float $latitude, float $longitude): string
    {
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        return "(6371000 * 2 * ASIN(SQRT(POWER(SIN(RADIANS(latitude - {$latitude}) / 2), 2) + COS(RADIANS({$latitude})) * COS(RADIANS(latitude)) * POWER(SIN(RADIANS(longitude - {$longitude}) / 2), 2))))";
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
