<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Filters;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    /**
     * The name of the relationship this manager handles.
     *
     * @var string
     */
    protected static string $relationship = 'messages';

    /**
     * Configure the form fields.
     *
     * @param Form $form
     *
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    /**
     * Configure the table columns, filters, and actions.
     *
     * @param Table $table
     *
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('message'),

                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime('d.m.Y H:i:s'),
            ])
            ->filters([
                Filters\CreatedAtFilter::make(),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Determines if the relation is read-only.
     *
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return false;
    }
}
