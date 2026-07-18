<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReportSavedViewTag extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'normalized_name',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savedViews(): BelongsToMany
    {
        return $this->belongsToMany(
            ReportSavedView::class,
            'report_saved_view_tag'
        );
    }
}
