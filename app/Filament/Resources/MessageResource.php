<?php

namespace App\Filament\Resources;

use App\Filament\Filters;
use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MessageResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string|null
     */
    protected static ?string $model = Message::class;

    /**
     * The icon to display in the navigation.
     *
     * @var string|null
     */
    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    /**
     * The sort order of the resource in the navigation.
     *
     * @var int|null
     */
    protected static ?int $navigationSort = 3;

    /**
     * Get the base Eloquent query for the resource.
     *
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user');
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
                    ->required()
                    ->formatStateUsing(fn(Message $record) => $record->user->email),

                Forms\Components\TextInput::make('created_at')
                    ->visibleOn('view')
                    ->formatStateUsing(fn(string $state) => Carbon::parse($state)->format('d.m.Y H:i:s')),

                Forms\Components\Textarea::make('message')
                    ->required()
                    ->autosize()
                    ->rows(3)
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->url(fn(Message $record): string => UserResource::getUrl('view', ['record' => $record->user_id])),

                Tables\Columns\TextColumn::make('message')
                    ->searchable()
                    ->limit(75),

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
            'index' => Pages\ListMessages::route('/'),
            'view'  => Pages\ViewMessage::route('/{record}'),
        ];
    }
}
