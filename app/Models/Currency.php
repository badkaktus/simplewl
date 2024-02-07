<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Currency
 *
 * @property int $id
 * @property string $name
 * @property string $short_code
 * @property string $code
 * @property int $precision
 * @property int $subunit
 * @property string $symbol
 * @property bool $symbol_first
 *
 * @method static Builder|Currency whereCode($value)
 * @method static Builder|Currency whereId($value)
 * @method static Builder|Currency whereName($value)
 * @method static Builder|Currency wherePrecision($value)
 * @method static Builder|Currency whereShortCode($value)
 * @method static Builder|Currency whereSubunit($value)
 * @method static Builder|Currency whereSymbol($value)
 * @method static Builder|Currency whereSymbolFirst($value)
 * @method static Collection|Currency[] all($columns = ['*'])
 * @method static Currency create(array $attributes = [])
 * @method static Currency find($id, $columns = ['*'])
 * @method static Currency findOrFail($id, $columns = ['*'])
 * @method static Currency first($columns = ['*'])
 * @method static Currency firstOrFail($columns = ['*'])
 * @method static Currency firstOrNew(array $attributes, array $values = [])
 * @method static Currency firstOrCreate(array $attributes, array $values = [])
 * @method static Currency updateOrCreate(array $attributes, array $values = [])
 * @method static Currency updateOrInsert(array $attributes, array $values = [])
 * @method static Collection hydrate(array $items)
 * @method static CurrencyFactory factory($count = null, $state = [])
 * @method static Builder|Currency newModelQuery()
 * @method static Builder|Currency newQuery()
 * @method static Builder|Currency query()
 * @mixin Eloquent
 */
class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_code',
        'code',
        'precision',
        'subunit',
        'symbol',
        'symbol_first',
    ];
}
