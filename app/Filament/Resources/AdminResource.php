<?php

namespace App\Filament\Resources;

use App\Filament\Filters;
use App\Filament\Resources\AdminResource\Pages;
use App\Models\Admin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdminResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string|null
     */
    protected static ?string $model = Admin::class;

    /**
     * The icon to display in the navigation.
     *
     * @var string|null
     */
    protected static ?string $navigationIcon = 'heroicon-o-user';

    /**
     * The sort order of the resource in the navigation.
     *
     * @var int|null
     */
    protected static ?int $navigationSort = 1;

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
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required(),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(),
                    ]),
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

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime('d.m.Y H:i:s'),
            ])
            ->filters([
                Filters\CreatedAtFilter::make(),
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
     * Get the relationships that should be displayed with the resource.
     *
     * @return array
     */
    public static function getRelations(): array
    {
        return [
            //
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
            'index' => Pages\ListAdmins::route('/'),
            'edit'  => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
