<?php

namespace App\Filament\Resources;

use App\Filament\Filters;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string|null
     */
    protected static ?string $model = User::class;

    /**
     * The icon to display in the navigation.
     *
     * @var string|null
     */
    protected static ?string $navigationIcon = 'heroicon-o-users';

    /**
     * The sort order of the resource in the navigation.
     *
     * @var int|null
     */
    protected static ?int $navigationSort = 1;

    /**
     * Get the base Eloquent query for the resource.
     *
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'signups',
                'messages',
            ]);
    }

    /**
     * Configure the form fields.
     *
     * @param Form $form
     *
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required(),

                Forms\Components\TextInput::make('created_at')
                    ->visibleOn('view')
                    ->formatStateUsing(fn(string $state) => Carbon::parse($state)->format('d.m.Y H:i:s')),
            ]);
    }

    /**
     * Configure the table columns, filters, and actions.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime('d.m.Y H:i:s'),

                Tables\Columns\TextColumn::make('signups_count')
                    ->label('Signups')
                    ->sortable(),

                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->sortable(),
            ])
            ->filters([
                Filters\CreatedAtFilter::make(),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    /**
     * Get the relationships that should be displayed with the resource.
     *
     * @return array
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\SignupsRelationManager::class,
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    /**
     * Get the resource pages.
     *
     * @return array
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view'  => Pages\ViewUser::route('/{record}'),
        ];
    }
}
