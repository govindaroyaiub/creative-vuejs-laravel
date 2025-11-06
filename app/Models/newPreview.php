<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Client;
use App\Models\User;
use App\Models\ColorPalette;
use App\Models\newCategory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class newPreview extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*']; // logs all attributes
    protected static $logName = 'Preview'; // name for this log

    protected $casts = [
        'team_members' => 'array',
        'requires_login' => 'boolean',
        'show_planetnine_logo' => 'boolean',
        'show_sidebar_logo' => 'boolean',
        'show_footer' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'slug',
        'name',
        'client_id',
        'header_logo_id',
        'team_members',
        'uploader_id',
        'color_palette_id',
        'requires_login',
        'show_planetnine_logo',
        'show_sidebar_logo',
        'show_footer',
    ];

    /**
     * Default eager loading relationships
     */
    protected $with = [];

    /**
     * Common relationships for eager loading
     */
    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with(['client', 'uploader', 'colorPalette']);
    }

    /**
     * Scope for recent previews
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for previews by client
     */
    public function scopeByClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for public previews
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('requires_login', false);
    }

    /**
     * Scope for private previews
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('requires_login', true);
    }

    /**
     * Scope for search
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('Preview');
    }

    /**
     * Optimized relationship with indexing hints
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->select(['id', 'name']);
    }

    /**
     * Optimized uploader relationship
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id')->select(['id', 'name', 'email']);
    }

    /**
     * Optimized color palette relationship
     */
    public function colorPalette(): BelongsTo
    {
        return $this->belongsTo(ColorPalette::class)->select(['id', 'name', 'colors']);
    }

    /**
     * Optimized categories relationship with counting
     */
    public function categories(): HasMany
    {
        return $this->hasMany(newCategory::class, 'preview_id');
    }

    /**
     * Get categories count efficiently
     */
    public function categoriesCount(): int
    {
        return $this->categories()->count();
    }

    /**
     * Efficient method to get preview with all necessary data
     */
    public static function getOptimizedPreview(int $id): ?self
    {
        return static::with(['client:id,name', 'uploader:id,name,email', 'colorPalette:id,name,colors'])
            ->find($id);
    }

    /**
     * Efficient paginated list with filters
     */
    public static function getOptimizedList(array $filters = [], int $perPage = 15)
    {
        $query = static::query()
            ->with(['client:id,name', 'uploader:id,name'])
            ->select(['id', 'slug', 'name', 'client_id', 'uploader_id', 'requires_login', 'created_at']);

        // Apply filters
        if (!empty($filters['client_id'])) {
            $query->byClient($filters['client_id']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['requires_login'])) {
            $query->where('requires_login', $filters['requires_login']);
        }

        if (!empty($filters['recent_days'])) {
            $query->recent($filters['recent_days']);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate slug on creation if not provided
        static::creating(function ($preview) {
            if (empty($preview->slug)) {
                $preview->slug = \Illuminate\Support\Str::slug($preview->name . '-' . time());
            }
        });
    }
}
