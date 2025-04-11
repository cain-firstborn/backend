<?php

namespace App\Filament\Filters;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

class CreatedAtFilter extends Filter
{
    /**
     * Create a new filter instance.
     */
    public static function make(?string $name = 'created_at'): static
    {
        return parent::make($name)
            ->form([
                DatePicker::make('from')
                    ->label('Created From:'),

                DatePicker::make('until')
                    ->label('Created Until:'),
            ])
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($from = data_get($data, 'from')) {
                    $indicators[] = Indicator::make('Created from ' . \Carbon\Carbon::parse($from)->toFormattedDateString())
                        ->removeField('from');
                }

                if ($until = data_get($data, 'until')) {
                    $indicators[] = Indicator::make('Created until ' . \Carbon\Carbon::parse($until)->toFormattedDateString())
                        ->removeField('until');
                }

                return $indicators;
            })
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        value   : data_get($data, 'from'),
                        callback: fn(Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        value   : data_get($data, 'until'),
                        callback: fn(Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            });
    }
}
