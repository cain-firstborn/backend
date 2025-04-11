<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Filters;
use App\Filament\Resources\UserResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SignupsRelationManager extends RelationManager
{
    protected static string $relationship = 'signups';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $resource = UserResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('created_at')
                    ->required()
                    ->formatStateUsing(fn(string $state) => \Carbon\Carbon::parse($state)->format('d.m.Y H:i:s')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID'),

                Tables\Columns\TextColumn::make('created_at')
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

    public function isReadOnly(): bool
    {
        return false;
    }
}
