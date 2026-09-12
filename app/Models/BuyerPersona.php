<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A saved buyer persona (the human decision-maker archetype), used to sharpen
 * Prospecting targeting, qualification, research and outreach copy.
 *
 * Array fields are stored as JSON and cast to arrays.
 */
class BuyerPersona extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'organization_id', 'name', 'avatar',
        'role_titles', 'demographics', 'goals', 'pains', 'objections',
        'buying_triggers', 'messaging_hooks', 'value_props', 'channels',
        'current_solution', 'keywords', 'is_template',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'role_titles' => 'array',
        'demographics' => 'array',
        'goals' => 'array',
        'pains' => 'array',
        'objections' => 'array',
        'buying_triggers' => 'array',
        'messaging_hooks' => 'array',
        'value_props' => 'array',
        'channels' => 'array',
        'keywords' => 'array',
        'is_template' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BuyerPersona $persona) {
            $persona->uuid = (string) Str::uuid();
        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function campaigns()
    {
        return $this->hasMany(ProspectingCampaign::class, 'buyer_persona_id');
    }

    /**
     * Render a compact, human-readable summary used inside AI prompts.
     */
    public function toPromptSummary(): string
    {
        $lines = ['Persona: ' . $this->name];

        $map = [
            'Role / titles' => $this->role_titles,
            'Goals' => $this->goals,
            'Pain points' => $this->pains,
            'Objections' => $this->objections,
            'Buying triggers' => $this->buying_triggers,
            'Messaging hooks' => $this->messaging_hooks,
            'Value props to lead with' => $this->value_props,
            'Where they hang out' => $this->channels,
            'Search keywords' => $this->keywords,
        ];

        foreach ($map as $label => $value) {
            $value = array_values(array_filter((array) $value, fn ($v) => trim((string) $v) !== ''));
            if ($value !== []) {
                $lines[] = "{$label}: " . implode('; ', $value);
            }
        }

        if (! empty(trim((string) $this->current_solution))) {
            $lines[] = 'Current solution / status quo: ' . trim((string) $this->current_solution);
        }

        return implode("\n", $lines);
    }
}
